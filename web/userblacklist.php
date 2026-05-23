<?php
/**
 * TSiSIP Control Panel — User Blacklist Management
 * CRUD for the userblacklist table (OpenSIPS userblacklist module).
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
            $username = trim($_POST['username'] ?? '');
            $domain = trim($_POST['domain'] ?? '');
            $prefix = trim($_POST['prefix'] ?? '');
            $whitelist = isset($_POST['whitelist']) ? 1 : 0;
            try {
                $stmt = $pdo->prepare("INSERT INTO userblacklist (username, domain, prefix, whitelist) VALUES (:username, :domain, :prefix, :whitelist)");
                $stmt->execute([':username' => $username, ':domain' => $domain, ':prefix' => $prefix, ':whitelist' => $whitelist]);
                $success = _('Entry created successfully.');
                logAuditEvent('USERBLACKLIST_CREATE', 'userblacklist', $username, true);
            } catch (PDOException $e) {
                $error = _('Failed to create entry: ') . $e->getMessage();
            }
        } elseif ($action === 'delete') {
            $id = $_POST['id'] ?? '';
            if ($id !== '') {
                try {
                    $stmt = $pdo->prepare("DELETE FROM userblacklist WHERE id = :id");
                    $stmt->execute([':id' => $id]);
                    $success = _('Entry deleted successfully.');
                    logAuditEvent('USERBLACKLIST_DELETE', 'userblacklist', $id, true);
                } catch (PDOException $e) {
                    $error = _('Failed to delete entry: ') . $e->getMessage();
                }
            }
        }
    }
}

$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 25;
$pagination = getPagination($page, $perPage);

$countStmt = $pdo->query("SELECT COUNT(*) FROM userblacklist");
$totalItems = (int) $countStmt->fetchColumn();

$listStmt = $pdo->prepare("SELECT id, username, domain, prefix, whitelist FROM userblacklist ORDER BY id DESC LIMIT :limit OFFSET :offset");
$listStmt->bindValue(':limit', $pagination['limit'], PDO::PARAM_INT);
$listStmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
$listStmt->execute();
$entries = $listStmt->fetchAll();

require_once __DIR__ . '/common/header.php';
?>
<div class="tsisip-dashboard">
    <h2><?php echo _('User Blacklist'); ?></h2>
    <p class="tsisip-text-muted"><?php echo _('Manage per-user and global ban/allow lists for the OpenSIPS userblacklist module.'); ?></p>

    <?php if ($error): ?>
        <div class="tsisip-alert tsisip-alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="tsisip-alert tsisip-alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="tsisip-dashboard-section">
        <h3><?php echo _('Add Entry'); ?></h3>
        <form method="POST" action="" class="tsisip-form">
            <?php echo csrfInput(); ?>
            <input type="hidden" name="action" value="create">
            <div class="tsisip-form-group">
                <label for="username"><?php echo _('Username (empty for global)'); ?></label>
                <input type="text" id="username" name="username" class="tsisip-input">
            </div>
            <div class="tsisip-form-group">
                <label for="domain"><?php echo _('Domain (empty for any)'); ?></label>
                <input type="text" id="domain" name="domain" class="tsisip-input">
            </div>
            <div class="tsisip-form-group">
                <label for="prefix"><?php echo _('Prefix / Pattern'); ?></label>
                <input type="text" id="prefix" name="prefix" class="tsisip-input" required>
            </div>
            <div class="tsisip-form-group">
                <label>
                    <input type="checkbox" name="whitelist">
                    <?php echo _('Whitelist (allow instead of block)'); ?>
                </label>
            </div>
            <button type="submit" class="tsisip-btn tsisip-btn-primary"><?php echo _('Add Entry'); ?></button>
        </form>
    </div>

    <div class="tsisip-dashboard-section">
        <h3><?php echo _('Entries'); ?> (<?php echo $totalItems; ?>)</h3>
        <table class="dataTable tsisip-table">
            <thead>
                <tr><th><?php echo _('Username'); ?></th><th><?php echo _('Domain'); ?></th><th><?php echo _('Prefix'); ?></th><th><?php echo _('Type'); ?></th><th><?php echo _('Actions'); ?></th></tr>
            </thead>
            <tbody>
                <?php foreach ($entries as $e): ?>
                <tr>
                    <td><?php echo htmlspecialchars($e['username'] ?: _('(global)')); ?></td>
                    <td><?php echo htmlspecialchars($e['domain'] ?: _('(any)')); ?></td>
                    <td><?php echo htmlspecialchars($e['prefix']); ?></td>
                    <td>
                        <?php if ($e['whitelist']): ?>
                            <span class="tsisip-badge tsisip-badge-success"><?php echo _('Allow'); ?></span>
                        <?php else: ?>
                            <span class="tsisip-badge tsisip-badge-error"><?php echo _('Block'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="tsisip-actions-column">
                        <form method="POST" action="" style="display:inline" onsubmit="return confirm('<?php echo _('Delete this entry?'); ?>');">
                            <?php echo csrfInput(); ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($e['id'], ENT_QUOTES); ?>">
                            <button type="submit" class="tsisip-btn tsisip-btn-danger"><?php echo _('Delete'); ?></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php echo renderPagination($page, $totalItems, $perPage, 'userblacklist.php'); ?>
    </div>
</div>
<?php require_once __DIR__ . '/common/footer.php'; ?>
