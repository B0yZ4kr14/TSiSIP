<?php
/**
 * dispatcher-rollback.php — Rollback a dispatcher change
 * Feature 035
 */

require_once __DIR__ . '/../../common/config.php';
require_once __DIR__ . '/../../common/csrf.php';
require_once __DIR__ . '/../../common/mi-http.php';
require_once __DIR__ . '/../../common/audit.php';

requireAuth();
requireCsrfForMutation();
checkPasswordChange();

$userRole = $_SESSION['user_role'] ?? 'readonly';
if (!in_array($userRole, ['admin', 'devops'], true)) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Admin or DevOps role required']);
    exit;
}

$changeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($changeId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing or invalid change id']);
    exit;
}

$pdo = getDb();

// Fetch the change log entry
$stmt = $pdo->prepare("SELECT * FROM dispatcher_change_log WHERE id = :id");
$stmt->execute([':id' => $changeId]);
$change = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$change) {
    http_response_code(404);
    echo json_encode(['error' => 'Change log entry not found']);
    exit;
}

$old = json_decode($change['old_snapshot'] ?? 'null', true);
if (empty($old) || !is_array($old)) {
    http_response_code(422);
    echo json_encode(['error' => 'No snapshot available for rollback']);
    exit;
}

// Restore the old state
$pdo->beginTransaction();
try {
    // If old snapshot has an 'id', update that row; otherwise insert
    if (!empty($old['id'])) {
        $stmt = $pdo->prepare(
            "UPDATE dispatcher SET setid = :setid, destination = :dest, socket = :socket,
             state = :state, probe_mode = :probe, weight = :weight, priority = :priority,
             attrs = :attrs, description = :desc WHERE id = :id"
        );
        $stmt->execute([
            ':id' => $old['id'],
            ':setid' => $old['setid'],
            ':dest' => $old['destination'],
            ':socket' => $old['socket'] ?? null,
            ':state' => $old['state'],
            ':probe' => $old['probe_mode'] ?? 0,
            ':weight' => $old['weight'] ?? '1',
            ':priority' => $old['priority'] ?? 0,
            ':attrs' => $old['attrs'] ?? null,
            ':desc' => $old['description'] ?? null,
        ]);
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO dispatcher (setid, destination, socket, state, probe_mode, weight, priority, attrs, description)
             VALUES (:setid, :dest, :socket, :state, :probe, :weight, :priority, :attrs, :desc)"
        );
        $stmt->execute([
            ':setid' => $old['setid'],
            ':dest' => $old['destination'],
            ':socket' => $old['socket'] ?? null,
            ':state' => $old['state'],
            ':probe' => $old['probe_mode'] ?? 0,
            ':weight' => $old['weight'] ?? '1',
            ':priority' => $old['priority'] ?? 0,
            ':attrs' => $old['attrs'] ?? null,
            ':desc' => $old['description'] ?? null,
        ]);
    }

    // Trigger ds_reload
    $result = miHttpCall('ds_reload', []);
    if (!$result['success']) {
        $pdo->rollBack();
        http_response_code(502);
        echo json_encode(['error' => 'MI ds_reload failed during rollback', 'detail' => $result['error']]);
        exit;
    }

    $pdo->commit();

    // Log the rollback
    $logStmt = $pdo->prepare(
        "INSERT INTO dispatcher_change_log (user_id, username, action, setid, destination_id, old_snapshot, new_snapshot)
         VALUES (:uid, :uname, 'ROLLBACK', :setid, :did, :old, :new)"
    );
    $logStmt->execute([
        ':uid' => $_SESSION['ocp_user_id'] ?? 0,
        ':uname' => $_SESSION['ocp_username'] ?? 'unknown',
        ':setid' => $old['setid'],
        ':did' => $old['id'] ?? null,
        ':old' => json_encode($change['new_snapshot']),
        ':new' => json_encode($old),
    ]);

    logAuditEvent('CONFIG_CHANGE', 'dispatcher', (string)($old['id'] ?? 0), true, ['action' => 'ROLLBACK', 'change_id' => $changeId]);

    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Rollback completed and reloaded']);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Rollback failed: ' . $e->getMessage()]);
}
