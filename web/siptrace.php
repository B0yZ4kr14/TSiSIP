<?php
/**
 * TSiSIP Control Panel — SIPtrace
 * SIP packet capture viewer (siptrace module)
 */

require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/pagination.php';
require_once __DIR__ . '/common/mi-http.php';

requireAuth();
checkPasswordChange();

$pdo = getDb();
$error = '';
$success = '';

// Admin-only purge
$canPurge = ($userRole === 'admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canPurge) {
    $action = $_POST['action'] ?? '';
    if ($action === 'purge') {
        $days = intval($_POST['days'] ?? 30);
        try {
            $stmt = $pdo->prepare('DELETE FROM sip_trace WHERE time_stamp < NOW() - INTERVAL \'? days\'');
            $stmt->execute([$days]);
            $deleted = $stmt->rowCount();
            $success = sprintf(_('Purged %d trace records older than %d days.'), $deleted, $days);
            logAuditEvent('SIPTRACE_PURGE', 'siptrace', "days=$days", true, ['deleted' => $deleted]);
        } catch (PDOException $e) {
            $error = _('Database error: ') . $e->getMessage();
        }
    }
}

// --- Fetch list ---
$methodFilter = $_GET['method'] ?? '';
$page   = max(1, intval($_GET['page'] ?? 1));
$callId = trim($_GET['callid'] ?? '');
$from   = trim($_GET['from'] ?? '');
$to     = trim($_GET['to'] ?? '');
$perPage = 25;

$where = [];
$params = [];
if ($callId !== '') {
    $where[] = 'callid ILIKE ?';
    $params[] = "%$callId%";
}
if ($from !== '') {
    $where[] = '(traced_user ILIKE ? OR fromip ILIKE ?)';
    $params[] = "%$from%";
    $params[] = "%$from%";
}
if ($to !== '') {
    $where[] = '(toip ILIKE ?)';
    $params[] = "%$to%";
}
if ($methodFilter !== '') {
    $where[] = 'method = ?';
    $params[] = $methodFilter;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM sip_trace $whereSql");
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));

$offset = ($page - 1) * $perPage;
$stmt = $pdo->prepare("SELECT * FROM sip_trace $whereSql ORDER BY time_stamp DESC LIMIT ? OFFSET ?");
$stmt->execute(array_merge($params, [$perPage, $offset]));
$traces = $stmt->fetchAll(PDO::FETCH_ASSOC);

$csrfToken = generateCsrfToken();

// Fetch live siptrace status for toggle button state
$miTrace = miHttpCall('sip_trace_status');
$traceStatus = $miTrace['data'] ?? [];

require_once __DIR__ . '/common/header.php';
?>

<div class="tsisip-page">
    <header class="tsisip-page-header" style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:8px;">
        <div>
            <h1 class="tsisip-page-title"><?php echo _('SIPtrace'); ?></h1>
            <p class="tsisip-page-subtitle"><?php echo _('SIP packet capture viewer'); ?></p>
        </div>
        <?php if (isDevOpsOrHigher()): ?>
            <?php
            $traceOn = false;
            if ($miTrace['success'] && is_array($traceStatus)) {
                $traceOn = ($traceStatus['status'] ?? $traceStatus['tracing'] ?? $traceStatus['state'] ?? '') === 'on' || ($traceStatus['status'] ?? '') === '1' || ($traceStatus['tracing'] ?? '') === '1';
            }
            ?>
            <button id="btn-siptrace-toggle" class="tsisip-btn <?php echo $traceOn ? 'tsisip-btn--danger' : 'tsisip-btn--success'; ?>"
                    data-state="<?php echo $traceOn ? 'on' : 'off'; ?>">
                <?php echo $traceOn ? _('Stop Capture') : _('Start Capture'); ?>
            </button>
        <?php endif; ?>
    </header>

    <?php if ($error): ?>
        <div class="tsisip-alert tsisip-alert--error" role="alert"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Real-time siptrace status via MI HTTP -->
    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo _('Live SIPtrace Status'); ?></h2>
        <?php if (!$miTrace['success']): ?>
            <div class="tsisip-badge tsisip-badge--warning" role="alert">
                <?php echo _('MI unavailable: ') . htmlspecialchars($miTrace['error']); ?>
            </div>
        <?php else:
            $traceStatus = $miTrace['data'] ?? [];
        ?>
            <table class="tsisip-table">
                <thead>
                    <tr><th><?php echo _('Attribute'); ?></th><th><?php echo _('Value'); ?></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($traceStatus as $key => $val): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($key); ?></td>
                            <td><code><?php echo htmlspecialchars(is_array($val) ? json_encode($val) : $val); ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($traceStatus)): ?>
                        <tr><td colspan="2" class="tsisip-empty"><?php echo _('No live status data.'); ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
    <?php if ($success): ?>
        <div class="tsisip-alert tsisip-alert--success" role="alert"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <section class="tsisip-section">
        <form method="get" class="tsisip-filter-bar">
            <input type="text" name="callid" placeholder="<?php echo _('Call-ID...'); ?>"
                   value="<?php echo htmlspecialchars($callId); ?>" class="tsisip-input">
            <input type="text" name="from" placeholder="<?php echo _('From / Source IP...'); ?>"
                   value="<?php echo htmlspecialchars($from); ?>" class="tsisip-input">
            <input type="text" name="to" placeholder="<?php echo _('To IP...'); ?>"
                   value="<?php echo htmlspecialchars($to); ?>" class="tsisip-input">
            <select name="method" class="tsisip-select">
                <option value=""><?php echo _('All Methods'); ?></option>
                <option value="INVITE" <?php echo $methodFilter === 'INVITE' ? 'selected' : ''; ?>>INVITE</option>
                <option value="REGISTER" <?php echo $methodFilter === 'REGISTER' ? 'selected' : ''; ?>>REGISTER</option>
                <option value="BYE" <?php echo $methodFilter === 'BYE' ? 'selected' : ''; ?>>BYE</option>
                <option value="OPTIONS" <?php echo $methodFilter === 'OPTIONS' ? 'selected' : ''; ?>>OPTIONS</option>
                <option value="ACK" <?php echo $methodFilter === 'ACK' ? 'selected' : ''; ?>>ACK</option>
                <option value="CANCEL" <?php echo $methodFilter === 'CANCEL' ? 'selected' : ''; ?>>CANCEL</option>
            </select>
            <button type="submit" class="tsisip-btn tsisip-btn--primary"><?php echo _('Filter'); ?></button>
            <a href="siptrace.php" class="tsisip-btn tsisip-btn--secondary"><?php echo _('Clear'); ?></a>
        </form>
    </section>

    <?php if ($canPurge): ?>
    <section class="tsisip-section">
        <form method="post" class="tsisip-filter-bar">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="action" value="purge">
            <label class="tsisip-label"><?php echo _('Purge records older than'); ?></label>
            <input type="number" name="days" value="30" class="tsisip-input" style="width:80px" min="1">
            <span><?php echo _('days'); ?></span>
            <button type="submit" class="tsisip-btn tsisip-btn--danger"
                    onclick="return confirm('<?php echo _('Permanently delete old traces?'); ?>')">
                <?php echo _('Purge'); ?>
            </button>
        </form>
    </section>
    <?php endif; ?>

    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo _('Trace Records'); ?></h2>
        <table class="tsisip-table">
            <thead>
                <tr>
                    <th><?php echo _('Time'); ?></th>
                    <th><?php echo _('Call-ID'); ?></th>
                    <th><?php echo _('Method'); ?></th>
                    <th><?php echo _('Status'); ?></th>
                    <th><?php echo _('From IP'); ?></th>
                    <th><?php echo _('To IP'); ?></th>
                    <th><?php echo _('Direction'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($traces)): ?>
                    <tr><td colspan="7" class="tsisip-empty"><?php echo _('No trace records found.'); ?></td></tr>
                <?php else: ?>
                    <?php foreach ($traces as $t): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($t['time_stamp']); ?></td>
                            <td><code class="tsisip-code--sm"><?php echo htmlspecialchars(substr($t['callid'], 0, 32)); ?>...</code></td>
                            <td><?php echo htmlspecialchars($t['method'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($t['status'] ?? ''); ?></td>
                            <td><code><?php echo htmlspecialchars($t['fromip'] ?? ''); ?></code></td>
                            <td><code><?php echo htmlspecialchars($t['toip'] ?? ''); ?></code></td>
                            <td><?php echo htmlspecialchars(strtoupper($t['direction'] ?? '')); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php renderPagination($page, $totalPages, $totalRows, $perPage, 'siptrace.php', ['callid' => $callId, 'from' => $from, 'to' => $to, 'method' => $methodFilter]); ?>
    </section>
</div>

<script>
<?php if (isDevOpsOrHigher()): ?>
(function() {
    var btn = document.getElementById('btn-siptrace-toggle');
    if (btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var isOn = btn.dataset.state === 'on';
            var cmd = isOn ? 'sip_trace_stop' : 'sip_trace_start';
            btn.disabled = true;
            TSiSIPMi.action(cmd, [], function() {
                btn.disabled = false;
                btn.dataset.state = isOn ? 'off' : 'on';
                btn.textContent = isOn ? <?php echo json_encode(_('Start Capture')); ?> : <?php echo json_encode(_('Stop Capture')); ?>;
                btn.classList.toggle('tsisip-btn--danger', !isOn);
                btn.classList.toggle('tsisip-btn--success', isOn);
            }, function() {
                btn.disabled = false;
            });
        });
    }
})();
<?php endif; ?>
</script>
<?php require_once __DIR__ . '/common/footer.php'; ?>
