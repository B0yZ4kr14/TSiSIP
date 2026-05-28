<?php
/**
 * dispatcher-crud.php — CRUD for OpenSIPS dispatcher destinations
 * Feature 035
 * Methods: GET (list), POST (add), PUT (update), DELETE (remove)
 */

require_once __DIR__ . '/../../common/config.php';
require_once __DIR__ . '/../../common/audit.php';

requireAuth();
checkPasswordChange();

$userRole = $_SESSION['user_role'] ?? 'readonly';
if (!in_array($userRole, ['admin', 'devops'], true)) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Admin or DevOps role required']);
    exit;
}

$pdo = getDb();
$method = $_SERVER['REQUEST_METHOD'];

function validateDestination(array $input): array {
    $errors = [];
    $setid = isset($input['setid']) ? (int)$input['setid'] : 0;
    if ($setid <= 0) {
        $errors[] = 'setid must be a positive integer';
    }
    $destination = trim($input['destination'] ?? '');
    if (empty($destination) || !preg_match('/^sip(s)?:[^\s]+$/i', $destination)) {
        $errors[] = 'destination must be a valid SIP URI (sip:host:port or sips:host:port)';
    }
    $state = isset($input['state']) ? (int)$input['state'] : 0;
    if ($state < 0 || $state > 2) {
        $errors[] = 'state must be 0 (inactive), 1 (active), or 2 (disabled)';
    }
    $priority = isset($input['priority']) ? (int)$input['priority'] : 0;
    if ($priority < 0) {
        $errors[] = 'priority must be non-negative';
    }
    return [$errors, [
        'setid' => $setid,
        'destination' => $destination,
        'socket' => $input['socket'] ?? null,
        'state' => $state,
        'probe_mode' => isset($input['probe_mode']) ? (int)$input['probe_mode'] : 0,
        'weight' => $input['weight'] ?? '1',
        'priority' => $priority,
        'attrs' => $input['attrs'] ?? null,
        'description' => $input['description'] ?? null,
    ]];
}

function logChange(PDO $pdo, string $action, int $setid, ?int $destId, ?array $old, ?array $new): void {
    $userId = $_SESSION['ocp_user_id'] ?? 0;
    $username = $_SESSION['ocp_username'] ?? 'unknown';
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO dispatcher_change_log (user_id, username, action, setid, destination_id, old_snapshot, new_snapshot)
             VALUES (:uid, :uname, :action, :setid, :did, :old, :new)"
        );
        $stmt->execute([
            ':uid' => $userId,
            ':uname' => $username,
            ':action' => $action,
            ':setid' => $setid,
            ':did' => $destId,
            ':old' => $old ? json_encode($old) : null,
            ':new' => $new ? json_encode($new) : null,
        ]);
    } catch (Exception $e) {
        error_log('Dispatcher change log failed: ' . $e->getMessage());
    }
}

switch ($method) {
    case 'GET':
        $setid = isset($_GET['setid']) ? (int)$_GET['setid'] : null;
        try {
            if ($setid !== null && $setid > 0) {
                $stmt = $pdo->prepare("SELECT * FROM dispatcher WHERE setid = :setid ORDER BY priority DESC, id");
                $stmt->execute([':setid' => $setid]);
            } else {
                $stmt = $pdo->query("SELECT * FROM dispatcher ORDER BY setid, priority DESC, id");
            }
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            http_response_code(200);
            header('Content-Type: application/json');
            echo json_encode(['destinations' => $rows]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error']);
        }
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        [$errors, $data] = validateDestination($input);
        if (!empty($errors)) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['errors' => $errors]);
            exit;
        }
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO dispatcher (setid, destination, socket, state, probe_mode, weight, priority, attrs, description)
                 VALUES (:setid, :dest, :socket, :state, :probe, :weight, :priority, :attrs, :desc)
                 RETURNING id"
            );
            $stmt->execute([
                ':setid' => $data['setid'],
                ':dest' => $data['destination'],
                ':socket' => $data['socket'],
                ':state' => $data['state'],
                ':probe' => $data['probe_mode'],
                ':weight' => $data['weight'],
                ':priority' => $data['priority'],
                ':attrs' => $data['attrs'],
                ':desc' => $data['description'],
            ]);
            $newId = (int)$stmt->fetchColumn();
            logChange($pdo, 'ADD', $data['setid'], $newId, null, $data);
            logAuditEvent('CONFIG_CHANGE', 'dispatcher', (string)$newId, true, ['action' => 'ADD', 'setid' => $data['setid'], 'destination' => $data['destination']]);
            http_response_code(201);
            header('Content-Type: application/json');
            echo json_encode(['id' => $newId, 'data' => $data]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Insert failed: ' . $e->getMessage()]);
        }
        break;

    case 'PUT':
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing or invalid id']);
            exit;
        }
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        [$errors, $data] = validateDestination($input);
        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode(['errors' => $errors]);
            exit;
        }
        try {
            // Fetch old snapshot
            $oldStmt = $pdo->prepare("SELECT * FROM dispatcher WHERE id = :id");
            $oldStmt->execute([':id' => $id]);
            $old = $oldStmt->fetch(PDO::FETCH_ASSOC);
            if (!$old) {
                http_response_code(404);
                echo json_encode(['error' => 'Destination not found']);
                exit;
            }
            $stmt = $pdo->prepare(
                "UPDATE dispatcher SET setid = :setid, destination = :dest, socket = :socket,
                 state = :state, probe_mode = :probe, weight = :weight, priority = :priority,
                 attrs = :attrs, description = :desc WHERE id = :id"
            );
            $stmt->execute([
                ':id' => $id,
                ':setid' => $data['setid'],
                ':dest' => $data['destination'],
                ':socket' => $data['socket'],
                ':state' => $data['state'],
                ':probe' => $data['probe_mode'],
                ':weight' => $data['weight'],
                ':priority' => $data['priority'],
                ':attrs' => $data['attrs'],
                ':desc' => $data['description'],
            ]);
            logChange($pdo, 'UPDATE', $data['setid'], $id, $old, $data);
            logAuditEvent('CONFIG_CHANGE', 'dispatcher', (string)$id, true, ['action' => 'UPDATE', 'setid' => $data['setid']]);
            http_response_code(200);
            echo json_encode(['id' => $id, 'data' => $data]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Update failed: ' . $e->getMessage()]);
        }
        break;

    case 'DELETE':
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing or invalid id']);
            exit;
        }
        try {
            $oldStmt = $pdo->prepare("SELECT * FROM dispatcher WHERE id = :id");
            $oldStmt->execute([':id' => $id]);
            $old = $oldStmt->fetch(PDO::FETCH_ASSOC);
            if (!$old) {
                http_response_code(404);
                echo json_encode(['error' => 'Destination not found']);
                exit;
            }
            $stmt = $pdo->prepare("DELETE FROM dispatcher WHERE id = :id");
            $stmt->execute([':id' => $id]);
            logChange($pdo, 'DELETE', (int)$old['setid'], $id, $old, null);
            logAuditEvent('CONFIG_CHANGE', 'dispatcher', (string)$id, true, ['action' => 'DELETE', 'setid' => $old['setid']]);
            http_response_code(204);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Delete failed: ' . $e->getMessage()]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}
