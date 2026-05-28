<?php
/**
 * TSiSIP System Health — Full-screen real-time metrics view
 * Feature 034
 */
require_once __DIR__ . '/common/config.php';
requireAuth();
checkPasswordChange();
logAuditEvent('CONFIG_VIEW', 'system', 'system-health', true);
require_once __DIR__ . '/common/header.php';

$pageTitle = _('System Health');
$isAdmin   = ($userRole === 'admin');
$isDevOps  = ($userRole === 'devops');
?>
<div id="content" class="tsisip-dashboard">
    <h1><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></h1>

    <!-- SSE Status Indicator -->
    <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:1rem;">
        <span class="tsisip-sse-status tsisip-sse-status--disconnected"></span>
        <span class="tsisip-text-muted"><?php echo _('Live Stream'); ?></span>
    </div>

    <!-- Anomaly Banner -->
    <div id="anomaly-banner" class="tsisip-alert" style="display:none;margin-bottom:1rem;">
        <strong><?php echo _('Anomaly Detected'); ?>:</strong>
        <span id="anomaly-text"></span>
    </div>

    <!-- Live Metrics Grid -->
    <div class="tsisip-dashboard-section">
        <h2><?php echo _('Live Metrics'); ?></h2>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px;">
            <div class="tsisip-metric-card">
                <div class="tsisip-metric-card__label"><?php echo _('Active Dialogs'); ?></div>
                <div class="tsisip-metric-card__value" data-sse-field="dialogs">0</div>
            </div>
            <div class="tsisip-metric-card">
                <div class="tsisip-metric-card__label"><?php echo _('RTP Sessions'); ?></div>
                <div class="tsisip-metric-card__value" data-sse-field="rtpengine.sessions">0</div>
            </div>
            <div class="tsisip-metric-card">
                <div class="tsisip-metric-card__label"><?php echo _('Pkg Memory'); ?></div>
                <div class="tsisip-metric-card__value" data-sse-field="memory.pkg_pct" data-sse-format="percent">0%</div>
            </div>
            <div class="tsisip-metric-card">
                <div class="tsisip-metric-card__label"><?php echo _('Shm Memory'); ?></div>
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
                <div class="tsisip-metric-card__label"><?php echo _('TCP Connections'); ?></div>
                <div class="tsisip-metric-card__value" data-sse-field="tcp_connections">0</div>
            </div>
            <div class="tsisip-metric-card">
                <div class="tsisip-metric-card__label"><?php echo _('Blacklists'); ?></div>
                <div class="tsisip-metric-card__value" data-sse-field="blacklists">0</div>
            </div>
            <div class="tsisip-metric-card">
                <div class="tsisip-metric-card__label"><?php echo _('USRLoc Contacts'); ?></div>
                <div class="tsisip-metric-card__value" data-sse-field="usrloc_contacts">0</div>
            </div>
        </div>
    </div>

    <?php if ($isAdmin || $isDevOps): ?>
    <!-- Trunk Providers -->
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

    <!-- Active Alerts -->
    <div class="tsisip-dashboard-section">
        <h2><?php echo _('Active Alerts'); ?></h2>
        <div id="alerts-widget">
            <div class="tsisip-text-muted"><?php echo _('Loading alerts...'); ?></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Dispatcher Sets -->
    <div class="tsisip-dashboard-section">
        <h2><?php echo _('Dispatcher Sets'); ?></h2>
        <div id="dispatcher-widget">
            <div class="tsisip-text-muted"><?php echo _('Loading dispatcher state...'); ?></div>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';

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

    function updateDispatcher(data) {
        const widget = document.getElementById('dispatcher-widget');
        if (!widget) return;
        const gateways = data.gateways || [];
        if (gateways.length === 0) {
            widget.innerHTML = '<div class="tsisip-text-muted">No dispatcher destinations</div>';
            return;
        }
        const bySet = {};
        gateways.forEach(g => {
            const setId = g.setid || 0;
            if (!bySet[setId]) bySet[setId] = [];
            bySet[setId].push(g);
        });
        widget.innerHTML = Object.keys(bySet).sort((a,b)=>a-b).map(setId => {
            return '<div style="margin-bottom:1rem;">' +
                '<h4>Set ' + setId + '</h4>' +
                '<div style="display:flex;flex-wrap:wrap;gap:0.5rem;">' +
                bySet[setId].map(g => {
                    const stateClass = g.state === 'Active' ? 'tsisip-badge-success' :
                        (g.state === 'Inactive' ? 'tsisip-badge-error' : 'tsisip-badge-warning');
                    return '<span class="tsisip-badge ' + stateClass + '" title="' + (g.uri || '') + '">' +
                        (g.uri || '') + ' (' + g.state + ')' +
                        '</span>';
                }).join('') +
                '</div></div>';
        }).join('');
    }

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
                        '<strong>' + (a.name || '') + '</strong> &mdash; ' + (a.summary || '') +
                        '</li>';
                }).join('') + '</ul>';
            })
            .catch(() => {
                widget.innerHTML = '<div class="tsisip-text-muted">Alerts unavailable</div>';
            });
    }

    if (window.TSiSIPEvents) {
        window.TSiSIPEvents.on('data', function(data) {
            updateAnomalyBanner(data);
            updateTrunkHealth(data);
            updateDispatcher(data);
        });
    }

    loadAlerts();
    setInterval(loadAlerts, 30000);
})();
</script>

<?php require_once __DIR__ . '/common/footer.php'; ?>
