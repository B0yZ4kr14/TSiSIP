<?php
/**
 * mfa-disable.php — Disable MFA for the current user (requires password + TOTP)
 * Feature 037
 */

require_once __DIR__ . '/../../common/config.php';
require_once __DIR__ . '/../../common/csrf.php';
require_once __DIR__ . '/../../lib/totp.php';

requireAuth();
requireCsrfForMutation();
checkPasswordChange();

$userId = $_SESSION['ocp_user_id'] ?? null;
if (!$userId) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$password = $input['password'] ?? '';
$code = preg_replace('/\D/', '', $input['code'] ?? '');

if (empty($password) || empty($code)) {
    http_response_code(400);
    echo json_encode(['error' => 'Password and TOTP code required']);
    exit;
}

$pdo = getDb();

// Verify password
$stmt = $pdo->prepare("SELECT password_hash FROM ocp_users WHERE id = :uid");
$stmt->execute([':uid' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user || !password_verify($password, $user['password_hash'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid password']);
    exit;
}

// Verify TOTP
$stmt = $pdo->prepare("SELECT secret_encrypted FROM ocp_user_mfa WHERE user_id = :uid");
$stmt->execute([':uid' => $userId]);
$mfa = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$mfa) {
    http_response_code(400);
    echo json_encode(['error' => 'MFA not enabled']);
    exit;
}

try {
    $secret = decryptSecret($mfa['secret_encrypted']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Decryption failed']);
    exit;
}

if (!verifyTotpCode($secret, $code)) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid TOTP code']);
    exit;
}

// Delete MFA data
$stmt = $pdo->prepare("DELETE FROM ocp_user_mfa WHERE user_id = :uid");
$stmt->execute([':uid' => $userId]);
$stmt = $pdo->prepare("DELETE FROM ocp_user_backup_codes WHERE user_id = :uid");
$stmt->execute([':uid' => $userId]);

logAuditEvent('MFA_DISABLED', 'user', $userId, true);
echo json_encode(['success' => true]);
