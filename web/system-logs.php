<?php
/**
 * TSiSIP Control Panel — System Logs Viewer
 */
require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';

requireAuth();
checkPasswordChange();
requireRole('admin');

logAuditEvent('CONFIG_VIEW', 'system', 'system-logs', true);

$logFile = $_GET['log'] ?? 'ocp';
$lines = (int)($_GET['lines'] ?? 100);
$allowedLogs = [
    'ocp' => '/var/log/apache2/error.log',
    'access' => '/var/log/apache2/access.log',
    'php' => '/var/log/php_errors.log',
];

$logPath = $allowedLogs[$logFile] ?? '';
$content = '';
if ($logPath && file_exists($logPath) && is_readable($logPath)) {
    $cmd = sprintf('tail -n %d %s 2>/dev/null', escapeshellarg($lines), escapeshellarg($logPath));
    $content = shell_exec($cmd) ?: 'Unable to read log file.';
} else {
    $content = 'Log file not available.';
}

require_once __DIR__ . '/common/header.php';
?>
<div id="content" class="tsisip-dashboard">
    <h1><?php echo _('System Logs'); ?></h1>

    <div class="tsisip-dashboard-section">
        <form method="GET" action="" class="tsisip-form" style="display:flex;gap:1rem;align-items:flex-end;">
            <div class="tsisip-form-group">
                <label class="tsisip-form-label"><?php echo _('Log File'); ?></label>
                <select name="log" class="tsisip-select">
                    <?php foreach ($allowedLogs as $key => $path): ?>
                        <option value="<?php echo $key; ?>" <?php echo $logFile === $key ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($key); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="tsisip-form-group">
                <label class="tsisip-form-label"><?php echo _('Lines'); ?></label>
                <input type="number" name="lines" value="<?php echo $lines; ?>" min="10" max="1000" class="tsisip-input" style="width:100px;">
            </div>
            <button type="submit" class="tsisip-btn tsisip-btn-primary"><?php echo _('View'); ?></button>
        </form>
    </div>

    <div class="tsisip-dashboard-section">
        <pre style="background:var(--tsisip-bg-secondary);padding:1rem;border-radius:8px;overflow-x:auto;max-height:600px;font-size:0.85rem;line-height:1.5;"><code><?php echo htmlspecialchars($content); ?></code></pre>
    </div>
</div>
<?php require_once __DIR__ . '/common/footer.php'; ?>
