<?php
/**
 * TSiSIP Control Panel — Create / Edit User
 * Feature 030: OCP User Management & RBAC
 */

require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';
require_once __DIR__ . '/common/password-policy.php';

requireAuth();
checkPasswordChange();
requireRole('admin');

$pdo = getDb();
$userId = $_GET['id'] ?? '';
$isEdit = $userId !== '';

$error = '';
$success = '';

$validRoles = ['admin', 'devops', 'dentist', 'assistant', 'user', 'readonly'];

// Load existing user for edit
$user = null;
if ($isEdit) {
    $stmt = $pdo->prepare("SELECT * FROM ocp_users WHERE id = :id AND deleted_at IS NULL LIMIT 1");
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch();
    if (!$user) {
        header('Location: users.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = _('Invalid CSRF token.');
    } else {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'readonly';
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $forcePasswordChange = isset($_POST['force_password_change']) ? true : false;
        $isActive = isset($_POST['is_active']) ? true : false;

        // Validation
        if ($username === '') {
            $error = _('Username is required.');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) && $email !== '') {
            $error = _('Invalid email address.');
        } elseif (!in_array($role, $validRoles, true)) {
            $error = _('Invalid role selected.');
        } elseif (!$isEdit && $password === '') {
            $error = _('Password is required for new users.');
        } elseif ($password !== '' && $password !== $confirmPassword) {
            $error = _('Passwords do not match.');
        } elseif ($password !== '') {
            $pwCheck = validatePassword($password);
            if (!$pwCheck['valid']) {
                $error = implode(' ', $pwCheck['errors']);
            }
        }

        // Prevent self-role-change
        if ($isEdit && $userId === ($_SESSION['ocp_user_id'] ?? '') && $role !== $user['role']) {
            $error = _('You cannot change your own role.');
        }

        if ($error === '') {
            try {
                if ($isEdit) {
                    // Update existing user
                    $fields = [
                        'email' => $email,
                        'role' => $role,
                        'force_password_change' => $forcePasswordChange ? 't' : 'f',
                        'is_active' => $isActive ? 't' : 'f',
                        'updated_at' => date('Y-m-d H:i:s'),
                    ];

                    if ($password !== '') {
                        $fields['password_hash'] = hashPassword($password);
                        $fields['password_changed_at'] = date('Y-m-d H:i:s');
                        $fields['force_password_change'] = 't';
                    }

                    $setParts = [];
                    $params = [':id' => $userId];
                    foreach ($fields as $k => $v) {
                        $setParts[] = "$k = :$k";
                        $params[":$k"] = $v;
                    }

                    $stmt = $pdo->prepare("UPDATE ocp_users SET " . implode(', ', $setParts) . " WHERE id = :id");
                    $stmt->execute($params);

                    // Insert password history if changed
                    if ($password !== '') {
                        $stmt = $pdo->prepare(
                            "INSERT INTO ocp_password_history (user_id, password_hash, changed_at) VALUES (:uid, :hash, NOW())"
                        );
                        $stmt->execute([':uid' => $userId, ':hash' => $fields['password_hash']]);
                    }

                    // Invalidate sessions if disabled
                    if (!$isActive) {
                        $stmt = $pdo->prepare("UPDATE ocp_user_sessions SET invalidated_at = NOW() WHERE user_id = :uid AND invalidated_at IS NULL");
                        $stmt->execute([':uid' => $userId]);
                    }

                    logAuditEvent('OCP_USER_UPDATE', 'ocp_user', $username, true, ['role' => $role]);
                    $success = _('User updated successfully.');
                } else {
                    // Create new user
                    $hash = hashPassword($password);
                    $stmt = $pdo->prepare(
                        "INSERT INTO ocp_users (username, email, password_hash, role, enabled, force_password_change, is_active, created_at, updated_at, password_changed_at)
                         VALUES (:username, :email, :password_hash, :role, true, :force_password_change, :is_active, NOW(), NOW(), NOW())"
                    );
                    $stmt->execute([
                        ':username' => $username,
                        ':email' => $email,
                        ':password_hash' => $hash,
                        ':role' => $role,
                        ':force_password_change' => $forcePasswordChange ? 't' : 'f',
                        ':is_active' => $isActive ? 't' : 'f',
                    ]);

                    $newId = $pdo->lastInsertId();
                    if (!$newId) {
                        $newId = $pdo->query("SELECT id FROM ocp_users WHERE username = " . $pdo->quote($username))->fetchColumn();
                    }

                    // Insert password history
                    $stmt = $pdo->prepare(
                        "INSERT INTO ocp_password_history (user_id, password_hash, changed_at) VALUES (:uid, :hash, NOW())"
                    );
                    $stmt->execute([':uid' => $newId, ':hash' => $hash]);

                    logAuditEvent('OCP_USER_CREATE', 'ocp_user', $username, true, ['role' => $role]);
                    $success = _('User created successfully.');
                    header('Location: users.php?success=' . urlencode($success));
                    exit;
                }
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'unique constraint') !== false || strpos($e->getMessage(), 'duplicate') !== false) {
                    $error = _('Username already exists.');
                } else {
                    $error = _('Database error: ') . $e->getMessage();
                }
            }
        }
    }
}

require_once __DIR__ . '/common/header.php';
?>
<div id="content">
    <h1><?php echo $isEdit ? _('Edit User') : _('Create User'); ?></h1>

    <?php if ($error): ?>
        <div class="tsisip-alert tsisip-alert--error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="tsisip-alert tsisip-alert--success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form method="post" class="tsisip-form" style="max-width:600px;">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken()); ?>">

        <div class="tsisip-form-group">
            <label class="tsisip-form-label"><?php echo _('Username'); ?></label>
            <input type="text" name="username" class="tsisip-input"
                   value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>"
                   <?php echo $isEdit ? 'readonly' : 'required'; ?>>
        </div>

        <div class="tsisip-form-group">
            <label class="tsisip-form-label"><?php echo _('Email'); ?></label>
            <input type="email" name="email" class="tsisip-input"
                   value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
        </div>

        <div class="tsisip-form-group">
            <label class="tsisip-form-label"><?php echo _('Role'); ?></label>
            <select name="role" class="tsisip-input" required>
                <?php foreach ($validRoles as $r): ?>
                    <option value="<?php echo $r; ?>" <?php echo ($user['role'] ?? '') === $r ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($r); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="tsisip-form-group">
            <label class="tsisip-form-label">
                <?php echo $isEdit ? _('New Password (leave blank to keep current)') : _('Password'); ?>
            </label>
            <input type="password" name="password" class="tsisip-input" <?php echo $isEdit ? '' : 'required'; ?>>
            <small class="tsisip-text-muted"><?php echo _('Min 8 chars, 1 upper, 1 lower, 1 digit'); ?></small>
        </div>

        <div class="tsisip-form-group">
            <label class="tsisip-form-label"><?php echo _('Confirm Password'); ?></label>
            <input type="password" name="confirm_password" class="tsisip-input" <?php echo $isEdit ? '' : 'required'; ?>>
        </div>

        <div class="tsisip-form-group">
            <label class="tsisip-form-label">
                <input type="checkbox" name="force_password_change" <?php echo ($user['force_password_change'] ?? false) ? 'checked' : ''; ?>>
                <?php echo _('Force password change on next login'); ?>
            </label>
        </div>

        <div class="tsisip-form-group">
            <label class="tsisip-form-label">
                <input type="checkbox" name="is_active" <?php echo ($user['is_active'] ?? true) ? 'checked' : ''; ?>>
                <?php echo _('Account active'); ?>
            </label>
        </div>

        <div style="display:flex;gap:12px;margin-top:16px;">
            <button type="submit" class="tsisip-btn tsisip-btn-primary">
                <?php echo $isEdit ? _('Update User') : _('Create User'); ?>
            </button>
            <a href="users.php" class="tsisip-btn tsisip-btn-outline"><?php echo _('Cancel'); ?></a>
        </div>
    </form>
</div>
<?php require_once __DIR__ . '/common/footer.php'; ?>
