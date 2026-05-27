<?php
/**
 * GET /api/v1/audit — Query audit log
 */

$pdo = getDb();

$limit = min((int)($_GET['limit'] ?? 50), 500);
$offset = (int)($_GET['offset'] ?? 0);

$where = [];
$params = [];

if (!empty($_GET['action'])) {
    $where[] = "action_type = :action";
    $params[':action'] = $_GET['action'];
}
if (!empty($_GET['user'])) {
    $where[] = "username = :user";
    $params[':user'] = $_GET['user'];
}
if (!empty($_GET['from'])) {
    $where[] = "created_at >= :from";
    $params[':from'] = $_GET['from'];
}
if (!empty($_GET['to'])) {
    $where[] = "created_at <= :to";
    $params[':to'] = $_GET['to'];
}

$sql = "SELECT id, action_type, target_type, target_id, username, success, details, ip_address, created_at
        FROM ocp_audit_log";
if (!empty($where)) {
    $sql .= " WHERE " . implode(' AND ', $where);
}
$sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['entries' => $entries, 'limit' => $limit, 'offset' => $offset]);
