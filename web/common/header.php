<?php
/**
 * TSiAPP Platform — TSiSIP Control Panel Header
 * Premium branded theme layer for the TSiAPP ecosystem
 */

// Asset manifest loading with graceful fallback
$manifestPath = __DIR__ . '/../tsisip/asset-manifest.json';
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
        return 'tsisip/' . ($type === 'css' || $type === 'js' ? $type . '/' : 'assets/') . $manifest['assets'][$type][$logicalName];
    }
    // Fallback to unhashed path
    $fallbackDir = $type === 'css' || $type === 'js' ? $type . '/' : 'assets/';
    return 'tsisip/' . $fallbackDir . $logicalName;
}

// User role for density rules (whitelisted)
$validRoles = ['admin', 'readonly', 'user'];
$userRole = 'readonly';
if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], $validRoles, true)) {
    $userRole = $_SESSION['user_role'];
}

// Locale for i18n
$ocpLocale = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'en_US';
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(substr($ocpLocale, 0, 2)); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0A1628">
    <title><?php echo _('TSiAPP — TSiSIP Control Panel'); ?></title>

    <!-- OCP Base Styles -->
    <link rel="stylesheet" href="css/main.css">

    <!-- TSiAPP Premium Theme Variables -->
    <link rel="stylesheet" href="<?php echo tsisip_asset('tsisip-variables.css', 'css'); ?>">

    <!-- TSiAPP Premium Theme Override Layer -->
    <link rel="stylesheet" href="<?php echo tsisip_asset('tsisip-theme.css', 'css'); ?>">

    <!-- TSiSIP Chart Module (loads D3.js on demand in chart views) -->
    <script type="module" src="<?php echo tsisip_asset('tsisip-charts.js', 'js'); ?>" defer></script>
</head>
<body data-tsisip-role="<?php echo htmlspecialchars($userRole); ?>">
    <header class="tsisip-header">
        <div class="tsisip-brand">
            <a href="./" class="logo-full" aria-label="<?php echo _('TSiAPP Home'); ?>">
                <img src="<?php echo tsisip_asset('tsiapp-logo-full.svg', 'svg'); ?>"
                     width="220" height="48"
                     alt="<?php echo _('TSiAPP Platform'); ?>">
            </a>
            <a href="./" class="logo-compact" aria-label="<?php echo _('TSiAPP Home'); ?>">
                <img src="<?php echo tsisip_asset('tsiapp-logo-compact.svg', 'svg'); ?>"
                     width="48" height="48"
                     alt="<?php echo _('TSiAPP Platform'); ?>">
            </a>
        </div>
        <button type="button"
                class="tsisip-sidebar-toggle"
                aria-expanded="false"
                aria-controls="sidebar"
                aria-label="<?php echo _('Toggle navigation'); ?>">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>
    </header>
