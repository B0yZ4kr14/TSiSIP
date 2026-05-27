#!/usr/bin/env php
<?php
/**
 * TSiSIP OCP — CDR Anonymization for LGPD Compliance (CLI)
 *
 * Anonymizes CDRs older than the retention period instead of deleting.
 * Preserves billing aggregates (duration, call counts) while removing
 * personally identifiable information.
 *
 * Usage:
 *   php purge-cdr.php [--dry-run]
 *
 * Environment:
 *   LGPD_ANONYMIZE_AFTER_DAYS — days after which CDRs are anonymized (default: 365)
 */

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

require_once __DIR__ . '/../common/config.php';
require_once __DIR__ . '/../common/audit.php';

$dryRun = in_array('--dry-run', $argv, true);

$days = getenv('LGPD_ANONYMIZE_AFTER_DAYS');
if ($days === false || $days === '') {
    $days = 365;
} else {
    $days = (int) $days;
    if ($days < 1) {
        fwrite(STDERR, "Invalid LGPD_ANONYMIZE_AFTER_DAYS: must be >= 1\n");
        exit(1);
    }
}

try {
    $pdo = getDb();

    // Count eligible CDRs
    $countStmt = $pdo->prepare(
        "SELECT COUNT(*) FROM cdr WHERE call_start < NOW() - INTERVAL '1 day' * :days AND from_user NOT LIKE 'anon_%'"
    );
    $countStmt->execute([':days' => $days]);
    $eligible = (int) $countStmt->fetchColumn();

    if ($dryRun) {
        echo "[DRY-RUN] {$eligible} CDRs eligible for anonymization (older than {$days} days).\n";
        exit(0);
    }

    if ($eligible === 0) {
        echo "No CDRs to anonymize.\n";
        exit(0);
    }

    logAuditEvent('LGPD_CDR_ANON', 'cdr', null, true, [
        'retention_days' => $days,
        'eligible_rows' => $eligible,
    ]);

    // Anonymize: hash from_user/to_user, nullify IPs, preserve duration/count
    $updateStmt = $pdo->prepare(
        "UPDATE cdr SET
            from_user = 'anon_' || SUBSTRING(MD5(from_user || call_id) FROM 1 FOR 16),
            to_user = 'anon_' || SUBSTRING(MD5(to_user || call_id) FROM 1 FOR 16),
            source_ip = NULL,
            destination_ip = NULL
         WHERE call_start < NOW() - INTERVAL '1 day' * :days
           AND from_user NOT LIKE 'anon_%'"
    );
    $updateStmt->execute([':days' => $days]);
    $anonymized = $updateStmt->rowCount();

    echo "Anonymized {$anonymized} CDRs older than {$days} days.\n";
    exit(0);
} catch (Throwable $e) {
    error_log('CDR anonymization failed: ' . $e->getMessage());
    try {
        logAuditEvent('LGPD_CDR_ANON', 'cdr', null, false, [
            'retention_days' => $days,
            'error' => $e->getMessage(),
        ]);
    } catch (Throwable $logEx) {
        error_log('Failed to log CDR anonymization failure: ' . $logEx->getMessage());
    }
    fwrite(STDERR, "Anonymization failed: " . $e->getMessage() . "\n");
    exit(1);
}
