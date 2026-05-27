<?php
/**
 * TSiSIP Control Panel — Gateway Health Status
 * Real-time gateway health via OpenSIPS MI HTTP interface.
 */
require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';
require_once __DIR__ . '/common/mi-http.php';

requireAuth();
checkPasswordChange();
requireRole('devops');

logAuditEvent('CONFIG_VIEW', 'system', 'gateway-health', true);

$gateways = [];
$miResult = miHttpCall('uac_reg_list');
if ($miResult['success'] && is_array($miResult['data'])) {
    $raw = $miResult['data'];
    if (isset($raw['Gateways']) && is_array($raw['Gateways'])) {
        $gateways = $raw['Gateways'];
    } elseif (isset($raw[0]) && is_array($raw[0])) {
        $gateways = $raw;
    }
}

$dispatcherHealth = [];
$dsResult = miHttpCall('ds_list');
if ($dsResult['success'] && is_array($dsResult['data'])) {
    $rawDs = $dsResult['data'];
    foreach ($rawDs as $key => $setEntries) {
        if (is_array($setEntries)) {
            foreach ($setEntries as $entry) {
                if (is_array($entry)) {
                    $dispatcherHealth[] = $entry;
                }
            }
        }
    }
}

require_once __DIR__ . '/common/header.php';
?>
<div id="content" class="tsisip-dashboard">
    <h1><?php echo _('Gateway Health Status'); ?></h1>

    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo _('UAC Registrations'); ?></h2>
        <?php if (empty($gateways)): ?>
            <div class="tsisip-badge tsisip-badge--info">
                <?php echo _('No gateway registrations configured or MI unreachable.'); ?>
            </div>
        <?php else: ?>
            <table class="tsisip-table dataTable">
                <thead>
                    <tr>
                        <th><?php echo _('Gateway'); ?></th>
                        <th><?php echo _('Registrar'); ?></th>
                        <th><?php echo _('AOR'); ?></th>
                        <th><?php echo _('Status'); ?></th>
                        <th><?php echo _('Expires'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($gateways as $gw): ?>
                        <?php if (!is_array($gw)) continue; ?>
                        <tr>
                            <td><?php echo htmlspecialchars($gw['l_id'] ?? $gw['id'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($gw['registrar'] ?? $gw['REGISTRAR'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($gw['aor'] ?? $gw['AOR'] ?? 'N/A'); ?></td>
                            <td>
                                <?php
                                $state = $gw['state'] ?? $gw['STATE'] ?? 'unknown';
                                $isRegistered = is_string($state)
                                    ? (stripos($state, 'registered') !== false || stripos($state, 'ok') !== false)
                                    : (bool)$state;
                                ?>
                                <span class="tsisip-badge tsisip-badge--<?php echo $isRegistered ? 'success' : 'danger'; ?>">
                                    <?php echo $isRegistered ? _('Registered') : _('Unregistered'); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars((string) ($gw['expires'] ?? $gw['EXPIRES'] ?? '—')); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>

    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo _('Dispatcher Targets'); ?></h2>
        <?php if (empty($dispatcherHealth)): ?>
            <div class="tsisip-badge tsisip-badge--info">
                <?php echo _('No dispatcher targets or MI unreachable.'); ?>
            </div>
        <?php else: ?>
            <table class="tsisip-table dataTable">
                <thead>
                    <tr>
                        <th><?php echo _('Set'); ?></th>
                        <th><?php echo _('Destination'); ?></th>
                        <th><?php echo _('State'); ?></th>
                        <th><?php echo _('Weight'); ?></th>
                        <th><?php echo _('Probing'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dispatcherHealth as $ds): ?>
                        <?php if (!is_array($ds)) continue; ?>
                        <tr>
                            <td><?php echo htmlspecialchars((string) ($ds['setid'] ?? $ds['SET'] ?? 'N/A')); ?></td>
                            <td><code><?php echo htmlspecialchars($ds['destination'] ?? $ds['URI'] ?? $ds['TARGET'] ?? 'N/A'); ?></code></td>
                            <td>
                                <?php
                                $flags = $ds['state'] ?? $ds['FLAGS'] ?? '';
                                $isActive = is_string($flags)
                                    ? (stripos($flags, 'A') !== false || stripos($flags, 'P') !== false)
                                    : (bool)$flags;
                                ?>
                                <span class="tsisip-badge tsisip-badge--<?php echo $isActive ? 'success' : 'danger'; ?>">
                                    <?php echo $isActive ? _('Active') : _('Inactive'); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars((string) ($ds['weight'] ?? $ds['WEIGHT'] ?? '—')); ?></td>
                            <td>
                                <?php
                                $probing = $ds['probing'] ?? $ds['PROBING'] ?? '';
                                $isProbing = is_string($probing)
                                    ? (stripos($probing, 'Yes') !== false || stripos($probing, 'On') !== false)
                                    : (bool)$probing;
                                ?>
                                <span class="tsisip-badge tsisip-badge--<?php echo $isProbing ? 'warning' : 'neutral'; ?>">
                                    <?php echo $isProbing ? _('Yes') : _('No'); ?>
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
