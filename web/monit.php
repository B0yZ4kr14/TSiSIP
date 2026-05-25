<?php
/**
 * TSiSIP Control Panel — Monit
 * Process monitoring and alerts (monit module)
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

// --- Handle mutating operations ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = _('Invalid CSRF token.');
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create' || $action === 'update') {
            $id   = $_POST['id'] ?? null;
            $name = trim($_POST['name'] ?? '');
            $proc = trim($_POST['process_name'] ?? '');
            $check = $_POST['check_type'] ?? 'process';
            $alert = trim($_POST['alert_email'] ?? '');
            $enabled = isset($_POST['enabled']) ? 1 : 0;

            if ($name === '' || $proc === '') {
                $error = _('Name and process name are required.');
            } else {
                try {
                    if ($action === 'create') {
                        $stmt = $pdo->prepare(
                            'INSERT INTO monit (name, process_name, check_type, alert_email, enabled)
                             VALUES (?, ?, ?, ?, ?)'
                        );
                        $stmt->execute([$name, $proc, $check, $alert, $enabled]);
                        $success = _('Monit check created successfully.');
                        logAuditEvent('MONIT_CREATE', 'monit', $name, true, ['process' => $proc]);
                    } else {
                        $stmt = $pdo->prepare(
                            'UPDATE monit SET name=?, process_name=?, check_type=?, alert_email=?, enabled=? WHERE id=?'
                        );
                        $stmt->execute([$name, $proc, $check, $alert, $enabled, $id]);
                        $success = _('Monit check updated successfully.');
                        logAuditEvent('MONIT_UPDATE', 'monit', $name, true);
                    }
                } catch (PDOException $e) {
                    $error = _('Database error: ') . $e->getMessage();
                }
            }
        } elseif ($action === 'delete') {
            $id = $_POST['id'] ?? '';
            try {
                $stmt = $pdo->prepare('SELECT name FROM monit WHERE id=?');
                $stmt->execute([$id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $stmt = $pdo->prepare('DELETE FROM monit WHERE id=?');
                    $stmt->execute([$id]);
                    $success = _('Monit check deleted successfully.');
                    logAuditEvent('MONIT_DELETE', 'monit', $row['name'], true);
                }
            } catch (PDOException $e) {
                $error = _('Database error: ') . $e->getMessage();
            }
        }
    }
}

// --- Fetch list ---
$page   = max(1, intval($_GET['page'] ?? 1));
$search = trim($_GET['search'] ?? '');
$perPage = 25;

$where = [];
$params = [];
if ($search !== '') {
    $where[] = '(name ILIKE ? OR process_name ILIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM monit $whereSql");
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));

$offset = ($page - 1) * $perPage;
$stmt = $pdo->prepare("SELECT * FROM monit $whereSql ORDER BY enabled DESC, name LIMIT ? OFFSET ?");
$stmt->execute(array_merge($params, [$perPage, $offset]));
$checks = $stmt->fetchAll(PDO::FETCH_ASSOC);

$editRow = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM monit WHERE id=?');
    $stmt->execute([$_GET['edit']]);
    $editRow = $stmt->fetch(PDO::FETCH_ASSOC);
}

$csrfToken = generateCsrfToken();
require_once __DIR__ . '/common/header.php';
?>

<div class="tsisip-page">
    <header class="tsisip-page-header">
        <h1 class="tsisip-page-title"><?php echo _('Monit'); ?></h1>
        <p class="tsisip-page-subtitle"><?php echo _('Process monitoring and alerts'); ?></p>
    </header>

    <?php if ($error): ?>
        <div class="tsisip-alert tsisip-alert--error" role="alert"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="tsisip-alert tsisip-alert--success" role="alert"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <section class="tsisip-section">
        <form method="get" class="tsisip-filter-bar">
            <input type="text" name="search" placeholder="<?php echo _('Search...'); ?>"
                   value="<?php echo htmlspecialchars($search); ?>" class="tsisip-input">
            <button type="submit" class="tsisip-btn tsisip-btn--primary"><?php echo _('Filter'); ?></button>
            <a href="monit.php" class="tsisip-btn tsisip-btn--secondary"><?php echo _('Clear'); ?></a>
        </form>
    </section>

    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo _('Monit Checks'); ?></h2>
        <table class="tsisip-table">
            <thead>
                <tr>
                    <th><?php echo _('Name'); ?></th>
                    <th><?php echo _('Process'); ?></th>
                    <th><?php echo _('Check Type'); ?></th>
                    <th><?php echo _('Alert Email'); ?></th>
                    <th><?php echo _('Status'); ?></th>
                    <th><?php echo _('Actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($checks)): ?>
                    <tr><td colspan="6" class="tsisip-empty"><?php echo _('No monit checks found.'); ?></td></tr>
                <?php else: ?>
                    <?php foreach ($checks as $c): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($c['name']); ?></td>
                            <td><code><?php echo htmlspecialchars($c['process_name']); ?></code></td>
                            <td><?php echo htmlspecialchars($c['check_type']); ?></td>
                            <td><?php echo htmlspecialchars($c['alert_email']); ?></td>
                            <td><?php echo $c['enabled'] ? '<span class="tsisip-tag tsisip-tag--success">'._('Enabled').'</span>' : '<span class="tsisip-tag tsisip-tag--muted">'._('Disabled').'</span>'; ?></td>
                            <td>
                                <a href="?edit=<?php echo $c['id']; ?>" class="tsisip-btn tsisip-btn--sm"><?php echo _('Edit'); ?></a>
                                <form method="post" style="display:inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                                    <button type="submit" class="tsisip-btn tsisip-btn--danger tsisip-btn--sm"
                                            onclick="return confirm('<?php echo _('Delete this check?'); ?>')">
                                        <?php echo _('Delete'); ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php renderPagination($page, $totalPages, $totalRows, $perPage, 'monit.php', ['search' => $search]); ?>
    </section>

    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo $editRow ? _('Edit Monit Check') : _('Add Monit Check'); ?></h2>
        <form method="post" class="tsisip-form">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="action" value="<?php echo $editRow ? 'update' : 'create'; ?>">
            <?php if ($editRow): ?><input type="hidden" name="id" value="<?php echo $editRow['id']; ?>"><?php endif; ?>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Name'); ?></label>
                <input type="text" name="name" required value="<?php echo $editRow ? htmlspecialchars($editRow['name']) : ''; ?>" class="tsisip-input">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Process Name'); ?></label>
                <input type="text" name="process_name" required value="<?php echo $editRow ? htmlspecialchars($editRow['process_name']) : ''; ?>" class="tsisip-input" placeholder="opensips">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Check Type'); ?></label>
                <select name="check_type" class="tsisip-select">
                    <option value="process" <?php echo !$editRow || $editRow['check_type'] === 'process' ? 'selected' : ''; ?>><?php echo _('Process'); ?></option>
                    <option value="file" <?php echo $editRow && $editRow['check_type'] === 'file' ? 'selected' : ''; ?>><?php echo _('File'); ?></option>
                    <option value="filesystem" <?php echo $editRow && $editRow['check_type'] === 'filesystem' ? 'selected' : ''; ?>><?php echo _('Filesystem'); ?></option>
                    <option value="host" <?php echo $editRow && $editRow['check_type'] === 'host' ? 'selected' : ''; ?>><?php echo _('Host'); ?></option>
                    <option value="network" <?php echo $editRow && $editRow['check_type'] === 'network' ? 'selected' : ''; ?>><?php echo _('Network'); ?></option>
                    <option value="program" <?php echo $editRow && $editRow['check_type'] === 'program' ? 'selected' : ''; ?>><?php echo _('Program'); ?></option>
                    <option value="system" <?php echo $editRow && $editRow['check_type'] === 'system' ? 'selected' : ''; ?>><?php echo _('System'); ?></option>
                </select>
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Alert Email'); ?></label>
                <input type="email" name="alert_email" value="<?php echo $editRow ? htmlspecialchars($editRow['alert_email']) : ''; ?>" class="tsisip-input" placeholder="admin@tsiapp.io">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-checkbox">
                    <input type="checkbox" name="enabled" <?php echo (!$editRow || $editRow['enabled']) ? 'checked' : ''; ?>>
                    <?php echo _('Enabled'); ?>
                </label>
            </div>
            <button type="submit" class="tsisip-btn tsisip-btn--primary"><?php echo $editRow ? _('Update') : _('Create'); ?></button>
            <?php if ($editRow): ?><a href="monit.php" class="tsisip-btn tsisip-btn--secondary"><?php echo _('Cancel'); ?></a><?php endif; ?>
        </form>
    </section>
</div>

<?php require_once __DIR__ . '/common/footer.php'; ?>
