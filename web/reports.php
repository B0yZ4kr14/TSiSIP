<?php
/**
 * TSiSIP Control Panel — System Reports
 * Aggregate statistics and trend reports.
 */
require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';

requireAuth();
checkPasswordChange();
requireRole('devops');

logAuditEvent('CONFIG_VIEW', 'system', 'reports', true);

$pdo = getDb();

// Time range
$range = $_GET['range'] ?? '24h';
$interval = match($range) {
    '1h' => '1 hour',
    '24h' => '24 hours',
    '7d' => '7 days',
    '30d' => '30 days',
    default => '24 hours',
};

// Login attempts over time
$loginStmt = $pdo->prepare(
    "SELECT DATE_TRUNC('hour', event_time) AS hour,
            COUNT(*) FILTER (WHERE action = 'LOGIN_SUCCESS') AS success,
            COUNT(*) FILTER (WHERE action = 'LOGIN_FAILURE') AS failure
     FROM ocp_audit_log
     WHERE event_time > NOW() - INTERVAL '{$interval}'
     GROUP BY hour
     ORDER BY hour"
);
$loginStmt->execute();
$loginTrends = $loginStmt->fetchAll();

// Most active users
$activeStmt = $pdo->prepare(
    "SELECT username, COUNT(*) AS events
     FROM ocp_audit_log
     WHERE event_time > NOW() - INTERVAL '{$interval}'
     GROUP BY username
     ORDER BY events DESC
     LIMIT 10"
);
$activeStmt->execute();
$activeUsers = $activeStmt->fetchAll();

// Action distribution
$actionStmt = $pdo->prepare(
    "SELECT action, COUNT(*) AS cnt
     FROM ocp_audit_log
     WHERE event_time > NOW() - INTERVAL '{$interval}'
     GROUP BY action
     ORDER BY cnt DESC
     LIMIT 10"
);
$actionStmt->execute();
$actionDist = $actionStmt->fetchAll();

// Dialog stats (current)
$dlgStmt = $pdo->query("SELECT COUNT(*) AS total FROM dialog");
$dlgTotal = $dlgStmt->fetch()['total'] ?? 0;

// Subscriber growth
$subStmt = $pdo->prepare(
    "SELECT DATE(datetime_created) AS date, COUNT(*) AS cnt
     FROM subscriber
     WHERE datetime_created > NOW() - INTERVAL '{$interval}'
     GROUP BY DATE(datetime_created)
     ORDER BY date"
);
$subStmt->execute();
$subGrowth = $subStmt->fetchAll();

require_once __DIR__ . '/common/header.php';
?>
<div id="content" class="tsisip-dashboard">
    <h1><?php echo _('System Reports'); ?></h1>

    <!-- Time Range Selector -->
    <div class="tsisip-dashboard-section">
        <div class="tsisip-btn-group">
            <?php foreach (['1h' => 'Last Hour', '24h' => 'Last 24 Hours', '7d' => 'Last 7 Days', '30d' => 'Last 30 Days'] as $val => $label): ?>
                <a href="?range=<?php echo $val; ?>"
                   class="tsisip-btn <?php echo $range === $val ? 'tsisip-btn-primary' : 'tsisip-btn-outline'; ?>">
                    <?php echo _($label); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="tsisip-dashboard-grid" style="grid-template-columns:repeat(4, 1fr);">
        <div class="tsisip-dashboard-card">
            <div class="tsisip-metric-value"><?php echo $dlgTotal; ?></div>
            <div class="tsisip-metric-label"><?php echo _('Active Dialogs'); ?></div>
        </div>
        <div class="tsisip-dashboard-card">
            <div class="tsisip-metric-value"><?php echo count($loginTrends); ?></div>
            <div class="tsisip-metric-label"><?php echo _('Hours with Activity'); ?></div>
        </div>
        <div class="tsisip-dashboard-card">
            <div class="tsisip-metric-value"><?php echo array_sum(array_column($loginTrends, 'success')); ?></div>
            <div class="tsisip-metric-label"><?php echo _('Successful Logins'); ?></div>
        </div>
        <div class="tsisip-dashboard-card">
            <div class="tsisip-metric-value" style="color:var(--tsisip-danger);">
                <?php echo array_sum(array_column($loginTrends, 'failure')); ?>
            </div>
            <div class="tsisip-metric-label"><?php echo _('Failed Logins'); ?></div>
        </div>
    </div>

    <!-- Active Users -->
    <div class="tsisip-dashboard-section">
        <h2 class="tsisip-section-title"><?php echo _('Most Active Users'); ?></h2>
        <table class="tsisip-table dataTable">
            <thead>
                <tr><th><?php echo _('User'); ?></th><th><?php echo _('Events'); ?></th></tr>
            </thead>
            <tbody>
                <?php foreach ($activeUsers as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                        <td><?php echo (int)$row['events']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Action Distribution -->
    <div class="tsisip-dashboard-section">
        <h2 class="tsisip-section-title"><?php echo _('Action Distribution'); ?></h2>
        <table class="tsisip-table dataTable">
            <thead>
                <tr><th><?php echo _('Action'); ?></th><th><?php echo _('Count'); ?></th></tr>
            </thead>
            <tbody>
                <?php foreach ($actionDist as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['action']); ?></td>
                        <td><?php echo (int)$row['cnt']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/common/footer.php'; ?>
