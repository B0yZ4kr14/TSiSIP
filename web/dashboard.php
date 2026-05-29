<?php
/**
 * TSiSIP Control Panel — Dashboard
 * Role-aware landing page after login with system management links.
 */
require_once __DIR__ . '/common/config.php';
requireAuth();
checkPasswordChange();
logAuditEvent('CONFIG_VIEW', 'system', 'dashboard', true);
require_once __DIR__ . '/common/header.php';

$roleLabels = [
    'admin'     => _('Administrator'),
    'devops'    => _('DevOps Engineer'),
    'dentist'   => _('Dentist'),
    'assistant' => _('Assistant'),
    'user'      => _('User'),
    'readonly'  => _('Read-Only User'),
];

$displayRole = isset($roleLabels[$userRole]) ? $roleLabels[$userRole] : $roleLabels['readonly'];

$systemLinks = [];
if ($userRole === 'admin' || $userRole === 'devops') {
    $systemLinks = [
        ['url' => 'dispatcher.php',   'label' => _('Dispatcher Targets'),   'icon' => 'route'],
        ['url' => 'rtpengine.php',    'label' => _('RTPengine Sessions'),   'icon' => 'broadcast'],
        ['url' => 'audit-log.php',    'label' => _('Audit Log & Compliance'), 'icon' => 'shield'],
        ['url' => 'users.php',        'label' => _('User Management'),      'icon' => 'users'],
        ['url' => 'dialplan.php',     'label' => _('Dialplan'),             'icon' => 'list'],
        ['url' => 'domains.php',      'label' => _('SIP Domains'),          'icon' => 'globe'],
        ['url' => 'dialog.php',       'label' => _('Active Dialogs'),       'icon' => 'phone'],
        ['url' => 'mi-commands.php',  'label' => _('MI Commands'),          'icon' => 'terminal'],
        ['url' => 'statistics.php',   'label' => _('Statistics'),           'icon' => 'chart'],
        ['url' => 'tls-management.php', 'label' => _('TLS Certificates'),    'icon' => 'lock'],
        ['url' => 'gateway-health.php', 'label' => _('Gateway Health'),       'icon' => 'heart'],
        ['url' => 'call-queue.php',     'label' => _('Live Call Queue'),      'icon' => 'queue'],
        ['url' => 'topology.php',       'label' => _('Network Topology'),     'icon' => 'network'],
        ['url' => 'failover.php',       'label' => _('Manual Failover'),      'icon' => 'switch'],
        ['url' => 'alert-history.php',  'label' => _('Alert History'),        'icon' => 'bell'],
        ['url' => 'rtpengine-status.php', 'label' => _('RTPengine Status'),       'icon' => 'broadcast'],
        ['url' => 'subscriber-stats.php', 'label' => _('Subscriber Statistics'),  'icon' => 'users'],
        ['url' => 'system-config.php',  'label' => _('System Configuration'),    'icon' => 'sliders'],
        ['url' => 'help.php',           'label' => _('Help & Documentation'),   'icon' => 'help-circle'],
        ['url' => 'about.php',          'label' => _('About'),              'icon' => 'info'],
        ['url' => 'feedback.php',       'label' => _('Feedback'),            'icon' => 'message'],
        ['url' => 'feedback-list.php',  'label' => _('Feedback Mgmt'),       'icon' => 'list'],
        ['url' => 'notes.php',          'label' => _('My Notes'),            'icon' => 'note'],
        ['url' => 'search.php',         'label' => _('Global Search'),       'icon' => 'search'],
        ['url' => 'system-health.php',  'label' => _('System Health'),     'icon' => 'health'],
        ['url' => 'api-docs.php',       'label' => _('API Documentation'),     'icon' => 'code'],
        ['url' => 'api-keys.php',       'label' => _('API Keys'),              'icon' => 'key'],
        ['url' => 'reports.php',        'label' => _('System Reports'),       'icon' => 'chart'],
        ['url' => 'scheduled-tasks.php','label' => _('Scheduled Tasks'),  'icon' => 'schedule'],
        ['url' => 'cache-manager.php',  'label' => _('Cache Manager'),     'icon' => 'memory'],
        ['url' => 'system-logs.php',    'label' => _('System Logs'),       'icon' => 'file-text'],
    ];
}

/* ------------------------------------------------------------------
 * Runtime links (all authenticated roles)
 * ------------------------------------------------------------------ */
$runtimeLinks = [
    ['url' => 'memory-status.php', 'label' => _('Memory Status'), 'icon' => 'memory'],
    ['url' => 'processes.php',     'label' => _('Processes'),     'icon' => 'cpu'],
    ['url' => 'usrloc.php',        'label' => _('USRLoc Live'),   'icon' => 'users'],
    ['url' => 'version.php',       'label' => _('Version'),       'icon' => 'info'],
];

/* ------------------------------------------------------------------
 * Security links (devops and admin only)
 * ------------------------------------------------------------------ */
$securityLinks = [];
if ($userRole === 'admin' || $userRole === 'devops') {
    $securityLinks = [
        ['url' => 'pike-monitor.php', 'label' => _('Pike Monitor'), 'icon' => 'shield'],
        ['url' => 'ratelimit.php',    'label' => _('Rate Limit'),   'icon' => 'gauge'],
    ];
}

/* ------------------------------------------------------------------
 * Wiki / Documentation links (all roles)
 * ------------------------------------------------------------------ */
$wikiLinks = [];
if (isset($roleNav[$userRole])) {
    foreach ($roleNav[$userRole] as $page) {
        if (isset($navLabels[$page])) {
            $wikiLinks[] = [
                'url'   => 'wiki.php?page=' . urlencode($page),
                'label' => $navLabels[$page],
            ];
        }
    }
}
?>
<div id="content" class="tsisip-dashboard">
    <h1>
        <?php echo _('Welcome'); ?>,
        <span class="tsisip-dashboard-role"><?php echo htmlspecialchars($displayRole, ENT_QUOTES, 'UTF-8'); ?></span>
    </h1>

    <!-- Anomaly Alert Banner -->
    <div id="anomaly-banner" class="tsisip-alert" style="display:none;margin-bottom:1rem;">
        <strong><?php echo _('Anomaly Detected'); ?>:</strong>
        <span id="anomaly-text"></span>
    </div>

    <!-- Live Metrics -->
    <div class="tsisip-dashboard-section">
        <h2><?php echo _('Live Metrics'); ?></h2>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:12px;">
            <div class="tsisip-metric-card">
                <div class="tsisip-metric-card__label"><?php echo _('Dialogs'); ?></div>
                <div class="tsisip-metric-card__value" data-sse-field="dialogs">0</div>
            </div>
            <div class="tsisip-metric-card">
                <div class="tsisip-metric-card__label"><?php echo _('RTP Sessions'); ?></div>
                <div class="tsisip-metric-card__value" data-sse-field="rtpengine.sessions">0</div>
            </div>
            <div class="tsisip-metric-card">
                <div class="tsisip-metric-card__label"><?php echo _('Pkg Mem'); ?></div>
                <div class="tsisip-metric-card__value" data-sse-field="memory.pkg_pct" data-sse-format="percent">0%</div>
            </div>
            <div class="tsisip-metric-card">
                <div class="tsisip-metric-card__label"><?php echo _('Shm Mem'); ?></div>
                <div class="tsisip-metric-card__value" data-sse-field="memory.shm_pct" data-sse-format="percent">0%</div>
            </div>
            <div class="tsisip-metric-card">
                <div class="tsisip-metric-card__label"><?php echo _('Processes'); ?></div>
                <div class="tsisip-metric-card__value" data-sse-field="processes">0</div>
            </div>
            <div class="tsisip-metric-card">
                <div class="tsisip-metric-card__label"><?php echo _('Blocked IPs'); ?></div>
                <div class="tsisip-metric-card__value" data-sse-field="pike_blocked">0</div>
            </div>
            <div class="tsisip-metric-card">
                <div class="tsisip-metric-card__label"><?php echo _('TCP Conns'); ?></div>
                <div class="tsisip-metric-card__value" data-sse-field="tcp_connections">0</div>
            </div>
            <div class="tsisip-metric-card">
                <div class="tsisip-metric-card__label"><?php echo _('Blacklists'); ?></div>
                <div class="tsisip-metric-card__value" data-sse-field="blacklists">0</div>
            </div>
        </div>
    </div>

    <?php if ($isAdmin || $isDevOps): ?>
    <!-- Trunk Provider Health -->
    <div class="tsisip-dashboard-section">
        <h2><?php echo _('Trunk Providers'); ?></h2>
        <div id="trunk-health-widget" class="tsisip-table-container">
            <table class="tsisip-table">
                <thead>
                    <tr>
                        <th><?php echo _('Name'); ?></th>
                        <th><?php echo _('Host'); ?></th>
                        <th><?php echo _('Status'); ?></th>
                        <th><?php echo _('Max CPS'); ?></th>
                    </tr>
                </thead>
                <tbody id="trunk-health-tbody">
                    <tr><td colspan="4" class="tsisip-text-muted"><?php echo _('Loading...'); ?></td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Dispatcher Health -->
    <div class="tsisip-dashboard-section">
        <h2><?php echo _('Dispatcher Health'); ?></h2>
        <div id="dispatcher-health-widget" class="tsisip-table-container">
            <table class="tsisip-table">
                <thead>
                    <tr>
                        <th><?php echo _('Set'); ?></th>
                        <th><?php echo _('Destination'); ?></th>
                        <th><?php echo _('State'); ?></th>
                        <th><?php echo _('Probe'); ?></th>
                        <th><?php echo _('Weight'); ?></th>
                    </tr>
                </thead>
                <tbody id="dispatcher-health-tbody">
                    <tr><td colspan="5" class="tsisip-text-muted"><?php echo _('Loading...'); ?></td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Active Alerts from Alertmanager -->
    <div class="tsisip-dashboard-section">
        <h2><?php echo _('Active Alerts'); ?></h2>
        <div id="alerts-widget">
            <div class="tsisip-text-muted"><?php echo _('Loading alerts...'); ?></div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($systemLinks)): ?>
    <div class="tsisip-dashboard-section">
        <h2><?php echo _('System Management'); ?></h2>
    <!-- Bookmarks -->
    <div class="tsisip-dashboard-section" data-widget="bookmarks">
    <!-- Recent Activity -->
    <div class="tsisip-dashboard-section" data-widget="activity">
        <h2 class="tsisip-section-title"><?php echo _('Recent Activity'); ?></h2>
        <div class="tsisip-activity-list">
            <?php
            $recent = getRecentActivity(5);
            foreach ($recent as $act): ?>
                <div class="tsisip-activity-item">
                    <span class="tsisip-activity-time"><?php echo htmlspecialchars(substr((string)$act['event_time'], 11, 5)); ?></span>
                    <span class="tsisip-activity-user"><?php echo htmlspecialchars($act['username']); ?></span>
                    <span class="tsisip-activity-action"><?php echo htmlspecialchars($act['action']); ?></span>
                    <span class="tsisip-activity-resource"><?php echo htmlspecialchars(($act['resource_type'] ?? '') . '/' . ($act['resource_id'] ?? '')); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
        <h2 class="tsisip-section-title"><?php echo _('Bookmarks'); ?></h2>
        <div class="tsisip-btn-group">
            <?php
            $pdo = getDb();
            $bmStmt = $pdo->prepare(
                "SELECT page_url, page_label, icon FROM ocp_user_bookmarks
                 WHERE user_id = :uid ORDER BY sort_order"
            );
            $bmStmt->execute([':uid' => $_SESSION['user_id'] ?? 0]);
            $bookmarks = $bmStmt->fetchAll();
            if (empty($bookmarks)): ?>
                <span class="tsisip-text-muted"><?php echo _('No bookmarks yet. Click the star icon on any page to add it.'); ?></span>
            <?php else:
                foreach ($bookmarks as $bm): ?>
                    <a href="<?php echo htmlspecialchars($bm['page_url']); ?>" class="tsisip-btn tsisip-btn-outline">
                        <?php echo htmlspecialchars(_($bm['page_label'])); ?>
                    </a>
                <?php endforeach;
            endif; ?>
        </div>
    </div>
        <div class="tsisip-dashboard-links">
            <?php foreach ($systemLinks as $link): ?>
                <a href="<?php echo htmlspecialchars($link['url'], ENT_QUOTES, 'UTF-8'); ?>"
                   class="tsisip-btn tsisip-btn-primary">
                    <?php echo htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($runtimeLinks)): ?>
    <div class="tsisip-dashboard-section">
        <h2><?php echo _('Runtime'); ?></h2>
        <div class="tsisip-dashboard-links">
            <?php foreach ($runtimeLinks as $link): ?>
                <a href="<?php echo htmlspecialchars($link['url'], ENT_QUOTES, 'UTF-8'); ?>"
                   class="tsisip-btn tsisip-btn-secondary">
                    <?php echo htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($securityLinks)): ?>
    <div class="tsisip-dashboard-section">
        <h2><?php echo _('Security'); ?></h2>
        <div class="tsisip-dashboard-links">
            <?php foreach ($securityLinks as $link): ?>
                <a href="<?php echo htmlspecialchars($link['url'], ENT_QUOTES, 'UTF-8'); ?>"
                   class="tsisip-btn tsisip-btn-secondary">
                    <?php echo htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="tsisip-dashboard-section">
        <h2><?php echo _('Documentation & Wiki'); ?></h2>
        <?php if (!empty($wikiLinks)): ?>
            <div class="tsisip-dashboard-links">
                <?php foreach ($wikiLinks as $link): ?>
                    <a href="<?php echo htmlspecialchars($link['url'], ENT_QUOTES, 'UTF-8'); ?>"
                       class="tsisip-btn tsisip-btn-secondary">
                        <?php echo htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="tsisip-text-muted"><?php echo _('No wiki links available for your role.'); ?></p>
        <?php endif; ?>
    </div>

    <div class="tsisip-dashboard-section">
        <h2><?php echo _('System Status'); ?></h2>
        <p><?php echo _('TSiSIP Control Panel is operational.'); ?></p>
        <ul class="tsisip-status-list">
            <li><span class="tsisip-status-dot tsisip-status-dot--ok"></span> OpenSIPS SIP Proxy</li>
            <li><span class="tsisip-status-dot tsisip-status-dot--ok"></span> RTPengine Media Relay</li>
            <li><span class="tsisip-status-dot tsisip-status-dot--ok"></span> PostgreSQL Database</li>
            <li><span class="tsisip-status-dot tsisip-status-dot--ok"></span> OCP Web Interface</li>
        </ul>
        <p class="tsisip-hint">
            <?php echo _('Feature 020 tools are now available: Dialog Viewer, MI Commands, Statistics, Dialplan, Domains, TLS Management, Gateway Health, Live Call Queue, Network Topology, Manual Failover, Alert History, RTPengine Status, Subscriber Statistics, System Configuration, and Help.'); ?>
        </p>
    </div>
    <!-- Auto-Healing Events Widget (Feature 036) -->
    <div class="tsisip-dashboard-section" id="autoheal-widget-section">
        <h2><?php echo _('Auto-Healing Events'); ?></h2>
        <div id="autoheal-events-widget" style="max-height:200px;overflow:auto;">
            <div class="tsisip-text-muted"><?php echo _('Loading...'); ?></div>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';

    // Anomaly banner updater
    function updateAnomalyBanner(data) {
        const banner = document.getElementById('anomaly-banner');
        const text = document.getElementById('anomaly-text');
        if (!banner || !text) return;
        const anomaly = data.anomaly || {};
        const zScore = parseFloat(anomaly.z_score || 0);
        if (zScore > 3.0) {
            banner.style.display = 'block';
            banner.className = 'tsisip-alert tsisip-alert--error';
            text.textContent = 'RPS=' + (anomaly.current_rps || 0).toFixed(1) +
                ' | Z=' + zScore.toFixed(2) + ' | Baseline=' + (anomaly.baseline_mean || 0).toFixed(1);
        } else if (zScore > 2.0) {
            banner.style.display = 'block';
            banner.className = 'tsisip-alert tsisip-alert--warning';
            text.textContent = 'Elevated traffic: RPS=' + (anomaly.current_rps || 0).toFixed(1) +
                ' | Z=' + zScore.toFixed(2);
        } else {
            banner.style.display = 'none';
        }
    }

    // Dispatcher health updater
    function updateDispatcherHealth(data) {
        const tbody = document.getElementById('dispatcher-health-tbody');
        if (!tbody) return;
        const gateways = data.gateways || [];
        if (gateways.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="tsisip-text-muted">No dispatcher destinations</td></tr>';
            return;
        }
        tbody.innerHTML = gateways.map(g => {
            const stateMap = { 'Active': 'success', 'Inactive': 'error', 'Probing': 'warning', 'Disabled': 'muted' };
            const stateClass = 'tsisip-badge-' + (stateMap[g.state] || 'muted');
            return '<tr>' +
                '<td>' + (g.setid || 0) + '</td>' +
                '<td>' + (g.uri || '') + '</td>' +
                '<td><span class="tsisip-badge ' + stateClass + '">' + (g.state || 'Unknown') + '</span></td>' +
                '<td>●</td>' +
                '<td>—</td>' +
                '</tr>';
        }).join('');
    }

    // Trunk health updater
    function updateTrunkHealth(data) {
        const tbody = document.getElementById('trunk-health-tbody');
        if (!tbody) return;
        const trunks = data.trunks || [];
        if (trunks.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="tsisip-text-muted">No trunk providers configured</td></tr>';
            return;
        }
        tbody.innerHTML = trunks.map(t => {
            const statusClass = t.enabled ? 'tsisip-badge-success' : 'tsisip-badge-error';
            const statusText = t.enabled ? 'Up' : 'Down';
            return '<tr>' +
                '<td>' + (t.name || '') + '</td>' +
                '<td>' + (t.host || '') + ':' + (t.port || '') + '</td>' +
                '<td><span class="tsisip-badge ' + statusClass + '">' + statusText + '</span></td>' +
                '<td>' + (t.max_cps || 0) + '</td>' +
                '</tr>';
        }).join('');
    }

    // Active alerts updater
    function loadAlerts() {
        const widget = document.getElementById('alerts-widget');
        if (!widget) return;
        fetch('api/v1/alerts.php')
            .then(r => r.json())
            .then(data => {
                const alerts = data.alerts || [];
                if (alerts.length === 0) {
                    widget.innerHTML = '<div class="tsisip-text-muted">No active alerts</div>';
                    return;
                }
                widget.innerHTML = '<ul class="tsisip-alert-list">' + alerts.map(a => {
                    const severityClass = a.severity === 'critical' ? 'tsisip-alert--error' :
                        (a.severity === 'warning' ? 'tsisip-alert--warning' : 'tsisip-alert--info');
                    return '<li class="tsisip-alert ' + severityClass + '" style="margin-bottom:0.5rem;">' +
                        '<strong>' + (a.name || '') + '</strong> — ' + (a.summary || '') +
                        '</li>';
                }).join('') + '</ul>';
            })
            .catch(() => {
                widget.innerHTML = '<div class="tsisip-text-muted">Alerts unavailable</div>';
            });
    }

    // Hook into existing SSE client
    if (window.TSiSIPEvents) {
        window.TSiSIPEvents.on('data', function(data) {
            updateAnomalyBanner(data);
            updateTrunkHealth(data);
            updateDispatcherHealth(data);
            updateAutohealEvents(data);
        });
    }

    // Load alerts on page load and every 30s
    // Auto-healing events updater (Feature 036 SSE)

    function updateAutohealEvents(data) {

        const widget = document.getElementById('autoheal-events-widget');

        if (!widget) return;

        const events = data.autoheal || [];

        if (events.length === 0) {

            widget.innerHTML = '<div class="tsisip-text-muted"><?php echo _('No recent auto-healing events'); ?></div>';

            return;

        }

        widget.innerHTML = '<ul class="tsisip-alert-list">' + events.map(e => {

            const color = e.result === 'success' ? 'tsisip-alert--success' :

                (e.result === 'failed' ? 'tsisip-alert--error' : 'tsisip-alert--warning');

            return '<li class="tsisip-alert ' + color + '" style="margin-bottom:0.5rem;">' +

                '<strong>' + (e.action || '?') + '</strong> — ' +

                (e.destination || '?') + ' (' + (e.result || '?') + ')' +

                '<br><small class="tsisip-text-muted">' + (e.created_at || '?') + '</small>' +

                '</li>';

        }).join('') + '</ul>';

    }


    loadAlerts();
    setInterval(loadAlerts, 30000);
})();
</script>

<!-- Feature 034: Historical Mini-Charts (Sparklines) -->
<div class="tsisip-dashboard-section" id="sparkline-section">
    <h2><?php echo _('Trends'); ?></h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:12px;">
        <div class="tsisip-metric-card">
            <div class="tsisip-metric-card__label"><?php echo _('Dialogs (5m)'); ?></div>
            <div class="tsisip-sparkline" id="sparkline-dialogs" style="height:60px;"></div>
        </div>
        <div class="tsisip-metric-card">
            <div class="tsisip-metric-card__label"><?php echo _('RTP Sessions (5m)'); ?></div>
            <div class="tsisip-sparkline" id="sparkline-rtp" style="height:60px;"></div>
        </div>
        <div class="tsisip-metric-card">
            <div class="tsisip-metric-card__label"><?php echo _('Pkg Memory (5m)'); ?></div>
            <div class="tsisip-sparkline" id="sparkline-pkgmem" style="height:60px;"></div>
        </div>
        <div class="tsisip-metric-card">
            <div class="tsisip-metric-card__label"><?php echo _('Blocked IPs (5m)'); ?></div>
            <div class="tsisip-sparkline" id="sparkline-pike" style="height:60px;"></div>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';
    const sparkData = { dialogs: [], rtp: [], pkgmem: [], pike: [] };
    const maxPoints = 60;

    function updateSparklines(data) {
        sparkData.dialogs.push(parseInt(data.dialogs || 0));
        sparkData.rtp.push(parseInt(data['rtpengine.sessions'] || 0));
        sparkData.pkgmem.push(parseFloat(data['memory.pkg_pct'] || 0));
        sparkData.pike.push(parseInt(data.pike_blocked || 0));
        for (const key of Object.keys(sparkData)) {
            if (sparkData[key].length > maxPoints) sparkData[key].shift();
            drawSparkline('sparkline-' + key, sparkData[key], key === 'pkgmem' ? '#dc3545' : '#0d6efd');
        }
    }

    function drawSparkline(id, values, color) {
        const el = document.getElementById(id);
        if (!el || values.length < 2) return;
        const w = el.clientWidth || 260;
        const h = 60;
        const min = Math.min(...values);
        const max = Math.max(...values);
        const range = max - min || 1;
        const step = w / (values.length - 1);
        let d = 'M0,' + (h - ((values[0] - min) / range) * h);
        for (let i = 1; i < values.length; i++) {
            d += ' L' + (i * step) + ',' + (h - ((values[i] - min) / range) * h);
        }
        el.innerHTML = '<svg width="' + w + '" height="' + h + '" style="overflow:visible">' +
            '<path d="' + d + '" fill="none" stroke="' + color + '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>' +
            '<circle cx="' + ((values.length - 1) * step) + '" cy="' + (h - ((values[values.length - 1] - min) / range) * h) + '" r="3" fill="' + color + '"/>' +
            '</svg>';
    }

    if (window.TSiSIPEvents) {
        window.TSiSIPEvents.on('data', function(data) { updateSparklines(data); });
    }
})();
</script>

<?php require_once __DIR__ . '/common/footer.php'; ?>
