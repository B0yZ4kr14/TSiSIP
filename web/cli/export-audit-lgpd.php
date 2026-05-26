#!/usr/bin/env php
<?php
/**
 * TSiSIP OCP — LGPD Right of Access Export (CLI)
 *
 * Generates a machine-readable JSON export of all audit events
 * for a given subscriber (username) or SIP URI.
 *
 * Usage:
 *   php export-audit-lgpd.php --subscriber=test@example.com [--output=/path/to/export.json]
 */

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

require_once __DIR__ . '/../common/config.php';
require_once __DIR__ . '/../common/audit.php';

$subscriber = null;
$outputPath = 'php://stdout';

foreach ($argv as $arg) {
    if (strpos($arg, '--subscriber=') === 0) {
        $subscriber = substr($arg, 13);
    } elseif (strpos($arg, '--output=') === 0) {
        $outputPath = substr($arg, 9);
    }
}

if (empty($subscriber)) {
    fwrite(STDERR, "Usage: php export-audit-lgpd.php --subscriber=<user@domain> [--output=<file.json>]\n");
    exit(1);
}

try {
    $pdo = getDb();

    $cdrStmt = $pdo->prepare(
        "SELECT id, call_id, call_start, call_end, duration, from_user, to_user,
                call_status, setup_time_ms, tenant_id, backend_label, created_at
         FROM cdr
         WHERE from_user = :subscriber OR to_user = :subscriber
         ORDER BY call_start DESC"
    );
    $cdrStmt->execute([':subscriber' => $subscriber]);
    $cdrs = $cdrStmt->fetchAll(PDO::FETCH_ASSOC);

    $ocpAuditStmt = $pdo->prepare(
        "SELECT id, event_time, username, action, resource_type, resource_id, success, details, ip_address
         FROM ocp_audit_log
         WHERE username = :subscriber
            OR details->>'subscriber' = :subscriber
            OR resource_id = :subscriber
         ORDER BY event_time DESC"
    );
    $ocpAuditStmt->execute([':subscriber' => $subscriber]);
    $ocpAudits = $ocpAuditStmt->fetchAll(PDO::FETCH_ASSOC);

    $authAuditStmt = $pdo->prepare(
        "SELECT id, event_time, username, domain, source_ip, sip_method, result, call_id
         FROM auth_audit_log
         WHERE username = :subscriber
         ORDER BY event_time DESC"
    );
    $authAuditStmt->execute([':subscriber' => $subscriber]);
    $authAudits = $authAuditStmt->fetchAll(PDO::FETCH_ASSOC);

    $export = [
        'export_type' => 'lgpd_right_of_access',
        'subscriber' => $subscriber,
        'generated_at' => date('c'),
        'cdrs' => $cdrs,
        'ocp_audit_events' => $ocpAudits,
        'auth_audit_events' => $authAudits,
    ];

    $json = json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        throw new RuntimeException('JSON encode failed: ' . json_last_error_msg());
    }

    file_put_contents($outputPath, $json);

    $countCdr = count($cdrs);
    $countOcp = count($ocpAudits);
    $countAuth = count($authAudits);
    fwrite(STDERR, "Exported {$countCdr} CDRs, {$countOcp} OCP audit events, and {$countAuth} auth events for {$subscriber}.\n");

    logAuditEvent('LGPD_EXPORT', 'subscriber', $subscriber, true, [
        'cdr_count' => $countCdr,
        'ocp_audit_count' => $countOcp,
        'auth_audit_count' => $countAuth,
        'output' => $outputPath,
    ]);

    exit(0);
} catch (Throwable $e) {
    error_log('LGPD export failed: ' . $e->getMessage());
    fwrite(STDERR, "Export failed: " . $e->getMessage() . "\n");
    exit(1);
}
