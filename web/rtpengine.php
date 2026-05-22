<?php
/**
 * TSiSIP Control Panel — RTPengine Module View Stub
 * Demonstrates D3.js chart integration point
 */
require_once __DIR__ . '/common/config.php';
requireAuth();
checkPasswordChange();
require_once __DIR__ . '/common/header.php';
?>
<div id="content">
    <h2><?php echo _('RTPengine Sessions'); ?></h2>
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
                <td>142</td>
                <td>5000</td>
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
            { label: 'Active', value: 142, color: 'var(--tsisip-accent-success)' },
            { label: 'Available', value: 4858, color: 'var(--tsisip-border-subtle)' }
        ],
        centerText: '142'
    });
});
</script>
<?php require_once __DIR__ . '/common/footer.php'; ?>
