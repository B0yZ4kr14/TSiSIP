<?php
/**
 * TSiSIP Admin API — Audit Logger
 */

function logProxyAudit(string $action, string $resourceType, string $resourceId, bool $result, array $details = []): void {
    try {
        $pdo = getPdo();
        $stmt = $pdo->prepare(
            "INSERT INTO auth_audit_log (event_time, action, resource_type, resource_id, user_id, result, details)
             VALUES (NOW(), :action, :resource_type, :resource_id, 'admin-api', :result, :details)"
        );
        $stmt->execute([
            ':action'        => $action,
            ':resource_type' => $resourceType,
            ':resource_id'   => $resourceId,
            ':result'        => $result ? 't' : 'f',
            ':details'       => json_encode($details),
        ]);
    } catch (PDOException $e) {
        error_log('Audit log failed: ' . $e->getMessage());
    }
}
