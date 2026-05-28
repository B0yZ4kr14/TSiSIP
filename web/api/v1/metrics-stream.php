<?php
/**
 * GET /api/v1/metrics-stream
 * Server-Sent Events endpoint for real-time OpenSIPS metrics.
 * Emits JSON payload every 5 seconds.
 */

require_once __DIR__ . '/../../common/config.php';
require_once __DIR__ . '/../../common/mi-http.php';

// Session auth required
requireAuth();

// Rate limit: one SSE connection per session
static $sseConnections = [];
$sid = session_id();
if (isset($sseConnections[$sid]) && $sseConnections[$sid] > time() - 30) {
    http_response_code(429);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Only one SSE connection per session allowed']);
    exit;
}
$sseConnections[$sid] = time();

// SSE headers
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');

// Disable output buffering
if (function_exists('apache_setenv')) {
    apache_setenv('no-gzip', '1');
}
ini_set('output_buffering', 'off');
ini_set('zlib.output_compression', false);
while (ob_get_level() > 0) {
    ob_end_flush();
}

set_time_limit(0);

$emitInterval = 5;
$maxRuntime = 300; // 5 minutes max per connection
$startTime = time();

function fetchMetrics(): array {
    $metrics = [
        'timestamp' => date('c'),
        'opensips'  => [],
        'trunks'    => [],
        'anomaly'   => [],
    ];

    // OpenSIPS MI: get_statistics all
    if (function_exists('miHttpCall')) {
        $stats = miHttpCall('get_statistics', ['all']);
        if ($stats['success'] && is_array($stats['data'])) {
            $metrics['opensips'] = $stats['data'];
        }
    }

    // OpenSIPS MI: dialog count
    $dlg = miHttpCall('dlg_list_count', []);
    if ($dlg['success'] && is_array($dlg['data'])) {
        $metrics['opensips']['dialogs'] = $dlg['data'];
    }

    // OpenSIPS MI: dispatcher list
    $ds = miHttpCall('ds_list', []);
    if ($ds['success'] && is_array($ds['data'])) {
        $metrics['opensips']['dispatcher'] = $ds['data'];
    }

    // PostgreSQL: trunk provider status
    try {
        $pdo = getDb();
        $stmt = $pdo->query(
            "SELECT id, name, host, port, enabled, max_cps FROM sip_trunk_providers WHERE enabled = true ORDER BY priority"
        );
        $metrics['trunks'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $metrics['trunks'] = [];
    }

    // Anomaly detector status
    $anomalyUrl = 'http://anomaly_detector:8080/api/v1/status';
    $apiKey = getenv('ANOMALY_API_KEY') ?: '';
    $ctx = stream_context_create([
        'http' => [
            'method'  => 'GET',
            'header'  => "X-API-Key: {$apiKey}\r\nAccept: application/json\r\n",
            'timeout' => 2,
        ],
    ]);
    $resp = @file_get_contents($anomalyUrl, false, $ctx);
    if ($resp !== false) {
        $decoded = json_decode($resp, true);
        if (is_array($decoded)) {
            $metrics['anomaly'] = $decoded;
        }
    }

    return $metrics;
}

// Send initial event
$data = fetchMetrics();
echo "event: metrics\n";
echo "data: " . json_encode($data) . "\n\n";
flush();

// Keepalive + metric loop
while (true) {
    sleep($emitInterval);

    // Check client disconnect
    if (connection_aborted()) {
        break;
    }

    // Max runtime guard
    if ((time() - $startTime) > $maxRuntime) {
        echo "event: close\n";
        echo "data: {\"reason\":\"max_runtime\"}\n\n";
        flush();
        break;
    }

    // Send keepalive every interval to detect disconnects faster
    echo "event: ping\n";
    echo "data: " . json_encode(['time' => time()]) . "\n\n";
    flush();

    $data = fetchMetrics();
    echo "event: metrics\n";
    echo "data: " . json_encode($data) . "\n\n";
    flush();
}

