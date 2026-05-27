<?php
/**
 * TSiSIP Control Panel — Public Health Check
 * Returns JSON status for load balancers and monitoring.
 */
header('Content-Type: application/json');

$checks = [];
$healthy = true;

// Check database
try {
    require_once __DIR__ . '/common/config.php';
    $pdo = getDb();
    $pdo->query('SELECT 1');
    $checks['database'] = ['status' => 'ok', 'latency_ms' => 0];
} catch (Throwable $e) {
    $checks['database'] = ['status' => 'error', 'message' => $e->getMessage()];
    $healthy = false;
}

// Check OpenSIPS MI
try {
    $miData = miHttpCall('get_statistics', ['statistics' => ['uptime']]);
    $checks['opensips'] = ['status' => 'ok', 'uptime' => $miData['uptime'] ?? 0];
} catch (Throwable $e) {
    $checks['opensips'] = ['status' => 'error', 'message' => 'MI unreachable'];
    $healthy = false;
}

$response = [
    'status' => $healthy ? 'healthy' : 'degraded',
    'timestamp' => gmdate('c'),
    'version' => '1.0.0',
    'checks' => $checks,
];

http_response_code($healthy ? 200 : 503);
echo json_encode($response, JSON_PRETTY_PRINT);
