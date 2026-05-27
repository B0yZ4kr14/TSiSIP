<?php
/**
 * TSiSIP Control Panel — RTPengine Status
 * Real-time RTPengine session and port utilization.
 */
require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';
require_once __DIR__ . '/common/mi-http.php';

requireAuth();
checkPasswordChange();
requireRole('devops');

logAuditEvent('CONFIG_VIEW', 'system', 'rtpengine-status', true);

// --- Fetch RTPengine info via MI ---
$sessions = [];
$ports = [];
$miResult = miHttpCall('rtpengine_show');
if ($miResult['success'] && is_array($miResult['data'])) {
    $raw = $miResult['data'];
    if (isset($raw['Sessions']) && is_array($raw['Sessions'])) {
        $sessions = $raw['Sessions'];
    }
    if (isset($raw['Ports']) && is_array($raw['Ports'])) {
        $ports = $raw['Ports'];
    }
}

// Fallback: try alternative MI command names
if (empty($sessions)) {
    $altResult = miHttpCall('rtpengine_list');
    if ($altResult['success'] && is_array($altResult['data'])) {
        $raw = $altResult['data'];
        if (isset($raw['Sessions']) && is_array($raw['Sessions'])) {
            $sessions = $raw['Sessions'];
        }
    }
}

require_once __DIR__ . '/common/header.php';
?>
<div id="content" class="tsisip-dashboard">
    <h1><?php echo _('RTPengine Status'); ?></h1>

    <div class="tsisip-dashboard-section">
        <p class="tsisip-text-muted">
            <?php echo _('Real-time RTPengine media relay status and port utilization.'); ?>
        </p>
    </div>

    <!-- Port Utilization -->
    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo _('Port Utilization'); ?></h2>
        <?php if (empty($ports)): ?>
            <div class="tsisip-badge tsisip-badge--info">
                <?php echo _('No port data or MI unreachable.'); ?>
            </div>
        <?php else: ?>
            <table class="tsisip-table dataTable">
                <thead>
                    <tr>
                        <th><?php echo _('Interface'); ?></th>
                        <th><?php echo _('Port Range'); ?></th>
                        <th><?php echo _('Used'); ?></th>
                        <th><?php echo _('Total'); ?></th>
                        <th><?php echo _('Utilization'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ports as $port): ?>
                        <?php if (!is_array($port)) continue; ?>
                        <tr>
                            <td><?php echo htmlspecialchars($port['interface'] ?? $port['INTERFACE'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars((string) ($port['range'] ?? $port['RANGE'] ?? 'N/A')); ?></td>
                            <td><?php echo htmlspecialchars((string) ($port['used'] ?? $port['USED'] ?? '0')); ?></td>
                            <td><?php echo htmlspecialchars((string) ($port['total'] ?? $port['TOTAL'] ?? '0')); ?></td>
                            <td>
                                <?php
                                $used = (int) ($port['used'] ?? $port['USED'] ?? 0);
                                $total = (int) ($port['total'] ?? $port['TOTAL'] ?? 1);
                                $pct = $total > 0 ? round(($used / $total) * 100, 1) : 0;
                                $badgeClass = 'tsisip-badge';
                                if ($pct < 50) $badgeClass = 'tsisip-badge tsisip-badge-success';
                                elseif ($pct < 80) $badgeClass = 'tsisip-badge tsisip-badge-warning';
                                else $badgeClass = 'tsisip-badge tsisip-badge-error';
                                ?>
                                <span class="<?php echo htmlspecialchars($badgeClass, ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo $pct; ?>%
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>

    <!-- Active Sessions -->
    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo _('Active Sessions'); ?></h2>
        <?php if (empty($sessions)): ?>
            <div class="tsisip-badge tsisip-badge--info">
                <?php echo _('No active sessions or MI unreachable.'); ?>
            </div>
        <?php else: ?>
            <table class="tsisip-table dataTable">
                <thead>
                    <tr>
                        <th><?php echo _('Call-ID'); ?></th>
                        <th><?php echo _('From Tag'); ?></th>
                        <th><?php echo _('To Tag'); ?></th>
                        <th><?php echo _('Media'); ?></th>
                        <th><?php echo _('Status'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sessions as $sess): ?>
                        <?php if (!is_array($sess)) continue; ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($sess['callid'] ?? $sess['call_id'] ?? $sess['CALLID'] ?? 'N/A'); ?></code></td>
                            <td><?php echo htmlspecialchars($sess['from_tag'] ?? $sess['fromTag'] ?? $sess['FROM_TAG'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($sess['to_tag'] ?? $sess['toTag'] ?? $sess['TO_TAG'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars((string) ($sess['media'] ?? $sess['MEDIA'] ?? '—')); ?></td>
                            <td>
                                <?php
                                $status = $sess['status'] ?? $sess['STATUS'] ?? 'unknown';
                                $badgeClass = 'tsisip-badge';
                                if (is_string($status)) {
                                    $lower = strtolower($status);
                                    if (in_array($lower, ['active', 'established', 'streaming'])) {
                                        $badgeClass = 'tsisip-badge tsisip-badge-success';
                                    } elseif (in_array($lower, ['paused', 'held'])) {
                                        $badgeClass = 'tsisip-badge tsisip-badge-warning';
                                    } elseif (in_array($lower, ['terminated', 'closed', 'error'])) {
                                        $badgeClass = 'tsisip-badge tsisip-badge-error';
                                    }
                                }
                                ?>
                                <span class="<?php echo htmlspecialchars($badgeClass, ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars((string)$status); ?>
                                </span>
                            </td>
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
