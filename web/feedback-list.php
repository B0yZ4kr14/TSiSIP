<?php
/**
 * TSiSIP Control Panel — Feedback List (Admin)
 */
require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';

requireAuth();
checkPasswordChange();
requireRole('admin');

$pdo = getDb();

// Update status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['feedback_id'], $_POST['status'])) {
    validateCsrfToken();
    $stmt = $pdo->prepare("UPDATE ocp_feedback SET status = :status, updated_at = NOW() WHERE id = :id");
    $stmt->execute([':status' => $_POST['status'], ':id' => $_POST['feedback_id']]);
    setFlash('success', _('Status updated'));
    header('Location: feedback-list.php');
    exit;
}

$stmt = $pdo->query("SELECT * FROM ocp_feedback ORDER BY created_at DESC LIMIT 100");
$feedbacks = $stmt->fetchAll();

logAuditEvent('CONFIG_VIEW', 'system', 'feedback-list', true);

require_once __DIR__ . '/common/header.php';
?>
<div id="content" class="tsisip-dashboard">
    <h1><?php echo _('Feedback Management'); ?></h1>

    <div class="tsisip-dashboard-section">
        <table class="tsisip-table dataTable">
            <thead>
                <tr>
                    <th><?php echo _('Date'); ?></th>
                    <th><?php echo _('User'); ?></th>
                    <th><?php echo _('Type'); ?></th>
                    <th><?php echo _('Message'); ?></th>
                    <th><?php echo _('Status'); ?></th>
                    <th><?php echo _('Action'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($feedbacks as $fb): ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string) ($fb['created_at'] ?? '—')); ?></td>
                        <td><?php echo htmlspecialchars($fb['username']); ?></td>
                        <td>
                            <span class="tsisip-badge tsisip-badge--<?php echo $fb['type']; ?>">
                                <?php echo htmlspecialchars($fb['type']); ?>
                            </span>
                        </td>
                        <td><?php echo nl2br(htmlspecialchars(substr($fb['message'], 0, 200))); ?></td>
                        <td>
                            <span class="tsisip-badge tsisip-badge--<?php echo $fb['status']; ?>">
                                <?php echo htmlspecialchars($fb['status']); ?>
                            </span>
                        </td>
                        <td>
                            <form method="POST" action="" style="display:inline;">
                                <?php echo csrfTokenField(); ?>
                                <input type="hidden" name="feedback_id" value="<?php echo (int)$fb['id']; ?>">
                                <select name="status" onchange="this.form.submit()" class="tsisip-select tsisip-select-sm">
                                    <option value="new" <?php echo $fb['status'] === 'new' ? 'selected' : ''; ?>>new</option>
                                    <option value="reviewing" <?php echo $fb['status'] === 'reviewing' ? 'selected' : ''; ?>>reviewing</option>
                                    <option value="resolved" <?php echo $fb['status'] === 'resolved' ? 'selected' : ''; ?>>resolved</option>
                                    <option value="declined" <?php echo $fb['status'] === 'declined' ? 'selected' : ''; ?>>declined</option>
                                </select>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/common/footer.php'; ?>
