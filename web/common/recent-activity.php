<?php
/**
 * TSiSIP Control Panel — Recent Activity Widget
 */
require_once __DIR__ . '/config.php';

function getRecentActivity(int $limit = 5): array {
    $pdo = getDb();
    $stmt = $pdo->prepare(
        "SELECT event_time, username, action, resource_type, resource_id, success
         FROM ocp_audit_log
         ORDER BY event_time DESC
         LIMIT :limit"
    );
    $stmt->execute([':limit' => $limit]);
    return $stmt->fetchAll();
}
