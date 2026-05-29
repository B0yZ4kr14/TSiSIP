<?php
/**
 * mfa-verify.php — TOTP verification screen during login
 * Feature 037
 */

require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/password-policy.php';

// User must have passed password check but not yet MFA
if (empty($_SESSION['mfa_pending_user_id'])) {
    header('Location: login.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = preg_replace('/\D/', '', $_POST['code'] ?? '');
    $userId = $_SESSION['mfa_pending_user_id'];

    $pdo = getDb();
    $stmt = $pdo->prepare("SELECT secret_encrypted, locked_until, last_code_window, failed_attempts FROM ocp_user_mfa WHERE user_id = :uid");
    $stmt->execute([':uid' => $userId]);
    $mfa = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$mfa) {
        $error = _('MFA not configured. Please contact an administrator.');
    } elseif ($mfa['locked_until'] && strtotime($mfa['locked_until']) > time()) {
        $error = _('Account temporarily locked. Please try again later.');
    } else {
        require_once __DIR__ . '/lib/totp.php';
        try {
            $secret = decryptSecret($mfa['secret_encrypted']);
            $currentWindow = getTotpWindow();

            // Prevent replay
            if ($mfa['last_code_window'] && (int)$mfa['last_code_window'] === $currentWindow) {
                $error = _('Code already used. Please wait for the next code.');
            } elseif (verifyTotpCode($secret, $code)) {
                // Success
                $stmt = $pdo->prepare("UPDATE ocp_user_mfa SET last_verified_at = NOW(), failed_attempts = 0, last_code_window = :win WHERE user_id = :uid");
                $stmt->execute([':win' => $currentWindow, ':uid' => $userId]);

                // Check password expiration (same logic as login.php)
                $forceChange = $_SESSION['mfa_pending_force_password_change'] ?? false;
                if (isPasswordExpired($pdo, $userId, 90)) {
                    $forceChange = true;
                }

                // Complete login
                $_SESSION['ocp_user_id'] = $userId;
                $_SESSION['ocp_username'] = $_SESSION['mfa_pending_username'];
                $_SESSION['ocp_user_role'] = $_SESSION['mfa_pending_role'];
                $_SESSION['ocp_force_password_change'] = $forceChange;
                unset($_SESSION['mfa_pending_user_id'], $_SESSION['mfa_pending_username'], $_SESSION['mfa_pending_role'], $_SESSION['mfa_pending_force_password_change']);
                session_regenerate_id(true);

                logAuditEvent('MFA_VERIFIED', 'user', $userId, true);
                if ($forceChange) {
                    header('Location: change-password.php');
                } else {
                    header('Location: dashboard.php');
                }
                exit;
            } else {
                // Failed
                $newFails = (int)$mfa['failed_attempts'] + 1;
                $lockedUntil = null;
                if ($newFails >= 3) {
                    $lockedUntil = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                }
                $stmt = $pdo->prepare("UPDATE ocp_user_mfa SET failed_attempts = :fails, locked_until = :locked WHERE user_id = :uid");
                $stmt->execute([':fails' => $newFails, ':locked' => $lockedUntil, ':uid' => $userId]);
                logAuditEvent('MFA_FAILED', 'user', $userId, false);
                $error = _('Invalid code. Please try again.');
                if ($lockedUntil) {
                    $error = _('Too many failed attempts. Account locked for 15 minutes.');
                }
            }
        } catch (Exception $e) {
            $error = _('Verification error. Please try again.');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo _('Two-Factor Authentication'); ?> — TSiSIP</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="tsisip/css/tsisip-theme.css">
</head>
<body class="tsisip-login-page">
    <div class="tsisip-login-container">
        <div class="tsisip-login-box">
            <h1><?php echo _('Two-Factor Authentication'); ?></h1>
            <p class="tsisip-text-muted"><?php echo _('Enter the 6-digit code from your authenticator app.'); ?></p>

            <?php if ($error): ?>
                <div class="tsisip-alert tsisip-alert--error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="tsisip-form-group">
                    <label for="code"><?php echo _('Authentication Code'); ?></label>
                    <input type="text" name="code" id="code" maxlength="6" pattern="[0-9]{6}" placeholder="000000" required autofocus autocomplete="off" inputmode="numeric">
                </div>
                <button type="submit" class="tsisip-btn tsisip-btn--primary tsisip-btn--block"><?php echo _('Verify'); ?></button>
            </form>

            <p style="margin-top:1rem;text-align:center;">
                <a href="mfa-backup.php"><?php echo _('Use a backup code'); ?></a>
            </p>
        </div>
    </div>
</body>
</html>
