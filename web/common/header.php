<?php
/**
 * TSiSIP Control Panel Header
 * Premium branded theme layer for the TSiSIP ecosystem
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
$validRoles = ['admin', 'devops', 'dentist', 'assistant', 'user', 'readonly'];
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
    <title><?php echo _('TSiSIP — TSiSIP Control Panel'); ?></title>

    <!-- OCP Base Styles -->
    <link rel="stylesheet" href="css/main.css">

    <!-- TSiSIP Premium Theme Variables -->
    <link rel="stylesheet" href="<?php echo tsisip_asset('tsisip-variables.css', 'css'); ?>">

    <!-- TSiSIP Premium Theme Override Layer -->
    <link rel="stylesheet" href="<?php echo tsisip_asset('tsisip-theme.css', 'css'); ?>">
    <link rel="stylesheet" href="<?php echo tsisip_asset('logo-effects.css', 'css'); ?>">

    <!-- TSiSIP Chart Module (loads D3.js on demand in chart views) -->
    <script type="module" src="<?php echo tsisip_asset('tsisip-charts.js', 'js'); ?>" defer></script>
</head>
<body data-tsisip-role="<?php echo htmlspecialchars($userRole); ?>">
    <header class="tsisip-header">
        <div class="tsisip-brand">
            <a href="./" class="logo-full" aria-label="<?php echo _('TSiSIP Home'); ?>">
                <img src="<?php echo tsisip_asset('tsisip-logo-full-v2.svg', 'svg'); ?>"
                     width="220" height="48"
                     alt="<?php echo _('TSiSIP Platform'); ?>">
            </a>
            <a href="./" class="logo-compact" aria-label="<?php echo _('TSiSIP Home'); ?>">
                <img src="<?php echo tsisip_asset('tsisip-logo-compact-v2.svg', 'svg'); ?>"
                     width="48" height="48"
                     alt="<?php echo _('TSiSIP Platform'); ?>">
            </a>
        </div>
    <nav class="tsisip-header-nav" aria-label="<?php echo _('Top navigation'); ?>">
        <a href="dashboard.php" class="tsisip-header-link<?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? ' is-active' : ''; ?>">
            <?php echo _('Dashboard'); ?>
        </a>
        <a href="wiki.php" class="tsisip-header-link<?php echo basename($_SERVER['PHP_SELF']) === 'wiki.php' ? ' is-active' : ''; ?>">
            <?php echo _('Wiki'); ?>
        </a>
    </nav>

    <span class="tsisip-badge tsisip-role-badge--<?php echo htmlspecialchars($userRole, ENT_QUOTES, 'UTF-8'); ?>">
        <?php echo htmlspecialchars(ucfirst($userRole), ENT_QUOTES, 'UTF-8'); ?>
    </span>

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

<?php require_once __DIR__ . '/role-nav.php'; ?>
