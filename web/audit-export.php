<?php
/**
 * TSiSIP Control Panel — Audit Log Export
 *
 * Streams audit log data as CSV or JSON.
 * Requires devops or admin role.
 */

require_once __DIR__ . '/common/config.php';

requireAuth();
checkPasswordChange();
requireRole('devops');

$format = $_GET['format'] ?? '';
if ($format !== 'csv' && $format !== 'json' && $format !== 'text') {
    http_response_code(400);
    echo _('Invalid format. Use ?format=csv, ?format=json, or ?format=text');
    exit;
}

// --- Filter handling (same as audit-log.php) ---
$filters = [
    'from'          => $_GET['from']          ?? date('Y-m-d', strtotime('-7 days')),
    'to'            => $_GET['to']            ?? date('Y-m-d'),
    'action'        => trim($_GET['action']        ?? ''),
    'username'      => trim($_GET['username']      ?? ''),
    'resource_type' => trim($_GET['resource_type'] ?? ''),
    'success'       => $_GET['success']       ?? '',
    'ip_address'    => trim($_GET['ip_address']    ?? ''),
    'q'             => trim($_GET['q']             ?? ''),
];

$where = [];
$params = [];

// Date range (inclusive, index-friendly)
$fromTime = $filters['from'] . ' 00:00:00';
$toTime   = date('Y-m-d', strtotime($filters['to'] . ' +1 day')) . ' 00:00:00';
$where[] = 'event_time >= :from_time AND event_time < :to_time';
$params[':from_time'] = $fromTime;
$params[':to_time']   = $toTime;

if ($filters['action'] !== '') {
    $where[] = 'action = :action';
    $params[':action'] = $filters['action'];
}
if ($filters['username'] !== '') {
    $where[] = 'username ILIKE :username';
    $params[':username'] = '%' . $filters['username'] . '%';
}
if ($filters['resource_type'] !== '') {
    $where[] = 'resource_type = :resource_type';
    $params[':resource_type'] = $filters['resource_type'];
}
if ($filters['success'] !== '') {
    $where[] = 'success = :success';
    $params[':success'] = ($filters['success'] === '1');
}
if ($filters['ip_address'] !== '') {
    $where[] = 'ip_address::text ILIKE :ip_address';
    $params[':ip_address'] = '%' . $filters['ip_address'] . '%';
}
if ($filters['q'] !== '') {
    $where[] = 'details::text ILIKE :q';
    $params[':q'] = '%' . $filters['q'] . '%';
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$pdo = getDb();

// Prepare streaming statement
$columns = [
    'id', 'event_time', 'user_id', 'username', 'action',
    'resource_type', 'resource_id', 'ip_address', 'user_agent',
    'success', 'details', 'prev_hash', 'hash',
];

$stmt = $pdo->prepare(
    "SELECT " . implode(', ', $columns) . "
     FROM ocp_audit_log
     $whereSql
     ORDER BY event_time DESC"
);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->execute();

// Build filename
$filename = 'tsisip-audit-log-' . date('Ymd-His') . '.' . $format;

if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-store, no-cache, must-revalidate');

    // UTF-8 BOM for Excel compatibility
    echo "\xEF\xBB\xBF";

    $output = fopen('php://output', 'w');
    fputcsv($output, $columns);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $outRow = [];
        foreach ($columns as $col) {
            $val = $row[$col] ?? '';
            if ($col === 'details' && $val !== '') {
                // Flatten JSON details to a single-line string for CSV
                $decoded = json_decode($val, true);
                if ($decoded !== null) {
                    $val = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
            }
            $outRow[] = $val;
        }
        fputcsv($output, $outRow);
    }

    fclose($output);

    // Log export after stream completes
    try {
        logAuditEvent('EXPORT_CSV', 'audit_log', null, true, [
            'filter_from' => $filters['from'],
            'filter_to'   => $filters['to'],
            'filter_action' => $filters['action'] ?: null,
        ]);
    } catch (Throwable $e) {
        error_log('Audit export CSV logging failed: ' . $e->getMessage());
    }
} elseif ($format === 'text') {
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="tsisip-audit-log-' . date('Ymd-His') . '.txt"');
    header('Cache-Control: no-store, no-cache, must-revalidate');

    echo str_repeat('=', 80) . "\n";
    echo _('TSiSIP Audit Log Export') . "\n";
    echo str_repeat('=', 80) . "\n";
    echo _('Filters: ') . json_encode($filters, JSON_UNESCAPED_UNICODE) . "\n";
    echo str_repeat('-', 80) . "\n";

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo _('ID: ') . ($row['id'] ?? 'N/A') . "\n";
        echo _('Time: ') . ($row['event_time'] ?? 'N/A') . "\n";
        echo _('User: ') . ($row['username'] ?? 'N/A') . "\n";
        echo _('Action: ') . ($row['action'] ?? 'N/A') . "\n";
        echo _('Resource: ') . ($row['resource_type'] ?? 'N/A') . ' / ' . ($row['resource_id'] ?? 'N/A') . "\n";
        echo _('IP: ') . ($row['ip_address'] ?? 'N/A') . "\n";
        echo _('Success: ') . (($row['success'] ?? false) ? _('Yes') : _('No')) . "\n";
        if (!empty($row['details'])) {
            $decoded = json_decode($row['details'], true);
            if ($decoded !== null) {
                echo _('Details: ') . json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
            } else {
                echo _('Details: ') . $row['details'] . "\n";
            }
        }
        echo str_repeat('-', 80) . "\n";
    }

    try {
        logAuditEvent('EXPORT_TEXT', 'audit_log', null, true, [
            'filter_from' => $filters['from'],
            'filter_to'   => $filters['to'],
            'filter_action' => $filters['action'] ?: null,
        ]);
    } catch (Throwable $e) {
        error_log('Audit export TEXT logging failed: ' . $e->getMessage());
    }
} else {
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-store, no-cache, must-revalidate');

    echo "[\n";
    $first = true;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (!$first) {
            echo ",\n";
        }
        $first = false;
        // Decode details JSONB so the JSON output contains a real object, not a string
        if (!empty($row['details'])) {
            $decoded = json_decode($row['details'], true);
            if ($decoded !== null) {
                $row['details'] = $decoded;
            }
        }
        echo json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    echo "\n]\n";

    // Log export after stream completes
    try {
        logAuditEvent('EXPORT_JSON', 'audit_log', null, true, [
            'filter_from' => $filters['from'],
            'filter_to'   => $filters['to'],
            'filter_action' => $filters['action'] ?: null,
        ]);
    } catch (Throwable $e) {
        error_log('Audit export JSON logging failed: ' . $e->getMessage());
    }
}
