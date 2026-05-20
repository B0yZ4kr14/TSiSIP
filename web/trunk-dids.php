<?php
/**
 * TSiSIP Control Panel — DID Mapping Management
 * CRUD on sip_trunk_did_mappings.
 */

require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';
require_once __DIR__ . '/common/pagination.php';

requireAuth();
checkPasswordChange();
requireRole('devops');

$pdo = getDb();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = _('Invalid CSRF token.');
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create') {
            $trunkProviderId = intval($_POST['trunk_provider_id'] ?? 0);
            $didNumber = trim($_POST['did_number'] ?? '');
            $tenantId = trim($_POST['tenant_id'] ?? '');
            $dispatcherSetid = intval($_POST['dispatcher_setid'] ?? 1);
            $destination = trim($_POST['destination'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $enabled = isset($_POST['enabled']) ? true : false;

            if ($trunkProviderId === 0 || $didNumber === '' || $tenantId === '') {
                $error = _('Trunk provider, DID number, and tenant are required.');
            } else {
                try {
                    $stmt = $pdo->prepare(
                        "INSERT INTO sip_trunk_did_mappings
                         (trunk_provider_id, did_number, tenant_id, dispatcher_setid, destination, description, enabled)
                         VALUES (:trunk_provider_id, :did_number, :tenant_id, :dispatcher_setid, :destination, :description, :enabled)"
                    );
                    $stmt->execute([
                        ':trunk_provider_id' => $trunkProviderId,
                        ':did_number' => $didNumber,
                        ':tenant_id' => $tenantId,
                        ':dispatcher_setid' => $dispatcherSetid,
                        ':destination' => $destination,
                        ':description' => $description,
                        ':enabled' => $enabled,
                    ]);
                    $newId = $pdo->lastInsertId();
                    $success = _('DID mapping created successfully.');
                    logAuditEvent('TRUNK_DID_CREATE', 'sip_trunk_did_mapping', (string)$newId, true, [
                        'did_number' => $didNumber,
                        'trunk_provider_id' => $trunkProviderId,
                        'tenant_id' => $tenantId,
                        'enabled' => $enabled,
                    ]);
                } catch (PDOException $e) {
                    $error = _('Failed to create DID mapping: ') . $e->getMessage();
                }
            }
        } elseif ($action === 'update') {
            $id = intval($_POST['id'] ?? 0);
            $trunkProviderId = intval($_POST['trunk_provider_id'] ?? 0);
            $didNumber = trim($_POST['did_number'] ?? '');
            $tenantId = trim($_POST['tenant_id'] ?? '');
            $dispatcherSetid = intval($_POST['dispatcher_setid'] ?? 1);
            $destination = trim($_POST['destination'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $enabled = isset($_POST['enabled']) ? true : false;

            if ($id === 0 || $trunkProviderId === 0 || $didNumber === '' || $tenantId === '') {
                $error = _('All required fields must be filled.');
            } else {
                try {
                    $stmt = $pdo->prepare(
                        "UPDATE sip_trunk_did_mappings SET
                         trunk_provider_id = :trunk_provider_id,
                         did_number = :did_number,
                         tenant_id = :tenant_id,
                         dispatcher_setid = :dispatcher_setid,
                         destination = :destination,
                         description = :description,
                         enabled = :enabled
                         WHERE id = :id"
                    );
                    $stmt->execute([
                        ':id' => $id,
                        ':trunk_provider_id' => $trunkProviderId,
                        ':did_number' => $didNumber,
                        ':tenant_id' => $tenantId,
                        ':dispatcher_setid' => $dispatcherSetid,
                        ':destination' => $destination,
                        ':description' => $description,
                        ':enabled' => $enabled,
                    ]);
                    $success = _('DID mapping updated successfully.');
                    logAuditEvent('TRUNK_DID_UPDATE', 'sip_trunk_did_mapping', (string)$id, true, [
                        'did_number' => $didNumber,
                        'trunk_provider_id' => $trunkProviderId,
                        'tenant_id' => $tenantId,
                        'enabled' => $enabled,
                    ]);
                } catch (PDOException $e) {
                    $error = _('Failed to update DID mapping: ') . $e->getMessage();
                }
            }
        } elseif ($action === 'toggle') {
            $id = intval($_POST['id'] ?? 0);
            $enabled = isset($_POST['enabled']) ? true : false;
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE sip_trunk_did_mappings SET enabled = :enabled WHERE id = :id");
                $stmt->execute([':id' => $id, ':enabled' => $enabled]);
                $success = _('DID mapping status updated.');
                logAuditEvent('TRUNK_DID_TOGGLE', 'sip_trunk_did_mapping', (string)$id, true, [
                    'enabled' => $enabled,
                ]);
            }
        } elseif ($action === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            if ($id > 0) {
                $stmt = $pdo->prepare("DELETE FROM sip_trunk_did_mappings WHERE id = :id");
                $stmt->execute([':id' => $id]);
                $success = _('DID mapping deleted.');
                logAuditEvent('TRUNK_DID_DELETE', 'sip_trunk_did_mapping', (string)$id, true);
            }
        }
    }
}

// Fetch dropdowns
$providers = $pdo->query("SELECT id, name FROM sip_trunk_providers ORDER BY name")->fetchAll();
$tenants = $pdo->query("SELECT id, name, sip_domain FROM tenants ORDER BY name")->fetchAll();

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 25;
$pagination = getPagination($page, $perPage);

$countStmt = $pdo->query("SELECT COUNT(*) FROM sip_trunk_did_mappings");
$totalItems = (int) $countStmt->fetchColumn();

$listStmt = $pdo->prepare(
    "SELECT m.id, m.trunk_provider_id, m.tenant_id, m.did_number, m.dispatcher_setid,
            m.destination, m.description, m.enabled,
            p.name AS provider_name, t.name AS tenant_name
     FROM sip_trunk_did_mappings m
     JOIN sip_trunk_providers p ON m.trunk_provider_id = p.id
     JOIN tenants t ON m.tenant_id = t.id
     ORDER BY m.did_number
     LIMIT :limit OFFSET :offset"
);
$listStmt->bindValue(':limit', $pagination['limit'], PDO::PARAM_INT);
$listStmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
$listStmt->execute();
$mappings = $listStmt->fetchAll();

require_once __DIR__ . '/common/header.php';
?>
<div id="content">
    <h2><?php echo _('DID Mappings'); ?></h2>

    <?php if ($error): ?>
        <div class="tsisip-badge tsisip-badge-error" role="alert"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="tsisip-badge tsisip-badge-success" role="status"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <!-- Create Form -->
    <div class="tsisip-dashboard-section">
        <h3><?php echo _('Add DID Mapping'); ?></h3>
        <form method="POST" action="" class="tsisip-form">
            <?php echo csrfInput(); ?>
            <input type="hidden" name="action" value="create">
            <div style="display:flex;flex-wrap:wrap;gap:1rem">
                <div class="tsisip-form-group">
                    <label for="did_number"><?php echo _('DID Number'); ?></label>
                    <input type="text" id="did_number" name="did_number" class="tsisip-input" required style="width:180px">
                </div>
                <div class="tsisip-form-group">
                    <label for="trunk_provider_id"><?php echo _('Trunk Provider'); ?></label>
                    <select id="trunk_provider_id" name="trunk_provider_id" class="tsisip-input" required style="width:200px">
                        <?php foreach ($providers as $pr): ?>
                            <option value="<?php echo htmlspecialchars($pr['id'], ENT_QUOTES); ?>">
                                <?php echo htmlspecialchars($pr['name'], ENT_QUOTES); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="tsisip-form-group">
                    <label for="tenant_id"><?php echo _('Tenant'); ?></label>
                    <select id="tenant_id" name="tenant_id" class="tsisip-input" required style="width:220px">
                        <?php foreach ($tenants as $t): ?>
                            <option value="<?php echo htmlspecialchars($t['id'], ENT_QUOTES); ?>">
                                <?php echo htmlspecialchars($t['name'] . ' (' . $t['sip_domain'] . ')', ENT_QUOTES); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="tsisip-form-group">
                    <label for="dispatcher_setid"><?php echo _('Dispatcher Set ID'); ?></label>
                    <input type="number" id="dispatcher_setid" name="dispatcher_setid" class="tsisip-input" value="1" min="1" style="width:120px">
                </div>
                <div class="tsisip-form-group">
                    <label for="destination"><?php echo _('Destination'); ?></label>
                    <input type="text" id="destination" name="destination" class="tsisip-input" placeholder="sip:reception@tsisip.local" style="width:240px">
                </div>
                <div class="tsisip-form-group">
                    <label for="description"><?php echo _('Description'); ?></label>
                    <input type="text" id="description" name="description" class="tsisip-input" style="width:200px">
                </div>
                <div class="tsisip-form-group">
                    <label>
                        <input type="checkbox" name="enabled" checked>
                        <?php echo _('Enabled'); ?>
                    </label>
                </div>
            </div>
            <button type="submit" class="tsisip-btn tsisip-btn-primary"><?php echo _('Create Mapping'); ?></button>
        </form>
    </div>

    <!-- List -->
    <div class="tsisip-dashboard-section">
        <h3><?php echo _('DID Mappings'); ?> (<?php echo $totalItems; ?>)</h3>
        <table class="dataTable tsisip-table">
            <thead>
                <tr>
                    <th><?php echo _('DID Number'); ?></th>
                    <th><?php echo _('Trunk Provider'); ?></th>
                    <th><?php echo _('Tenant'); ?></th>
                    <th><?php echo _('Set ID'); ?></th>
                    <th><?php echo _('Destination'); ?></th>
                    <th><?php echo _('Description'); ?></th>
                    <th><?php echo _('Status'); ?></th>
                    <th><?php echo _('Actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($mappings as $m): ?>
                <tr>
                    <td><?php echo htmlspecialchars($m['did_number']); ?></td>
                    <td><?php echo htmlspecialchars($m['provider_name']); ?></td>
                    <td><?php echo htmlspecialchars($m['tenant_name']); ?></td>
                    <td><?php echo htmlspecialchars($m['dispatcher_setid']); ?></td>
                    <td><?php echo htmlspecialchars($m['destination'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($m['description'] ?? ''); ?></td>
                    <td>
                        <?php if ($m['enabled']): ?>
                            <span class="tsisip-badge tsisip-badge-success"><?php echo _('Enabled'); ?></span>
                        <?php else: ?>
                            <span class="tsisip-badge tsisip-badge-error"><?php echo _('Disabled'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="tsisip-actions-column">
                        <form method="POST" action="" style="display:inline">
                            <?php echo csrfInput(); ?>
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($m['id'], ENT_QUOTES); ?>">
                            <input type="hidden" name="enabled" value="<?php echo $m['enabled'] ? '0' : '1'; ?>">
                            <button type="submit" class="tsisip-btn tsisip-btn-secondary">
                                <?php echo $m['enabled'] ? _('Disable') : _('Enable'); ?>
                            </button>
                        </form>
                        <button type="button" class="tsisip-btn tsisip-btn-secondary"
                                onclick="document.getElementById('edit-m<?php echo htmlspecialchars($m['id'], ENT_QUOTES); ?>').style.display='table-row'">
                            <?php echo _('Edit'); ?>
                        </button>
                        <form method="POST" action="" style="display:inline" onsubmit="return confirm('<?php echo _('Delete this mapping?'); ?>')">
                            <?php echo csrfInput(); ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($m['id'], ENT_QUOTES); ?>">
                            <button type="submit" class="tsisip-btn tsisip-btn-secondary tsisip-btn-delete"><?php echo _('Delete'); ?></button>
                        </form>
                    </td>
                </tr>
                <tr id="edit-m<?php echo htmlspecialchars($m['id'], ENT_QUOTES); ?>" style="display:none">
                    <td colspan="8">
                        <form method="POST" action="" class="tsisip-form">
                            <?php echo csrfInput(); ?>
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($m['id'], ENT_QUOTES); ?>">
                            <div style="display:flex;flex-wrap:wrap;gap:1rem">
                                <div class="tsisip-form-group">
                                    <label><?php echo _('DID Number'); ?></label>
                                    <input type="text" name="did_number" class="tsisip-input" value="<?php echo htmlspecialchars($m['did_number'], ENT_QUOTES); ?>" required style="width:180px">
                                </div>
                                <div class="tsisip-form-group">
                                    <label><?php echo _('Trunk Provider'); ?></label>
                                    <select name="trunk_provider_id" class="tsisip-input" required style="width:200px">
                                        <?php foreach ($providers as $pr): ?>
                                            <option value="<?php echo htmlspecialchars($pr['id'], ENT_QUOTES); ?>" <?php echo $pr['id'] == $m['trunk_provider_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($pr['name'], ENT_QUOTES); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="tsisip-form-group">
                                    <label><?php echo _('Tenant'); ?></label>
                                    <select name="tenant_id" class="tsisip-input" required style="width:220px">
                                        <?php foreach ($tenants as $t): ?>
                                            <option value="<?php echo htmlspecialchars($t['id'], ENT_QUOTES); ?>" <?php echo $t['id'] == $m['tenant_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($t['name'] . ' (' . $t['sip_domain'] . ')', ENT_QUOTES); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="tsisip-form-group">
                                    <label><?php echo _('Dispatcher Set ID'); ?></label>
                                    <input type="number" name="dispatcher_setid" class="tsisip-input" value="<?php echo htmlspecialchars($m['dispatcher_setid'], ENT_QUOTES); ?>" min="1" style="width:120px">
                                </div>
                                <div class="tsisip-form-group">
                                    <label><?php echo _('Destination'); ?></label>
                                    <input type="text" name="destination" class="tsisip-input" value="<?php echo htmlspecialchars($m['destination'] ?? '', ENT_QUOTES); ?>" style="width:240px">
                                </div>
                                <div class="tsisip-form-group">
                                    <label><?php echo _('Description'); ?></label>
                                    <input type="text" name="description" class="tsisip-input" value="<?php echo htmlspecialchars($m['description'] ?? '', ENT_QUOTES); ?>" style="width:200px">
                                </div>
                                <div class="tsisip-form-group">
                                    <label>
                                        <input type="checkbox" name="enabled" <?php echo $m['enabled'] ? 'checked' : ''; ?>>
                                        <?php echo _('Enabled'); ?>
                                    </label>
                                </div>
                            </div>
                            <button type="submit" class="tsisip-btn tsisip-btn-primary"><?php echo _('Save'); ?></button>
                            <button type="button" class="tsisip-btn tsisip-btn-secondary"
                                    onclick="document.getElementById('edit-m<?php echo htmlspecialchars($m['id'], ENT_QUOTES); ?>').style.display='none'">
                                <?php echo _('Cancel'); ?>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php echo renderPagination($page, $totalItems, $perPage, 'trunk-dids.php'); ?>
    </div>
</div>
<?php require_once __DIR__ . '/common/footer.php'; ?>
