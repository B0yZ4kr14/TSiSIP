<?php
/**
 * TSiSIP Backup Status Dashboard
 * Feature 032: Automated Backup Verification
 */
require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/header.php';

// Require admin or devops role
if (!$user || ($user['role'] !== 'admin' && $user['role'] !== 'devops')) {
    header('HTTP/1.1 403 Forbidden');
    echo '<p class="error">' . _('Access denied') . '</p>';
    require_once __DIR__ . '/common/footer.php';
    exit;
}

$backupDir = realpath(__DIR__ . '/../backups');
if (!$backupDir || !is_dir($backupDir)) {
    $backupDir = __DIR__ . '/../backups';
}

$backups = [];
$totalSize = 0;

foreach (glob("$backupDir/tsisip_db_*.sql.gz") as $file) {
    $metaFile = $file . '.meta.json';
    $meta = [];
    if (file_exists($metaFile)) {
        $meta = json_decode(file_get_contents($metaFile), true) ?: [];
    }
    
    $size = filesize($file);
    $totalSize += $size;
    
    $backups[] = [
        'file' => basename($file),
        'timestamp' => $meta['iso_timestamp'] ?? date('Y-m-d H:i:s', filemtime($file)),
        'size_human' => $meta['size_human'] ?? number_format($size / 1024 / 1024, 2) . ' MB',
        'size_bytes' => $size,
        'checksum' => $meta['checksum'] ?? 'N/A',
        'verify_status' => $meta['verify_status'] ?? 'unknown',
        'verify_timestamp' => $meta['verify_timestamp'] ?? 'N/A',
        'age_hours' => round((time() - filemtime($file)) / 3600, 1),
    ];
}

// Sort by timestamp descending
usort($backups, function ($a, $b) {
    return strtotime($b['timestamp']) - strtotime($a['timestamp']);
});

$pageTitle = _('Backup Status');
?>
<div id="content" class="tsisip-dashboard">
    <h1><?= htmlspecialchars($pageTitle) ?></h1>
    
    <div class="stats-grid" class="tsisip-dashboard-section">
        <div class="stat-card" class="tsisip-card">
            <h3><?= _('Total Backups') ?></h3>
            <p style="font-size: 2rem; margin: 0;"><?= count($backups) ?></p>
        </div>
        <div class="stat-card" class="tsisip-card">
            <h3><?= _('Total Storage') ?></h3>
            <p style="font-size: 2rem; margin: 0;"><?= number_format($totalSize / 1024 / 1024, 2) ?> MB</p>
        </div>
        <div class="stat-card" class="tsisip-card">
            <h3><?= _('Latest Backup') ?></h3>
            <p style="font-size: 1.2rem; margin: 0;"><?= $backups[0]['timestamp'] ?? _('None') ?></p>
        </div>
        <div class="stat-card" class="tsisip-card">
            <h3><?= _('Verified') ?></h3>
            <p style="font-size: 2rem; margin: 0;"><?= count(array_filter($backups, fn($b) => $b['verify_status'] === 'pass')) ?></p>
        </div>
    </div>
    
    <h2><?= _('Backup History') ?></h2>
    <table class="data-table" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: #e9ecef;">
                <th class="tsisip-table-header"><?= _('File') ?></th>
                <th class="tsisip-table-header"><?= _('Timestamp') ?></th>
                <th class="tsisip-table-cell tsisip-text-right"><?= _('Size') ?></th>
                <th class="tsisip-table-header"><?= _('Age') ?></th>
                <th class="tsisip-table-cell tsisip-text-center"><?= _('Status') ?></th>
                <th class="tsisip-table-header"><?= _('Verified At') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($backups as $backup): ?>
            <tr style="border-bottom: 1px solid #dee2e6;">
                <td class="tsisip-table-cell tsisip-font-mono"><?= htmlspecialchars($backup['file']) ?></td>
                <td style="padding: 0.75rem;"><?= htmlspecialchars($backup['timestamp']) ?></td>
                <td class="tsisip-table-cell tsisip-text-right"><?= htmlspecialchars($backup['size_human']) ?></td>
                <td style="padding: 0.75rem;"><?= $backup['age_hours'] ?>h</td>
                <td class="tsisip-table-cell tsisip-text-center">
                    <?php if ($backup['verify_status'] === 'pass'): ?>
                        <span class="tsisip-badge tsisip-badge--success">PASS</span>
                    <?php elseif ($backup['verify_status'] === 'fail'): ?>
                        <span class="tsisip-badge tsisip-badge--error">FAIL</span>
                    <?php else: ?>
                        <span class="tsisip-badge tsisip-badge--warning">PENDING</span>
                    <?php endif; ?>
                </td>
                <td style="padding: 0.75rem; font-size: 0.85rem;"><?= $backup['verify_timestamp'] !== 'N/A' ? htmlspecialchars($backup['verify_timestamp']) : '-' ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($backups)): ?>
            <tr>
                <td colspan="6" class="tsisip-empty-state"><?= _('No backups found') ?></td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/common/footer.php'; ?>
