<?php
/**
 * GET /api/v1/audit
 * Query audit log with filters.
 */

$pdo = getDb();
$where = ['1=1'];
$params = [];

$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$action = $_GET['action'] ?? '';
$user = $_GET['user'] ?? '';

if ($from !== '') {
    $where[] = "event_time >= :from";
    $params[':from'] = $from;
}
if ($to !== '') {
    $where[] = "event_time <= :to";
    $params[':to'] = $to;
}
if ($action !== '') {
    $where[] = "action = :action";
    $params[':action'] = $action;
}
if ($user !== '') {
    $where[] = "username = :user";
    $params[':user'] = $user;
}

$sql = "SELECT id, event_time, username, action, resource_type, resource_id, success, ip_address, details
        FROM auth_audit_log WHERE " . implode(' AND ', $where) . " ORDER BY event_time DESC LIMIT 500";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

http_response_code(200);
echo json_encode(['data' => $rows], JSON_PRETTY_PRINT);
