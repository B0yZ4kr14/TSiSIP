<?php
/**
 * TSiSIP Control Panel — Theme Preference Handler
 * Saves the user's theme preference to the session.
 */
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $theme = $_POST['theme'] ?? 'light';
    $validThemes = ['light', 'dark'];
    if (in_array($theme, $validThemes, true)) {
        $_SESSION['tsisip_theme'] = $theme;
    }
}

// Return JSON for AJAX requests
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'theme' => $_SESSION['tsisip_theme'] ?? 'light']);
    exit;
}

// Redirect for regular POST
$referer = $_SERVER['HTTP_REFERER'] ?? '/dashboard.php';
// Ensure referer is an absolute path to avoid resolving relative to /common/
if (!str_starts_with($referer, '/')) {
    $referer = '/' . $referer;
}
header('Location: ' . $referer);
exit;
