<?php
/**
 * TSiSIP Control Panel — Load Balancer
 * Alternative load balancing to dispatcher (load_balancer module)
 */

require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';
require_once __DIR__ . '/common/pagination.php';
require_once __DIR__ . '/common/mi-http.php';

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
            $groupId  = intval($_POST['group_id'] ?? 0);
            $dstUri   = trim($_POST['dst_uri'] ?? '');
            $resources= trim($_POST['resources'] ?? '');
            $probeMode= intval($_POST['probe_mode'] ?? 0);
            $description = trim($_POST['description'] ?? '');

            if ($groupId < 1 || $dstUri === '' || $resources === '') {
                $error = _('Group ID, destination URI, and resources are required.');
            } else {
                if ($action === 'create') {
                    try {
                        $stmt = $pdo->prepare(
                            'INSERT INTO load_balancer (group_id, dst_uri, resources, probe_mode, description) VALUES (?, ?, ?, ?, ?)'
                        );
                        $stmt->execute([$groupId, $dstUri, $resources, $probeMode, $description]);
                        $success = _('Load balancer destination created successfully.');
                        logAuditEvent('LB_CREATE', 'load_balancer', $dstUri, true, ['group' => $groupId]);
                    } catch (PDOException $e) {
                        $error = _('Database error: ') . $e->getMessage();
                    }
                } else {
                    $id = $_POST['id'] ?? '';
                    try {
                        $stmt = $pdo->prepare(
                            'UPDATE load_balancer SET group_id=?, dst_uri=?, resources=?, probe_mode=?, description=?, last_modified=NOW() WHERE id=?'
                        );
                        $stmt->execute([$groupId, $dstUri, $resources, $probeMode, $description, $id]);
                        $success = _('Load balancer destination updated successfully.');
                        logAuditEvent('LB_UPDATE', 'load_balancer', $dstUri, true, ['group' => $groupId]);
                    } catch (PDOException $e) {
                        $error = _('Database error: ') . $e->getMessage();
                    }
                }
            }
        } elseif ($action === 'delete') {
            $id = $_POST['id'] ?? '';
            try {
                $stmt = $pdo->prepare('SELECT dst_uri FROM load_balancer WHERE id=?');
                $stmt->execute([$id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $stmt = $pdo->prepare('DELETE FROM load_balancer WHERE id=?');
                    $stmt->execute([$id]);
                    $success = _('Load balancer destination deleted successfully.');
                    logAuditEvent('LB_DELETE', 'load_balancer', $row['dst_uri'], true);
                }
            } catch (PDOException $e) {
                $error = _('Database error: ') . $e->getMessage();
            }
        } elseif ($action === 'toggle') {
            $id = $_POST['id'] ?? '';
            try {
                $stmt = $pdo->prepare('SELECT probe_mode FROM load_balancer WHERE id=?');
                $stmt->execute([$id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $newMode = $row['probe_mode'] ? 0 : 1;
                    $stmt = $pdo->prepare('UPDATE load_balancer SET probe_mode=? WHERE id=?');
                    $stmt->execute([$newMode, $id]);
                    $success = $newMode ? _('Probing enabled.') : _('Probing disabled.');
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
    $where[] = '(dst_uri ILIKE ? OR resources ILIKE ? OR description ILIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM load_balancer $whereSql");
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));

$offset = ($page - 1) * $perPage;
$stmt = $pdo->prepare("SELECT * FROM load_balancer $whereSql ORDER BY group_id, dst_uri LIMIT ? OFFSET ?");
$stmt->execute(array_merge($params, [$perPage, $offset]));
$entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

$csrfToken = generateCsrfToken();
require_once __DIR__ . '/common/header.php';
?>

<div class="tsisip-page">
    <header class="tsisip-page-header">
        <h1 class="tsisip-page-title"><?php echo _('Load Balancer'); ?></h1>
        <p class="tsisip-page-subtitle"><?php echo _('Alternative load balancing destinations'); ?></p>
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
            <a href="load-balancer.php" class="tsisip-btn tsisip-btn--secondary"><?php echo _('Clear'); ?></a>
        </form>
    </section>

    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo _('Destinations'); ?></h2>
        <table class="tsisip-table">
            <thead>
                <tr>
                    <th><?php echo _('Group'); ?></th>
                    <th><?php echo _('Destination URI'); ?></th>
                    <th><?php echo _('Resources'); ?></th>
                    <th><?php echo _('Probe'); ?></th>
                    <th><?php echo _('Description'); ?></th>
                    <th><?php echo _('Actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($entries)): ?>
                    <tr><td colspan="6" class="tsisip-empty"><?php echo _('No destinations found.'); ?></td></tr>
                <?php else: ?>
                    <?php foreach ($entries as $e): ?>
                        <tr>
                            <td><?php echo $e['group_id']; ?></td>
                            <td><code><?php echo htmlspecialchars($e['dst_uri']); ?></code></td>
                            <td><?php echo htmlspecialchars($e['resources']); ?></td>
                            <td>
                                <span class="tsisip-badge tsisip-badge--<?php echo $e['probe_mode'] ? 'success' : 'neutral'; ?>">
                                    <?php echo $e['probe_mode'] ? _('On') : _('Off'); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($e['description'] ?? ''); ?></td>
                            <td>
                                <form method="post" style="display:inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?php echo $e['id']; ?>">
                                    <button type="submit" class="tsisip-btn tsisip-btn--secondary tsisip-btn--sm">
                                        <?php echo $e['probe_mode'] ? _('Disable') : _('Enable'); ?>
                                    </button>
                                </form>
                                <form method="post" style="display:inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $e['id']; ?>">
                                    <button type="submit" class="tsisip-btn tsisip-btn--danger tsisip-btn--sm"
                                            onclick="return confirm('<?php echo _('Delete?'); ?>')">
                                        <?php echo _('Delete'); ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php renderPagination($page, $totalPages, $totalRows, $perPage, 'load-balancer.php', ['search' => $search]); ?>
    </section>

    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo _('Add Destination'); ?></h2>
        <form method="post" class="tsisip-form">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="action" value="create">
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Group ID'); ?></label>
                <input type="number" name="group_id" required class="tsisip-input" min="1">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Destination URI'); ?></label>
                <input type="text" name="dst_uri" required class="tsisip-input" placeholder="sip:backend.example.com:5060">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Resources'); ?></label>
                <input type="text" name="resources" required class="tsisip-input" placeholder="pstn=32; audio=100">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Probe Mode'); ?></label>
                <select name="probe_mode" class="tsisip-select">
                    <option value="0"><?php echo _('Disabled'); ?></option>
                    <option value="1"><?php echo _('Enabled'); ?></option>
                </select>
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Description'); ?></label>
                <input type="text" name="description" class="tsisip-input">
            </div>
            <button type="submit" class="tsisip-btn tsisip-btn--primary"><?php echo _('Create'); ?></button>
        </form>
    </section>
</div>

<?php require_once __DIR__ . '/common/footer.php'; ?>
