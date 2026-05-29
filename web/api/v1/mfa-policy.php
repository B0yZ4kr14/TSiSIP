<?php
/**
 * mfa-policy.php — Get or set MFA policy per role
 * Feature 037
 */

require_once __DIR__ . '/../../common/config.php';
require_once __DIR__ . '/../../common/csrf.php';

requireAuth();
requireCsrfForMutation();
checkPasswordChange();

$userRole = $_SESSION['ocp_user_role'] ?? 'readonly';
if ($userRole !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Admin role required']);
    exit;
}

$pdo = getDb();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->query("SELECT role, enforced, grace_period_days FROM mfa_policy ORDER BY role");
    $policies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['policies' => $policies]);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $role = $input['role'] ?? '';
    $enforced = filter_var($input['enforced'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $graceDays = (int)($input['grace_period_days'] ?? 7);

    if (empty($role)) {
        http_response_code(400);
        echo json_encode(['error' => 'role required']);
        exit;
    }

    $stmt = $pdo->prepare(
        "INSERT INTO mfa_policy (role, enforced, grace_period_days) VALUES (:role, :enforced, :grace)
         ON CONFLICT (role) DO UPDATE SET enforced = EXCLUDED.enforced, grace_period_days = EXCLUDED.grace_period_days, updated_at = NOW()"
    );
    $stmt->execute([':role' => $role, ':enforced' => $enforced ? 't' : 'f', ':grace' => $graceDays]);

    logAuditEvent('CONFIG_CHANGE', 'mfa_policy', $role, true, ['enforced' => $enforced, 'grace_days' => $graceDays]);
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
