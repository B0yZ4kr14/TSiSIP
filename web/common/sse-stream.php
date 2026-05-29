<?php
/**
 * TSiSIP Control Panel — Server-Sent Events (SSE) Stream
 * Lightweight alternative to WebSocket for real-time updates.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/mi-http.php';

// Validate session via token
$token = $_GET['token'] ?? '';
if (!validateCsrfToken($token)) {
    http_response_code(403);
    echo "event: error\ndata: Invalid token\n\n";
    exit;
}

// SSE headers
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');

set_time_limit(0);
ob_implicit_flush(true);

$lastData = null;
$counter = 0;

while (true) {
    $counter++;
    $data = [];

    // Gateway status
    try {
        $dsData = miHttpCall('ds_list', []);
        $data['gateways'] = [];
        foreach ($dsData['Partitions'] ?? [] as $part) {
            foreach ($part['SETS'] ?? [] as $set) {
                foreach ($set['Destinations'] ?? [] as $dest) {
                    $data['gateways'][] = [
                        'uri' => $dest['URI'] ?? '',
                        'state' => $dest['State'] ?? 'Unknown',
                        'setid' => $set['SetID'] ?? 0,
                    ];
                }
            }
        }
    } catch (Throwable $e) {
        $data['gateways'] = [];
    }

    // Active dialogs
    try {
        $dlgData = miHttpCall('dlg_list', []);
        $data['dialogs'] = count($dlgData['Dialogs'] ?? []);
    } catch (Throwable $e) {
        $data['dialogs'] = 0;
    }

    // RTPengine
    try {
        $rtpData = miHttpCall('rtpengine_show', ['all' => true]);
        $data['rtpengine'] = [
            'sessions' => count($rtpData['Sessions'] ?? []),
        ];
    } catch (Throwable $e) {
        $data['rtpengine'] = ['sessions' => 0];
    }

    // Memory status
    try {
        $pkgResult = miHttpCall('get_statistics', ['pkg:' => true]);
        $shmResult = miHttpCall('get_statistics', ['shm:' => true]);
        $pkgUsed = 0; $pkgTotal = 1;
        $shmUsed = 0; $shmTotal = 1;
        foreach ($pkgResult['data'] ?? [] as $stat) {
            if (str_contains($stat['name'] ?? '', 'used')) $pkgUsed = (int)($stat['value'] ?? 0);
            if (str_contains($stat['name'] ?? '', 'total_size')) $pkgTotal = (int)($stat['value'] ?? 1);
        }
        foreach ($shmResult['data'] ?? [] as $stat) {
            if (str_contains($stat['name'] ?? '', 'used')) $shmUsed = (int)($stat['value'] ?? 0);
            if (str_contains($stat['name'] ?? '', 'total_size')) $shmTotal = (int)($stat['value'] ?? 1);
        }
        $data['memory'] = [
            'pkg_pct' => round(($pkgUsed / $pkgTotal) * 100, 1),
            'shm_pct' => round(($shmUsed / $shmTotal) * 100, 1),
            'pkg_used' => $pkgUsed,
            'shm_used' => $shmUsed,
        ];
    } catch (Throwable $e) {
        $data['memory'] = ['pkg_pct' => 0, 'shm_pct' => 0, 'pkg_used' => 0, 'shm_used' => 0];
    }

    // Process count
    try {
        $psData = miHttpCall('ps', []);
        $data['processes'] = count($psData['data'] ?? []);
    } catch (Throwable $e) {
        $data['processes'] = 0;
    }

    // Pike blocked count
    try {
        $pikeData = miHttpCall('pike_list', []);
        $data['pike_blocked'] = count($pikeData['data'] ?? []);
    } catch (Throwable $e) {
        $data['pike_blocked'] = 0;
    }

    // Rate limit pipes
    try {
        $rlData = miHttpCall('ratelimit_status', []);
        $data['ratelimit_pipes'] = count($rlData['data'] ?? []);
    } catch (Throwable $e) {
        $data['ratelimit_pipes'] = 0;
    }

    // TCP connections
    try {
        $tcpData = miHttpCall('tcp_list', []);
        $data['tcp_connections'] = count($tcpData['data'] ?? []);
    } catch (Throwable $e) {
        $data['tcp_connections'] = 0;
    }

    // Blacklists
    try {
        $blData = miHttpCall('list_blacklists', []);
        $data['blacklists'] = count($blData['data'] ?? []);
    } catch (Throwable $e) {
        $data['blacklists'] = 0;
    }

    // USRLoc contacts
    try {
        $ulData = miHttpCall('ul_dump', []);
        $data['usrloc_contacts'] = count($ulData['data'] ?? []);
    } catch (Throwable $e) {
        $data['usrloc_contacts'] = 0;
    }

    // Trunk provider status (from PostgreSQL)
    try {
        $pdo = getDb();
        $stmt = $pdo->query(
            "SELECT id, name, host, port, enabled, max_cps FROM sip_trunk_providers ORDER BY priority"
        );
        $data['trunks'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $data['trunks'] = [];
    }

    // Auto-healing events (Feature 036)

    try {

        $pdo = getDb();

        $stmt = $pdo->query(

            "SELECT id, action, setid, destination_id, new_snapshot, created_at

             FROM dispatcher_change_log

             WHERE action IN ('AUTO_ROLLBACK','AUTO_FAILOVER','AUTO_PROBE')

             ORDER BY created_at DESC LIMIT 10"

        );

        $data['autoheal'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (Throwable $e) {

        $data['autoheal'] = [];

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
            $data['anomaly'] = $decoded;
        }
    } else {
        $data['anomaly'] = ['current_rps' => 0, 'z_score' => 0];
    }

    // dispatcher.reload event (Feature 035)
    if (!empty($_SESSION['dispatcher_reload_event'])) {
        $data['dispatcher_reload'] = $_SESSION['dispatcher_reload_event'];
        unset($_SESSION['dispatcher_reload_event']);
    }

    // Only send if data changed (or every 5th iteration ~25s)
    $json = json_encode($data);
    if ($json !== $lastData || $counter % 5 === 0) {
        echo "data: " . $json . "\n\n";
        $lastData = $json;
    }

    // Send heartbeat
    if ($counter % 6 === 0) {
        echo "event: heartbeat\ndata: " . time() . "\n\n";
    }

    // dispatcher.reload event broadcast
    if (!empty($_SESSION['dispatcher_reload_broadcast'])) {
        echo "event: dispatcher.reload\ndata: " . json_encode(['reloaded_at' => time()]) . "\n\n";
        unset($_SESSION['dispatcher_reload_broadcast']);
    }

    if (connection_aborted()) {
        break;
    }

    sleep(5);
}
