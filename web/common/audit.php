<?php
/**
 * TSiSIP Control Panel — Audit Log Library
 *
 * Append-only audit trail with SHA-256 hash chain.
 * Fails safely — any exception is caught and written to error_log,
 * never propagated to the caller.
 */

/**
 * Convert a boolean-ish database value to a canonical '1'/'0' string.
 */
function _auditBoolToString($value): string {
    if (is_bool($value)) {
        return $value ? '1' : '0';
    }
    if (is_int($value) || is_float($value)) {
        return $value ? '1' : '0';
    }
    if (is_string($value)) {
        $lower = strtolower(trim($value));
        return ($lower === 't' || $lower === 'true' || $lower === '1' || $lower === 'on' || $lower === 'yes') ? '1' : '0';
    }
    return '0';
}

/**
 * Build the canonical string used for SHA-256 hash computation.
 */
function _auditCanonicalHash(
    string $eventTime,
    ?string $userId,
    string $username,
    string $action,
    ?string $resourceType,
    ?string $resourceId,
    string $ip,
    ?string $userAgent,
    bool $success,
    ?string $detailsJson,
    ?string $prevHash
): string {
    return implode('|', [
        $eventTime,
        $userId ?? '',
        $username,
        $action,
        $resourceType ?? '',
        $resourceId ?? '',
        $ip,
        $userAgent ?? '',
        $success ? '1' : '0',
        $detailsJson ?? '',
        $prevHash ?? '',
    ]);
}

/**
 * Log an audit event to the ocp_audit_log table.
 *
 * @param string      $action       Canonical action code (e.g. LOGIN, SUBSCRIBER_CREATE).
 * @param string|null $resourceType Domain entity (e.g. subscriber, dispatcher, ocp_user).
 * @param string|null $resourceId   Primary key or identifier of the affected resource.
 * @param bool        $success      Whether the action completed successfully.
 * @param array|null  $details      Structured payload (must never contain passwords or HA1 hashes).
 */
function logAuditEvent(
    string $action,
    ?string $resourceType = null,
    ?string $resourceId = null,
    bool $success = true,
    ?array $details = null
): void {
    try {
        $pdo = getDb();

        // Session / user context
        $userId   = $_SESSION['ocp_user_id']    ?? null;
        $username = $_SESSION['ocp_username']   ?? 'anonymous';

        // Proxy-aware IP resolution
        $ip = '0.0.0.0';
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'];
            // X-Forwarded-For may contain multiple IPs; use the first (client)
            $first = explode(',', $forwarded)[0];
            $first = trim($first);
            if (filter_var($first, FILTER_VALIDATE_IP)) {
                $ip = $first;
            }
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            if (filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP)) {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
        }

        // User agent
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        // Truncate to column limits
        $username      = substr($username, 0, 64);
        $action        = substr($action, 0, 64);
        $resourceType  = $resourceType !== null ? substr($resourceType, 0, 64) : null;
        $resourceId    = $resourceId   !== null ? substr($resourceId,   0, 255) : null;
        $userAgent     = $userAgent    !== null ? substr($userAgent,    0, 512) : null;

        // Previous hash for chain
        $prevHash = null;
        $prevStmt = $pdo->query('SELECT hash FROM ocp_audit_log ORDER BY id DESC LIMIT 1');
        if ($prevStmt !== false) {
            $prevRow = $prevStmt->fetch(PDO::FETCH_ASSOC);
            if ($prevRow && !empty($prevRow['hash'])) {
                $prevHash = $prevRow['hash'];
            }
        }

        // Use UTC timestamp so the canonical form is deterministic and
        // matches the normalized format used during integrity verification.
        $eventTime = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('c');

        // Encode details safely
        $detailsJson = null;
        if ($details !== null) {
            $detailsJson = json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($detailsJson === false) {
                $detailsJson = '{}';
            }
        }

        // Compute tamper-evident hash
        $canonical = _auditCanonicalHash(
            $eventTime,
            $userId,
            $username,
            $action,
            $resourceType,
            $resourceId,
            $ip,
            $userAgent,
            $success,
            $detailsJson,
            $prevHash
        );
        $hash = hash('sha256', $canonical);

        // Insert audit row
        $stmt = $pdo->prepare(
            'INSERT INTO ocp_audit_log
             (event_time, user_id, username, action, resource_type, resource_id,
              ip_address, user_agent, success, details, prev_hash, hash)
             VALUES (:event_time, :user_id, :username, :action, :resource_type,
                     :resource_id, :ip_address, :user_agent, :success,
                     :details, :prev_hash, :hash)'
        );
        $stmt->execute([
            ':event_time'    => $eventTime,
            ':user_id'       => $userId,
            ':username'      => $username,
            ':action'        => $action,
            ':resource_type' => $resourceType,
            ':resource_id'   => $resourceId,
            ':ip_address'    => $ip,
            ':user_agent'    => $userAgent,
            ':success'       => $success,
            ':details'       => $detailsJson,
            ':prev_hash'     => $prevHash,
            ':hash'          => $hash,
        ]);
    } catch (Throwable $e) {
        error_log('Audit log failed: ' . $e->getMessage());
    }
}

/**
 * Verify the integrity of the audit log hash chain.
 *
 * Iterates rows ordered by id, recomputes the expected hash for each row,
 * and validates that prev_hash links are unbroken.
 *
 * @return array List of per-row results or ['error' => string] on DB failure.
 */
function verifyAuditLogIntegrity(): array {
    try {
        $pdo = getDb();
    } catch (Throwable $e) {
        error_log('Audit log integrity check failed: ' . $e->getMessage());
        return ['error' => $e->getMessage()];
    }

    $results = [];
    $prevHash = null;
    $lastId = 0;
    $chunkSize = 1000;

    do {
        try {
            $stmt = $pdo->prepare('SELECT * FROM ocp_audit_log WHERE id > :last_id ORDER BY id ASC LIMIT :limit');
            $stmt->bindValue(':last_id', $lastId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $chunkSize, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('Audit log integrity check failed: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }

        foreach ($rows as $row) {
            // Normalize PostgreSQL timestamp string to ISO-8601 with 'T' in UTC
            // so it matches the format generated by logAuditEvent().
            $eventTime = $row['event_time'];
            try {
                $dt = new DateTimeImmutable($eventTime, new DateTimeZone('UTC'));
                $eventTime = $dt->format('c');
            } catch (Exception $e) {
                // keep raw value
            }

            $expectedHash = hash('sha256', _auditCanonicalHash(
                $eventTime,
                $row['user_id'] ?? null,
                $row['username'],
                $row['action'],
                $row['resource_type'] ?? null,
                $row['resource_id']   ?? null,
                $row['ip_address'],
                $row['user_agent']    ?? null,
                _auditBoolToString($row['success']) === '1',
                $row['details'] ?? null,
                $row['prev_hash']
            ));

            $hashValid = ($expectedHash === $row['hash']);
            // Chain is valid if prev_hash is genesis or points to an existing earlier record
            $chainValid = ($row['prev_hash'] === 'genesis') || ($row['prev_hash'] === $prevHash);
            // If prev_hash doesn't match immediate predecessor, verify it exists in history (tolerates gaps from retention purge)
            if (!$chainValid && $row['prev_hash'] !== null) {
                $checkStmt = $pdo->prepare('SELECT 1 FROM ocp_audit_log WHERE hash = :hash LIMIT 1');
                $checkStmt->execute([':hash' => $row['prev_hash']]);
                $chainValid = ($checkStmt->fetchColumn() !== false);
            }
            $valid = $hashValid && $chainValid;

            $results[] = [
                'id'            => (int) $row['id'],
                'valid'         => $valid,
                'expected_hash' => $expectedHash,
                'actual_hash'   => $row['hash'],
                'chain_valid'   => $chainValid,
                'hash_valid'    => $hashValid,
            ];

            $prevHash = $row['hash'];
            $lastId = (int) $row['id'];
        }
    } while (count($rows) === $chunkSize);

    return $results;
}
