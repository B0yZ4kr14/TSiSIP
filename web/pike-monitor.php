<?php
/**
 * TSiSIP Control Panel — Pike Monitor
 * DDoS protection blocked IP viewer with search and threat levels.
 */
require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';
require_once __DIR__ . '/common/mi-http.php';

requireAuth();
checkPasswordChange();
requireRole('devops');

$pageTitle = _('Pike Monitor');

$miData = ['success' => false, 'error' => null, 'data' => null];
$blockedIps = [];

try {
    $result = miHttpCall('pike_list', []);
    $miData = $result;
    if ($result['success'] && is_array($result['data'])) {
        $raw = $result['data'];
        if (isset($raw['IPs']) && is_array($raw['IPs'])) {
            $blockedIps = $raw['IPs'];
        } elseif (isset($raw[0]) && is_array($raw[0])) {
            $blockedIps = $raw;
        } else {
            foreach ($raw as $key => $val) {
                if (is_array($val)) {
                    $blockedIps[] = $val;
                }
            }
        }
    }
} catch (Exception $e) {
    $miData['error'] = $e->getMessage();
}

require_once __DIR__ . '/common/header.php';
?>
<div class="tsisip-page">
    <div class="tsisip-page-header">
        <h1 class="tsisip-page-title"><?php echo $pageTitle; ?></h1>
    </div>

    <?php if (!$miData['success']): ?>
        <div class="tsisip-alert tsisip-alert--warning" role="alert">
            <?php echo _('MI Error:'); ?> <?php echo htmlspecialchars($miData['error'] ?? 'Unknown'); ?>
        </div>
    <?php else: ?>
        <section class="tsisip-section">
            <div class="tsisip-filter-bar">
                <input type="text" id="pike-search-ip" class="tsisip-input" placeholder="<?php echo _('Enter IP to check...'); ?>">
                <button type="button" id="btn-pike-check" class="tsisip-btn tsisip-btn--primary"><?php echo _('Check IP'); ?></button>
            </div>
            <div id="pike-check-result" style="margin-top:12px;"></div>
        </section>

        <section class="tsisip-section">
            <h2 class="tsisip-section-title"><?php echo _('Blocked IPs'); ?></h2>
            <div style="overflow-x:auto;">
                <table class="tsisip-table dataTable" data-tsisip-sortable>
                    <thead>
                        <tr>
                            <th><?php echo _('IP Address'); ?></th>
                            <th><?php echo _('Timestamp'); ?></th>
                            <th><?php echo _('Hits'); ?></th>
                            <th><?php echo _('Status'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($blockedIps)): ?>
                            <tr>
                                <td colspan="4" class="tsisip-empty"><?php echo _('No blocked IPs.'); ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($blockedIps as $ip): ?>
                                <?php if (!is_array($ip)) continue; ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($ip['ip'] ?? $ip['IP'] ?? 'N/A'); ?></code></td>
                                    <td><?php echo htmlspecialchars($ip['timestamp'] ?? $ip['expires'] ?? $ip['TIME'] ?? '—'); ?></td>
                                    <td><?php echo htmlspecialchars((string) ($ip['hits'] ?? $ip['HITS'] ?? '—')); ?></td>
                                    <td>
                                        <?php
                                        $status = strtolower((string) ($ip['status'] ?? $ip['STATE'] ?? 'blocked'));
                                        if (stripos($status, 'block') !== false) {
                                            $badgeClass = 'tsisip-badge tsisip-badge-error';
                                            $label = _('Blocked');
                                        } elseif (stripos($status, 'warn') !== false) {
                                            $badgeClass = 'tsisip-badge tsisip-badge-warning';
                                            $label = _('Warned');
                                        } else {
                                            $badgeClass = 'tsisip-badge tsisip-badge-error';
                                            $label = _('Blocked');
                                        }
                                        ?>
                                        <span class="<?php echo htmlspecialchars($badgeClass, ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo $label; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>
</div>

<script>
(function() {
    'use strict';
    document.getElementById('btn-pike-check').addEventListener('click', function() {
        const ip = document.getElementById('pike-search-ip').value.trim();
        const resultDiv = document.getElementById('pike-check-result');
        if (!ip) {
            resultDiv.innerHTML = '<span class="tsisip-badge tsisip-badge-warning">' + <?php echo json_encode(_('Please enter an IP address.')); ?> + '</span>';
            return;
        }
        resultDiv.innerHTML = '<span class="tsisip-badge tsisip-badge-info">' + <?php echo json_encode(_('Checking...')); ?> + '</span>';
        TSiSIPMi.action('pike_check_ip', [ip], function(data) {
            const status = (data && data.status) ? data.status : (typeof data === 'string' ? data : 'safe');
            const isBlocked = String(status).toLowerCase().indexOf('block') !== -1;
            const isWarned = String(status).toLowerCase().indexOf('warn') !== -1;
            let cls = 'tsisip-badge tsisip-badge-success';
            let label = <?php echo json_encode(_('Safe')); ?>;
            if (isBlocked) {
                cls = 'tsisip-badge tsisip-badge-error';
                label = <?php echo json_encode(_('Blocked')); ?>;
            } else if (isWarned) {
                cls = 'tsisip-badge tsisip-badge-warning';
                label = <?php echo json_encode(_('Warned')); ?>;
            }
            resultDiv.innerHTML = '<span class="' + cls + '">' + label + '</span> <code>' + ip + '</code>';
        }, function(err) {
            resultDiv.innerHTML = '<span class="tsisip-badge tsisip-badge-error">' + <?php echo json_encode(_('Error:')); ?> + ' ' + (err || 'Unknown') + '</span>';
        });
    });
})();
</script>

<?php require_once __DIR__ . '/common/footer.php'; ?>
