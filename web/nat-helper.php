<?php
/**
 * TSiSIP Control Panel — NAT Helper
 * NAT helper socket and ping statistics.
 */
require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';
require_once __DIR__ . '/common/mi-http.php';

requireAuth();
checkPasswordChange();

$pageTitle = _('NAT Helper');

$socketData = ['success' => false, 'error' => null, 'data' => null];
$pingData   = ['success' => false, 'error' => null, 'data' => null];
$sockets = [];
$pings   = [];

try {
    $sResult = miHttpCall('nh_show_sockets', []);
    $socketData = $sResult;
    if ($sResult['success'] && is_array($sResult['data'])) {
        $raw = $sResult['data'];
        if (isset($raw['Sockets']) && is_array($raw['Sockets'])) {
            $sockets = $raw['Sockets'];
        } elseif (isset($raw[0]) && is_array($raw[0])) {
            $sockets = $raw;
        } else {
            foreach ($raw as $key => $val) {
                if (is_array($val)) {
                    $sockets[] = $val;
                }
            }
        }
    }
} catch (Exception $e) {
    $socketData['error'] = $e->getMessage();
}

try {
    $pResult = miHttpCall('nh_show_ping', []);
    $pingData = $pResult;
    if ($pResult['success'] && is_array($pResult['data'])) {
        $raw = $pResult['data'];
        if (isset($raw['Pings']) && is_array($raw['Pings'])) {
            $pings = $raw['Pings'];
        } elseif (isset($raw[0]) && is_array($raw[0])) {
            $pings = $raw;
        } else {
            foreach ($raw as $key => $val) {
                if (is_array($val)) {
                    $pings[] = $val;
                }
            }
        }
    }
} catch (Exception $e) {
    $pingData['error'] = $e->getMessage();
}

require_once __DIR__ . '/common/header.php';
?>
<div class="tsisip-page">
    <div class="tsisip-page-header">
        <h1 class="tsisip-page-title"><?php echo $pageTitle; ?></h1>
        <div class="tsisip-actions">
            <button type="button" class="tsisip-btn tsisip-btn-secondary" onclick="TSiSIPMi.exportData('nh_show_sockets', [], 'csv')"><?php echo _('Export CSV'); ?></button>
            <button type="button" class="tsisip-btn tsisip-btn-secondary" onclick="TSiSIPMi.exportData('nh_show_sockets', [], 'json')"><?php echo _('Export JSON'); ?></button>
        </div>
    </div>

    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo _('NAT Helper Sockets'); ?></h2>
        <?php if (!$socketData['success']): ?>
            <?php echo miErrorBanner($socketData['error'] ?? _('Unknown')); ?>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="tsisip-table dataTable" data-tsisip-sortable>
                    <thead>
                        <tr>
                            <th><?php echo _('Protocol'); ?></th>
                            <th><?php echo _('Address'); ?></th>
                            <th><?php echo _('Port'); ?></th>
                            <th><?php echo _('Options'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($sockets)): ?>
                            <tr>
                                <td colspan="4" class="tsisip-empty"><?php echo _('No socket data.'); ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($sockets as $s): ?>
                                <?php if (!is_array($s)) continue; ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($s['proto'] ?? $s['PROTO'] ?? '—'); ?></td>
                                    <td><code><?php echo htmlspecialchars($s['address'] ?? $s['ADDRESS'] ?? '—'); ?></code></td>
                                    <td><?php echo htmlspecialchars((string) ($s['port'] ?? $s['PORT'] ?? '—')); ?></td>
                                    <td><code><?php echo htmlspecialchars($s['options'] ?? $s['OPTIONS'] ?? '—'); ?></code></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo _('Ping Statistics'); ?></h2>
        <?php if (!$pingData['success']): ?>
            <?php echo miErrorBanner($pingData['error'] ?? _('Unknown')); ?>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="tsisip-table dataTable" data-tsisip-sortable>
                    <thead>
                        <tr>
                            <th><?php echo _('Target'); ?></th>
                            <th><?php echo _('Sent'); ?></th>
                            <th><?php echo _('Received'); ?></th>
                            <th><?php echo _('Lost'); ?></th>
                            <th><?php echo _('Status'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pings)): ?>
                            <tr>
                                <td colspan="5" class="tsisip-empty"><?php echo _('No ping data.'); ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pings as $p): ?>
                                <?php if (!is_array($p)) continue; ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($p['target'] ?? $p['ip'] ?? $p['TARGET'] ?? 'N/A'); ?></code></td>
                                    <td><?php echo htmlspecialchars((string) ($p['sent'] ?? $p['SENT'] ?? '0')); ?></td>
                                    <td><?php echo htmlspecialchars((string) ($p['received'] ?? $p['RECEIVED'] ?? '0')); ?></td>
                                    <td><?php echo htmlspecialchars((string) ($p['lost'] ?? $p['LOST'] ?? '0')); ?></td>
                                    <td>
                                        <?php
                                        $status = strtolower((string) ($p['status'] ?? $p['STATE'] ?? 'unknown'));
                                        $badgeClass = 'tsisip-badge';
                                        if (stripos($status, 'ok') !== false || stripos($status, 'up') !== false) {
                                            $badgeClass = 'tsisip-badge tsisip-badge-success';
                                        } elseif (stripos($status, 'fail') !== false || stripos($status, 'down') !== false) {
                                            $badgeClass = 'tsisip-badge tsisip-badge-error';
                                        }
                                        ?>
                                        <span class="<?php echo htmlspecialchars($badgeClass, ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars(ucfirst($status)); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php require_once __DIR__ . '/common/footer.php'; ?>
