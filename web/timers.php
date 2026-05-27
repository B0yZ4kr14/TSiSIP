<?php
/**
 * TSiSIP Control Panel — Timers
 * OpenSIPS timer schedule viewer.
 */
require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';
require_once __DIR__ . '/common/mi-http.php';

requireAuth();
checkPasswordChange();
requireRole('devops');

$pageTitle = _('Timers');

$timers = [];
$miData = ['success' => false, 'error' => null, 'data' => null];

try {
    $result = miHttpCall('list_timers', []);
    $miData = $result;
    if ($result['success'] && is_array($result['data'])) {
        $raw = $result['data'];
        if (isset($raw['Timers']) && is_array($raw['Timers'])) {
            $timers = $raw['Timers'];
        } elseif (isset($raw['timers']) && is_array($raw['timers'])) {
            $timers = $raw['timers'];
        } elseif (isset($raw[0]) && is_array($raw[0])) {
            $timers = $raw;
        } else {
            foreach ($raw as $key => $val) {
                if (is_array($val)) {
                    $timers[] = $val;
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

    <?php if (!$miData["success"]): ?>
        <?php echo miErrorBanner($miData["error"] ?? _("Unknown")); ?>
    <?php else: ?>
        <section class="tsisip-section">
            <h2 class="tsisip-section-title"><?php echo _('Timer Schedule'); ?></h2>
            <div style="overflow-x:auto;">
                <table class="tsisip-table dataTable" data-tsisip-sortable>
                    <thead>
                        <tr>
                            <th><?php echo _('Name'); ?></th>
                            <th><?php echo _('Interval (ms)'); ?></th>
                            <th><?php echo _('Last Run'); ?></th>
                            <th><?php echo _('Next Run'); ?></th>
                            <th><?php echo _('Status'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($timers)): ?>
                            <tr>
                                <td colspan="5" class="tsisip-empty"><?php echo _('No timers configured.'); ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($timers as $t): ?>
                                <?php if (!is_array($t)) continue; ?>
                                <?php
                                $name     = htmlspecialchars($t['name'] ?? $t['timer'] ?? $t['NAME'] ?? 'N/A');
                                $interval = (int) ($t['interval'] ?? $t['INTERVAL'] ?? 0);
                                $lastRun  = htmlspecialchars($t['last_run'] ?? $t['last'] ?? $t['LAST_RUN'] ?? '—');
                                $nextRun  = htmlspecialchars($t['next_run'] ?? $t['next'] ?? $t['NEXT_RUN'] ?? '—');
                                $status   = strtolower((string) ($t['status'] ?? $t['STATE'] ?? 'active'));

                                $badgeClass = 'tsisip-badge tsisip-badge-success';
                                if (stripos($status, 'pause') !== false || stripos($status, 'stop') !== false) {
                                    $badgeClass = 'tsisip-badge tsisip-badge-error';
                                } elseif (stripos($status, 'wait') !== false || stripos($status, 'idle') !== false) {
                                    $badgeClass = 'tsisip-badge tsisip-badge-warning';
                                }
                                ?>
                                <tr>
                                    <td><code><?php echo $name; ?></code></td>
                                    <td><?php echo number_format($interval); ?></td>
                                    <td><?php echo $lastRun; ?></td>
                                    <td><?php echo $nextRun; ?></td>
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
