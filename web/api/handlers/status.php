<?php
/**
 * GET /api/v1/status — System health status
 */

$status = [
    'opensips' => 'unknown',
    'rtpengine' => 'unknown',
    'postgres' => 'unknown',
    'pgbouncer' => 'unknown',
    'ocp' => 'unknown',
];

// Check OpenSIPS via MI
$opensipsStatus = @file_get_contents('http://opensips:8080/mi');
$status['opensips'] = $opensipsStatus !== false ? 'healthy' : 'unhealthy';

// Check RTPengine
$rtpengineStatus = @file_get_contents('http://rtpengine:8080/health');
$status['rtpengine'] = $rtpengineStatus !== false ? 'healthy' : 'unhealthy';

// Check PostgreSQL
try {
    $pdo = getDb();
    $pdo->query('SELECT 1');
    $status['postgres'] = 'healthy';
    $status['pgbouncer'] = 'healthy';
} catch (Exception $e) {
    $status['postgres'] = 'unhealthy';
    $status['pgbouncer'] = 'unhealthy';
}

$status['ocp'] = 'healthy';
$status['timestamp'] = date('c');

echo json_encode(['status' => $status]);
