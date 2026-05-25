<?php
/**
 * TSiSIP Control Panel — Keepalived
 * Virtual IP and health-check management (keepalived module)
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
            $id    = $_POST['id'] ?? null;
            $vrrpId = intval($_POST['vrrp_id'] ?? 0);
            $state = $_POST['state'] ?? 'backup';
            $prio  = intval($_POST['priority'] ?? 100);
            $iface = trim($_POST['interface'] ?? '');
            $virt  = trim($_POST['virtual_ip'] ?? '');
            $enabled = isset($_POST['enabled']) ? 1 : 0;

            if ($vrrpId < 1 || $iface === '' || $virt === '') {
                $error = _('VRRP ID, interface and virtual IP are required.');
            } elseif (!filter_var($virt, FILTER_VALIDATE_IP)) {
                $error = _('Invalid virtual IP address.');
            } else {
                try {
                    if ($action === 'create') {
                        $stmt = $pdo->prepare(
                            'INSERT INTO keepalived (vrrp_id, state, priority, interface, virtual_ip, enabled)
                             VALUES (?, ?, ?, ?, ?, ?)'
                        );
                        $stmt->execute([$vrrpId, $state, $prio, $iface, $virt, $enabled]);
                        $success = _('Keepalived instance created successfully.');
                        logAuditEvent('KEEPALIVED_CREATE', 'keepalived', $virt, true, ['vrrp_id' => $vrrpId]);
                    } else {
                        $stmt = $pdo->prepare(
                            'UPDATE keepalived SET vrrp_id=?, state=?, priority=?, interface=?, virtual_ip=?, enabled=? WHERE id=?'
                        );
                        $stmt->execute([$vrrpId, $state, $prio, $iface, $virt, $enabled, $id]);
                        $success = _('Keepalived instance updated successfully.');
                        logAuditEvent('KEEPALIVED_UPDATE', 'keepalived', $virt, true);
                    }
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'unique constraint') !== false) {
                        $error = _('This VRRP ID/interface combination already exists.');
                    } else {
                        $error = _('Database error: ') . $e->getMessage();
                    }
                }
            }
        } elseif ($action === 'delete') {
            $id = $_POST['id'] ?? '';
            try {
                $stmt = $pdo->prepare('SELECT virtual_ip FROM keepalived WHERE id=?');
                $stmt->execute([$id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $stmt = $pdo->prepare('DELETE FROM keepalived WHERE id=?');
                    $stmt->execute([$id]);
                    $success = _('Keepalived instance deleted successfully.');
                    logAuditEvent('KEEPALIVED_DELETE', 'keepalived', $row['virtual_ip'], true);
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
    $where[] = '(virtual_ip ILIKE ? OR interface ILIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM keepalived $whereSql");
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));

$offset = ($page - 1) * $perPage;
$stmt = $pdo->prepare("SELECT * FROM keepalived $whereSql ORDER BY enabled DESC, vrrp_id LIMIT ? OFFSET ?");
$stmt->execute(array_merge($params, [$perPage, $offset]));
$instances = $stmt->fetchAll(PDO::FETCH_ASSOC);

$editRow = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM keepalived WHERE id=?');
    $stmt->execute([$_GET['edit']]);
    $editRow = $stmt->fetch(PDO::FETCH_ASSOC);
}

$csrfToken = generateCsrfToken();
require_once __DIR__ . '/common/header.php';
?>

<div class="tsisip-page">
    <header class="tsisip-page-header">
        <h1 class="tsisip-page-title"><?php echo _('Keepalived'); ?></h1>
        <p class="tsisip-page-subtitle"><?php echo _('Virtual IP and high-availability management'); ?></p>
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
            <a href="keepalived.php" class="tsisip-btn tsisip-btn--secondary"><?php echo _('Clear'); ?></a>
        </form>
    </section>

    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo _('Keepalived Instances'); ?></h2>
        <table class="tsisip-table">
            <thead>
                <tr>
                    <th><?php echo _('VRRP ID'); ?></th>
                    <th><?php echo _('State'); ?></th>
                    <th><?php echo _('Priority'); ?></th>
                    <th><?php echo _('Interface'); ?></th>
                    <th><?php echo _('Virtual IP'); ?></th>
                    <th><?php echo _('Status'); ?></th>
                    <th><?php echo _('Actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($instances)): ?>
                    <tr><td colspan="7" class="tsisip-empty"><?php echo _('No Keepalived instances found.'); ?></td></tr>
                <?php else: ?>
                    <?php foreach ($instances as $i): ?>
                        <tr>
                            <td><?php echo $i['vrrp_id']; ?></td>
                            <td><?php echo htmlspecialchars(strtoupper($i['state'])); ?></td>
                            <td><?php echo $i['priority']; ?></td>
                            <td><?php echo htmlspecialchars($i['interface']); ?></td>
                            <td><code><?php echo htmlspecialchars($i['virtual_ip']); ?></code></td>
                            <td><?php echo $i['enabled'] ? '<span class="tsisip-tag tsisip-tag--success">'._('Enabled').'</span>' : '<span class="tsisip-tag tsisip-tag--muted">'._('Disabled').'</span>'; ?></td>
                            <td>
                                <a href="?edit=<?php echo $i['id']; ?>" class="tsisip-btn tsisip-btn--sm"><?php echo _('Edit'); ?></a>
                                <form method="post" style="display:inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $i['id']; ?>">
                                    <button type="submit" class="tsisip-btn tsisip-btn--danger tsisip-btn--sm"
                                            onclick="return confirm('<?php echo _('Delete this instance?'); ?>')">
                                        <?php echo _('Delete'); ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php renderPagination($page, $totalPages, $totalRows, $perPage, 'keepalived.php', ['search' => $search]); ?>
    </section>

    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo $editRow ? _('Edit Keepalived Instance') : _('Add Keepalived Instance'); ?></h2>
        <form method="post" class="tsisip-form">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="action" value="<?php echo $editRow ? 'update' : 'create'; ?>">
            <?php if ($editRow): ?><input type="hidden" name="id" value="<?php echo $editRow['id']; ?>"><?php endif; ?>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('VRRP ID'); ?></label>
                <input type="number" name="vrrp_id" required min="1" value="<?php echo $editRow ? intval($editRow['vrrp_id']) : '1'; ?>" class="tsisip-input">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('State'); ?></label>
                <select name="state" class="tsisip-select">
                    <option value="master" <?php echo $editRow && $editRow['state'] === 'master' ? 'selected' : ''; ?>><?php echo _('Master'); ?></option>
                    <option value="backup" <?php echo !$editRow || $editRow['state'] === 'backup' ? 'selected' : ''; ?>><?php echo _('Backup'); ?></option>
                </select>
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Priority'); ?></label>
                <input type="number" name="priority" min="1" max="255" value="<?php echo $editRow ? intval($editRow['priority']) : '100'; ?>" class="tsisip-input">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Interface'); ?></label>
                <input type="text" name="interface" required value="<?php echo $editRow ? htmlspecialchars($editRow['interface']) : 'eth0'; ?>" class="tsisip-input">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Virtual IP'); ?></label>
                <input type="text" name="virtual_ip" required value="<?php echo $editRow ? htmlspecialchars($editRow['virtual_ip']) : ''; ?>" class="tsisip-input" placeholder="10.0.0.100">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-checkbox">
                    <input type="checkbox" name="enabled" <?php echo (!$editRow || $editRow['enabled']) ? 'checked' : ''; ?>>
                    <?php echo _('Enabled'); ?>
                </label>
            </div>
            <button type="submit" class="tsisip-btn tsisip-btn--primary"><?php echo $editRow ? _('Update') : _('Create'); ?></button>
            <?php if ($editRow): ?><a href="keepalived.php" class="tsisip-btn tsisip-btn--secondary"><?php echo _('Cancel'); ?></a><?php endif; ?>
        </form>
    </section>
</div>

<?php require_once __DIR__ . '/common/footer.php'; ?>
