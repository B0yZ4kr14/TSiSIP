<?php
/**
 * TSiSIP Control Panel — Server-Sent Events (SSE) Stream
 * Lightweight alternative to WebSocket for real-time updates.
 */
require_once __DIR__ . '/config.php';

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

    if (connection_aborted()) {
        break;
    }

    sleep(5);
}
