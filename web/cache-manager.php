<?php
/**
 * TSiSIP Control Panel — Cache Manager
 */
require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';
require_once __DIR__ . '/common/page-cache.php';

requireAuth();
checkPasswordChange();
requireRole('admin');

$cache = new PageCache();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear'])) {
    validateCsrfToken();
    $cache->invalidate();
    setFlash('success', _('Cache cleared successfully'));
    logAuditEvent('CACHE_CLEAR', 'system', 'all', true);
    header('Location: cache-manager.php');
    exit;
}

$stats = $cache->stats();

logAuditEvent('CONFIG_VIEW', 'system', 'cache-manager', true);

require_once __DIR__ . '/common/header.php';
?>
<div id="content" class="tsisip-dashboard">
    <h1><?php echo _('Cache Manager'); ?></h1>

    <div class="tsisip-dashboard-grid" style="grid-template-columns:repeat(3, 1fr);">
        <div class="tsisip-dashboard-card">
            <div class="tsisip-metric-value"><?php echo (int)$stats['entries']; ?></div>
            <div class="tsisip-metric-label"><?php echo _('Cached Pages'); ?></div>
        </div>
        <div class="tsisip-dashboard-card">
            <div class="tsisip-metric-value"><?php echo $stats['size_kb']; ?> KB</div>
            <div class="tsisip-metric-label"><?php echo _('Cache Size'); ?></div>
        </div>
        <div class="tsisip-dashboard-card">
            <div class="tsisip-metric-value">60s</div>
            <div class="tsisip-metric-label"><?php echo _('Default TTL'); ?></div>
        </div>
    </div>

    <div class="tsisip-dashboard-section" style="margin-top:2rem;">
        <form method="POST" action="" class="tsisip-form">
            <?php echo csrfTokenField(); ?>
            <button type="submit" name="clear" class="tsisip-btn tsisip-btn-warning"
                    onclick="return confirm('<?php echo _('Clear all cached pages?'); ?>')">
                <?php echo _('Clear All Cache'); ?>
            </button>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/common/footer.php'; ?>
