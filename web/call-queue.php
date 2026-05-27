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

// --- Fetch transaction list via MI ---
$transactions = [];
$miResult = miHttpCall('t_list');
if ($miResult['success'] && is_array($miResult['data'])) {
    $raw = $miResult['data'];
    if (isset($raw['Transactions']) && is_array($raw['Transactions'])) {
        $transactions = $raw['Transactions'];
    } elseif (isset($raw[0]) && is_array($raw[0])) {
        $transactions = $raw;
    }
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
                <div style="font-size:1.75rem;font-weight:700;color:var(--tsisip-primary-blue);"><?php echo count($transactions); ?></div>
            </div>
        </div>
    </div>

    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo _('Active Transactions'); ?></h2>
        <?php if (empty($transactions)): ?>
            <div class="tsisip-badge tsisip-badge--info">
                <?php echo _('No active transactions or MI unreachable.'); ?>
            </div>
        <?php else: ?>
            <table class="tsisip-table dataTable">
                <thead>
                    <tr>
                        <th><?php echo _('Method'); ?></th>
                        <th><?php echo _('From'); ?></th>
                        <th><?php echo _('To'); ?></th>
                        <th><?php echo _('Call-ID'); ?></th>
                        <th><?php echo _('State'); ?></th>
                        <th><?php echo _('Time'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $txn): ?>
                        <?php if (!is_array($txn)) continue; ?>
                        <tr>
                            <td><?php echo htmlspecialchars($txn['method'] ?? $txn['METHOD'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($txn['from'] ?? $txn['from_uri'] ?? $txn['FROM'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($txn['to'] ?? $txn['to_uri'] ?? $txn['TO'] ?? 'N/A'); ?></td>
                            <td><code><?php echo htmlspecialchars($txn['callid'] ?? $txn['call_id'] ?? $txn['CALLID'] ?? 'N/A'); ?></code></td>
                            <td>
                                <?php
                                $state = $txn['state'] ?? $txn['STATUS'] ?? 'unknown';
                                $badgeClass = 'tsisip-badge';
                                if (is_numeric($state)) {
                                    $s = (int)$state;
                                    if ($s >= 200 && $s < 300) $badgeClass = 'tsisip-badge tsisip-badge-success';
                                    elseif ($s >= 100 && $s < 200) $badgeClass = 'tsisip-badge tsisip-badge-warning';
                                    elseif ($s >= 300) $badgeClass = 'tsisip-badge tsisip-badge-error';
                                } else {
                                    $stateStr = strtolower((string)$state);
                                    if (in_array($stateStr, ['confirmed', 'established', 'completed'])) {
                                        $badgeClass = 'tsisip-badge tsisip-badge-success';
                                    } elseif (in_array($stateStr, ['trying', 'proceeding', 'ringing'])) {
                                        $badgeClass = 'tsisip-badge tsisip-badge-warning';
                                    } elseif (in_array($stateStr, ['terminated', 'failed', 'deleted'])) {
                                        $badgeClass = 'tsisip-badge tsisip-badge-error';
                                    }
                                }
                                ?>
                                <span class="<?php echo htmlspecialchars($badgeClass, ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars((string)$state); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars((string) ($txn['time'] ?? $txn['timestamp'] ?? $txn['TIME'] ?? '—')); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
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
