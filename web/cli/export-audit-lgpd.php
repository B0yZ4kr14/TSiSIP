#!/usr/bin/env php
<?php
/**
 * TSiSIP OCP — LGPD Right of Access Export (CLI)
 *
 * Generates a machine-readable JSON export of all audit events
 * for a given subscriber (username) or SIP URI.
 *
 * Uses chunked queries (LIMIT/OFFSET) and streams JSON output
 * to avoid loading large result sets into memory.
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

const CHUNK_SIZE = 10000;

/**
 * Stream query results through a generator to keep memory bounded.
 */
function streamQueryRows(PDO $pdo, string $sql, string $subscriber): Generator {
    $offset = 0;
    do {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':subscriber' => $subscriber, ':offset' => $offset]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            yield $row;
        }
        $offset += CHUNK_SIZE;
    } while (count($rows) === CHUNK_SIZE);
}

/**
 * Write a JSON array to a stream, consuming a generator.
 */
function writeJsonArrayFromGenerator($fp, Generator $gen, int &$count): void {
    $first = true;
    fwrite($fp, '[');
    foreach ($gen as $row) {
        if (!$first) {
            fwrite($fp, ',');
        }
        $first = false;
        fwrite($fp, "\n" . json_encode($row, JSON_UNESCAPED_UNICODE));
        $count++;
    }
    fwrite($fp, "\n]");
}

try {
    $pdo = getDb();

    $fp = fopen($outputPath, 'w');
    if ($fp === false) {
        throw new RuntimeException("Cannot open output: {$outputPath}");
    }

    $cdrSql = "SELECT id, call_id, call_start, call_end, duration, from_user, to_user,
                      call_status, setup_time_ms, tenant_id, backend_label, created_at
               FROM cdr
               WHERE from_user = :subscriber OR to_user = :subscriber
               ORDER BY call_start DESC
               LIMIT " . CHUNK_SIZE . " OFFSET :offset";

    $ocpAuditSql = "SELECT id, event_time, username, action, resource_type, resource_id, success, details, ip_address
                    FROM ocp_audit_log
                    WHERE username = :subscriber
                       OR details->>'subscriber' = :subscriber
                       OR resource_id = :subscriber
                    ORDER BY event_time DESC
                    LIMIT " . CHUNK_SIZE . " OFFSET :offset";

    $authAuditSql = "SELECT id, event_time, username, domain, source_ip, sip_method, result, call_id
                     FROM auth_audit_log
                     WHERE username = :subscriber
                     ORDER BY event_time DESC
                     LIMIT " . CHUNK_SIZE . " OFFSET :offset";

    $countCdr = 0;
    $countOcp = 0;
    $countAuth = 0;

    fwrite($fp, "{\n");
    fwrite($fp, '  "export_type": "lgpd_right_of_access",' . "\n");
    fwrite($fp, '  "subscriber": ' . json_encode($subscriber) . "," . "\n");
    fwrite($fp, '  "generated_at": ' . json_encode(date('c')) . "," . "\n");

    fwrite($fp, '  "cdrs": ');
    writeJsonArrayFromGenerator($fp, streamQueryRows($pdo, $cdrSql, $subscriber), $countCdr);
    fwrite($fp, "," . "\n");

    fwrite($fp, '  "ocp_audit_events": ');
    writeJsonArrayFromGenerator($fp, streamQueryRows($pdo, $ocpAuditSql, $subscriber), $countOcp);
    fwrite($fp, "," . "\n");

    fwrite($fp, '  "auth_audit_events": ');
    writeJsonArrayFromGenerator($fp, streamQueryRows($pdo, $authAuditSql, $subscriber), $countAuth);
    fwrite($fp, "\n");

    fwrite($fp, "}\n");
    fclose($fp);

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
