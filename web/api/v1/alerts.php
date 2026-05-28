<?php
/**
 * GET /api/v1/alerts
 * Returns currently firing alerts from Alertmanager.
 */

require_once __DIR__ . '/../../common/config.php';

requireAuth();

$alertmanagerUrl = 'http://alertmanager:9093/api/v1/alerts';
$ctx = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => "Accept: application/json\r\n",
        'timeout' => 5,
    ],
]);

$resp = @file_get_contents($alertmanagerUrl, false, $ctx);
if ($resp === false) {
    http_response_code(503);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Alertmanager unreachable']);
    exit;
}

$decoded = json_decode($resp, true);
if (!is_array($decoded) || !isset($decoded['data'])) {
    http_response_code(502);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid response from Alertmanager']);
    exit;
}

// Filter to firing alerts only and normalize
$firing = [];
foreach ($decoded['data'] as $alert) {
    if (($alert['status']['state'] ?? '') !== 'firing') {
        continue;
    }
    $firing[] = [
        'name'      => $alert['labels']['alertname'] ?? 'unknown',
        'severity'  => $alert['labels']['severity'] ?? 'warning',
        'summary'   => $alert['annotations']['summary'] ?? '',
        'description' => $alert['annotations']['description'] ?? '',
        'startedAt' => $alert['startsAt'] ?? null,
        'labels'    => $alert['labels'] ?? [],
    ];
}

http_response_code(200);
header('Content-Type: application/json');
echo json_encode(['alerts' => $firing, 'count' => count($firing)]);
