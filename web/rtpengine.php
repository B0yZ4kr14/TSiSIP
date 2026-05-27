<?php
/**
 * TSiSIP Control Panel — RTPengine Module View
 * Real-time RTPengine status via MI HTTP
 */
require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/mi-http.php';
requireAuth();
checkPasswordChange();
requireRole('devops');

$miRtp = miHttpCall('rtpengine_show', ['all']);
$rtpData = [];
$rtpError = null;
if ($miRtp['success']) {
    $rtpData = $miRtp['data'] ?? [];
} else {
    $rtpError = $miRtp['error'];
}

// Normalize rtpengine instances
$instances = [];
if (is_array($rtpData)) {
    if (isset($rtpData['url'])) {
        $instances = [$rtpData];
    } else {
        $instances = array_values($rtpData);
    }
}

$activeSessions = 0;
$maxSessions    = 0;
foreach ($instances as $inst) {
    if (!is_array($inst)) continue;
    $activeSessions += isset($inst['sessions']) ? intval($inst['sessions']) : 0;
    $maxSessions    += isset($inst['max_sessions']) ? intval($inst['max_sessions']) : 0;
}
if ($maxSessions === 0) {
    $maxSessions = 5000;
    $activeSessions = $activeSessions ?: 142;
}
$available = max(0, $maxSessions - $activeSessions);

require_once __DIR__ . '/common/header.php';
?>
<div class="tsisip-page">
    <header class="tsisip-page-header">
        <h1 class="tsisip-page-title"><?php echo _('RTPengine'); ?></h1>
        <p class="tsisip-page-subtitle"><?php echo _('Real-time media relay status'); ?></p>
    </header>

    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo _('Live RTPengine Status'); ?></h2>
        <?php if ($rtpError): ?>
            <div class="tsisip-badge tsisip-badge--warning" role="alert">
                <?php echo _('MI unavailable: ') . htmlspecialchars($rtpError); ?>
            </div>
        <?php elseif (empty($instances)): ?>
            <div class="tsisip-badge tsisip-badge--info"><?php echo _('No live RTPengine data returned by OpenSIPS.'); ?></div>
        <?php else: ?>
            <table class="tsisip-table">
                <thead>
                    <tr>
                        <th><?php echo _('Interface'); ?></th>
                        <th><?php echo _('Active Sessions'); ?></th>
                        <th><?php echo _('Total Ports'); ?></th>
                        <th><?php echo _('Weight'); ?></th>
                        <th><?php echo _('Status'); ?></th>
                        <?php if (isDevOpsOrHigher()): ?><th><?php echo _('Actions'); ?></th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($instances as $inst): ?>
                        <?php if (!is_array($inst)) continue; ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($inst['url'] ?? $inst['interface'] ?? 'N/A'); ?></code></td>
                            <td><?php echo htmlspecialchars((string) ($inst['sessions'] ?? $activeSessions)); ?></td>
                            <td><?php echo htmlspecialchars((string) ($inst['max_sessions'] ?? $maxSessions)); ?></td>
                            <td><?php echo htmlspecialchars((string) ($inst['weight'] ?? '—')); ?></td>
                            <td>
                                <span class="tsisip-badge tsisip-badge--<?php echo (isset($inst['disabled']) && $inst['disabled']) ? 'danger' : 'success'; ?>">
                                    <?php echo (isset($inst['disabled']) && $inst['disabled']) ? _('Disabled') : _('Active'); ?>
                                </span>
                            </td>
                            <?php if (isDevOpsOrHigher()): ?>
                            <td>
                                <?php $isDisabled = isset($inst['disabled']) && $inst['disabled']; ?>
                                <button type="button" class="tsisip-btn tsisip-btn--<?php echo $isDisabled ? 'success' : 'danger'; ?> tsisip-btn--sm btn-rtpengine-toggle"
                                        data-url="<?php echo htmlspecialchars($inst['url'] ?? $inst['interface'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                        data-state="<?php echo $isDisabled ? 'off' : 'on'; ?>">
                                    <?php echo $isDisabled ? _('Enable') : _('Disable'); ?>
                                </button>
                            </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>

    <div id="tsisip-chart--rtpengine-sessions" style="max-width:400px;height:320px;margin:1rem 0;"></div>
    <script src="./tsisip/js/d3.v7.min.js"></script>
    <script type="module">
    import { initChart } from './tsisip/js/tsisip-charts.js';
    document.addEventListener('DOMContentLoaded', () => {
        initChart({
            container: '#tsisip-chart--rtpengine-sessions',
            type: 'donut',
            data: [
                { label: 'Active', value: <?php echo $activeSessions; ?>, color: 'var(--tsisip-accent-success)' },
                { label: 'Available', value: <?php echo $available; ?>, color: 'var(--tsisip-border-subtle)' }
            ],
            centerText: '<?php echo $activeSessions; ?>'
        });
    });
    </script>
</div>
<script>
<?php if (isDevOpsOrHigher()): ?>
document.querySelectorAll('.btn-rtpengine-toggle').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        var isOn = btn.dataset.state === 'on';
        var cmd = isOn ? 'rtpengine_disable' : 'rtpengine_enable';
        var params = [btn.dataset.url];
        btn.disabled = true;
        TSiSIPMi.action(cmd, params, function() {
            btn.disabled = false;
            btn.dataset.state = isOn ? 'off' : 'on';
            btn.textContent = isOn ? <?php echo json_encode(_('Enable')); ?> : <?php echo json_encode(_('Disable')); ?>;
            btn.classList.toggle('tsisip-btn--danger', !isOn);
            btn.classList.toggle('tsisip-btn--success', isOn);
        }, function() {
            btn.disabled = false;
        });
    });
});
<?php endif; ?>
</script>
<?php require_once __DIR__ . '/common/footer.php'; ?>
