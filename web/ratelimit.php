<?php
/**
 * TSiSIP Control Panel — Rate Limit Status
 * Pipe status viewer with per-pipe reset action.
 */
require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';
require_once __DIR__ . '/common/mi-http.php';

requireAuth();
checkPasswordChange();
requireRole('devops');

$pageTitle = _('Rate Limit Status');

$miData = ['success' => false, 'error' => null, 'data' => null];
$pipes = [];

try {
    $result = miHttpCall('ratelimit_status', []);
    $miData = $result;
    if ($result['success'] && is_array($result['data'])) {
        $raw = $result['data'];
        if (isset($raw['Pipes']) && is_array($raw['Pipes'])) {
            $pipes = $raw['Pipes'];
        } elseif (isset($raw['pipes']) && is_array($raw['pipes'])) {
            $pipes = $raw['pipes'];
        } elseif (isset($raw[0]) && is_array($raw[0])) {
            $pipes = $raw;
        } else {
            foreach ($raw as $key => $val) {
                if (is_array($val)) {
                    $pipes[] = $val;
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
        <div class="tsisip-actions">
            <button type="button" class="tsisip-btn tsisip-btn-secondary" onclick="TSiSIPMi.exportData('ratelimit_status', [], 'csv')"><?php echo _('Export CSV'); ?></button>
            <button type="button" class="tsisip-btn tsisip-btn-secondary" onclick="TSiSIPMi.exportData('ratelimit_status', [], 'json')"><?php echo _('Export JSON'); ?></button>
        </div>
    </div>

    <?php if (!$miData["success"]): ?>
        <?php echo miErrorBanner($miData["error"] ?? _("Unknown")); ?>
    <?php else: ?>
        <section class="tsisip-section">
            <h2 class="tsisip-section-title"><?php echo _('Pipe Status'); ?></h2>
            <div style="overflow-x:auto;">
                <table class="tsisip-table dataTable" data-tsisip-sortable id="ratelimit-table">
                    <thead>
                        <tr>
                            <th><?php echo _('Pipe Name'); ?></th>
                            <th><?php echo _('Limit'); ?></th>
                            <th><?php echo _('Current'); ?></th>
                            <th><?php echo _('Status'); ?></th>
                            <th><?php echo _('Utilization'); ?></th>
                            <?php if (isDevOpsOrHigher()): ?>
                                <th><?php echo _('Actions'); ?></th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pipes)): ?>
                            <tr>
                                <td colspan="<?php echo isDevOpsOrHigher() ? 6 : 5; ?>" class="tsisip-empty"><?php echo _('No rate limit pipes configured.'); ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pipes as $pipe): ?>
                                <?php if (!is_array($pipe)) continue; ?>
                                <?php
                                $name    = htmlspecialchars($pipe['name'] ?? $pipe['pipe'] ?? $pipe['PIPE'] ?? 'N/A');
                                $limit   = (int) ($pipe['limit'] ?? $pipe['LIMIT'] ?? 0);
                                $current = (int) ($pipe['current'] ?? $pipe['CURRENT'] ?? 0);
                                $status  = strtolower((string) ($pipe['status'] ?? $pipe['STATE'] ?? 'active'));
                                $pct     = $limit > 0 ? round(($current / $limit) * 100, 1) : 0;
                                $badgeClass = 'tsisip-badge tsisip-badge-success';
                                if (stripos($status, 'block') !== false || stripos($status, 'drop') !== false) {
                                    $badgeClass = 'tsisip-badge tsisip-badge-error';
                                } elseif (stripos($status, 'warn') !== false || $pct > 80) {
                                    $badgeClass = 'tsisip-badge tsisip-badge-warning';
                                }
                                ?>
                                <tr data-pipe="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>">
                                    <td><code><?php echo $name; ?></code></td>
                                    <td><?php echo number_format($limit); ?></td>
                                    <td><?php echo number_format($current); ?></td>
                                    <td>
                                        <span class="<?php echo htmlspecialchars($badgeClass, ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars(ucfirst($status)); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="background:var(--tsisip-border-subtle);border-radius:4px;height:16px;overflow:hidden;min-width:100px;">
                                            <div style="width:<?php echo min($pct, 100); ?>%;background:<?php echo $pct > 80 ? 'var(--tsisip-danger)' : ($pct > 50 ? 'var(--tsisip-warning)' : 'var(--tsisip-success)'); ?>;height:100%;"></div>
                                        </div>
                                        <small style="color:var(--tsisip-text-secondary);"><?php echo $pct; ?>%</small>
                                    </td>
                                    <?php if (isDevOpsOrHigher()): ?>
                                        <td>
                                            <button type="button" class="tsisip-btn tsisip-btn--sm tsisip-btn--secondary btn-reset-pipe"
                                                    data-pipe="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>">
                                                <?php echo _('Reset'); ?>
                                            </button>
                                        </td>
                                    <?php endif; ?>
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
<?php if (isDevOpsOrHigher()): ?>
(function() {
    'use strict';
    document.getElementById('ratelimit-table').addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-reset-pipe');
        if (!btn) return;
        e.preventDefault();
        const pipeName = btn.dataset.pipe;
        if (!pipeName) return;
        if (!confirm(<?php echo json_encode(_('Reset this pipe?')); ?>)) return;
        btn.disabled = true;
        TSiSIPMi.action('ratelimit_reset', [pipeName], function() {
            btn.disabled = false;
            setTimeout(function() { location.reload(); }, 600);
        }, function() {
            btn.disabled = false;
        });
    });
})();
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/common/footer.php'; ?>
