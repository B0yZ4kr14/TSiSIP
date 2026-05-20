<?php
/**
 * TSiSIP Control Panel — Force Passphrase Change
 */

require_once __DIR__ . '/common/config.php';
requireAuth();

$mustChange = !empty($_SESSION['ocp_force_password_change']);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

            unset($_SESSION['ocp_force_password_change']);
            $success = _('Passphrase updated successfully.');
            header('Location: dashboard.php');
            exit;
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
