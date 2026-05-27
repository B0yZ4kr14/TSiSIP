<?php
/**
 * TSiSIP Control Panel — Unified System Health Dashboard
 * Shows all component statuses in one view.
 */
require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';

requireAuth();
checkPasswordChange();
requireRole('devops');

logAuditEvent('CONFIG_VIEW', 'system', 'system-health', true);

$pdo = getDb();

// Component health checks
$components = [];

// OpenSIPS — check via MI
$opensipsStatus = 'unknown';
$opensipsVersion = '—';
$opensipsUptime = '—';
try {
    $miData = miHttpCall('get_statistics', ['all' => false, 'statistics' => ['opensips_version', 'uptime']]);
    $opensipsStatus = 'healthy';
    $opensipsVersion = $miData['opensips_version'] ?? '—';
    $opensipsUptime = isset($miData['uptime']) ? gmdate('H:i:s', (int)$miData['uptime']) : '—';
} catch (Throwable $e) {
    $opensipsStatus = 'unhealthy';
}
$components[] = [
    'name' => 'OpenSIPS',
    'status' => $opensipsStatus,
    'version' => $opensipsVersion,
    'uptime' => $opensipsUptime,
    'icon' => 'sip',
    'link' => 'gateway-health.php',
];

// RTPengine — check via MI
try {
    $rtpData = miHttpCall('rtpengine_show', ['all' => true]);
    $components[] = [
        'name' => 'RTPengine',
        'status' => 'healthy',
        'version' => '—',
        'uptime' => '—',
        'icon' => 'media',
        'link' => 'rtpengine-status.php',
    ];
} catch (Throwable $e) {
    $components[] = [
        'name' => 'RTPengine',
        'status' => 'unhealthy',
        'version' => '—',
        'uptime' => '—',
        'icon' => 'media',
        'link' => 'rtpengine-status.php',
    ];
}

// PostgreSQL — check via query
try {
    $dbCheck = $pdo->query("SELECT version() AS v, now() AS t");
    $dbInfo = $dbCheck->fetch();
    $pgVersion = preg_replace('/^PostgreSQL\s+([\d.]+).*/', '$1', $dbInfo['v'] ?? '');
    $components[] = [
        'name' => 'PostgreSQL',
        'status' => 'healthy',
        'version' => $pgVersion,
        'uptime' => '—',
        'icon' => 'database',
        'link' => '#',
    ];
} catch (Throwable $e) {
    $components[] = [
        'name' => 'PostgreSQL',
        'status' => 'unhealthy',
        'version' => '—',
        'uptime' => '—',
        'icon' => 'database',
        'link' => '#',
    ];
}

// OCP — always healthy if page loads
$components[] = [
    'name' => 'Control Panel',
    'status' => 'healthy',
    'version' => '1.0.0',
    'uptime' => '—',
    'icon' => 'web',
    'link' => '#',
];

// System metrics
$metrics = [];

// Active dialogs
$dlgStmt = $pdo->query("SELECT COUNT(*) AS cnt FROM dialog");
$metrics['active_calls'] = $dlgStmt->fetch()['cnt'] ?? 0;

// Active subscribers
$subStmt = $pdo->query("SELECT COUNT(*) AS cnt FROM subscriber");
$metrics['subscribers'] = $subStmt->fetch()['cnt'] ?? 0;

// Recent audit events
$auditStmt = $pdo->query("SELECT COUNT(*) AS cnt FROM ocp_audit_log WHERE event_time > NOW() - INTERVAL '1 hour'");
$metrics['audit_events_1h'] = $auditStmt->fetch()['cnt'] ?? 0;

// Failed logins (last hour)
$failStmt = $pdo->query("SELECT COUNT(*) AS cnt FROM ocp_audit_log WHERE action = 'LOGIN_FAILURE' AND event_time > NOW() - INTERVAL '1 hour'");
$metrics['failed_logins_1h'] = $failStmt->fetch()['cnt'] ?? 0;

require_once __DIR__ . '/common/header.php';
?>
<div id="content" class="tsisip-dashboard">
    <h1><?php echo _('System Health'); ?></h1>

    <!-- Component Cards -->
    <div class="tsisip-dashboard-grid">
        <?php foreach ($components as $comp): ?>
            <a href="<?php echo htmlspecialchars($comp['link']); ?>" style="text-decoration:none;">
                <div class="tsisip-dashboard-card tsisip-status-card--<?php echo $comp['status']; ?>">
                    <div class="tsisip-card-header">
                        <h3><?php echo _($comp['name']); ?></h3>
                        <span class="tsisip-status-indicator tsisip-status-indicator--<?php echo $comp['status']; ?>"></span>
                    </div>
                    <div class="tsisip-card-body">
                        <div class="tsisip-data-row">
                            <span class="tsisip-data-label"><?php echo _('Status'); ?></span>
                            <span class="tsisip-data-value">
                                <span class="tsisip-badge tsisip-badge--<?php echo $comp['status']; ?>">
                                    <?php echo _($comp['status']); ?>
                                </span>
                            </span>
                        </div>
                        <?php if ($comp['version'] !== '—'): ?>
                            <div class="tsisip-data-row">
                                <span class="tsisip-data-label"><?php echo _('Version'); ?></span>
                                <span class="tsisip-data-value"><?php echo htmlspecialchars($comp['version']); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($comp['uptime'] !== '—'): ?>
                            <div class="tsisip-data-row">
                                <span class="tsisip-data-label"><?php echo _('Uptime'); ?></span>
                                <span class="tsisip-data-value"><?php echo htmlspecialchars($comp['uptime']); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Metrics -->
    <div class="tsisip-dashboard-section" style="margin-top:2rem;">
        <h2 class="tsisip-section-title"><?php echo _('System Metrics'); ?></h2>
        <div class="tsisip-dashboard-grid" style="grid-template-columns:repeat(4, 1fr);">
            <div class="tsisip-dashboard-card">
                <div class="tsisip-metric-value"><?php echo (int)$metrics['active_calls']; ?></div>
                <div class="tsisip-metric-label"><?php echo _('Active Calls'); ?></div>
            </div>
            <div class="tsisip-dashboard-card">
                <div class="tsisip-metric-value"><?php echo (int)$metrics['subscribers']; ?></div>
                <div class="tsisip-metric-label"><?php echo _('Subscribers'); ?></div>
            </div>
            <div class="tsisip-dashboard-card">
                <div class="tsisip-metric-value"><?php echo (int)$metrics['audit_events_1h']; ?></div>
                <div class="tsisip-metric-label"><?php echo _('Events (1h)'); ?></div>
            </div>
            <div class="tsisip-dashboard-card">
                <div class="tsisip-metric-value" style="color:var(--tsisip-danger);">
                    <?php echo (int)$metrics['failed_logins_1h']; ?>
                </div>
                <div class="tsisip-metric-label"><?php echo _('Failed Logins (1h)'); ?></div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="tsisip-dashboard-section" style="margin-top:2rem;">
        <h2 class="tsisip-section-title"><?php echo _('Quick Actions'); ?></h2>
        <div class="tsisip-btn-group">
            <a href="gateway-health.php" class="tsisip-btn tsisip-btn-outline"><?php echo _('Gateway Health'); ?></a>
            <a href="call-queue.php" class="tsisip-btn tsisip-btn-outline"><?php echo _('Call Queue'); ?></a>
            <a href="rtpengine-status.php" class="tsisip-btn tsisip-btn-outline"><?php echo _('RTPengine'); ?></a>
            <a href="audit-log.php" class="tsisip-btn tsisip-btn-outline"><?php echo _('Audit Log'); ?></a>
        </div>
    </div>
</div>

<style>
.tsisip-status-card--healthy { border-left: 4px solid var(--tsisip-success); }
.tsisip-status-card--unhealthy { border-left: 4px solid var(--tsisip-danger); }
.tsisip-status-card--unknown { border-left: 4px solid var(--tsisip-warning-border); }
.tsisip-status-indicator { width:12px; height:12px; border-radius:50%; display:inline-block; }
.tsisip-status-indicator--healthy { background:var(--tsisip-success); }
.tsisip-status-indicator--unhealthy { background:var(--tsisip-danger); }
.tsisip-status-indicator--unknown { background:var(--tsisip-warning-border); }
.tsisip-metric-value { font-size:2rem; font-weight:700; color:var(--tsisip-primary-blue); }
.tsisip-metric-label { font-size:0.875rem; color:var(--tsisip-text-secondary); }
</style>
<?php require_once __DIR__ . '/common/footer.php'; ?>
