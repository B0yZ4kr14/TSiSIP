<?php
/**
 * TSiSIP Control Panel — About
 */
require_once __DIR__ . '/common/config.php';

requireAuth();
checkPasswordChange();

logAuditEvent('CONFIG_VIEW', 'system', 'about', true);

$version = '1.0.0';
$buildDate = date('Y-m-d', filemtime(__DIR__ . '/../Dockerfile'));
$phpVersion = PHP_VERSION;

require_once __DIR__ . '/common/header.php';
?>
<div id="content" class="tsisip-dashboard">
    <h1><?php echo _('About TSiSIP'); ?></h1>

    <div class="tsisip-dashboard-section">
        <div class="tsisip-data-row">
            <span class="tsisip-data-label"><?php echo _('Version'); ?></span>
            <span class="tsisip-data-value"><code><?php echo htmlspecialchars($version); ?></code></span>
        </div>
        <div class="tsisip-data-row">
            <span class="tsisip-data-label"><?php echo _('Build Date'); ?></span>
            <span class="tsisip-data-value"><?php echo htmlspecialchars($buildDate); ?></span>
        </div>
        <div class="tsisip-data-row">
            <span class="tsisip-data-label"><?php echo _('PHP Version'); ?></span>
            <span class="tsisip-data-value"><code><?php echo htmlspecialchars($phpVersion); ?></code></span>
        </div>
        <div class="tsisip-data-row">
            <span class="tsisip-data-label"><?php echo _('OpenSIPS'); ?></span>
            <span class="tsisip-data-value">3.6 LTS</span>
        </div>
        <div class="tsisip-data-row">
            <span class="tsisip-data-label"><?php echo _('PostgreSQL'); ?></span>
            <span class="tsisip-data-value">15+</span>
        </div>
        <div class="tsisip-data-row">
            <span class="tsisip-data-label"><?php echo _('RTPengine'); ?></span>
            <span class="tsisip-data-value">Latest</span>
        </div>
    </div>

    <div class="tsisip-dashboard-section">
        <h2 class="tsisip-section-title"><?php echo _('Credits'); ?></h2>
        <p><?php echo _('TSiSIP is built with:'); ?></p>
        <ul>
            <li>OpenSIPS 3.6 LTS</li>
            <li>PostgreSQL</li>
            <li>RTPengine</li>
            <li>PHP 8.2</li>
            <li>Apache</li>
            <li>Docker</li>
        </ul>
    </div>

    <div class="tsisip-dashboard-section">
        <h2 class="tsisip-section-title"><?php echo _('License'); ?></h2>
        <p><?php echo _('TSiSIP is proprietary software. All rights reserved.'); ?></p>
    </div>
</div>
<?php require_once __DIR__ . '/common/footer.php'; ?>
