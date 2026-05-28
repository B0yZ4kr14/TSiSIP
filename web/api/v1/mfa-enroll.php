<?php
/**
 * mfa-enroll.php — Generate TOTP secret, QR code, and verify first code
 * Feature 037
 */

require_once __DIR__ . '/../../common/config.php';
require_once __DIR__ . '/../../lib/totp.php';

requireAuth();
checkPasswordChange();

$userId = $_SESSION['ocp_user_id'] ?? null;
if (!$userId) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$pdo = getDb();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Generate new secret and QR code
    $secret = generateTotpSecret();
    $username = $_SESSION['ocp_username'] ?? 'user';
    $uri = buildOtpAuthUri($username, $secret);
    $qrSvg = generateQrCodeSvg($uri);

    // Store secret temporarily in session (not DB until verified)
    $_SESSION['mfa_enroll_secret'] = $secret;

    echo json_encode([
        'secret' => $secret,
        'qr_svg' => $qrSvg,
        'manual_entry' => chunk_split($secret, 4, ' '),
    ]);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $code = preg_replace('/\D/', '', $input['code'] ?? '');
    $secret = $_SESSION['mfa_enroll_secret'] ?? '';

    if (empty($secret)) {
        http_response_code(400);
        echo json_encode(['error' => 'Enrollment session expired. Please restart.']);
        exit;
    }

    if (!verifyTotpCode($secret, $code)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid code. Please check your authenticator app and try again.']);
        exit;
    }

    // Encrypt and store secret
    try {
        $encrypted = encryptSecret($secret);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Encryption failed']);
        exit;
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO ocp_user_mfa (user_id, secret_encrypted, enabled_at, failed_attempts, last_code_window)
             VALUES (:uid, :secret, NOW(), 0, NULL)
             ON CONFLICT (user_id) DO UPDATE SET
                secret_encrypted = EXCLUDED.secret_encrypted,
                enabled_at = NOW(),
                failed_attempts = 0,
                locked_until = NULL,
                last_code_window = NULL"
        );
        $stmt->execute([':uid' => $userId, ':secret' => $encrypted]);

        // Generate backup codes
        $backupCodes = generateBackupCodes(10);
        $stmt = $pdo->prepare("DELETE FROM ocp_user_backup_codes WHERE user_id = :uid");
        $stmt->execute([':uid' => $userId]);

        $insertStmt = $pdo->prepare("INSERT INTO ocp_user_backup_codes (user_id, code_hash) VALUES (:uid, :hash)");
        foreach ($backupCodes as $bc) {
            $insertStmt->execute([':uid' => $userId, ':hash' => password_hash($bc, PASSWORD_BCRYPT)]);
        }

        $pdo->commit();
        unset($_SESSION['mfa_enroll_secret']);
        logAuditEvent('MFA_ENABLED', 'user', $userId, true);

        echo json_encode(['success' => true, 'backup_codes' => $backupCodes]);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Enrollment failed: ' . $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
