<?php
/**
 * GET /api/v1/metrics-history?metric=NAME&hours=24
 * Returns historical time-series data from Prometheus query_range.
 */

require_once __DIR__ . '/../../common/config.php';

requireAuth();

$metric = $_GET['metric'] ?? '';
$hours = min(168, max(1, (int) ($_GET['hours'] ?? 24)));

$allowedMetrics = [
    'opensips_dialogs_active' => 'opensips_dialogs_active',
    'opensips_usrloc_contacts' => 'opensips_usrloc_contacts',
    'rtpengine_sessions' => 'rtpengine_sessions',
    'opensips_received_replies_rate' => 'rate(opensips_received_replies_total[1m])',
];

if (!isset($allowedMetrics[$metric])) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unknown metric', 'allowed' => array_keys($allowedMetrics)]);
    exit;
}

$promQuery = $allowedMetrics[$metric];
$end = time();
$start = $end - ($hours * 3600);
$step = max(60, (int) (($hours * 3600) / 100)); // ~100 data points max

$promUrl = sprintf(
    'http://prometheus:9090/api/v1/query_range?query=%s&start=%d&end=%d&step=%d',
    urlencode($promQuery),
    $start,
    $end,
    $step
);

$ctx = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => "Accept: application/json\r\n",
        'timeout' => 10,
    ],
]);

$resp = @file_get_contents($promUrl, false, $ctx);
if ($resp === false) {
    http_response_code(503);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Prometheus unreachable']);
    exit;
}

$decoded = json_decode($resp, true);
if (!is_array($decoded) || ($decoded['status'] ?? '') !== 'success') {
    http_response_code(502);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid response from Prometheus']);
    exit;
}

$result = $decoded['data']['result'] ?? [];
$series = [];
foreach ($result as $r) {
    $values = [];
    foreach ($r['values'] ?? [] as $v) {
        $values[] = [
            't' => (int) $v[0],
            'v' => (float) $v[1],
        ];
    }
    $series[] = [
        'metric' => $r['metric'] ?? [],
        'values' => $values,
    ];
}

http_response_code(200);
header('Content-Type: application/json');
echo json_encode([
    'metric' => $metric,
    'hours'  => $hours,
    'series' => $series,
]);
