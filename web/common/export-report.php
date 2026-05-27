<?php
/**
 * TSiSIP Control Panel — Export Report Data
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/csrf.php';

requireAuth();
requireRole('devops');

$range = $_GET['range'] ?? '24h';
$format = $_GET['format'] ?? 'csv';
$reportType = $_GET['type'] ?? 'logins';

$interval = match($range) {
    '1h' => '1 hour',
    '24h' => '24 hours',
    '7d' => '7 days',
    '30d' => '30 days',
    default => '24 hours',
};

$pdo = getDb();

if ($reportType === 'logins') {
    $stmt = $pdo->prepare(
        "SELECT DATE_TRUNC('hour', event_time) AS hour,
                COUNT(*) FILTER (WHERE action = 'LOGIN_SUCCESS') AS success,
                COUNT(*) FILTER (WHERE action = 'LOGIN_FAILURE') AS failure
         FROM ocp_audit_log
         WHERE event_time > NOW() - INTERVAL '{$interval}'
         GROUP BY hour
         ORDER BY hour"
    );
    $stmt->execute();
    $data = $stmt->fetchAll();
    $filename = "login-trends-{$range}";
    $headers = ['Hour', 'Success', 'Failure'];
} elseif ($reportType === 'users') {
    $stmt = $pdo->prepare(
        "SELECT username, COUNT(*) AS events
         FROM ocp_audit_log
         WHERE event_time > NOW() - INTERVAL '{$interval}'
         GROUP BY username
         ORDER BY events DESC"
    );
    $stmt->execute();
    $data = $stmt->fetchAll();
    $filename = "active-users-{$range}";
    $headers = ['Username', 'Events'];
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Unknown report type']);
    exit;
}

if ($format === 'csv') {
    header('Content-Type: text/csv');
    header("Content-Disposition: attachment; filename={$filename}.csv");
    $out = fopen('php://output', 'w');
    fputcsv($out, $headers);
    foreach ($data as $row) {
        fputcsv($out, array_values($row));
    }
    fclose($out);
} else {
    header('Content-Type: application/json');
    header("Content-Disposition: attachment; filename={$filename}.json");
    echo json_encode($data, JSON_PRETTY_PRINT);
}
