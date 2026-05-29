<?php
/**
 * TSiSIP Control Panel — Feedback
 */
require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';

requireAuth();
checkPasswordChange();

$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken();
    $type = $_POST['type'] ?? 'suggestion';
    $message = trim($_POST['message'] ?? '');

    if ($message !== '') {
        $pdo = getDb();
        $stmt = $pdo->prepare(
            "INSERT INTO ocp_feedback (user_id, username, type, message, created_at)
             VALUES (:uid, :user, :type, :msg, NOW())"
        );
        $stmt->execute([
            ':uid' => $_SESSION['ocp_user_id'] ?? 0,
            ':user' => $_SESSION['username'] ?? 'unknown',
            ':type' => $type,
            ':msg' => $message,
        ]);
        logAuditEvent('FEEDBACK', 'system', $type, true);
        setFlash('success', _('Thank you for your feedback!'));
        $success = true;
    }
}

require_once __DIR__ . '/common/header.php';
?>
<div id="content" class="tsisip-dashboard">
    <h1><?php echo _('Feedback'); ?></h1>

    <?php if ($success): ?>
        <div class="tsisip-badge tsisip-badge--success"><?php echo _('Feedback submitted successfully.'); ?></div>
    <?php endif; ?>

    <div class="tsisip-dashboard-section">
        <form method="POST" action="" class="tsisip-form">
            <?php echo csrfTokenField(); ?>

            <div class="tsisip-form-group">
                <label class="tsisip-form-label"><?php echo _('Type'); ?></label>
                <select name="type" class="tsisip-select" required>
                    <option value="suggestion"><?php echo _('Suggestion'); ?></option>
                    <option value="bug"><?php echo _('Bug Report'); ?></option>
                    <option value="feature"><?php echo _('Feature Request'); ?></option>
                    <option value="other"><?php echo _('Other'); ?></option>
                </select>
            </div>

            <div class="tsisip-form-group">
                <label class="tsisip-form-label"><?php echo _('Message'); ?></label>
                <textarea name="message" class="tsisip-input" rows="6" required
                          placeholder="<?php echo _('Describe your feedback...'); ?>"></textarea>
            </div>

            <button type="submit" class="tsisip-btn tsisip-btn-primary"><?php echo _('Submit Feedback'); ?></button>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/common/footer.php'; ?>
