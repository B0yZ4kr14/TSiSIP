<?php
/**
 * TSiSIP Control Panel — Save Dashboard Layout
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/csrf.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['widgets'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

$pdo = getDb();
$userId = $_SESSION['user_id'] ?? 0;

// Upsert preference
$stmt = $pdo->prepare(
    "INSERT INTO ocp_user_preferences (user_id, preference_key, preference_value, updated_at)
     VALUES (:uid, 'dashboard_layout', :val, NOW())
     ON CONFLICT (user_id, preference_key) DO UPDATE SET
         preference_value = EXCLUDED.preference_value,
         updated_at = NOW()"
);
$stmt->execute([
    ':uid' => $userId,
    ':val' => json_encode($input['widgets']),
]);

echo json_encode(['success' => true]);
