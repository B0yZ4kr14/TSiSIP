<?php
/**
 * TSiSIP Control Panel — Audit Log & Compliance Dashboard
 *
 * Searchable, filterable audit trail for admin and devops roles.
 */

require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/pagination.php';
require_once __DIR__ . '/common/export-text.php';

requireAuth();
checkPasswordChange();
requireRole('devops');

$pdo = getDb();

// --- Filter handling ---
$filters = [
    'from'          => $_GET['from']          ?? date('Y-m-d', strtotime('-7 days')),
    'to'            => $_GET['to']            ?? date('Y-m-d'),
    'action'        => trim($_GET['action']        ?? ''),
    'username'      => trim($_GET['username']      ?? ''),
    'resource_type' => trim($_GET['resource_type'] ?? ''),
    'success'       => $_GET['success']       ?? '',
    'ip_address'    => trim($_GET['ip_address']    ?? ''),
    'q'             => trim($_GET['q']             ?? ''),
];

$where = [];
$params = [];

// Date range (inclusive, index-friendly)
$fromTime = $filters['from'] . ' 00:00:00';
$toTime   = date('Y-m-d', strtotime($filters['to'] . ' +1 day')) . ' 00:00:00';
$where[] = 'event_time >= :from_time AND event_time < :to_time';
$params[':from_time'] = $fromTime;
$params[':to_time']   = $toTime;

if ($filters['action'] !== '') {
    $where[] = 'action = :action';
    $params[':action'] = $filters['action'];
}
if ($filters['username'] !== '') {
    $where[] = 'username ILIKE :username';
    $params[':username'] = '%' . $filters['username'] . '%';
}
if ($filters['resource_type'] !== '') {
    $where[] = 'resource_type = :resource_type';
    $params[':resource_type'] = $filters['resource_type'];
}
if ($filters['success'] !== '') {
    $where[] = 'success = :success';
    $params[':success'] = ($filters['success'] === '1');
}
if ($filters['ip_address'] !== '') {
    $where[] = 'ip_address::text ILIKE :ip_address';
    $params[':ip_address'] = '%' . $filters['ip_address'] . '%';
}
if ($filters['q'] !== '') {
    $where[] = 'details::text ILIKE :q';
    $params[':q'] = '%' . $filters['q'] . '%';
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// --- Pagination ---
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = max(1, min(200, intval($_GET['per_page'] ?? 50)));
$pagination = getPagination($page, $perPage);

// --- Count ---
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM ocp_audit_log $whereSql");
$countStmt->execute($params);
$totalItems = (int) $countStmt->fetchColumn();

// --- Fetch results ---
$listStmt = $pdo->prepare(
    "SELECT id, event_time, user_id, username, action, resource_type,
            resource_id, ip_address, user_agent, success, details, prev_hash, hash
     FROM ocp_audit_log
     $whereSql
     ORDER BY event_time DESC
     LIMIT :limit OFFSET :offset"
);
foreach ($params as $k => $v) {
    $listStmt->bindValue($k, $v);
}
$listStmt->bindValue(':limit', $pagination['limit'], PDO::PARAM_INT);
$listStmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
$listStmt->execute();
$rows = $listStmt->fetchAll();

// Canonical values for dropdowns
$canonicalActions = [
    'LOGIN',
    'LOGOUT',
    'PASSWORD_CHANGE',
    'SUBSCRIBER_CREATE',
    'SUBSCRIBER_UPDATE',
    'SUBSCRIBER_TOGGLE',
    'DISPATCHER_CREATE',
    'DISPATCHER_UPDATE',
    'DISPATCHER_DELETE',
    'DISPATCHER_TOGGLE',
    'CONFIG_VIEW',
    'EXPORT_CSV',
    'EXPORT_JSON',
    'RETENTION_RUN',
];

$canonicalResourceTypes = [
    'subscriber',
    'dispatcher',
    'ocp_user',
    'audit_log',
    'system',
];

// Build base URL for pagination and export links (preserve filters except page)
$baseQuery = array_diff_key($filters, ['page' => 1]);
$baseQuery = array_filter($baseQuery, fn($v) => $v !== '');

// Export URLs
$exportCsvQuery  = array_merge($baseQuery, ['format' => 'csv']);
$exportJsonQuery = array_merge($baseQuery, ['format' => 'json']);

require_once __DIR__ . '/common/header.php';
?>
<div id="content">
    <h2><?php echo _('Audit Log & Compliance'); ?></h2>

    <!-- Filter Form -->
    <div class="tsisip-dashboard-section">
        <form method="GET" action="audit-log.php" class="tsisip-form">
            <div style="display:flex;flex-wrap:wrap;gap:1rem;align-items:flex-end">
                <div class="tsisip-form-group">
                    <label for="from"><?php echo _('From'); ?></label>
                    <input type="date" id="from" name="from" class="tsisip-input"
                           value="<?php echo htmlspecialchars($filters['from'], ENT_QUOTES); ?>">
                </div>
                <div class="tsisip-form-group">
                    <label for="to"><?php echo _('To'); ?></label>
                    <input type="date" id="to" name="to" class="tsisip-input"
                           value="<?php echo htmlspecialchars($filters['to'], ENT_QUOTES); ?>">
                </div>
                <div class="tsisip-form-group">
                    <label for="action"><?php echo _('Action'); ?></label>
                    <select id="action" name="action" class="tsisip-input">
                        <option value=""><?php echo _('All'); ?></option>
                        <?php foreach ($canonicalActions as $a): ?>
                            <option value="<?php echo htmlspecialchars($a, ENT_QUOTES); ?>"
                                <?php echo $filters['action'] === $a ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($a); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="tsisip-form-group">
                    <label for="username"><?php echo _('User'); ?></label>
                    <input type="text" id="username" name="username" class="tsisip-input"
                           value="<?php echo htmlspecialchars($filters['username'], ENT_QUOTES); ?>"
                           placeholder="<?php echo _('Username'); ?>">
                </div>
                <div class="tsisip-form-group">
                    <label for="resource_type"><?php echo _('Resource Type'); ?></label>
                    <select id="resource_type" name="resource_type" class="tsisip-input">
                        <option value=""><?php echo _('All'); ?></option>
                        <?php foreach ($canonicalResourceTypes as $rt): ?>
                            <option value="<?php echo htmlspecialchars($rt, ENT_QUOTES); ?>"
                                <?php echo $filters['resource_type'] === $rt ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($rt); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="tsisip-form-group">
                    <label for="success"><?php echo _('Status'); ?></label>
                    <select id="success" name="success" class="tsisip-input">
                        <option value=""><?php echo _('All'); ?></option>
                        <option value="1" <?php echo $filters['success'] === '1' ? 'selected' : ''; ?>><?php echo _('Success'); ?></option>
                        <option value="0" <?php echo $filters['success'] === '0' ? 'selected' : ''; ?>><?php echo _('Failure'); ?></option>
                    </select>
                </div>
                <div class="tsisip-form-group">
                    <label for="ip_address"><?php echo _('IP Address'); ?></label>
                    <input type="text" id="ip_address" name="ip_address" class="tsisip-input"
                           value="<?php echo htmlspecialchars($filters['ip_address'], ENT_QUOTES); ?>"
                           placeholder="<?php echo _('IP'); ?>">
                </div>
                <div class="tsisip-form-group">
                    <label for="q"><?php echo _('Search Details'); ?></label>
                    <input type="text" id="q" name="q" class="tsisip-input"
                           value="<?php echo htmlspecialchars($filters['q'], ENT_QUOTES); ?>"
                           placeholder="<?php echo _('JSON text search'); ?>">
                </div>
                <div class="tsisip-form-group">
                    <button type="submit" class="tsisip-btn tsisip-btn-primary">
                        <?php echo _('Filter'); ?>
                    </button>
                    <a href="audit-log.php" class="tsisip-btn tsisip-btn-secondary">
                        <?php echo _('Reset'); ?>
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Export Toolbar -->
    <div class="tsisip-dashboard-section" style="display:flex;gap:0.5rem;align-items:center">
        <span class="tsisip-text-muted"><?php echo _('Export:'); ?></span>
        <a href="audit-export.php?<?php echo htmlspecialchars(http_build_query($exportCsvQuery), ENT_QUOTES); ?>"
           class="tsisip-btn tsisip-btn-secondary">
            <?php echo _('Export CSV'); ?>
        </a>
        <a href="audit-export.php?<?php echo htmlspecialchars(http_build_query($exportJsonQuery), ENT_QUOTES); ?>"
           class="tsisip-btn tsisip-btn-secondary">
            <?php echo _('Export JSON'); ?>
        </a>
        <?php
        $exportTextQuery = array_merge($baseQuery, ['format' => 'text']);
        ?>
        <a href="audit-export.php?<?php echo htmlspecialchars(http_build_query($exportTextQuery), ENT_QUOTES); ?>"
           class="tsisip-btn tsisip-btn-secondary">
            <?php echo _('Export Text'); ?>
        </a>
    </div>

    <!-- Results -->
    <div class="tsisip-dashboard-section">
        <?php if (empty($rows)): ?>
            <p class="tsisip-text-muted tsisip-text-center">
                <?php echo _('No audit events found.'); ?>
            </p>
        <?php else: ?>
            <table class="dataTable tsisip-table">
                <thead>
                    <tr>
                        <th><?php echo _('Event Time'); ?></th>
                        <th><?php echo _('User'); ?></th>
                        <th><?php echo _('Action'); ?></th>
                        <th><?php echo _('Resource'); ?></th>
                        <th><?php echo _('Resource ID'); ?></th>
                        <th><?php echo _('IP Address'); ?></th>
                        <th><?php echo _('Success'); ?></th>
                        <th><?php echo _('Details'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td>
                                <?php
                                $dt = new DateTime($row['event_time']);
                                echo htmlspecialchars($dt->format('Y-m-d H:i:s T'), ENT_QUOTES);
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                            <td><?php echo htmlspecialchars($row['action']); ?></td>
                            <td><?php echo htmlspecialchars($row['resource_type'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['resource_id'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['ip_address']); ?></td>
                            <td>
                                <?php if ($row['success']): ?>
                                    <span class="tsisip-badge tsisip-badge-success"><?php echo _('Success'); ?></span>
                                <?php else: ?>
                                    <span class="tsisip-badge tsisip-badge-error"><?php echo _('Failure'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($row['details'])): ?>
                                    <button type="button" class="tsisip-btn tsisip-btn-secondary"
                                            onclick="document.getElementById('details-<?php echo (int)$row['id']; ?>').style.display = document.getElementById('details-<?php echo (int)$row['id']; ?>').style.display === 'none' ? 'block' : 'none';">
                                        <?php echo _('Toggle'); ?>
                                    </button>
                                    <pre id="details-<?php echo (int)$row['id']; ?>" style="display:none;margin-top:0.5rem;max-width:400px;overflow:auto;background:#0f1c2e;padding:0.5rem;border-radius:4px;"><code><?php echo htmlspecialchars(json_encode(json_decode($row['details'], true), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)); ?></code></pre>
                                <?php else: ?>
                                    <span class="tsisip-text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php echo renderPagination($page, $totalItems, $perPage, 'audit-log.php'); ?>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/common/footer.php'; ?>
