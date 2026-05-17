<?php
/**
 * TSiAPP Platform — TSiSIP Control Panel Login
 * Premium branded login view for the TSiAPP ecosystem
 */

session_start();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = _('Invalid credentials');
}

$manifestPath = __DIR__ . '/tsisip/asset-manifest.json';
$manifest = [];
if (file_exists($manifestPath)) {
    $manifestRaw = file_get_contents($manifestPath);
    if ($manifestRaw !== false) {
        $manifest = json_decode($manifestRaw, true) ?: [];
    }
}

function tsisip_asset(string $logicalName, string $type = 'css'): string {
    global $manifest;
    if (isset($manifest['assets'][$type][$logicalName])) {
        return 'tsisip/' . ($type === 'css' || $type === 'js' ? $type . '/' : 'assets/') . $manifest['assets'][$type][$logicalName];
    }
    $fallbackDir = $type === 'css' || $type === 'js' ? $type . '/' : 'assets/';
    return 'tsisip/' . $fallbackDir . $logicalName;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1A3A5C">
    <title><?php echo _('TSiAPP — Sign In'); ?></title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="<?php echo tsisip_asset('tsisip-variables.css', 'css'); ?>">
    <link rel="stylesheet" href="<?php echo tsisip_asset('tsisip-theme.css', 'css'); ?>">
</head>
<body class="tsisip-login">
    <div class="tsisip-login-card">
        <div class="logo">
            <img src="<?php echo tsisip_asset('tsiapp-logo-full.svg', 'svg'); ?>"
                 width="220" height="48"
                 alt="<?php echo _('TSiAPP Platform'); ?>">
        </div>
        <h1 class="tsisip-text-center tsisip-mb-2"><?php echo _('Welcome back'); ?></h1>
        <p class="tsisip-login-subtitle"><?php echo _('TSiSIP Control Panel'); ?></p>
        <?php if ($error): ?>
            <div class="tsisip-badge tsisip-badge-error tsisip-mb-4" role="alert">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="tsisip-form-group">
                <label for="username" class="tsisip-sr-only"><?php echo _('Username'); ?></label>
                <input type="text"
                       id="username"
                       name="username"
                       class="tsisip-input"
                       placeholder="<?php echo _('Username'); ?>"
                       required
                       autocomplete="username">
            </div>
            <div class="tsisip-form-group">
                <label for="pass" class="tsisip-sr-only"><?php echo _('Passphrase'); ?></label>
                <input type="password"
                       id="pass"
                       name="pass"
                       class="tsisip-input"
                       placeholder="<?php echo _('Passphrase'); ?>"
                       required
                       autocomplete="current-password">
            </div>
            <button type="submit" class="tsisip-btn tsisip-btn-primary" style="width:100%">
                <?php echo _('Sign In'); ?>
            </button>
        </form>
    </div>
</body>
</html>
