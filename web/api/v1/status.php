<?php
/**
 * GET /api/v1/status
 * Returns system health status.
 */

require_once __DIR__ . '/../../common/mi-http.php';

$status = [
    'timestamp' => date('c'),
    'services' => [],
];

// OpenSIPS check
$opensipsStatus = 'unknown';
try {
    $opensipsAvailable = false;
    if (function_exists('miHttpAvailable')) {
        $opensipsAvailable = miHttpAvailable();
    }
    $opensipsStatus = $opensipsAvailable ? 'healthy' : 'unreachable';
} catch (Exception $e) {
    $opensipsStatus = 'error';
}
$status['services']['opensips'] = $opensipsStatus;

// PostgreSQL check
$dbStatus = 'unknown';
try {
    $pdo = getDb();
    $pdo->query('SELECT 1');
    $dbStatus = 'healthy';
} catch (Exception $e) {
    $dbStatus = 'error';
}
$status['services']['database'] = $dbStatus;

// OCP self
$status['services']['ocp'] = 'healthy';

http_response_code(200);
echo json_encode($status, JSON_PRETTY_PRINT);
