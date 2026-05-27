<?php
/**
 * TSiSIP Control Panel — Personal Notes
 */
require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';

requireAuth();
checkPasswordChange();

$pdo = getDb();
$userId = $_SESSION['user_id'] ?? 0;

// Create note
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'])) {
    validateCsrfToken();
    $stmt = $pdo->prepare(
        "INSERT INTO ocp_user_notes (user_id, title, content, color)
         VALUES (:uid, :title, :content, :color)"
    );
    $stmt->execute([
        ':uid' => $userId,
        ':title' => $_POST['title'],
        ':content' => $_POST['content'] ?? '',
        ':color' => $_POST['color'] ?? 'yellow',
    ]);
    setFlash('success', _('Note created'));
    header('Location: notes.php');
    exit;
}

// Delete note
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM ocp_user_notes WHERE id = :id AND user_id = :uid");
    $stmt->execute([':id' => (int)$_GET['delete'], ':uid' => $userId]);
    setFlash('success', _('Note deleted'));
    header('Location: notes.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM ocp_user_notes WHERE user_id = :uid ORDER BY pinned DESC, created_at DESC");
$stmt->execute([':uid' => $userId]);
$notes = $stmt->fetchAll();

logAuditEvent('CONFIG_VIEW', 'user', 'notes', true);

require_once __DIR__ . '/common/header.php';
?>
<div id="content" class="tsisip-dashboard">
    <h1><?php echo _('My Notes'); ?></h1>

    <div class="tsisip-dashboard-section">
        <form method="POST" action="" class="tsisip-form">
            <?php echo csrfTokenField(); ?>
            <div class="tsisip-form-group">
                <input type="text" name="title" class="tsisip-input" placeholder="<?php echo _('Note title...'); ?>" required>
            </div>
            <div class="tsisip-form-group">
                <textarea name="content" class="tsisip-input" rows="3" placeholder="<?php echo _('Note content...'); ?>"></textarea>
            </div>
            <div class="tsisip-form-group">
                <select name="color" class="tsisip-select">
                    <option value="yellow"><?php echo _('Yellow'); ?></option>
                    <option value="blue"><?php echo _('Blue'); ?></option>
                    <option value="green"><?php echo _('Green'); ?></option>
                    <option value="red"><?php echo _('Red'); ?></option>
                </select>
            </div>
            <button type="submit" class="tsisip-btn tsisip-btn-primary"><?php echo _('Add Note'); ?></button>
        </form>
    </div>

    <div class="tsisip-dashboard-grid">
        <?php foreach ($notes as $note): ?>
            <div class="tsisip-dashboard-card tsisip-note--<?php echo $note['color']; ?>">
                <div class="tsisip-card-header">
                    <h3><?php echo htmlspecialchars($note['title']); ?></h3>
                    <?php if ($note['pinned']): ?>
                        <span class="tsisip-badge tsisip-badge--info"><?php echo _('Pinned'); ?></span>
                    <?php endif; ?>
                </div>
                <div class="tsisip-card-body">
                    <p><?php echo nl2br(htmlspecialchars($note['content'])); ?></p>
                    <small class="tsisip-text-muted"><?php echo htmlspecialchars((string) ($note['created_at'] ?? '')); ?></small>
                </div>
                <div class="tsisip-card-footer">
                    <a href="?delete=<?php echo (int)$note['id']; ?>" class="tsisip-btn tsisip-btn-sm tsisip-btn-outline"
                       onclick="return confirm('<?php echo _('Delete this note?'); ?>')">
                        <?php echo _('Delete'); ?>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
.tsisip-note--yellow { border-left: 4px solid #fbbf24; }
.tsisip-note--blue   { border-left: 4px solid #60a5fa; }
.tsisip-note--green  { border-left: 4px solid #34d399; }
.tsisip-note--red    { border-left: 4px solid #f87171; }
</style>
<?php require_once __DIR__ . '/common/footer.php'; ?>
