<?php
/**
 * dispatcher-probe.php — SIP OPTIONS probe to a destination
 * Feature 035
 */

require_once __DIR__ . '/../../common/config.php';

requireAuth();
checkPasswordChange();

$destination = $_GET['destination'] ?? '';
if (empty($destination) || !preg_match('/^sip(s)?:[^\s]+$/i', $destination)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid destination URI']);
    exit;
}

// Parse sip:host:port
$parsed = parse_url($destination);
$host = $parsed['host'] ?? '';
$port = $parsed['port'] ?? 5060;
if (empty($host)) {
    http_response_code(400);
    echo json_encode(['error' => 'Could not parse host from destination']);
    exit;
}

$callId = uniqid('probe-', true);
$fromTag = uniqid('tag-', true);
$branch = 'z9hG4bK' . uniqid();

$msg = "OPTIONS {$destination} SIP/2.0\r\n" .
    "Via: SIP/2.0/UDP 127.0.0.1:5060;branch={$branch}\r\n" .
    "From: <sip:probe@localhost>;tag={$fromTag}\r\n" .
    "To: <{$destination}>\r\n" .
    "Call-ID: {$callId}\r\n" .
    "CSeq: 1 OPTIONS\r\n" .
    "Max-Forwards: 70\r\n" .
    "Content-Length: 0\r\n\r\n";

$start = microtime(true);
$sock = @fsockopen('udp://' . $host, $port, $errno, $errstr, 3);
$result = ['reachable' => false, 'code' => null, 'rtt_ms' => null, 'error' => null];

if ($sock) {
    fwrite($sock, $msg);
    stream_set_timeout($sock, 3);
    $response = fread($sock, 1024);
    $info = stream_get_meta_data($sock);
    fclose($sock);

    $result['rtt_ms'] = round((microtime(true) - $start) * 1000, 2);

    if ($info['timed_out']) {
        $result['error'] = 'Timeout waiting for response';
    } elseif (!empty($response)) {
        if (preg_match('/SIP\/2\.0 (\d{3})/', $response, $m)) {
            $result['code'] = (int)$m[1];
            $result['reachable'] = ($result['code'] >= 100 && $result['code'] < 700);
        }
    } else {
        $result['error'] = 'No response received';
    }
} else {
    $result['error'] = $errstr ?: 'Connection failed';
}

http_response_code(200);
header('Content-Type: application/json');
echo json_encode($result);
