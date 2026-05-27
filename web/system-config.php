<?php
/**
 * TSiSIP Control Panel — System Configuration Viewer
 * Read-only view of OpenSIPS configuration parameters via MI.
 */
require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';
require_once __DIR__ . '/common/mi-http.php';

requireAuth();
checkPasswordChange();
requireRole('admin');

logAuditEvent('CONFIG_VIEW', 'system', 'system-config', true);

// --- Fetch core configuration via MI ---
$coreParams = [];
$miResult = miHttpCall('get_statistics', ['all']);
if ($miResult['success'] && is_array($miResult['data'])) {
    $raw = $miResult['data'];
    if (is_array($raw)) {
        foreach ($raw as $key => $value) {
            if (is_string($key) && str_starts_with($key, 'core:')) {
                $coreParams[$key] = $value;
            }
        }
    }
}

// --- Fetch module parameters ---
$moduleParams = [];
$modules = ['dialog', 'tm', 'usrloc', 'dispatcher', 'rtpengine', 'auth'];
foreach ($modules as $mod) {
    $modResult = miHttpCall('get_statistics', [$mod . ':*']);
    if ($modResult['success'] && is_array($modResult['data'])) {
        $raw = $modResult['data'];
        if (is_array($raw)) {
            foreach ($raw as $key => $value) {
                if (is_string($key)) {
                    $moduleParams[$mod][$key] = $value;
                }
            }
        }
    }
}

require_once __DIR__ . '/common/header.php';
?>
<div id="content" class="tsisip-dashboard">
    <h1><?php echo _('System Configuration'); ?></h1>

    <div class="tsisip-dashboard-section">
        <p class="tsisip-text-muted">
            <?php echo _('Read-only view of OpenSIPS runtime configuration and statistics.'); ?>
        </p>
    </div>

    <!-- Core Parameters -->
    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo _('Core Parameters'); ?></h2>
        <?php if (empty($coreParams)): ?>
            <div class="tsisip-badge tsisip-badge--info"><?php echo _('No core data or MI unreachable.'); ?></div>
        <?php else: ?>
            <table class="tsisip-table dataTable">
                <thead>
                    <tr>
                        <th><?php echo _('Parameter'); ?></th>
                        <th><?php echo _('Value'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($coreParams as $key => $value): ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($key); ?></code></td>
                            <td><?php echo htmlspecialchars((string) $value); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>

    <!-- Module Parameters -->
    <?php foreach ($moduleParams as $mod => $params): ?>
        <section class="tsisip-section">
            <h2 class="tsisip-section-title"><?php echo htmlspecialchars(ucfirst($mod)); ?> <?php echo _('Module'); ?></h2>
            <?php if (empty($params)): ?>
                <div class="tsisip-badge tsisip-badge--info"><?php echo _('No data for this module.'); ?></div>
            <?php else: ?>
                <table class="tsisip-table dataTable">
                    <thead>
                        <tr>
                            <th><?php echo _('Parameter'); ?></th>
                            <th><?php echo _('Value'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($params as $key => $value): ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($key); ?></code></td>
                                <td><?php echo htmlspecialchars((string) $value); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>
</div>
<?php require_once __DIR__ . '/common/footer.php'; ?>
