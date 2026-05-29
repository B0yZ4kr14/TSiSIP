<?php
/**
 * dispatcher-reload.php — Trigger OpenSIPS ds_reload via MI HTTP
 * Feature 035
 */

require_once __DIR__ . '/../../common/config.php';
require_once __DIR__ . '/../../common/csrf.php';
require_once __DIR__ . '/../../common/mi-http.php';
require_once __DIR__ . '/../../common/audit.php';

requireAuth();
requireCsrfForMutation();
checkPasswordChange();

$userRole = $_SESSION['user_role'] ?? 'readonly';
if (!in_array($userRole, ['admin', 'devops'], true)) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Admin or DevOps role required']);
    exit;
}

// Rate limit: max 5 reloads per minute per session
$rateKey = 'dispatcher_reload_' . session_id();
$now = time();
$window = 60;
$maxAttempts = 5;

if (!isset($_SESSION[$rateKey])) {
    $_SESSION[$rateKey] = ['count' => 0, 'window_start' => $now];
}
if (($now - $_SESSION[$rateKey]['window_start']) > $window) {
    $_SESSION[$rateKey] = ['count' => 0, 'window_start' => $now];
}
$_SESSION[$rateKey]['count']++;
if ($_SESSION[$rateKey]['count'] > $maxAttempts) {
    http_response_code(429);
    header('Content-Type: application/json');
    header('Retry-After: ' . ($window - ($now - $_SESSION[$rateKey]['window_start'])));
    echo json_encode(['error' => 'Rate limit exceeded: max 5 reloads per minute']);
    exit;
}

// Trigger MI ds_reload
$result = miHttpCall('ds_reload', []);
if (!$result['success']) {
    http_response_code(502);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'MI ds_reload failed', 'detail' => $result['error']]);
    exit;
}

// Log audit
logAuditEvent('CONFIG_CHANGE', 'dispatcher', 'reload', true, ['action' => 'RELOAD', 'mi_result' => 'success']);

http_response_code(200);
header('Content-Type: application/json');
echo json_encode(['success' => true, 'message' => 'Dispatcher reload triggered', 'mi_response' => $result['data']]);
