<?php
/**
 * TSiSIP Control Panel — Force Passphrase Change
 */

require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';
requireAuth();

$mustChange = !empty($_SESSION['ocp_force_password_change']);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = _('Invalid or missing CSRF token.');
    } else {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password']     ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($current === '' || $new === '' || $confirm === '') {
        $error = _('All fields are required.');
    } elseif ($new !== $confirm) {
        $error = _('New passphrases do not match.');
    } elseif (strlen($new) < 12) {
        $error = _('Passphrase must be at least 12 characters long.');
    } elseif (!preg_match('/[A-Z]/', $new) ||
              !preg_match('/[a-z]/', $new) ||
              !preg_match('/[0-9]/', $new) ||
              !preg_match('/[^A-Za-z0-9]/', $new)) {
        $error = _('Passphrase must contain uppercase, lowercase, number, and symbol.');
    } else {
        $pdo = getDb();
        $stmt = $pdo->prepare(
            "SELECT password_hash FROM ocp_users WHERE id = :id LIMIT 1"
        );
        $stmt->execute([':id' => $_SESSION['ocp_user_id']]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($current, $row['password_hash'])) {
            $error = _('Current passphrase is incorrect.');
        } else {
            $newHash = password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]);
            $upd = $pdo->prepare(
                "UPDATE ocp_users
                 SET password_hash = :hash,
                     force_password_change = FALSE,
                     updated_at = NOW()
                 WHERE id = :id"
            );
            $upd->execute([':hash' => $newHash, ':id' => $_SESSION['ocp_user_id']]);

            logAuditEvent('PASSWORD_CHANGE', 'ocp_user', $_SESSION['ocp_username'] ?? 'unknown', true);

            // Dedicated password-change audit (security_constitution.md section 7)
            $ip = 'unknown';
            if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $first = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
                $first = trim($first);
                if (filter_var($first, FILTER_VALIDATE_IP)) {
                    $ip = $first;
                }
            } elseif (!empty($_SERVER['REMOTE_ADDR']) && filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP)) {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
            $auditStmt = $pdo->prepare(
                "INSERT INTO ocp_password_changes
                 (user_id, username, changed_by, changed_by_name, source_ip, user_agent, success)
                 VALUES (:uid, :uname, :cbid, :cbname, :ip, :ua, true)"
            );
            $auditStmt->execute([
                ':uid'    => $_SESSION['ocp_user_id'],
                ':uname'  => $_SESSION['ocp_username'] ?? 'unknown',
                ':cbid'   => $_SESSION['ocp_user_id'],
                ':cbname' => $_SESSION['ocp_username'] ?? 'unknown',
                ':ip'     => $ip,
                ':ua'     => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 512),
            ]);

            unset($_SESSION['ocp_force_password_change']);
            $success = _('Passphrase updated successfully.');
            header('Location: dashboard.php');
            exit;
        }
    }

    if ($error !== '') {
        logAuditEvent('PASSWORD_CHANGE', 'ocp_user', $_SESSION['ocp_username'] ?? 'unknown', false, ['reason' => $error]);

        // Dedicated password-change audit for failures
        $failIp = 'unknown';
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ff = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
            $ff = trim($ff);
            if (filter_var($ff, FILTER_VALIDATE_IP)) {
                $failIp = $ff;
            }
        } elseif (!empty($_SERVER['REMOTE_ADDR']) && filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP)) {
            $failIp = $_SERVER['REMOTE_ADDR'];
        }
        $failAudit = $pdo->prepare(
            "INSERT INTO ocp_password_changes
             (user_id, username, changed_by, changed_by_name, source_ip, user_agent, success, failure_reason)
             VALUES (:uid, :uname, :cbid, :cbname, :ip, :ua, false, :reason)"
        );
        $failAudit->execute([
            ':uid'     => $_SESSION['ocp_user_id'] ?? 0,
            ':uname'   => $_SESSION['ocp_username'] ?? 'unknown',
            ':cbid'    => $_SESSION['ocp_user_id'] ?? 0,
            ':cbname'  => $_SESSION['ocp_username'] ?? 'unknown',
            ':ip'      => $failIp,
            ':ua'      => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 512),
            ':reason'  => substr($error, 0, 255),
        ]);
    }
    }
}

require_once __DIR__ . '/common/header.php';
?>
<div class="tsisip-container">
    <?php require_once __DIR__ . '/common/role-nav.php'; ?>
    <main class="tsisip-main">
        <h1 class="tsisip-page-title"><?php echo _('Change Passphrase'); ?></h1>

        <?php if ($mustChange): ?>
            <div class="tsisip-badge tsisip-badge-warning tsisip-mb-4" role="alert">
                <?php echo _('You must change your passphrase before continuing.'); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="tsisip-badge tsisip-badge-error tsisip-mb-4" role="alert">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="tsisip-badge tsisip-badge-success tsisip-mb-4" role="status">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="tsisip-form" style="max-width:480px">
            <?php echo csrfInput(); ?>
            <div class="tsisip-form-group">
                <label for="current_password"><?php echo _('Current Passphrase'); ?></label>
                <input type="password"
                       id="current_password"
                       name="current_password"
                       class="tsisip-input"
                       required
                       autocomplete="current-password">
            </div>
            <div class="tsisip-form-group">
                <label for="new_password"><?php echo _('New Passphrase'); ?></label>
                <input type="password"
                       id="new_password"
                       name="new_password"
                       class="tsisip-input"
                       required
                       minlength="12"
                       autocomplete="new-password">
                <p class="tsisip-hint">
                    <?php echo _('Minimum 12 characters, with uppercase, lowercase, number, and symbol.'); ?>
                </p>
            </div>
            <div class="tsisip-form-group">
                <label for="confirm_password"><?php echo _('Confirm New Passphrase'); ?></label>
                <input type="password"
                       id="confirm_password"
                       name="confirm_password"
                       class="tsisip-input"
                       required
                       minlength="12"
                       autocomplete="new-password">
            </div>
            <button type="submit" class="tsisip-btn tsisip-btn-primary">
                <?php echo _('Update Passphrase'); ?>
            </button>
        </form>
    </main>
</div>
<?php require_once __DIR__ . '/common/footer.php'; ?>
