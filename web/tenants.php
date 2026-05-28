<?php
/**
 * TSiSIP Control Panel — Tenant Management
 * CRUD for the tenants table (multi-tenant SIP domain registry).
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
            $name = trim($_POST['name'] ?? '');
            $sipDomain = trim($_POST['sip_domain'] ?? '');
            $enabled = isset($_POST['enabled']) ? true : false;

            if ($name === '' || $sipDomain === '') {
                $error = _('Name and SIP domain are required.');
            } else {
                try {
                    $stmt = $pdo->prepare(
                        "INSERT INTO tenants (name, sip_domain, enabled) VALUES (:name, :sip_domain, :enabled)"
                    );
                    $stmt->execute([':name' => $name, ':sip_domain' => $sipDomain, ':enabled' => $enabled]);
                    $success = _('Tenant created successfully.');
                    logAuditEvent('TENANT_CREATE', 'tenant', $name, true, ['sip_domain' => $sipDomain]);
                } catch (PDOException $e) {
                    $error = _('Failed to create tenant: ') . $e->getMessage();
                }
            }
        } elseif ($action === 'update') {
            $id = $_POST['id'] ?? '';
            $name = trim($_POST['name'] ?? '');
            $sipDomain = trim($_POST['sip_domain'] ?? '');
            $enabled = isset($_POST['enabled']) ? true : false;

            if ($id === '' || $name === '' || $sipDomain === '') {
                $error = _('ID, name, and SIP domain are required.');
            } else {
                try {
                    $stmt = $pdo->prepare(
                        "UPDATE tenants SET name = :name, sip_domain = :sip_domain, enabled = :enabled WHERE id = :id"
                    );
                    $stmt->execute([':id' => $id, ':name' => $name, ':sip_domain' => $sipDomain, ':enabled' => $enabled]);
                    $success = _('Tenant updated successfully.');
                    logAuditEvent('TENANT_UPDATE', 'tenant', $name, true);
                } catch (PDOException $e) {
                    $error = _('Failed to update tenant: ') . $e->getMessage();
                }
            }
        } elseif ($action === 'delete') {
            $id = $_POST['id'] ?? '';
            if ($id !== '') {
                try {
                    $stmt = $pdo->prepare("DELETE FROM tenants WHERE id = :id");
                    $stmt->execute([':id' => $id]);
                    $success = _('Tenant deleted successfully.');
                    logAuditEvent('TENANT_DELETE', 'tenant', $id, true);
                } catch (PDOException $e) {
                    $error = _('Failed to delete tenant: ') . $e->getMessage();
                }
            }
        }
    }
}

$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 25;
$pagination = getPagination($page, $perPage);

$countStmt = $pdo->query("SELECT COUNT(*) FROM tenants");
$totalItems = (int) $countStmt->fetchColumn();

$listStmt = $pdo->prepare("SELECT id, name, sip_domain, enabled, created_at FROM tenants ORDER BY name LIMIT :limit OFFSET :offset");
$listStmt->bindValue(':limit', $pagination['limit'], PDO::PARAM_INT);
$listStmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
$listStmt->execute();
$tenants = $listStmt->fetchAll();

require_once __DIR__ . '/common/header.php';
?>
<div id="content" class="tsisip-dashboard">
    <h2><?php echo _('Tenant Management'); ?></h2>

    <?php if ($error): ?>
        <div class="tsisip-alert tsisip-alert--error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="tsisip-alert tsisip-alert--success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="tsisip-dashboard-section">
        <h3><?php echo _('Add New Tenant'); ?></h3>
        <form method="POST" action="" class="tsisip-form">
            <?php echo csrfInput(); ?>
            <input type="hidden" name="action" value="create">
            <div class="tsisip-form-group">
                <label for="name"><?php echo _('Name'); ?></label>
                <input type="text" id="name" name="name" class="tsisip-input" required>
            </div>
            <div class="tsisip-form-group">
                <label for="sip_domain"><?php echo _('SIP Domain'); ?></label>
                <input type="text" id="sip_domain" name="sip_domain" class="tsisip-input" required>
            </div>
            <div class="tsisip-form-group">
                <label>
                    <input type="checkbox" name="enabled" checked>
                    <?php echo _('Enabled'); ?>
                </label>
            </div>
            <button type="submit" class="tsisip-btn tsisip-btn-primary"><?php echo _('Create Tenant'); ?></button>
        </form>
    </div>

    <div class="tsisip-dashboard-section">
        <h3><?php echo _('Tenants'); ?> (<?php echo $totalItems; ?>)</h3>
        <table class="dataTable tsisip-table">
            <thead>
                <tr>
                    <th><?php echo _('Name'); ?></th>
                    <th><?php echo _('SIP Domain'); ?></th>
                    <th><?php echo _('Status'); ?></th>
                    <th><?php echo _('Created'); ?></th>
                    <th><?php echo _('Actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tenants as $t): ?>
                <tr>
                    <td><?php echo htmlspecialchars($t['name']); ?></td>
                    <td><?php echo htmlspecialchars($t['sip_domain']); ?></td>
                    <td>
                        <?php if ($t['enabled']): ?>
                            <span class="tsisip-badge tsisip-badge-success"><?php echo _('Active'); ?></span>
                        <?php else: ?>
                            <span class="tsisip-badge tsisip-badge-error"><?php echo _('Disabled'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($t['created_at']); ?></td>
                    <td class="tsisip-actions-column">
                        <button type="button" class="tsisip-btn tsisip-btn-secondary"
                                onclick="document.getElementById('edit-<?php echo htmlspecialchars($t['id'], ENT_QUOTES); ?>').style.display='block'">
                            <?php echo _('Edit'); ?>
                        </button>
                        <form method="POST" action="" style="display:inline" onsubmit="return confirm('<?php echo _('Delete this tenant?'); ?>');">
                            <?php echo csrfInput(); ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($t['id'], ENT_QUOTES); ?>">
                            <button type="submit" class="tsisip-btn tsisip-btn-danger"><?php echo _('Delete'); ?></button>
                        </form>
                    </td>
                </tr>
                <tr id="edit-<?php echo htmlspecialchars($t['id'], ENT_QUOTES); ?>" style="display:none">
                    <td colspan="5">
                        <form method="POST" action="" class="tsisip-form">
                            <?php echo csrfInput(); ?>
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($t['id'], ENT_QUOTES); ?>">
                            <div class="tsisip-form-group">
                                <label><?php echo _('Name'); ?></label>
                                <input type="text" name="name" class="tsisip-input" value="<?php echo htmlspecialchars($t['name'], ENT_QUOTES); ?>" required>
                            </div>
                            <div class="tsisip-form-group">
                                <label><?php echo _('SIP Domain'); ?></label>
                                <input type="text" name="sip_domain" class="tsisip-input" value="<?php echo htmlspecialchars($t['sip_domain'], ENT_QUOTES); ?>" required>
                            </div>
                            <div class="tsisip-form-group">
                                <label>
                                    <input type="checkbox" name="enabled" <?php echo $t['enabled'] ? 'checked' : ''; ?>>
                                    <?php echo _('Enabled'); ?>
                                </label>
                            </div>
                            <button type="submit" class="tsisip-btn tsisip-btn-primary"><?php echo _('Save Changes'); ?></button>
                            <button type="button" class="tsisip-btn tsisip-btn-secondary"
                                    onclick="document.getElementById('edit-<?php echo htmlspecialchars($t['id'], ENT_QUOTES); ?>').style.display='none'">
                                <?php echo _('Cancel'); ?>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php echo renderPagination($page, $totalItems, $perPage, 'tenants.php'); ?>
    </div>
</div>
<?php require_once __DIR__ . '/common/footer.php'; ?>
