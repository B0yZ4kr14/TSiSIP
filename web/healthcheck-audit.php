<?php
/**
 * TSiSIP OCP — Audit Healthcheck Endpoint
 *
 * Lightweight endpoint for container health checks.
 * Verifies database connectivity and audit table accessibility.
 */

header('Content-Type: application/json');

try {
    require_once __DIR__ . '/common/config.php';
    $pdo = getDb();
    $stmt = $pdo->query('SELECT COUNT(*) FROM ocp_audit_log');
    $count = (int) $stmt->fetchColumn();

    echo json_encode([
        'status' => 'ok',
        'audit_log_count' => $count,
        'timestamp' => date('c'),
    ]);
} catch (Throwable $e) {
    http_response_code(503);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'timestamp' => date('c'),
    ]);
}
