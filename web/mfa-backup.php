<?php
/**
 * mfa-backup.php — Backup code recovery screen during login
 * Feature 037
 */

require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/password-policy.php';

if (empty($_SESSION['mfa_pending_user_id'])) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $userId = $_SESSION['mfa_pending_user_id'];

    $pdo = getDb();
    $stmt = $pdo->prepare("SELECT id, code_hash FROM ocp_user_backup_codes WHERE user_id = :uid AND used_at IS NULL");
    $stmt->execute([':uid' => $userId]);
    $codes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $matched = false;
    foreach ($codes as $c) {
        if (password_verify($code, $c['code_hash'])) {
            $matched = true;
            $stmt = $pdo->prepare("UPDATE ocp_user_backup_codes SET used_at = NOW() WHERE id = :id");
            $stmt->execute([':id' => $c['id']]);
            break;
        }
    }

    if ($matched) {
        // Check password expiration (same logic as login.php)
        $forceChange = $_SESSION['mfa_pending_force_password_change'] ?? false;
        if (isPasswordExpired($pdo, $userId, 90)) {
            $forceChange = true;
        }

        $_SESSION['ocp_user_id'] = $userId;
        $_SESSION['ocp_username'] = $_SESSION['mfa_pending_username'];
        $_SESSION['ocp_user_role'] = $_SESSION['mfa_pending_role'];
        $_SESSION['ocp_force_password_change'] = $forceChange;
        unset($_SESSION['mfa_pending_user_id'], $_SESSION['mfa_pending_username'], $_SESSION['mfa_pending_role'], $_SESSION['mfa_pending_force_password_change']);
        session_regenerate_id(true);
        logAuditEvent('BACKUP_CODE_USED', 'user', $userId, true);
        if ($forceChange) {
            header('Location: change-password.php');
        } else {
            header('Location: dashboard.php');
        }
        exit;
    } else {
        logAuditEvent('BACKUP_CODE_USED', 'user', $userId, false);
        $error = _('Invalid or already used backup code.');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo _('Backup Code Recovery'); ?> — TSiSIP</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="tsisip/css/tsisip-theme.css">
</head>
<body class="tsisip-login-page">
    <div class="tsisip-login-container">
        <div class="tsisip-login-box">
            <h1><?php echo _('Backup Code Recovery'); ?></h1>
            <p class="tsisip-text-muted"><?php echo _('Enter one of your backup codes to access your account.'); ?></p>

            <?php if ($error): ?>
                <div class="tsisip-alert tsisip-alert--error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="tsisip-form-group">
                    <label for="code"><?php echo _('Backup Code'); ?></label>
                    <input type="text" name="code" id="code" maxlength="12" placeholder="XXXX-XXXX-XXXX" required autofocus autocomplete="off">
                </div>
                <button type="submit" class="tsisip-btn tsisip-btn--primary tsisip-btn--block"><?php echo _('Verify'); ?></button>
            </form>

            <p style="margin-top:1rem;text-align:center;">
                <a href="mfa-verify.php"><?php echo _('Use authenticator code instead'); ?></a>
            </p>
        </div>
    </div>
</body>
</html>
