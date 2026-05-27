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

// User role from OCP session (populated by login.php via config.php)
$validRoles = ['admin', 'devops', 'dentist', 'assistant', 'user', 'readonly'];
$userRole = 'readonly';
if (isset($_SESSION['ocp_user_role']) && in_array($_SESSION['ocp_user_role'], $validRoles, true)) {
    $userRole = $_SESSION['ocp_user_role'];
}

// Locale already initialized in config.php
?>
<?php
// Security headers for all OCP responses
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self';");
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(substr($ocpLocale, 0, 2)); ?>" data-theme="<?php echo htmlspecialchars($_SESSION['tsisip_theme'] ?? 'light', ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0A1628">
    <title><?php echo _('TSiSIP — Control Panel'); ?></title>

    <!-- OCP Base Styles -->
    <link rel="stylesheet" href="css/main.css">

    <!-- TSiSIP Premium Theme Variables -->
    <link rel="stylesheet" href="<?php echo tsisip_asset('tsisip-variables.css', 'css'); ?>">

    <!-- TSiSIP Premium Theme Override Layer -->
    <link rel="stylesheet" href="<?php echo tsisip_asset('tsisip-theme.css', 'css'); ?>">
    <link rel="stylesheet" href="<?php echo tsisip_asset('logo-effects.css', 'css'); ?>">

    <!-- TSiSIP Chart Module (loads D3.js on demand in chart views) -->
    <script type="module" src="<?php echo tsisip_asset('tsisip-charts.js', 'js'); ?>" defer></script>
    <script src="<?php echo tsisip_asset('theme-toggle.js', 'js'); ?>" defer></script>
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
    </nav>

    <a href="profile.php" style="text-decoration:none;"><span class="tsisip-badge tsisip-role-badge--<?php echo htmlspecialchars($userRole, ENT_QUOTES, 'UTF-8'); ?>">
        <?php echo htmlspecialchars(ucfirst($userRole), ENT_QUOTES, 'UTF-8'); ?>
    </span>

    <?php if (!empty($_SESSION['ocp_user_id'])): ?>
        <a href="logout.php" class="tsisip-header-link" aria-label="<?php echo _('Sign out'); ?>">
            <?php echo _('Logout'); ?>
        </a>
    <?php endif; ?>

    <!-- Language selector -->
    <form method="POST" action="common/set-language.php" style="display:inline;margin-left:8px;">
        <?php echo csrfInput(); ?>
        <select name="lang" class="tsisip-input" style="padding:4px 8px;font-size:13px;" onchange="this.form.submit()">
            <option value="en_US" <?php echo ($ocpLocale === 'en_US') ? 'selected' : ''; ?>>English</option>
            <option value="es_ES" <?php echo ($ocpLocale === 'es_ES') ? 'selected' : ''; ?>>Español</option>
            <option value="pt_BR" <?php echo ($ocpLocale === 'pt_BR') ? 'selected' : ''; ?>>Português</option>
        </select>
    </form>

    <!-- Theme toggle -->
    <button type="button" id="theme-toggle" class="tsisip-header-link" style="background:none;border:none;cursor:pointer;padding:4px 8px;" onclick="tsisipToggleTheme()">
        <!-- Icon set by theme-toggle.js -->
    </button>

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
