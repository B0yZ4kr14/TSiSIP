<?php
/**
 * TSiSIP Control Panel — Language Switcher
 * Sets the session language and redirects back.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/csrf.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        echo _('Invalid CSRF token.');
        exit;
    }

    $lang = $_POST['lang'] ?? 'en_US';
    $validLocales = ['en_US', 'es_ES', 'pt_BR'];
    if (in_array($lang, $validLocales, true)) {
        $_SESSION['lang'] = $lang;
        logAuditEvent('LANGUAGE_CHANGE', 'system', $lang, true);
    }
}

$referer = $_SERVER['HTTP_REFERER'] ?? 'dashboard.php';
header('Location: ' . $referer);
exit;
