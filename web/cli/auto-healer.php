<?php
/**
 * auto-healer.php — Auto-Healing SIP Infrastructure (Feature 036)
 *
 * CLI service that polls dispatcher health and triggers automatic remediation.
 * Intended to run via cron every minute or as a systemd timer.
 *
 * Usage: php /var/www/html/cli/auto-healer.php [--dry-run]
 */

require_once __DIR__ . '/../common/config.php';
require_once __DIR__ . '/../common/mi-http.php';

$dryRun = in_array('--dry-run', $argv, true);
$logPrefix = $dryRun ? '[DRY-RUN]' : '[AUTOHEAL]';

function logMsg(string $msg): void {
    global $logPrefix;
    $line = date('c') . ' ' . $logPrefix . ' ' . $msg;
    echo $line . PHP_EOL;
    error_log($line);
}

function getConfig(PDO $pdo, string $key, string $default): string {
    $stmt = $pdo->prepare("SELECT value FROM autoheal_config WHERE key = :k");
    $stmt->execute([':k' => $key]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['value'] : $default;
}

function probeDestination(string $destination, int $timeoutSec): array {
    $result = ['reachable' => false, 'code' => null, 'rtt_ms' => null, 'error' => null];
    if (!preg_match('/^sip(s)?:[^\s]+$/i', $destination)) {
        $result['error'] = 'Invalid destination URI';
        return $result;
    }
    $parsed = parse_url($destination);
    $host = $parsed['host'] ?? '';
    $port = $parsed['port'] ?? 5060;
    if (empty($host)) {
        $result['error'] = 'Could not parse host';
        return $result;
    }

    $callId = uniqid('probe-', true);
    $fromTag = uniqid('tag-', true);
    $branch = 'z9hG4bK' . uniqid();
    $msg = "OPTIONS {$destination} SIP/2.0\r\n" .
        "Via: SIP/2.0/UDP 127.0.0.1:5060;branch={$branch}\r\n" .
        "From: <sip:probe@localhost>;tag={$fromTag}\r\n" .
        "To: <{$destination}>\r\n" .
        "Call-ID: {$callId}\r\n" .
        "CSeq: 1 OPTIONS\r\n" .
        "Max-Forwards: 70\r\n" .
        "Content-Length: 0\r\n\r\n";

    $start = microtime(true);
    $sock = @fsockopen('udp://' . $host, (int)$port, $errno, $errstr, $timeoutSec);
    if (!$sock) {
        $result['error'] = $errstr ?: 'Connection failed';
        return $result;
    }
    fwrite($sock, $msg);
    stream_set_timeout($sock, $timeoutSec);
    $response = fread($sock, 1024);
    $info = stream_get_meta_data($sock);
    fclose($sock);

    $result['rtt_ms'] = round((microtime(true) - $start) * 1000, 2);
    if ($info['timed_out']) {
        $result['error'] = 'Timeout';
    } elseif (!empty($response) && preg_match('/SIP\/2\.0 (\d{3})/', $response, $m)) {
        $result['code'] = (int)$m[1];
        $result['reachable'] = ($result['code'] >= 100 && $result['code'] < 700);
    } else {
        $result['error'] = 'No valid response';
    }
    return $result;
}

function getDestinationsFromDb(PDO $pdo): array {
    $stmt = $pdo->query("SELECT id, setid, destination FROM dispatcher ORDER BY setid, id");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getDestinationsFromMi(): array {
    $result = miHttpCall('ds_list', []);
    if (!$result['success'] || !isset($result['data']['Sets'])) {
        return [];
    }
    $dests = [];
    foreach ($result['data']['Sets'] as $set) {
        $setid = $set['SetID'] ?? 0;
        foreach ($set['Destinations'] ?? [] as $d) {
            $dests[] = [
                'setid' => $setid,
                'destination' => $d['URI'] ?? '',
                'state' => $d['State'] ?? 'Active',
            ];
        }
    }
    return $dests;
}

function recordHealth(PDO $pdo, int $destId, int $setid, string $destination, array $probe, int $failureCount): void {
    $stmt = $pdo->prepare(
        "INSERT INTO dispatcher_health_log (destination_id, setid, destination, reachable, sip_code, rtt_ms, failure_count)
         VALUES (:did, :setid, :dest, :reachable, :code, :rtt, :fc)"
    );
    $stmt->execute([
        ':did' => $destId,
        ':setid' => $setid,
        ':dest' => $destination,
        ':reachable' => $probe['reachable'] ? 't' : 'f',
        ':code' => $probe['code'],
        ':rtt' => $probe['rtt_ms'],
        ':fc' => $failureCount,
    ]);
}

function getRecentFailureCount(PDO $pdo, int $destId, int $windowMin): int {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM dispatcher_health_log
         WHERE destination_id = :did AND reachable = false
         AND checked_at > NOW() - INTERVAL '{$windowMin} minutes'"
    );
    $stmt->execute([':did' => $destId]);
    return (int)$stmt->fetchColumn();
}

function isCircuitBreakerOpen(PDO $pdo, int $threshold, int $cooldownMin): bool {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM dispatcher_change_log
         WHERE action IN ('AUTO_ROLLBACK','AUTO_FAILOVER')
         AND new_snapshot->>'result' = 'failed'
         AND created_at > NOW() - INTERVAL '{$cooldownMin} minutes'"
    );
    $stmt->execute();
    $failures = (int)$stmt->fetchColumn();
    return $failures >= $threshold;
}

function logAutohealAction(PDO $pdo, string $action, int $setid, ?int $destId, ?array $old, ?array $new, string $result): void {
    $stmt = $pdo->prepare(
        "INSERT INTO dispatcher_change_log (user_id, username, action, setid, destination_id, old_snapshot, new_snapshot)
         VALUES (:uid, :uname, :action, :setid, :did, :old, :new)"
    );
    $stmt->execute([
        ':uid' => 0,
        ':uname' => 'auto-healer',
        ':action' => $action,
        ':setid' => $setid,
        ':did' => $destId,
        ':old' => $old ? json_encode($old) : null,
        ':new' => $new ? json_encode(array_merge($new ?? [], ['result' => $result])) : json_encode(['result' => $result]),
    ]);
}

function setDestinationState(PDO $pdo, int $destId, int $setid, string $destination, int $newState, bool $dryRun): bool {
    // Update PostgreSQL
    if (!$dryRun) {
        $stmt = $pdo->prepare("UPDATE dispatcher SET state = :s WHERE id = :id");
        $stmt->execute([':s' => $newState, ':id' => $destId]);
    }
    // Update OpenSIPS MI runtime state
    // ds_set_state takes: setid, state (0=active, 1=inactive, 2=probing, 3=disabled), destination URI
    $miResult = miHttpCall('ds_set_state', [$setid, $newState, $destination]);
    return $miResult['success'];
}

function tryAutoRollback(PDO $pdo, int $destId, int $windowMin, bool $dryRun): array {
    $stmt = $pdo->prepare(
        "SELECT id, old_snapshot, new_snapshot FROM dispatcher_change_log
         WHERE destination_id = :did AND action IN ('ADD','UPDATE')
         AND created_at > NOW() - INTERVAL '{$windowMin} minutes'
         ORDER BY created_at DESC LIMIT 1"
    );
    $stmt->execute([':did' => $destId]);
    $change = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$change || empty($change['old_snapshot'])) {
        return ['performed' => false, 'reason' => 'No recent change with snapshot'];
    }
    $old = json_decode($change['old_snapshot'], true);
    if (!is_array($old)) {
        return ['performed' => false, 'reason' => 'Invalid snapshot JSON'];
    }
    if ($dryRun) {
        return ['performed' => false, 'reason' => 'Dry-run: would rollback changelog #' . $change['id']];
    }
    // Replay old state
    $stmt = $pdo->prepare(
        "UPDATE dispatcher SET setid = :setid, destination = :dest, socket = :socket,
         state = :state, probe_mode = :probe, weight = :weight, priority = :priority,
         attrs = :attrs, description = :desc WHERE id = :id"
    );
    $ok = $stmt->execute([
        ':id' => $destId,
        ':setid' => $old['setid'] ?? 0,
        ':dest' => $old['destination'] ?? '',
        ':socket' => $old['socket'] ?? null,
        ':state' => $old['state'] ?? 0,
        ':probe' => $old['probe_mode'] ?? 0,
        ':weight' => $old['weight'] ?? '1',
        ':priority' => $old['priority'] ?? 0,
        ':attrs' => $old['attrs'] ?? null,
        ':desc' => $old['description'] ?? null,
    ]);
    if (!$ok) {
        return ['performed' => false, 'reason' => 'UPDATE failed'];
    }
    // Also update MI runtime
    miHttpCall('ds_reload', []);
    return ['performed' => true, 'changelog_id' => $change['id']];
}

// ==================== MAIN ====================

logMsg('Starting auto-healer cycle');

$pdo = getDb();

// Load configuration
$probeTimeout = (int)getConfig($pdo, 'probe_timeout_sec', '3');
$rollbackWindow = (int)getConfig($pdo, 'auto_rollback_window_min', '15');
$failoverThreshold = (int)getConfig($pdo, 'auto_failover_threshold', '5');
$failoverWindow = (int)getConfig($pdo, 'auto_failover_window_min', '10');
$cbThreshold = (int)getConfig($pdo, 'circuit_breaker_failures', '3');
$cbCooldown = (int)getConfig($pdo, 'circuit_breaker_cooldown_min', '30');

// Check circuit breaker
if (isCircuitBreakerOpen($pdo, $cbThreshold, $cbCooldown)) {
    logMsg('Circuit breaker is OPEN — skipping auto-healing actions');
    exit(0);
}

// Get destinations from DB
$destinations = getDestinationsFromDb($pdo);
if (empty($destinations)) {
    logMsg('No dispatcher destinations found');
    exit(0);
}

$miDests = getDestinationsFromMi();
$miDestMap = [];
foreach ($miDests as $d) {
    $miDestMap[$d['destination']] = $d;
}

foreach ($destinations as $dest) {
    $destId = (int)$dest['id'];
    $setid = (int)$dest['setid'];
    $destination = $dest['destination'];

    // Probe
    $probe = probeDestination($destination, $probeTimeout);
    $recentFailures = getRecentFailureCount($pdo, $destId, $failoverWindow);
    $failureCount = $probe['reachable'] ? 0 : ($recentFailures + 1);

    recordHealth($pdo, $destId, $setid, $destination, $probe, $failureCount);
    logMsg("Probed {$destination}: reachable=" . ($probe['reachable'] ? 'true' : 'false') .
           " code=" . ($probe['code'] ?? 'null') . " rtt=" . ($probe['rtt_ms'] ?? 'null') .
           " failures={$failureCount}");

    if ($probe['reachable']) {
        continue; // Healthy — nothing to do
    }

    // Decision: auto-rollback if recent change and first failures
    if ($failureCount >= 1 && $failureCount < $failoverThreshold) {
        $rollbackResult = tryAutoRollback($pdo, $destId, $rollbackWindow, $dryRun);
        if ($rollbackResult['performed']) {
            logMsg("Auto-rollback performed for {$destination} (changelog #{$rollbackResult['changelog_id']})");
            logAutohealAction($pdo, 'AUTO_ROLLBACK', $setid, $destId, null, ['destination' => $destination], 'success');
        } else {
            logMsg("Auto-rollback skipped for {$destination}: {$rollbackResult['reason']}");
        }
    }

    // Decision: auto-failover if threshold reached
    if ($failureCount >= $failoverThreshold) {
        logMsg("Auto-failover threshold reached for {$destination} ({$failureCount} failures)");
        $ok = setDestinationState($pdo, $destId, $setid, $destination, 1, $dryRun);
        if ($ok) {
            logMsg("Auto-failover succeeded for {$destination}");
            logAutohealAction($pdo, 'AUTO_FAILOVER', $setid, $destId,
                ['state' => 0], ['state' => 1, 'destination' => $destination], 'success');
        } else {
            logMsg("Auto-failover FAILED for {$destination}");
            logAutohealAction($pdo, 'AUTO_FAILOVER', $setid, $destId,
                ['state' => 0], ['state' => 1, 'destination' => $destination], 'failed');
        }
    }
}

logMsg('Auto-healer cycle complete');
exit(0);
