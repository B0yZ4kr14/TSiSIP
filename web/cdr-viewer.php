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
$fromUser = trim($_GET['from_user'] ?? '');
$callStatus = trim($_GET['call_status'] ?? '');
$tenantId = trim($_GET['tenant_id'] ?? '');

$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 25;
$pagination = getPagination($page, $perPage);

// --- Fetch tenants for filter dropdown ---
$tenants = [];
try {
    $tenants = $pdo->query("SELECT id, name FROM tenants ORDER BY name")->fetchAll();
} catch (PDOException $e) {
    // tenants table may not exist in older schemas
}

// --- Build WHERE clause ---
$where = ["call_start >= :from_date AND call_start < :to_date_plus_one"];
$params = [
    ':from_date'        => $fromDate . ' 00:00:00',
    ':to_date_plus_one' => date('Y-m-d', strtotime($toDate . ' +1 day')) . ' 00:00:00',
];

if ($sipCode !== '') {
    $where[] = "sip_method = :sip_code";
    $params[':sip_code'] = $sipCode;
}
if ($fromUser !== '') {
    $where[] = "from_user ILIKE :from_user";
    $params[':from_user'] = '%' . $fromUser . '%';
}
if ($callStatus !== '') {
    $where[] = "call_status = :call_status";
    $params[':call_status'] = $callStatus;
}
if ($tenantId !== '') {
    $where[] = "tenant_id = :tenant_id";
    $params[':tenant_id'] = $tenantId;
}

$whereSql = implode(' AND ', $where);

// --- Count ---
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM cdr WHERE $whereSql");
$countStmt->execute($params);
$totalItems = (int) $countStmt->fetchColumn();

// --- Fetch ---
$listStmt = $pdo->prepare(
    "SELECT id, call_start, call_end, duration, from_user, to_user, sip_method, call_status, setup_time_ms, tenant_id, created_at
     FROM cdr
     WHERE $whereSql
     ORDER BY call_start DESC
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
                <label for="from_user"><?php echo _('From User'); ?></label>
                <input type="text" id="from_user" name="from_user" class="tsisip-input" value="<?php echo htmlspecialchars($fromUser); ?>" placeholder="user@domain" style="width:18ch">
            </div>
            <div class="tsisip-form-group">
                <label for="call_status"><?php echo _('Call Status'); ?></label>
                <select id="call_status" name="call_status" class="tsisip-input">
                    <option value=""><?php echo _('All'); ?></option>
                    <option value="answered" <?php echo $callStatus === 'answered' ? 'selected' : ''; ?>><?php echo _('Answered'); ?></option>
                    <option value="no_answer" <?php echo $callStatus === 'no_answer' ? 'selected' : ''; ?>><?php echo _('No Answer'); ?></option>
                    <option value="busy" <?php echo $callStatus === 'busy' ? 'selected' : ''; ?>><?php echo _('Busy'); ?></option>
                    <option value="failed" <?php echo $callStatus === 'failed' ? 'selected' : ''; ?>><?php echo _('Failed'); ?></option>
                    <option value="unknown" <?php echo $callStatus === 'unknown' ? 'selected' : ''; ?>><?php echo _('Unknown'); ?></option>
                </select>
            </div>
            <div class="tsisip-form-group">
                <label for="tenant_id"><?php echo _('Tenant'); ?></label>
                <select id="tenant_id" name="tenant_id" class="tsisip-input">
                    <option value=""><?php echo _('All'); ?></option>
                    <?php foreach ($tenants as $t): ?>
                        <option value="<?php echo htmlspecialchars($t['id'], ENT_QUOTES); ?>" <?php echo $tenantId === $t['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($t['name'], ENT_QUOTES); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="tsisip-form-group">
                <label for="sip_code"><?php echo _('SIP Method'); ?></label>
                <input type="text" id="sip_code" name="sip_code" class="tsisip-input" value="<?php echo htmlspecialchars($sipCode); ?>" placeholder="INVITE, BYE..." maxlength="16" style="width:12ch">
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
                    <th><?php echo _('From'); ?></th>
                    <th><?php echo _('To'); ?></th>
                    <th><?php echo _('Method'); ?></th>
                    <th><?php echo _('Status'); ?></th>
                    <th><?php echo _('Setup (ms)'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cdrs as $cdr): ?>
                <tr>
                    <td><?php echo htmlspecialchars($cdr['call_start']); ?></td>
                    <td><?php echo htmlspecialchars($cdr['call_end'] ?? ''); ?></td>
                    <td><?php echo number_format($cdr['duration'] ?? 0, 1); ?>s</td>
                    <td><?php echo htmlspecialchars(($cdr['from_user'] ?? '') . '@' . ($cdr['to_user'] ?? '')); ?></td>
                    <td><?php echo htmlspecialchars($cdr['to_user'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($cdr['sip_method'] ?? ''); ?></td>
                    <td>
                        <?php
                        $status = $cdr['call_status'] ?? 'unknown';
                        $badgeClass = 'tsisip-badge';
                        if ($status === 'answered') {
                            $badgeClass = 'tsisip-badge tsisip-badge-success';
                        } elseif (in_array($status, ['failed', 'busy'])) {
                            $badgeClass = 'tsisip-badge tsisip-badge-error';
                        } elseif ($status === 'no_answer') {
                            $badgeClass = 'tsisip-badge tsisip-badge-warning';
                        }
                        ?>
                        <span class="<?php echo $badgeClass; ?>"><?php echo htmlspecialchars($status); ?></span>
                    </td>
                    <td><?php echo number_format($cdr['setup_time_ms'] ?? 0, 0); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($cdrs)): ?>
                <tr>
                    <td colspan="8" class="tsisip-text-center tsisip-text-muted"><?php echo _('No records found.'); ?></td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php echo renderPagination($page, $totalItems, $perPage, 'cdr-viewer.php?' . http_build_query(['from' => $fromDate, 'to' => $toDate, 'sip_code' => $sipCode, 'from_user' => $fromUser, 'call_status' => $callStatus, 'tenant_id' => $tenantId])); ?>
    </div>
</div>
<?php require_once __DIR__ . '/common/footer.php'; ?>
