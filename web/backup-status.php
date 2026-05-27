<?php
/**
 * TSiSIP Backup Status Dashboard
 * Feature 032: Automated Backup Verification
 */
require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/auth.php';
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
<div class="container">
    <h1><?= htmlspecialchars($pageTitle) ?></h1>
    
    <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin: 1rem 0;">
        <div class="stat-card" style="background: #f8f9fa; padding: 1rem; border-radius: 8px;">
            <h3><?= _('Total Backups') ?></h3>
            <p style="font-size: 2rem; margin: 0;"><?= count($backups) ?></p>
        </div>
        <div class="stat-card" style="background: #f8f9fa; padding: 1rem; border-radius: 8px;">
            <h3><?= _('Total Storage') ?></h3>
            <p style="font-size: 2rem; margin: 0;"><?= number_format($totalSize / 1024 / 1024, 2) ?> MB</p>
        </div>
        <div class="stat-card" style="background: #f8f9fa; padding: 1rem; border-radius: 8px;">
            <h3><?= _('Latest Backup') ?></h3>
            <p style="font-size: 1.2rem; margin: 0;"><?= $backups[0]['timestamp'] ?? _('None') ?></p>
        </div>
        <div class="stat-card" style="background: #f8f9fa; padding: 1rem; border-radius: 8px;">
            <h3><?= _('Verified') ?></h3>
            <p style="font-size: 2rem; margin: 0;"><?= count(array_filter($backups, fn($b) => $b['verify_status'] === 'pass')) ?></p>
        </div>
    </div>
    
    <h2><?= _('Backup History') ?></h2>
    <table class="data-table" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: #e9ecef;">
                <th style="padding: 0.75rem; text-align: left;"><?= _('File') ?></th>
                <th style="padding: 0.75rem; text-align: left;"><?= _('Timestamp') ?></th>
                <th style="padding: 0.75rem; text-align: right;"><?= _('Size') ?></th>
                <th style="padding: 0.75rem; text-align: left;"><?= _('Age') ?></th>
                <th style="padding: 0.75rem; text-align: center;"><?= _('Status') ?></th>
                <th style="padding: 0.75rem; text-align: left;"><?= _('Verified At') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($backups as $backup): ?>
            <tr style="border-bottom: 1px solid #dee2e6;">
                <td style="padding: 0.75rem; font-family: monospace; font-size: 0.85rem;"><?= htmlspecialchars($backup['file']) ?></td>
                <td style="padding: 0.75rem;"><?= htmlspecialchars($backup['timestamp']) ?></td>
                <td style="padding: 0.75rem; text-align: right;"><?= htmlspecialchars($backup['size_human']) ?></td>
                <td style="padding: 0.75rem;"><?= $backup['age_hours'] ?>h</td>
                <td style="padding: 0.75rem; text-align: center;">
                    <?php if ($backup['verify_status'] === 'pass'): ?>
                        <span style="background: #d4edda; color: #155724; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.85rem;">PASS</span>
                    <?php elseif ($backup['verify_status'] === 'fail'): ?>
                        <span style="background: #f8d7da; color: #721c24; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.85rem;">FAIL</span>
                    <?php else: ?>
                        <span style="background: #fff3cd; color: #856404; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.85rem;">PENDING</span>
                    <?php endif; ?>
                </td>
                <td style="padding: 0.75rem; font-size: 0.85rem;"><?= $backup['verify_timestamp'] !== 'N/A' ? htmlspecialchars($backup['verify_timestamp']) : '-' ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($backups)): ?>
            <tr>
                <td colspan="6" style="padding: 2rem; text-align: center; color: #6c757d;"><?= _('No backups found') ?></td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/common/footer.php'; ?>
