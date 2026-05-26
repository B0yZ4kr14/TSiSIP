<?php
/**
 * TSiSIP Control Panel — Trunk Provider Management
 * CRUD on sip_trunk_providers with credential encryption.
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

$trunkCredKey = getTrunkCredKey();

// --- Handle mutating operations ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = _('Invalid CSRF token.');
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create') {
            $name = trim($_POST['name'] ?? '');
            $host = trim($_POST['host'] ?? '');
            $port = intval($_POST['port'] ?? 5060);
            $transport = trim($_POST['transport'] ?? 'udp');
            $authUsername = trim($_POST['auth_username'] ?? '');
            $authPassword = $_POST['auth_password'] ?? '';
            $fromDomain = trim($_POST['from_domain'] ?? '');
            $callerIdPrefix = trim($_POST['caller_id_prefix'] ?? '');
            $priority = intval($_POST['priority'] ?? 100);
            $enabled = isset($_POST['enabled']) ? true : false;

            if ($name === '' || $host === '') {
                $error = _('Name and host are required.');
            } elseif ($authPassword !== '' && $trunkCredKey === '') {
                $error = _('Trunk credential encryption key is not available.');
            } else {
                try {
                    $sql = "INSERT INTO sip_trunk_providers
                            (name, host, port, transport, auth_username, auth_password_encrypted,
                             from_domain, caller_id_prefix, priority, enabled)
                            VALUES (:name, :host, :port, :transport, :auth_username, ";
                    if ($authPassword !== '') {
                        $sql .= "pgp_sym_encrypt(:auth_password, :key), ";
                    } else {
                        $sql .= "NULL, ";
                    }
                    $sql .= ":from_domain, :caller_id_prefix, :priority, :enabled)";
                    $stmt = $pdo->prepare($sql);
                    $params = [
                        ':name' => $name,
                        ':host' => $host,
                        ':port' => $port,
                        ':transport' => $transport,
                        ':auth_username' => $authUsername,
                        ':from_domain' => $fromDomain,
                        ':caller_id_prefix' => $callerIdPrefix,
                        ':priority' => $priority,
                        ':enabled' => $enabled,
                    ];
                    if ($authPassword !== '') {
                        $params[':auth_password'] = $authPassword;
                        $params[':key'] = $trunkCredKey;
                    }
                    $stmt->execute($params);
                    $newId = $pdo->lastInsertId();
                    $success = _('Trunk provider created successfully.');
                    logAuditEvent('TRUNK_PROVIDER_CREATE', 'sip_trunk_provider', (string)$newId, true, [
                        'name' => $name,
                        'host' => $host,
                        'port' => $port,
                        'transport' => $transport,
                        'priority' => $priority,
                        'enabled' => $enabled,
                    ]);
                } catch (PDOException $e) {
                    $error = _('Failed to create trunk provider: ') . $e->getMessage();
                }
            }
        } elseif ($action === 'update') {
            $id = intval($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $host = trim($_POST['host'] ?? '');
            $port = intval($_POST['port'] ?? 5060);
            $transport = trim($_POST['transport'] ?? 'udp');
            $authUsername = trim($_POST['auth_username'] ?? '');
            $authPassword = $_POST['auth_password'] ?? '';
            $fromDomain = trim($_POST['from_domain'] ?? '');
            $callerIdPrefix = trim($_POST['caller_id_prefix'] ?? '');
            $priority = intval($_POST['priority'] ?? 100);
            $enabled = isset($_POST['enabled']) ? true : false;

            if ($id === 0 || $name === '' || $host === '') {
                $error = _('ID, name, and host are required.');
            } elseif ($authPassword !== '' && $trunkCredKey === '') {
                $error = _('Trunk credential encryption key is not available.');
            } else {
                try {
                    if ($authPassword !== '') {
                        $stmt = $pdo->prepare(
                            "UPDATE sip_trunk_providers SET
                             name = :name,
                             host = :host,
                             port = :port,
                             transport = :transport,
                             auth_username = :auth_username,
                             auth_password_encrypted = pgp_sym_encrypt(:auth_password, :key),
                             from_domain = :from_domain,
                             caller_id_prefix = :caller_id_prefix,
                             priority = :priority,
                             enabled = :enabled,
                             updated_at = NOW()
                             WHERE id = :id"
                        );
                        $stmt->execute([
                            ':id' => $id,
                            ':name' => $name,
                            ':host' => $host,
                            ':port' => $port,
                            ':transport' => $transport,
                            ':auth_username' => $authUsername,
                            ':auth_password' => $authPassword,
                            ':key' => $trunkCredKey,
                            ':from_domain' => $fromDomain,
                            ':caller_id_prefix' => $callerIdPrefix,
                            ':priority' => $priority,
                            ':enabled' => $enabled,
                        ]);
                    } else {
                        $stmt = $pdo->prepare(
                            "UPDATE sip_trunk_providers SET
                             name = :name,
                             host = :host,
                             port = :port,
                             transport = :transport,
                             auth_username = :auth_username,
                             from_domain = :from_domain,
                             caller_id_prefix = :caller_id_prefix,
                             priority = :priority,
                             enabled = :enabled,
                             updated_at = NOW()
                             WHERE id = :id"
                        );
                        $stmt->execute([
                            ':id' => $id,
                            ':name' => $name,
                            ':host' => $host,
                            ':port' => $port,
                            ':transport' => $transport,
                            ':auth_username' => $authUsername,
                            ':from_domain' => $fromDomain,
                            ':caller_id_prefix' => $callerIdPrefix,
                            ':priority' => $priority,
                            ':enabled' => $enabled,
                        ]);
                    }
                    $success = _('Trunk provider updated successfully.');
                    logAuditEvent('TRUNK_PROVIDER_UPDATE', 'sip_trunk_provider', (string)$id, true, [
                        'name' => $name,
                        'host' => $host,
                        'port' => $port,
                        'transport' => $transport,
                        'priority' => $priority,
                        'enabled' => $enabled,
                    ]);
                } catch (PDOException $e) {
                    $error = _('Failed to update trunk provider: ') . $e->getMessage();
                }
            }
        } elseif ($action === 'toggle') {
            $id = intval($_POST['id'] ?? 0);
            $enabled = isset($_POST['enabled']) ? true : false;
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE sip_trunk_providers SET enabled = :enabled, updated_at = NOW() WHERE id = :id");
                $stmt->execute([':id' => $id, ':enabled' => $enabled]);
                $success = _('Trunk provider status updated.');
                logAuditEvent('TRUNK_PROVIDER_TOGGLE', 'sip_trunk_provider', (string)$id, true, [
                    'enabled' => $enabled,
                ]);
            }
        } elseif ($action === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            if ($id > 0) {
                $stmt = $pdo->prepare("DELETE FROM sip_trunk_providers WHERE id = :id");
                $stmt->execute([':id' => $id]);
                $success = _('Trunk provider deleted.');
                logAuditEvent('TRUNK_PROVIDER_DELETE', 'sip_trunk_provider', (string)$id, true);
            }
        }
    }
}

// --- List with pagination ---
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 25;
$pagination = getPagination($page, $perPage);

$countStmt = $pdo->query("SELECT COUNT(*) FROM sip_trunk_providers");
$totalItems = (int) $countStmt->fetchColumn();

$listStmt = $pdo->prepare(
    "SELECT id, name, host, port, transport, auth_username, from_domain,
            caller_id_prefix, priority, enabled, created_at, updated_at
     FROM sip_trunk_providers
     ORDER BY priority ASC, name ASC
     LIMIT :limit OFFSET :offset"
);
$listStmt->bindValue(':limit', $pagination['limit'], PDO::PARAM_INT);
$listStmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
$listStmt->execute();
$providers = $listStmt->fetchAll();

require_once __DIR__ . '/common/header.php';
?>
<div id="content">
    <h2><?php echo _('Trunk Providers'); ?></h2>

    <?php if ($error): ?>
        <div class="tsisip-badge tsisip-badge-error" role="alert"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="tsisip-badge tsisip-badge-success" role="status"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <!-- Create Form -->
    <div class="tsisip-dashboard-section">
        <h3><?php echo _('Add Trunk Provider'); ?></h3>
        <form method="POST" action="" class="tsisip-form">
            <?php echo csrfInput(); ?>
            <input type="hidden" name="action" value="create">
            <div style="display:flex;flex-wrap:wrap;gap:1rem">
                <div class="tsisip-form-group">
                    <label for="name"><?php echo _('Name'); ?></label>
                    <input type="text" id="name" name="name" class="tsisip-input" required style="width:200px">
                </div>
                <div class="tsisip-form-group">
                    <label for="host"><?php echo _('Host'); ?></label>
                    <input type="text" id="host" name="host" class="tsisip-input" required style="width:200px">
                </div>
                <div class="tsisip-form-group">
                    <label for="port"><?php echo _('Port'); ?></label>
                    <input type="number" id="port" name="port" class="tsisip-input" value="5060" min="1" max="65535" required style="width:100px">
                </div>
                <div class="tsisip-form-group">
                    <label for="transport"><?php echo _('Transport'); ?></label>
                    <select id="transport" name="transport" class="tsisip-input" style="width:120px">
                        <option value="udp">UDP</option>
                        <option value="tcp">TCP</option>
                        <option value="tls">TLS</option>
                        <option value="ws">WS</option>
                        <option value="wss">WSS</option>
                    </select>
                </div>
                <div class="tsisip-form-group">
                    <label for="auth_username"><?php echo _('Auth Username'); ?></label>
                    <input type="text" id="auth_username" name="auth_username" class="tsisip-input" style="width:180px">
                </div>
                <div class="tsisip-form-group">
                    <label for="auth_password"><?php echo _('Auth Password'); ?></label>
                    <input type="password" id="auth_password" name="auth_password" class="tsisip-input" style="width:180px">
                </div>
                <div class="tsisip-form-group">
                    <label for="from_domain"><?php echo _('From Domain'); ?></label>
                    <input type="text" id="from_domain" name="from_domain" class="tsisip-input" style="width:200px">
                </div>
                <div class="tsisip-form-group">
                    <label for="caller_id_prefix"><?php echo _('Caller ID Prefix'); ?></label>
                    <input type="text" id="caller_id_prefix" name="caller_id_prefix" class="tsisip-input" style="width:150px">
                </div>
                <div class="tsisip-form-group">
                    <label for="priority"><?php echo _('Priority'); ?></label>
                    <input type="number" id="priority" name="priority" class="tsisip-input" value="100" style="width:100px">
                </div>
                <div class="tsisip-form-group">
                    <label>
                        <input type="checkbox" name="enabled" checked>
                        <?php echo _('Enabled'); ?>
                    </label>
                </div>
            </div>
            <button type="submit" class="tsisip-btn tsisip-btn-primary"><?php echo _('Create Provider'); ?></button>
        </form>
    </div>

    <!-- List -->
    <div class="tsisip-dashboard-section">
        <h3><?php echo _('Trunk Providers'); ?> (<?php echo $totalItems; ?>)</h3>
        <table class="dataTable tsisip-table">
            <thead>
                <tr>
                    <th><?php echo _('Name'); ?></th>
                    <th><?php echo _('Host'); ?></th>
                    <th><?php echo _('Port'); ?></th>
                    <th><?php echo _('Transport'); ?></th>
                    <th><?php echo _('Auth Username'); ?></th>
                    <th><?php echo _('From Domain'); ?></th>
                    <th><?php echo _('Prefix'); ?></th>
                    <th><?php echo _('Priority'); ?></th>
                    <th><?php echo _('Status'); ?></th>
                    <th><?php echo _('Actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($providers as $p): ?>
                <tr>
                    <td><?php echo htmlspecialchars($p['name']); ?></td>
                    <td><?php echo htmlspecialchars($p['host']); ?></td>
                    <td><?php echo htmlspecialchars($p['port']); ?></td>
                    <td><?php echo htmlspecialchars($p['transport']); ?></td>
                    <td><?php echo htmlspecialchars($p['auth_username'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($p['from_domain'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($p['caller_id_prefix'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($p['priority']); ?></td>
                    <td>
                        <?php if ($p['enabled']): ?>
                            <span class="tsisip-badge tsisip-badge-success"><?php echo _('Enabled'); ?></span>
                        <?php else: ?>
                            <span class="tsisip-badge tsisip-badge-error"><?php echo _('Disabled'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="tsisip-actions-column">
                        <form method="POST" action="" style="display:inline">
                            <?php echo csrfInput(); ?>
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($p['id'], ENT_QUOTES); ?>">
                            <input type="hidden" name="enabled" value="<?php echo $p['enabled'] ? '0' : '1'; ?>">
                            <button type="submit" class="tsisip-btn tsisip-btn-secondary">
                                <?php echo $p['enabled'] ? _('Disable') : _('Enable'); ?>
                            </button>
                        </form>
                        <button type="button" class="tsisip-btn tsisip-btn-secondary"
                                onclick="document.getElementById('edit-p<?php echo htmlspecialchars($p['id'], ENT_QUOTES); ?>').style.display='table-row'">
                            <?php echo _('Edit'); ?>
                        </button>
                        <form method="POST" action="" style="display:inline" onsubmit="return confirm('<?php echo _('Delete this provider?'); ?>')">
                            <?php echo csrfInput(); ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($p['id'], ENT_QUOTES); ?>">
                            <button type="submit" class="tsisip-btn tsisip-btn-secondary tsisip-btn-delete"><?php echo _('Delete'); ?></button>
                        </form>
                    </td>
                </tr>
                <tr id="edit-p<?php echo htmlspecialchars($p['id'], ENT_QUOTES); ?>" style="display:none">
                    <td colspan="10">
                        <form method="POST" action="" class="tsisip-form">
                            <?php echo csrfInput(); ?>
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($p['id'], ENT_QUOTES); ?>">
                            <div style="display:flex;flex-wrap:wrap;gap:1rem">
                                <div class="tsisip-form-group">
                                    <label><?php echo _('Name'); ?></label>
                                    <input type="text" name="name" class="tsisip-input" value="<?php echo htmlspecialchars($p['name'], ENT_QUOTES); ?>" required style="width:200px">
                                </div>
                                <div class="tsisip-form-group">
                                    <label><?php echo _('Host'); ?></label>
                                    <input type="text" name="host" class="tsisip-input" value="<?php echo htmlspecialchars($p['host'], ENT_QUOTES); ?>" required style="width:200px">
                                </div>
                                <div class="tsisip-form-group">
                                    <label><?php echo _('Port'); ?></label>
                                    <input type="number" name="port" class="tsisip-input" value="<?php echo htmlspecialchars($p['port'], ENT_QUOTES); ?>" min="1" max="65535" required style="width:100px">
                                </div>
                                <div class="tsisip-form-group">
                                    <label><?php echo _('Transport'); ?></label>
                                    <select name="transport" class="tsisip-input" style="width:120px">
                                        <option value="udp" <?php echo $p['transport'] === 'udp' ? 'selected' : ''; ?>>UDP</option>
                                        <option value="tcp" <?php echo $p['transport'] === 'tcp' ? 'selected' : ''; ?>>TCP</option>
                                        <option value="tls" <?php echo $p['transport'] === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                        <option value="ws" <?php echo $p['transport'] === 'ws' ? 'selected' : ''; ?>>WS</option>
                                        <option value="wss" <?php echo $p['transport'] === 'wss' ? 'selected' : ''; ?>>WSS</option>
                                    </select>
                                </div>
                                <div class="tsisip-form-group">
                                    <label><?php echo _('Auth Username'); ?></label>
                                    <input type="text" name="auth_username" class="tsisip-input" value="<?php echo htmlspecialchars($p['auth_username'] ?? '', ENT_QUOTES); ?>" style="width:180px">
                                </div>
                                <div class="tsisip-form-group">
                                    <label><?php echo _('Auth Password (leave blank to keep)'); ?></label>
                                    <input type="password" name="auth_password" class="tsisip-input" style="width:180px">
                                </div>
                                <div class="tsisip-form-group">
                                    <label><?php echo _('From Domain'); ?></label>
                                    <input type="text" name="from_domain" class="tsisip-input" value="<?php echo htmlspecialchars($p['from_domain'] ?? '', ENT_QUOTES); ?>" style="width:200px">
                                </div>
                                <div class="tsisip-form-group">
                                    <label><?php echo _('Caller ID Prefix'); ?></label>
                                    <input type="text" name="caller_id_prefix" class="tsisip-input" value="<?php echo htmlspecialchars($p['caller_id_prefix'] ?? '', ENT_QUOTES); ?>" style="width:150px">
                                </div>
                                <div class="tsisip-form-group">
                                    <label><?php echo _('Priority'); ?></label>
                                    <input type="number" name="priority" class="tsisip-input" value="<?php echo htmlspecialchars($p['priority'], ENT_QUOTES); ?>" style="width:100px">
                                </div>
                                <div class="tsisip-form-group">
                                    <label>
                                        <input type="checkbox" name="enabled" <?php echo $p['enabled'] ? 'checked' : ''; ?>>
                                        <?php echo _('Enabled'); ?>
                                    </label>
                                </div>
                            </div>
                            <button type="submit" class="tsisip-btn tsisip-btn-primary"><?php echo _('Save'); ?></button>
                            <button type="button" class="tsisip-btn tsisip-btn-secondary"
                                    onclick="document.getElementById('edit-p<?php echo htmlspecialchars($p['id'], ENT_QUOTES); ?>').style.display='none'">
                                <?php echo _('Cancel'); ?>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php echo renderPagination($page, $totalItems, $perPage, 'trunk-providers.php'); ?>
    </div>
</div>
<?php require_once __DIR__ . '/common/footer.php'; ?>
