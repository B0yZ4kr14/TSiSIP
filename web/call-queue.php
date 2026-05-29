<?php
/**
 * TSiSIP Control Panel — Live Call Queue Monitor
 * Real-time view of active SIP transactions and pending requests.
 */
require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';
require_once __DIR__ . '/common/mi-http.php';

requireAuth();
checkPasswordChange();
requireRole('devops');

logAuditEvent('CONFIG_VIEW', 'system', 'call-queue', true);

// --- Fetch transaction count via MI statistics ---
// OpenSIPS 3.6 TM module does not export a 't_list' MI command.
// Use tm:inuse_statistics to get the count of active transactions.
$transactionCount = 0;
$tmResult = miHttpCall('get_statistics', ['tm:inuse_transactions']);
if ($tmResult['success'] && is_array($tmResult['data'])) {
    $transactionCount = (int) ($tmResult['data']['tm:inuse_transactions'] ?? 0);
}

// --- Fetch dialog count for queue depth ---
$dialogCount = 0;
$dlgResult = miHttpCall('get_statistics', ['dialog:active_dialogs']);
if ($dlgResult['success'] && is_array($dlgResult['data'])) {
    $dialogCount = (int) ($dlgResult['data']['dialog:active_dialogs'] ?? 0);
}

require_once __DIR__ . '/common/header.php';
?>
<div id="content" class="tsisip-dashboard">
    <h1><?php echo _('Live Call Queue Monitor'); ?></h1>

    <div class="tsisip-dashboard-section">
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:16px;margin-bottom:24px;">
            <div class="tsisip-metric-card" style="background:var(--tsisip-surface-card);border:1px solid var(--tsisip-border-subtle);border-radius:8px;padding:16px;text-align:center;">
                <div style="font-size:var(--tsisip-text-sm);color:var(--tsisip-text-secondary);margin-bottom:8px;"><?php echo _('Active Dialogs'); ?></div>
                <div style="font-size:1.75rem;font-weight:700;color:var(--tsisip-primary-blue);"><?php echo (int)$dialogCount; ?></div>
            </div>
            <div class="tsisip-metric-card" style="background:var(--tsisip-surface-card);border:1px solid var(--tsisip-border-subtle);border-radius:8px;padding:16px;text-align:center;">
                <div style="font-size:var(--tsisip-text-sm);color:var(--tsisip-text-secondary);margin-bottom:8px;"><?php echo _('Pending Transactions'); ?></div>
                <div style="font-size:1.75rem;font-weight:700;color:var(--tsisip-primary-blue);"><?php echo (int)$transactionCount; ?></div>
            </div>
        </div>
    </div>

    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo _('Active Transactions'); ?></h2>
        <div class="tsisip-badge tsisip-badge--info">
            <?php echo _('Transaction count is shown above. OpenSIPS 3.6 does not expose a per-transaction listing MI command.'); ?>
        </div>
    </section>
</div>

<script>
(function() {
    'use strict';
    async function refreshData() {
        try {
            const resp = await fetch(window.location.href + '?ajax=1');
            if (!resp.ok) throw new Error('HTTP ' + resp.status);
            const data = await resp.json();
            if (data.error) throw new Error(data.error);
            // Update timestamp if present
            const tsEl = document.getElementById('last-updated');
            if (tsEl) {
                tsEl.textContent = '<?php echo _('Updated: '); ?>' + new Date().toLocaleTimeString();
            }
        } catch (e) {
            console.error('Auto-refresh failed:', e);
        }
    }
    // Auto-refresh every 15 seconds
    setInterval(refreshData, 15000);
})();
</script>

<?php require_once __DIR__ . '/common/footer.php'; ?>
