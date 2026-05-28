<?php
/**
 * autoheal-events.php — SSE stream of auto-healing events
 * Feature 036
 */

require_once __DIR__ . '/../../common/config.php';

requireAuth();
checkPasswordChange();

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

$pdo = getDb();
$lastId = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;

while (true) {
    $stmt = $pdo->prepare(
        "SELECT id, action, setid, destination_id, new_snapshot, created_at
         FROM dispatcher_change_log
         WHERE action IN ('AUTO_ROLLBACK','AUTO_FAILOVER','AUTO_PROBE')
         AND id > :last_id
         ORDER BY id ASC LIMIT 10"
    );
    $stmt->execute([':last_id' => $lastId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        $lastId = (int)$row['id'];
        $snap = json_decode($row['new_snapshot'] ?? '{}', true);
        $payload = [
            'id' => $lastId,
            'action' => $row['action'],
            'setid' => (int)$row['setid'],
            'destination_id' => (int)$row['destination_id'],
            'destination' => $snap['destination'] ?? 'unknown',
            'result' => $snap['result'] ?? 'unknown',
            'created_at' => $row['created_at'],
        ];
        echo "data: " . json_encode($payload) . "\n\n";
    }

    if (ob_get_level() > 0) {
        ob_flush();
    }
    flush();

    sleep(5);
}
