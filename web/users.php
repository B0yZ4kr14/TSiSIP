<?php
/**
 * TSiSIP Control Panel — OCP User Management
 * CRUD for ocp_users table (admin-only for create/delete, devops can view).
 */

require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';
require_once __DIR__ . '/common/pagination.php';

requireAuth();
checkPasswordChange();
requireRole('devops');

$isAdmin = isAdmin();
$pdo = getDb();
$error = '';
$success = '';

$validRoles = ['admin', 'devops', 'dentist', 'assistant', 'user', 'readonly'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = _('Invalid CSRF token.');
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create' && $isAdmin) {
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'readonly';
            $enabled = isset($_POST['enabled']) ? true : false;

            if ($username === '' || $password === '') {
                $error = _('Username and password are required.');
            } elseif (strlen($password) < 8) {
                $error = _('Password must be at least 8 characters.');
            } elseif (!in_array($role, $validRoles, true)) {
                $error = _('Invalid role selected.');
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                try {
                    $stmt = $pdo->prepare(
                        "INSERT INTO ocp_users (username, email, password_hash, role, enabled, force_password_change)
                         VALUES (:username, :email, :password_hash, :role, :enabled, true)"
                    );
                    $stmt->execute([
                        ':username' => $username,
                        ':email' => $email,
                        ':password_hash' => $hash,
                        ':role' => $role,
                        ':enabled' => $enabled,
                    ]);
                    $success = _('User created successfully.');
                    logAuditEvent('OCP_USER_CREATE', 'ocp_user', $username, true, ['role' => $role]);
                } catch (PDOException $e) {
                    $error = _('Failed to create user: ') . $e->getMessage();
                }
            }
        } elseif ($action === 'update' && $isAdmin) {
            $id = $_POST['id'] ?? '';
            $email = trim($_POST['email'] ?? '');
            $role = $_POST['role'] ?? 'readonly';
            $enabled = isset($_POST['enabled']) ? true : false;
            $password = $_POST['password'] ?? '';

            if ($id === '') {
                $error = _('User ID is required.');
            } elseif (!in_array($role, $validRoles, true)) {
                $error = _('Invalid role selected.');
            } else {
                try {
                    if ($password !== '') {
                        if (strlen($password) < 8) {
                            $error = _('Password must be at least 8 characters.');
                        } else {
                            $hash = password_hash($password, PASSWORD_BCRYPT);
                            $stmt = $pdo->prepare(
                                "UPDATE ocp_users SET email = :email, role = :role, enabled = :enabled, password_hash = :password_hash, updated_at = NOW() WHERE id = :id"
                            );
                            $stmt->execute([':id' => $id, ':email' => $email, ':role' => $role, ':enabled' => $enabled, ':password_hash' => $hash]);
                        }
                    } else {
                        $stmt = $pdo->prepare(
                            "UPDATE ocp_users SET email = :email, role = :role, enabled = :enabled, updated_at = NOW() WHERE id = :id"
                        );
                        $stmt->execute([':id' => $id, ':email' => $email, ':role' => $role, ':enabled' => $enabled]);
                    }
                    if (!$error) {
                        $success = _('User updated successfully.');
                        logAuditEvent('OCP_USER_UPDATE', 'ocp_user', $id, true);
                    }
                } catch (PDOException $e) {
                    $error = _('Failed to update user: ') . $e->getMessage();
                }
            }
        } elseif ($action === 'delete' && $isAdmin) {
            $id = $_POST['id'] ?? '';
            if ($id !== '') {
                try {
                    $stmt = $pdo->prepare("DELETE FROM ocp_users WHERE id = :id");
                    $stmt->execute([':id' => $id]);
                    $success = _('User deleted successfully.');
                    logAuditEvent('OCP_USER_DELETE', 'ocp_user', $id, true);
                } catch (PDOException $e) {
                    $error = _('Failed to delete user: ') . $e->getMessage();
                }
            }
        } elseif (in_array($action, ['create', 'update', 'delete'], true) && !$isAdmin) {
            $error = _('Admin role required for this action.');
        }
    }
}

$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 25;
$pagination = getPagination($page, $perPage);

$countStmt = $pdo->query("SELECT COUNT(*) FROM ocp_users");
$totalItems = (int) $countStmt->fetchColumn();

$listStmt = $pdo->prepare("SELECT id, username, email, role, enabled, failed_attempts, locked_until, last_login_at, created_at FROM ocp_users ORDER BY username LIMIT :limit OFFSET :offset");
$listStmt->bindValue(':limit', $pagination['limit'], PDO::PARAM_INT);
$listStmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
$listStmt->execute();
$users = $listStmt->fetchAll();

require_once __DIR__ . '/common/header.php';
?>
<div class="tsisip-dashboard">
    <h2><?php echo _('OCP User Management'); ?></h2>

    <?php if ($error): ?>
        <div class="tsisip-alert tsisip-alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="tsisip-alert tsisip-alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if ($isAdmin): ?>
    <div class="tsisip-dashboard-section">
        <h3><?php echo _('Add New User'); ?></h3>
        <form method="POST" action="" class="tsisip-form">
            <?php echo csrfInput(); ?>
            <input type="hidden" name="action" value="create">
            <div class="tsisip-form-group">
                <label for="username"><?php echo _('Username'); ?></label>
                <input type="text" id="username" name="username" class="tsisip-input" required>
            </div>
            <div class="tsisip-form-group">
                <label for="email"><?php echo _('Email'); ?></label>
                <input type="email" id="email" name="email" class="tsisip-input">
            </div>
            <div class="tsisip-form-group">
                <label for="password"><?php echo _('Password'); ?></label>
                <input type="password" id="password" name="password" class="tsisip-input" required minlength="8">
            </div>
            <div class="tsisip-form-group">
                <label for="role"><?php echo _('Role'); ?></label>
                <select id="role" name="role" class="tsisip-input">
                    <?php foreach ($validRoles as $r): ?>
                        <option value="<?php echo htmlspecialchars($r, ENT_QUOTES); ?>"><?php echo htmlspecialchars(ucfirst($r)); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="tsisip-form-group">
                <label>
                    <input type="checkbox" name="enabled" checked>
                    <?php echo _('Enabled'); ?>
                </label>
            </div>
            <button type="submit" class="tsisip-btn tsisip-btn-primary"><?php echo _('Create User'); ?></button>
        </form>
    </div>
    <?php endif; ?>

    <div class="tsisip-dashboard-section">
        <h3><?php echo _('Users'); ?> (<?php echo $totalItems; ?>)</h3>
        <table class="dataTable tsisip-table">
            <thead>
                <tr>
                    <th><?php echo _('Username'); ?></th>
                    <th><?php echo _('Email'); ?></th>
                    <th><?php echo _('Role'); ?></th>
                    <th><?php echo _('Status'); ?></th>
                    <th><?php echo _('Last Login'); ?></th>
                    <th><?php echo _('Actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><?php echo htmlspecialchars($u['username']); ?></td>
                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                    <td><span class="tsisip-badge tsisip-role-badge--<?php echo htmlspecialchars($u['role']); ?>"><?php echo htmlspecialchars(ucfirst($u['role'])); ?></span></td>
                    <td>
                        <?php if ($u['enabled']): ?>
                            <span class="tsisip-badge tsisip-badge-success"><?php echo _('Active'); ?></span>
                        <?php else: ?>
                            <span class="tsisip-badge tsisip-badge-error"><?php echo _('Disabled'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $u['last_login_at'] ? htmlspecialchars($u['last_login_at']) : _('Never'); ?></td>
                    <td class="tsisip-actions-column">
                        <?php if ($isAdmin): ?>
                        <button type="button" class="tsisip-btn tsisip-btn-secondary"
                                onclick="document.getElementById('edit-<?php echo htmlspecialchars($u['id'], ENT_QUOTES); ?>').style.display='block'">
                            <?php echo _('Edit'); ?>
                        </button>
                        <form method="POST" action="" style="display:inline" onsubmit="return confirm('<?php echo _('Delete this user?'); ?>');">
                            <?php echo csrfInput(); ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($u['id'], ENT_QUOTES); ?>">
                            <button type="submit" class="tsisip-btn tsisip-btn-danger"><?php echo _('Delete'); ?></button>
                        </form>
                        <?php else: ?>
                            <span class="tsisip-text-muted"><?php echo _('Admin only'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if ($isAdmin): ?>
                <tr id="edit-<?php echo htmlspecialchars($u['id'], ENT_QUOTES); ?>" style="display:none">
                    <td colspan="6">
                        <form method="POST" action="" class="tsisip-form">
                            <?php echo csrfInput(); ?>
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($u['id'], ENT_QUOTES); ?>">
                            <div class="tsisip-form-group">
                                <label><?php echo _('Email'); ?></label>
                                <input type="email" name="email" class="tsisip-input" value="<?php echo htmlspecialchars($u['email'] ?? '', ENT_QUOTES); ?>">
                            </div>
                            <div class="tsisip-form-group">
                                <label><?php echo _('Password (leave blank to keep)'); ?></label>
                                <input type="password" name="password" class="tsisip-input" minlength="8">
                            </div>
                            <div class="tsisip-form-group">
                                <label><?php echo _('Role'); ?></label>
                                <select name="role" class="tsisip-input">
                                    <?php foreach ($validRoles as $r): ?>
                                        <option value="<?php echo htmlspecialchars($r, ENT_QUOTES); ?>" <?php echo $r === $u['role'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars(ucfirst($r)); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="tsisip-form-group">
                                <label>
                                    <input type="checkbox" name="enabled" <?php echo $u['enabled'] ? 'checked' : ''; ?>>
                                    <?php echo _('Enabled'); ?>
                                </label>
                            </div>
                            <button type="submit" class="tsisip-btn tsisip-btn-primary"><?php echo _('Save Changes'); ?></button>
                            <button type="button" class="tsisip-btn tsisip-btn-secondary"
                                    onclick="document.getElementById('edit-<?php echo htmlspecialchars($u['id'], ENT_QUOTES); ?>').style.display='none'">
                                <?php echo _('Cancel'); ?>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php echo renderPagination($page, $totalItems, $perPage, 'users.php'); ?>
    </div>
</div>
<?php require_once __DIR__ . '/common/footer.php'; ?>
