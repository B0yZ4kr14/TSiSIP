<?php
/**
 * dispatcher-import.php — Bulk import dispatcher destinations from CSV
 * Feature 035
 */

require_once __DIR__ . '/../../common/config.php';
require_once __DIR__ . '/../../common/csrf.php';
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

if (empty($_FILES['csv']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'CSV file required']);
    exit;
}

$tmpPath = $_FILES['csv']['tmp_name'];
$handle = fopen($tmpPath, 'r');
if (!$handle) {
    http_response_code(400);
    echo json_encode(['error' => 'Cannot read CSV file']);
    exit;
}

// Read header
$header = fgetcsv($handle);
if (!$header) {
    http_response_code(400);
    echo json_encode(['error' => 'Empty or invalid CSV']);
    exit;
}

$expected = ['setid', 'destination', 'state', 'probe_mode', 'weight', 'priority', 'attrs', 'description'];
$header = array_map('strtolower', array_map('trim', $header));
if ($header !== $expected) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid CSV header', 'expected' => $expected, 'got' => $header]);
    exit;
}

$pdo = getDb();
$imported = 0;
$errors = [];
$line = 1;

$pdo->beginTransaction();
try {
    $insertStmt = $pdo->prepare(
        "INSERT INTO dispatcher (setid, destination, state, probe_mode, weight, priority, attrs, description)
         VALUES (:setid, :dest, :state, :probe, :weight, :priority, :attrs, :desc)"
    );

    while ($row = fgetcsv($handle)) {
        $line++;
        if (count($row) < 8) {
            $errors[] = "Line {$line}: insufficient columns";
            continue;
        }
        [$setid, $destination, $state, $probe_mode, $weight, $priority, $attrs, $description] = $row;
        $setid = (int)$setid;
        $state = (int)$state;
        $probe_mode = (int)$probe_mode;
        $priority = (int)$priority;

        if ($setid <= 0) {
            $errors[] = "Line {$line}: invalid setid";
            continue;
        }
        if (empty($destination) || !preg_match('/^sip(s)?:[^\s]+$/i', $destination)) {
            $errors[] = "Line {$line}: invalid destination URI";
            continue;
        }

        $insertStmt->execute([
            ':setid' => $setid,
            ':dest' => $destination,
            ':state' => $state,
            ':probe' => $probe_mode,
            ':weight' => $weight,
            ':priority' => $priority,
            ':attrs' => $attrs ?: null,
            ':desc' => $description ?: null,
        ]);
        $imported++;
    }

    fclose($handle);
    $pdo->commit();

    logAuditEvent('CONFIG_CHANGE', 'dispatcher', 'import', true, ['imported' => $imported, 'errors' => count($errors)]);

    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode(['imported' => $imported, 'errors' => $errors, 'total_lines' => $line - 1]);
} catch (Exception $e) {
    $pdo->rollBack();
    fclose($handle);
    http_response_code(500);
    echo json_encode(['error' => 'Import failed: ' . $e->getMessage()]);
}
