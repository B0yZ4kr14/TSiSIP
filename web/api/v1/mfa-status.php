<?php
/**
 * mfa-status.php — Check MFA enrollment status for current user
 * Feature 037
 */

require_once __DIR__ . '/../../common/config.php';

requireAuth();
checkPasswordChange();

$userId = $_SESSION['ocp_user_id'] ?? null;
if (!$userId) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$pdo = getDb();
$stmt = $pdo->prepare("SELECT 1 FROM ocp_user_mfa WHERE user_id = :uid");
$stmt->execute([':uid' => $userId]);
$enabled = (bool)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM ocp_user_backup_codes WHERE user_id = :uid AND used_at IS NULL");
$stmt->execute([':uid' => $userId]);
$backupCodesLeft = (int)$stmt->fetchColumn();

echo json_encode([
    'enabled' => $enabled,
    'backup_codes_left' => $backupCodesLeft,
]);
