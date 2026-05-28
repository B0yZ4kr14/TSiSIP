<?php
/**
 * metrics-autoheal.php — Prometheus metrics for auto-healing
 * Feature 036
 */

require_once __DIR__ . '/../../common/config.php';

// No auth required for Prometheus scrape (access controlled by network)
header('Content-Type: text/plain; charset=utf-8');

$pdo = getDb();

// tsisip_autoheal_actions_total
$stmt = $pdo->query(
    "SELECT action, new_snapshot->>'result' as result, COUNT(*) as cnt
     FROM dispatcher_change_log
     WHERE action IN ('AUTO_ROLLBACK','AUTO_FAILOVER','AUTO_PROBE')
     GROUP BY action, new_snapshot->>'result'"
);
$actions = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($actions as $a) {
    $result = $a['result'] ?? 'unknown';
    echo "tsisip_autoheal_actions_total{action=\"" . $a['action'] . "\",result=\"" . $result . "\"} " . $a['cnt'] . "\n";
}
if (empty($actions)) {
    echo "tsisip_autoheal_actions_total{action=\"AUTO_ROLLBACK\",result=\"success\"} 0\n";
    echo "tsisip_autoheal_actions_total{action=\"AUTO_FAILOVER\",result=\"success\"} 0\n";
}

// tsisip_autoheal_circuit_breaker_state
$cbThreshold = 3;
$cbCooldown = 30;
$stmt = $pdo->query(
    "SELECT COUNT(*) FROM dispatcher_change_log
     WHERE action IN ('AUTO_ROLLBACK','AUTO_FAILOVER')
     AND new_snapshot->>'result' = 'failed'
     AND created_at > NOW() - INTERVAL '{$cbCooldown} minutes'"
);
$cbFailures = (int)$stmt->fetchColumn();
$cbOpen = $cbFailures >= $cbThreshold ? 1 : 0;
echo "tsisip_autoheal_circuit_breaker_state " . $cbOpen . "\n";

// tsisip_autoheal_destinations_unhealthy
$stmt = $pdo->query(
    "SELECT COUNT(DISTINCT destination_id) FROM dispatcher_health_log
     WHERE reachable = false
     AND checked_at > NOW() - INTERVAL '5 minutes'"
);
echo "tsisip_autoheal_destinations_unhealthy " . (int)$stmt->fetchColumn() . "\n";
