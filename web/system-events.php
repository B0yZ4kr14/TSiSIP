<?php
require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';

requireAuth();
checkPasswordChange();
requireRole('admin');

logAuditEvent('CONFIG_VIEW', 'system', 'system-events', true);

$pageTitle = _('System Events');

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 50;
$offset = ($page - 1) * $perPage;

// Filter
$filterAction = $_GET['action'] ?? '';
$filterUser = $_GET['user'] ?? '';

$events = [];
$totalCount = 0;

try {
    $pdo = getDb();
    
    $where = [];
    $params = [];
    if ($filterAction !== '') {
        $where[] = "action = :action";
        $params[':action'] = $filterAction;
    }
    if ($filterUser !== '') {
        $where[] = "username ILIKE :user";
        $params[':user'] = '%' . $filterUser . '%';
    }
    
    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM ocp_audit_log $whereSql");
    $countStmt->execute($params);
    $totalCount = (int)$countStmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT * FROM ocp_audit_log $whereSql ORDER BY id DESC LIMIT :limit OFFSET :offset");
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $events = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('TSiSIP system events query failed: ' . $e->getMessage());
}

// Get distinct actions for filter dropdown
$actions = [];
try {
    $pdo = getDb();
    $actions = $pdo->query("SELECT DISTINCT action FROM ocp_audit_log ORDER BY action LIMIT 100")->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    // ignore
}

$totalPages = (int)ceil($totalCount / $perPage);

require_once __DIR__ . '/common/header.php';
?>
<div class="tsisip-page">
    <div class="tsisip-page-header">
        <h1 class="tsisip-page-title"><?php echo $pageTitle; ?></h1>
    </div>

    <div class="tsisip-filter-bar" style="margin-bottom:16px;">
        <form method="GET" action="" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
            <?php echo csrfInput(); ?>
            <select name="action" class="tsisip-input">
                <option value=""><?php echo _('All Actions'); ?></option>
                <?php foreach ($actions as $act): ?>
                    <option value="<?php echo htmlspecialchars($act); ?>" <?php echo $filterAction === $act ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($act); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="user" class="tsisip-input" placeholder="<?php echo _('Filter by user...'); ?>" value="<?php echo htmlspecialchars($filterUser); ?>">
            <button type="submit" class="tsisip-btn tsisip-btn--primary"><?php echo _('Filter'); ?></button>
            <a href="system-events.php" class="tsisip-btn tsisip-btn--secondary"><?php echo _('Clear'); ?></a>
        </form>
    </div>

    <?php if (empty($events)): ?>
        <div class="tsisip-alert tsisip-alert--info"><?php echo _('No events found.'); ?></div>
    <?php else: ?>
        <div style="overflow-x:auto;">
            <table class="tsisip-table" data-tsisip-sortable>
                <thead>
                    <tr>
                        <th><?php echo _('ID'); ?></th>
                        <th><?php echo _('Time'); ?></th>
                        <th><?php echo _('User'); ?></th>
                        <th><?php echo _('Action'); ?></th>
                        <th><?php echo _('Resource'); ?></th>
                        <th><?php echo _('Success'); ?></th>
                        <th><?php echo _('IP'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($events as $evt): ?>
                        <tr>
                            <td><?php echo (int)$evt['id']; ?></td>
                            <td><?php echo htmlspecialchars(substr($evt['event_time'], 0, 19)); ?></td>
                            <td><?php echo htmlspecialchars($evt['username']); ?></td>
                            <td><span class="tsisip-badge tsisip-badge--<?php echo $evt['success'] ? 'success' : 'error'; ?>"><?php echo htmlspecialchars($evt['action']); ?></span></td>
                            <td><?php echo htmlspecialchars(($evt['resource_type'] ?? '') . ': ' . ($evt['resource_id'] ?? '')); ?></td>
                            <td><?php echo $evt['success'] ? _('Yes') : _('No'); ?></td>
                            <td><code><?php echo htmlspecialchars($evt['ip_address']); ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="tsisip-pagination" style="margin-top:16px;">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&action=<?php echo urlencode($filterAction); ?>&user=<?php echo urlencode($filterUser); ?>" class="tsisip-btn tsisip-btn--secondary">← <?php echo _('Previous'); ?></a>
                <?php endif; ?>
                <span style="margin:0 12px;"><?php echo _('Page'); ?> <?php echo $page; ?> / <?php echo $totalPages; ?> (<?php echo number_format($totalCount); ?> <?php echo _('total'); ?>)</span>
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&action=<?php echo urlencode($filterAction); ?>&user=<?php echo urlencode($filterUser); ?>" class="tsisip-btn tsisip-btn--secondary"><?php echo _('Next'); ?> →</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/common/footer.php'; ?>
