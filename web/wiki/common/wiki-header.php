<?php
/**
 * TSiSIP Wiki Header
 * Simplified branded theme layer for the TSiSIP Wiki
 */

// Asset manifest loading with graceful fallback
$manifestPath = __DIR__ . '/../../tsisip/asset-manifest.json';
$manifest = [];
if (file_exists($manifestPath)) {
    $manifestRaw = file_get_contents($manifestPath);
    if ($manifestRaw !== false) {
        $manifest = json_decode($manifestRaw, true) ?: [];
    }
}

// Helper to resolve hashed asset path
function tsisip_asset(string $logicalName, string $type = 'css'): string {
    global $manifest;
    if (isset($manifest['assets'][$type][$logicalName])) {
        return '../tsisip/' . ($type === 'css' || $type === 'js' ? $type . '/' : 'assets/') . $manifest['assets'][$type][$logicalName];
    }
    // Fallback to unhashed path
    $fallbackDir = $type === 'css' || $type === 'js' ? $type . '/' : 'assets/';
    return '../tsisip/' . $fallbackDir . $logicalName;
}

// Locale for i18n
$ocpLocale = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'en_US';
?>
<?php
// Security headers for all Wiki responses
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self';");
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(substr($ocpLocale, 0, 2)); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0A1628">
    <title><?php echo _('TSiSIP — Wiki'); ?></title>

    <!-- OCP Base Styles -->
    <link rel="stylesheet" href="../css/main.css">

    <!-- TSiSIP Premium Theme Variables -->
    <link rel="stylesheet" href="<?php echo tsisip_asset('tsisip-variables.css', 'css'); ?>">

    <!-- TSiSIP Premium Theme Override Layer -->
    <link rel="stylesheet" href="<?php echo tsisip_asset('tsisip-theme.css', 'css'); ?>">
    <link rel="stylesheet" href="<?php echo tsisip_asset('logo-effects.css', 'css'); ?>">
</head>
<body data-tsisip-role="wiki">
    <header class="tsisip-header tsisip-wiki-header">
        <div class="tsisip-brand">
            <a href="../" class="logo-full" aria-label="<?php echo _('TSiSIP Home'); ?>">
                <img src="<?php echo tsisip_asset('tsisip-logo-full-v2.svg', 'svg'); ?>" alt="<?php echo _('TSiSIP Logo'); ?>"
                     width="220" height="48"
                     alt="<?php echo _('TSiSIP Platform'); ?>">
            </a>
            <a href="../" class="logo-compact" aria-label="<?php echo _('TSiSIP Home'); ?>">
                <img src="<?php echo tsisip_asset('tsisip-logo-compact-v2.svg', 'svg'); ?>" alt="<?php echo _('TSiSIP Logo'); ?>"
                     width="48" height="48"
                     alt="<?php echo _('TSiSIP Platform'); ?>">
            </a>
        </div>
        <nav class="tsisip-header-nav" aria-label="<?php echo _('Wiki navigation'); ?>">
            <a href="./" class="tsisip-header-link<?php echo basename($_SERVER['PHP_SELF']) === 'index.php' && empty($_GET['page']) ? ' is-active' : ''; ?>">
                <?php echo _('Wiki Home'); ?>
            </a>
            <a href="../dashboard.php" class="tsisip-header-link">
                <?php echo _('Control Panel'); ?>
            </a>
        </nav>
        <?php if (!empty($_SESSION['ocp_user_id'])): ?>
            <a href="../logout.php" class="tsisip-header-link" aria-label="<?php echo _('Sign out'); ?>">
                <?php echo _('Logout'); ?>
            </a>
        <?php endif; ?>
    </header>
