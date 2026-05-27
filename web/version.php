<?php
/**
 * TSiSIP Control Panel — Version & Modules
 * OpenSIPS version, build info, and loaded modules.
 */
require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';
require_once __DIR__ . '/common/mi-http.php';

requireAuth();
checkPasswordChange();

$pageTitle = _('Version & Modules');

$versionData = ['success' => false, 'error' => null, 'data' => null];
$whichData   = ['success' => false, 'error' => null, 'data' => null];
$versionStr  = '—';
$gitHash     = '—';
$buildFlags  = '—';
$modules     = [];

try {
    $vResult = miHttpCall('version', []);
    $versionData = $vResult;
    if ($vResult['success'] && is_array($vResult['data'])) {
        $raw = $vResult['data'];
        $versionStr = $raw['version'] ?? $raw['VERSION'] ?? (is_string($raw) ? $raw : '—');
        $gitHash    = $raw['git'] ?? $raw['hash'] ?? $raw['GIT'] ?? '—';
        $buildFlags = $raw['flags'] ?? $raw['build_flags'] ?? $raw['FLAGS'] ?? '—';
        if (is_string($raw)) {
            $versionStr = $raw;
        }
    } elseif ($vResult['success'] && is_string($vResult['data'])) {
        $versionStr = $vResult['data'];
    }
} catch (Exception $e) {
    $versionData['error'] = $e->getMessage();
}

try {
    $wResult = miHttpCall('which', []);
    $whichData = $wResult;
    if ($wResult['success'] && is_array($wResult['data'])) {
        $raw = $wResult['data'];
        if (isset($raw['Modules']) && is_array($raw['Modules'])) {
            $modules = $raw['Modules'];
        } elseif (isset($raw['modules']) && is_array($raw['modules'])) {
            $modules = $raw['modules'];
        } elseif (isset($raw[0]) && is_array($raw[0])) {
            $modules = $raw;
        } else {
            foreach ($raw as $key => $val) {
                if (is_array($val)) {
                    $modules[] = $val;
                } elseif (is_string($val)) {
                    $modules[] = ['name' => $val];
                }
            }
        }
    }
} catch (Exception $e) {
    $whichData['error'] = $e->getMessage();
}

require_once __DIR__ . '/common/header.php';
?>
<div class="tsisip-page">
    <div class="tsisip-page-header">
        <h1 class="tsisip-page-title"><?php echo $pageTitle; ?></h1>
        <div class="tsisip-actions">
            <button type="button" class="tsisip-btn tsisip-btn-secondary" onclick="TSiSIPMi.exportData('version', [], 'csv')"><?php echo _('Export CSV'); ?></button>
            <button type="button" class="tsisip-btn tsisip-btn-secondary" onclick="TSiSIPMi.exportData('version', [], 'json')"><?php echo _('Export JSON'); ?></button>
        </div>
    </div>

    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo _('Build Information'); ?></h2>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:16px;">
            <div class="tsisip-metric-card" style="background:var(--tsisip-surface-card);border:1px solid var(--tsisip-border-subtle);border-radius:8px;padding:16px;">
                <div style="font-size:var(--tsisip-text-sm);color:var(--tsisip-text-secondary);margin-bottom:8px;"><?php echo _('Version'); ?></div>
                <div style="font-size:1.25rem;font-weight:700;color:var(--tsisip-primary-blue);">
                    <code><?php echo htmlspecialchars($versionStr); ?></code>
                </div>
            </div>
            <div class="tsisip-metric-card" style="background:var(--tsisip-surface-card);border:1px solid var(--tsisip-border-subtle);border-radius:8px;padding:16px;">
                <div style="font-size:var(--tsisip-text-sm);color:var(--tsisip-text-secondary);margin-bottom:8px;"><?php echo _('Git Hash'); ?></div>
                <div style="font-size:1.25rem;font-weight:700;color:var(--tsisip-primary-blue);">
                    <code><?php echo htmlspecialchars($gitHash); ?></code>
                </div>
            </div>
            <div class="tsisip-metric-card" style="background:var(--tsisip-surface-card);border:1px solid var(--tsisip-border-subtle);border-radius:8px;padding:16px;">
                <div style="font-size:var(--tsisip-text-sm);color:var(--tsisip-text-secondary);margin-bottom:8px;"><?php echo _('Build Flags'); ?></div>
                <div style="font-size:0.9rem;font-weight:600;color:var(--tsisip-text-primary);">
                    <code><?php echo htmlspecialchars($buildFlags); ?></code>
                </div>
            </div>
        </div>
    </section>

    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo _('Loaded Modules'); ?> (<?php echo count($modules); ?>)</h2>
        <?php if (!$whichData['success']): ?>
            <?php echo miErrorBanner($whichData['error'] ?? _('Unknown')); ?>
        <?php else: ?>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:8px;">
                <?php foreach ($modules as $mod): ?>
                    <?php
                    $modName = '';
                    if (is_array($mod)) {
                        $modName = $mod['name'] ?? $mod['module'] ?? $mod['MODULE'] ?? '';
                    } elseif (is_string($mod)) {
                        $modName = $mod;
                    }
                    if ($modName === '') continue;
                    ?>
                    <div style="background:var(--tsisip-surface-card);border:1px solid var(--tsisip-border-subtle);border-radius:6px;padding:8px 12px;text-align:center;">
                        <code style="font-size:0.85rem;"><?php echo htmlspecialchars($modName); ?></code>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($modules)): ?>
                    <div class="tsisip-empty" style="grid-column:1 / -1;"><?php echo _('No module data.'); ?></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php require_once __DIR__ . '/common/footer.php'; ?>
