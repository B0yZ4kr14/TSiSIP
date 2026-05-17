<?php
/**
 * TSiSIP OpenSIPS Control Panel - Dispatcher Module View Stub
 * Demonstrates D3.js chart integration point
 */
require_once __DIR__ . '/common/header.php';
?>
<div id="content">
    <h2><?php echo _('Dispatcher Targets'); ?></h2>
    <div id="tsisip-chart--dispatcher-load" style="max-width:800px;height:320px;margin:1rem 0;"></div>
    <table class="dataTable tsisip-table">
        <thead>
            <tr>
                <th><?php echo _('Set ID'); ?></th>
                <th><?php echo _('Destination'); ?></th>
                <th><?php echo _('State'); ?></th>
                <th><?php echo _('Weight'); ?></th>
                <th class="tsisip-actions-column"><?php echo _('Actions'); ?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1</td>
                <td class="mono-cell">sip:pbx1.internal:5060</td>
                <td><span class="tsisip-badge tsisip-badge-success"><?php echo _('Active'); ?></span></td>
                <td>50</td>
                <td class="tsisip-actions-column">
                    <button type="button" class="tsisip-btn tsisip-btn-secondary tsisip-btn-edit"><?php echo _('Edit'); ?></button>
                    <button type="button" class="tsisip-btn tsisip-btn-secondary tsisip-btn-delete"><?php echo _('Delete'); ?></button>
                </td>
            </tr>
            <tr>
                <td>1</td>
                <td class="mono-cell">sip:pbx2.internal:5060</td>
                <td><span class="tsisip-badge tsisip-badge-success"><?php echo _('Active'); ?></span></td>
                <td>50</td>
                <td class="tsisip-actions-column">
                    <button type="button" class="tsisip-btn tsisip-btn-secondary tsisip-btn-edit"><?php echo _('Edit'); ?></button>
                    <button type="button" class="tsisip-btn tsisip-btn-secondary tsisip-btn-delete"><?php echo _('Delete'); ?></button>
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
        container: '#tsisip-chart--dispatcher-load',
        type: 'bar',
        data: [
            { label: 'PBX1', value: 50, color: 'var(--tsisip-accent-success)' },
            { label: 'PBX2', value: 50, color: 'var(--tsisip-accent-success)' },
            { label: 'PBX3', value: 0, color: 'var(--tsisip-accent-error)' }
        ]
    });
});
</script>
<?php require_once __DIR__ . '/common/footer.php'; ?>
