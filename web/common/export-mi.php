<?php
/**
 * TSiSIP — Generic MI Data Export Endpoint
 *
 * Exports OpenSIPS MI command output as CSV or JSON.
 * Supports flat array-of-objects responses.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/mi-http.php';

header('Content-Type: application/json; charset=utf-8');

requireAuth();

$cmd = $_GET['cmd'] ?? '';
$paramsRaw = $_GET['params'] ?? '[]';
$format = $_GET['format'] ?? 'json';
$csrfToken = $_GET['csrf_token'] ?? '';

if (!validateCsrfToken($csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => _('Invalid CSRF token')]);
    exit;
}

// Whitelist of exportable read-only commands
$exportable = [
    'pike_list', 'ratelimit_status', 'htable_dump', 'nh_show_sockets',
    'nh_show_ping', 'tcp_list', 'dlg_list', 'ps', 'list_blacklists',
    'list_timers', 'ul_dump', 'version', 'which', 'get_statistics',
    'clusterer_list', 'ds_list', 'lb_list', 'rtpengine_show',
    'uac_reg_list', 'cc_list_agents', 'sip_trace_status',
    'status_report', 'tls_list', 'list_sockets',
];

if (!in_array($cmd, $exportable, true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => _('Command not exportable')]);
    exit;
}

$params = json_decode($paramsRaw, true);
if (!is_array($params)) {
    $params = [];
}

$result = miHttpCall($cmd, $params);

if (!$result['success']) {
    http_response_code(502);
    echo json_encode(['success' => false, 'error' => $result['error']]);
    exit;
}

$data = $result['data'];
$safeCmd = preg_replace('/[^a-z0-9_-]/i', '_', $cmd);
$timestamp = date('Ymd-His');

if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename={$safeCmd}-{$timestamp}.csv");

    $out = fopen('php://output', 'w');

    // Normalize data to array of arrays
    $rows = [];
    if (is_array($data) && isset($data[0]) && is_array($data[0])) {
        $rows = $data;
    } elseif (is_array($data)) {
        $rows = [$data];
    }

    if (!empty($rows)) {
        // Write headers from first row keys
        fputcsv($out, array_keys($rows[0]));
        foreach ($rows as $row) {
            fputcsv($out, array_values($row));
        }
    }

    fclose($out);
} else {
    header('Content-Type: application/json; charset=utf-8');
    header("Content-Disposition: attachment; filename={$safeCmd}-{$timestamp}.json");
    echo json_encode(['success' => true, 'command' => $cmd, 'exported_at' => date('c'), 'data' => $data], JSON_PRETTY_PRINT);
}
