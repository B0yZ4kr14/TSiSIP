#!/usr/bin/env php
<?php
/**
 * TSiSIP OCP — Audit Log Retention Purge (CLI)
 *
 * Runs ocp_audit_log_retention_purge() and logs the action.
 * Intended to be called from cron.
 */

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

require_once __DIR__ . '/../common/config.php';
require_once __DIR__ . '/../common/audit.php';

$retentionDays = getenv('OCP_AUDIT_RETENTION_DAYS');
if ($retentionDays === false || $retentionDays === '') {
    $retentionDays = 90;
} else {
    $retentionDays = (int) $retentionDays;
    if ($retentionDays < 1) {
        fwrite(STDERR, "Invalid OCP_AUDIT_RETENTION_DAYS: must be >= 1\n");
        exit(1);
    }
}

try {
    $pdo = getDb();

    // Log the retention run start
    logAuditEvent('RETENTION_RUN', 'audit_log', null, true, [
        'retention_days' => $retentionDays,
        'trigger' => 'scheduled',
    ]);

    $stmt = $pdo->prepare('SELECT ocp_audit_log_retention_purge(:days)');
    $stmt->execute([':days' => $retentionDays]);
    $deleted = (int) $stmt->fetchColumn();

    echo "Purged {$deleted} audit log rows older than {$retentionDays} days.\n";
    exit(0);
} catch (Throwable $e) {
    error_log('Audit retention purge failed: ' . $e->getMessage());
    // Attempt to log failure
    try {
        logAuditEvent('RETENTION_RUN', 'audit_log', null, false, [
            'retention_days' => $retentionDays,
            'error' => $e->getMessage(),
        ]);
    } catch (Throwable $logEx) {
        error_log('Failed to log retention failure: ' . $logEx->getMessage());
    }
    fwrite(STDERR, "Purge failed: " . $e->getMessage() . "\n");
    exit(1);
}
