<?php
/**
 * TSiSIP Control Panel — CDR Viewer
 * Read-only filtered query interface for Call Detail Records.
 */

require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/pagination.php';

requireAuth();
checkPasswordChange();
requireRole('devops');

$pdo = getDb();

// --- Filters ---
$fromDate = $_GET['from'] ?? date('Y-m-d', strtotime('-7 days'));
$toDate   = $_GET['to']   ?? date('Y-m-d');
$sipCode  = trim($_GET['sip_code'] ?? '');

$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 25;
$pagination = getPagination($page, $perPage);

// --- Build WHERE clause ---
$where = ["start_time >= :from_date AND start_time < :to_date_plus_one"];
$params = [
    ':from_date'        => $fromDate . ' 00:00:00',
    ':to_date_plus_one' => date('Y-m-d', strtotime($toDate . ' +1 day')) . ' 00:00:00',
];

if ($sipCode !== '') {
    $where[] = "sip_code = :sip_code";
    $params[':sip_code'] = $sipCode;
}

$whereSql = implode(' AND ', $where);

// --- Count ---
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM cdr WHERE $whereSql");
$countStmt->execute($params);
$totalItems = (int) $countStmt->fetchColumn();

// --- Fetch ---
$listStmt = $pdo->prepare(
    "SELECT id, start_time, end_time, duration, sip_code, sip_reason, setuptime, created
     FROM cdr
     WHERE $whereSql
     ORDER BY start_time DESC
     LIMIT :limit OFFSET :offset"
);
foreach ($params as $k => $v) {
    $listStmt->bindValue($k, $v);
}
$listStmt->bindValue(':limit', $pagination['limit'], PDO::PARAM_INT);
$listStmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
$listStmt->execute();
$cdrs = $listStmt->fetchAll();

require_once __DIR__ . '/common/header.php';
?>
<div id="content">
    <h2><?php echo _('Call Detail Records'); ?></h2>

    <!-- Filters -->
    <div class="tsisip-dashboard-section">
        <form method="GET" action="" class="tsisip-form" style="display:flex;flex-wrap:wrap;gap:1rem;align-items:flex-end">
            <div class="tsisip-form-group">
                <label for="from"><?php echo _('From'); ?></label>
                <input type="date" id="from" name="from" class="tsisip-input" value="<?php echo htmlspecialchars($fromDate); ?>">
            </div>
            <div class="tsisip-form-group">
                <label for="to"><?php echo _('To'); ?></label>
                <input type="date" id="to" name="to" class="tsisip-input" value="<?php echo htmlspecialchars($toDate); ?>">
            </div>
            <div class="tsisip-form-group">
                <label for="sip_code"><?php echo _('SIP Code'); ?></label>
                <input type="text" id="sip_code" name="sip_code" class="tsisip-input" value="<?php echo htmlspecialchars($sipCode); ?>" placeholder="200, 404, 486..." maxlength="3" style="width:8ch">
            </div>
            <button type="submit" class="tsisip-btn tsisip-btn-primary"><?php echo _('Filter'); ?></button>
        </form>
    </div>

    <!-- Results -->
    <div class="tsisip-dashboard-section">
        <h3><?php echo _('Records'); ?> (<?php echo $totalItems; ?>)</h3>
        <table class="dataTable tsisip-table">
            <thead>
                <tr>
                    <th><?php echo _('Start Time'); ?></th>
                    <th><?php echo _('End Time'); ?></th>
                    <th><?php echo _('Duration'); ?></th>
                    <th><?php echo _('Setup Time'); ?></th>
                    <th><?php echo _('SIP Code'); ?></th>
                    <th><?php echo _('SIP Reason'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cdrs as $cdr): ?>
                <tr>
                    <td><?php echo htmlspecialchars($cdr['start_time']); ?></td>
                    <td><?php echo htmlspecialchars($cdr['end_time']); ?></td>
                    <td><?php echo number_format($cdr['duration'] ?? 0, 1); ?>s</td>
                    <td><?php echo number_format($cdr['setuptime'] ?? 0, 3); ?>s</td>
                    <td>
                        <?php
                        $code = $cdr['sip_code'] ?? '';
                        $badgeClass = 'tsisip-badge';
                        if ($code === '200') {
                            $badgeClass = 'tsisip-badge tsisip-badge-success';
                        } elseif (in_array($code, ['404', '486', '487', '480', '408'])) {
                            $badgeClass = 'tsisip-badge tsisip-badge-error';
                        }
                        ?>
                        <span class="<?php echo $badgeClass; ?>"><?php echo htmlspecialchars($code); ?></span>
                    </td>
                    <td><?php echo htmlspecialchars($cdr['sip_reason']); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($cdrs)): ?>
                <tr>
                    <td colspan="6" class="tsisip-text-center tsisip-text-muted"><?php echo _('No records found.'); ?></td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php echo renderPagination($page, $totalItems, $perPage, 'cdr-viewer.php?' . http_build_query(['from' => $fromDate, 'to' => $toDate, 'sip_code' => $sipCode])); ?>
    </div>
</div>
<?php require_once __DIR__ . '/common/footer.php'; ?>
