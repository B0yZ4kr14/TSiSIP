<?php
/**
 * TSiSIP Control Panel — OpenSIPS Processes
 * Process list with memory and status indicators.
 */
require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';
require_once __DIR__ . '/common/mi-http.php';

requireAuth();
checkPasswordChange();

$pageTitle = _('OpenSIPS Processes');

$processes = [];
$miData = ['success' => false, 'error' => null, 'data' => null];

try {
    $result = miHttpCall('ps', []);
    $miData = $result;
    if ($result['success'] && is_array($result['data'])) {
        $raw = $result['data'];
        if (isset($raw['Processes']) && is_array($raw['Processes'])) {
            $processes = $raw['Processes'];
        } elseif (isset($raw['processes']) && is_array($raw['processes'])) {
            $processes = $raw['processes'];
        } elseif (isset($raw[0]) && is_array($raw[0])) {
            $processes = $raw;
        } else {
            foreach ($raw as $key => $val) {
                if (is_array($val)) {
                    $processes[] = $val;
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
            <h2 class="tsisip-section-title"><?php echo _('Process List'); ?></h2>
            <div style="overflow-x:auto;">
                <table class="tsisip-table dataTable" data-tsisip-sortable>
                    <thead>
                        <tr>
                            <th><?php echo _('PID'); ?></th>
                            <th><?php echo _('Type'); ?></th>
                            <th><?php echo _('Description'); ?></th>
                            <th><?php echo _('Memory'); ?></th>
                            <th><?php echo _('Status'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($processes)): ?>
                            <tr>
                                <td colspan="5" class="tsisip-empty"><?php echo _('No process data.'); ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($processes as $p): ?>
                                <?php if (!is_array($p)) continue; ?>
                                <?php
                                $pid = (int) ($p['pid'] ?? $p['PID'] ?? 0);
                                $type = htmlspecialchars($p['type'] ?? $p['TYPE'] ?? '—');
                                $desc = htmlspecialchars($p['description'] ?? $p['desc'] ?? $p['DESCRIPTION'] ?? '—');
                                $mem = (int) ($p['memory'] ?? $p['mem'] ?? $p['MEMORY'] ?? 0);
                                $status = strtolower((string) ($p['status'] ?? $p['STATE'] ?? 'running'));

                                $badgeClass = 'tsisip-badge tsisip-badge-success';
                                if (stripos($status, 'idle') !== false) {
                                    $badgeClass = 'tsisip-badge tsisip-badge-info';
                                } elseif (stripos($status, 'busy') !== false || stripos($status, 'working') !== false) {
                                    $badgeClass = 'tsisip-badge tsisip-badge-warning';
                                } elseif (stripos($status, 'error') !== false || stripos($status, 'dead') !== false) {
                                    $badgeClass = 'tsisip-badge tsisip-badge-error';
                                }
                                ?>
                                <tr>
                                    <td><code><?php echo $pid > 0 ? $pid : '—'; ?></code></td>
                                    <td><?php echo $type; ?></td>
                                    <td><?php echo $desc; ?></td>
                                    <td><?php echo $mem > 0 ? number_format($mem) . ' KB' : '—'; ?></td>
                                    <td>
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
        </section>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/common/footer.php'; ?>
