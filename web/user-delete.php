<?php
/**
 * TSiSIP Control Panel — Soft Delete User
 * Feature 030: OCP User Management & RBAC
 */

require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';

requireAuth();
checkPasswordChange();
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: users.php');
    exit;
}

if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => _('Invalid CSRF token.')];
    header('Location: users.php');
    exit;
}

$userId = $_POST['user_id'] ?? '';
$currentUserId = $_SESSION['ocp_user_id'] ?? '';

if ($userId === '' || $userId === $currentUserId) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => _('You cannot delete yourself.')];
    header('Location: users.php');
    exit;
}

$pdo = getDb();

// Prevent deleting the last admin
$stmt = $pdo->prepare("SELECT role FROM ocp_users WHERE id = :id AND deleted_at IS NULL LIMIT 1");
$stmt->execute([':id' => $userId]);
$targetUser = $stmt->fetch();

if (!$targetUser) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => _('User not found.')];
    header('Location: users.php');
    exit;
}

if ($targetUser['role'] === 'admin') {
    $stmt = $pdo->query("SELECT COUNT(*) FROM ocp_users WHERE role = 'admin' AND deleted_at IS NULL");
    $adminCount = (int) $stmt->fetchColumn();
    if ($adminCount <= 1) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => _('Cannot delete the last admin account.')];
        header('Location: users.php');
        exit;
    }
}

try {
    // Soft delete
    $stmt = $pdo->prepare("UPDATE ocp_users SET deleted_at = NOW(), is_active = false, updated_at = NOW() WHERE id = :id");
    $stmt->execute([':id' => $userId]);

    // Invalidate all active sessions
    $stmt = $pdo->prepare("UPDATE ocp_user_sessions SET invalidated_at = NOW() WHERE user_id = :uid AND invalidated_at IS NULL");
    $stmt->execute([':uid' => $userId]);

    logAuditEvent('OCP_USER_DELETE', 'ocp_user', $targetUser['role'], true, ['user_id' => $userId]);
    $_SESSION['flash'] = ['type' => 'success', 'message' => _('User deleted successfully.')];
} catch (PDOException $e) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => _('Failed to delete user: ') . $e->getMessage()];
}

header('Location: users.php');
exit;
