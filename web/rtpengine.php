<?php
/**
 * TSiSIP Control Panel — RTPengine Module View Stub
 * Demonstrates D3.js chart integration point
 */
require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/mi-http.php';
requireAuth();
checkPasswordChange();

$miRtp = miHttpCall('rtpengine_show');
$rtpData = [];
$rtpError = null;
if ($miRtp['success']) {
    $rtpData = $miRtp['data'] ?? [];
} else {
    $rtpError = $miRtp['error'];
}

$activeSessions = is_array($rtpData) && isset($rtpData['sessions']) ? intval($rtpData['sessions']) : 142;
$maxSessions    = is_array($rtpData) && isset($rtpData['max_sessions']) ? intval($rtpData['max_sessions']) : 5000;
$available      = max(0, $maxSessions - $activeSessions);

require_once __DIR__ . '/common/header.php';
?>
<div id="content">
    <h2><?php echo _('RTPengine Sessions'); ?></h2>

    <?php if ($rtpError): ?>
        <div class="tsisip-badge tsisip-badge--warning" role="alert">
            <?php echo _('MI unavailable: ') . htmlspecialchars($rtpError); ?>
        </div>
    <?php endif; ?>

    <div id="tsisip-chart--rtpengine-sessions" style="max-width:400px;height:320px;margin:1rem 0;"></div>
    <table class="dataTable tsisip-table">
        <thead>
            <tr>
                <th><?php echo _('Instance'); ?></th>
                <th><?php echo _('Active'); ?></th>
                <th><?php echo _('Max'); ?></th>
                <th class="tsisip-actions-column"><?php echo _('Actions'); ?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>rtpengine-01</td>
                <td><?php echo $activeSessions; ?></td>
                <td><?php echo $maxSessions; ?></td>
                <td class="tsisip-actions-column">
                    <button type="button" class="tsisip-btn tsisip-btn-secondary tsisip-btn-edit"><?php echo _('Edit'); ?></button>
                </td>
            </tr>
        </tbody>
    </table>
</div>
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
<?php require_once __DIR__ . '/common/footer.php'; ?>
