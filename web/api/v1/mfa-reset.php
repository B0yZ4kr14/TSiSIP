<?php
/**
 * mfa-reset.php — Admin reset MFA for a user
 * Feature 037
 */

require_once __DIR__ . '/../../common/config.php';

requireAuth();
checkPasswordChange();

$userRole = $_SESSION['ocp_user_role'] ?? 'readonly';
if ($userRole !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Admin role required']);
    exit;
}

$targetUserId = $_POST['user_id'] ?? '';
if (empty($targetUserId)) {
    http_response_code(400);
    echo json_encode(['error' => 'user_id required']);
    exit;
}

$pdo = getDb();
$stmt = $pdo->prepare("DELETE FROM ocp_user_mfa WHERE user_id = :uid");
$stmt->execute([':uid' => $targetUserId]);
$stmt = $pdo->prepare("DELETE FROM ocp_user_backup_codes WHERE user_id = :uid");
$stmt->execute([':uid' => $targetUserId]);

logAuditEvent('MFA_RESET_BY_ADMIN', 'user', $targetUserId, true, ['admin_id' => $_SESSION['ocp_user_id']]);
echo json_encode(['success' => true]);
