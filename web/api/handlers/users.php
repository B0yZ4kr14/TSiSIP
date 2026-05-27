<?php
/**
 * GET /api/v1/users — List users
 * POST /api/v1/users — Create user
 * PATCH /api/v1/users/:id — Update user
 * DELETE /api/v1/users/:id — Soft delete user
 */

require_once __DIR__ . '/../../common/password-policy.php';

$pdo = getDb();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->prepare(
        "SELECT id, username, email, role, enabled, is_active, force_password_change, created_at, last_login_at
         FROM ocp_users WHERE deleted_at IS NULL ORDER BY username"
    );
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['users' => $users]);
    exit;
}

// Write operations require read-write scope
global $auth;
requireApiWriteScope($auth);

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $username = trim($input['username'] ?? '');
    $email = trim($input['email'] ?? '');
    $role = $input['role'] ?? 'readonly';
    $password = $input['password'] ?? '';

    if ($username === '' || $password === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Username and password are required']);
        exit;
    }

    $pwCheck = validatePassword($password);
    if (!$pwCheck['valid']) {
        http_response_code(400);
        echo json_encode(['error' => $pwCheck['errors']]);
        exit;
    }

    try {
        $hash = hashPassword($password);
        $stmt = $pdo->prepare(
            "INSERT INTO ocp_users (username, email, password_hash, role, enabled, force_password_change, is_active, created_at, updated_at)
             VALUES (:username, :email, :password_hash, :role, true, true, true, NOW(), NOW())"
        );
        $stmt->execute([
            ':username' => $username,
            ':email' => $email,
            ':password_hash' => $hash,
            ':role' => $role,
        ]);
        logAuditEvent('API_USER_CREATE', 'ocp_user', $username, true, ['role' => $role]);
        http_response_code(201);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    } catch (PDOException $e) {
        http_response_code(409);
        echo json_encode(['error' => 'Username already exists']);
    }
    exit;
}

if ($method === 'PATCH') {
    $userId = $_REQUEST['user_id'] ?? '';
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$userId) {
        http_response_code(400);
        echo json_encode(['error' => 'User ID required']);
        exit;
    }

    $fields = [];
    $params = [':id' => $userId];

    if (isset($input['email'])) {
        $fields[] = "email = :email";
        $params[':email'] = $input['email'];
    }
    if (isset($input['role'])) {
        $fields[] = "role = :role";
        $params[':role'] = $input['role'];
    }
    if (isset($input['is_active'])) {
        $fields[] = "is_active = :is_active";
        $params[':is_active'] = $input['is_active'] ? 't' : 'f';
    }
    if (isset($input['password']) && $input['password'] !== '') {
        $pwCheck = validatePassword($input['password']);
        if (!$pwCheck['valid']) {
            http_response_code(400);
            echo json_encode(['error' => $pwCheck['errors']]);
            exit;
        }
        $fields[] = "password_hash = :password_hash";
        $fields[] = "password_changed_at = NOW()";
        $params[':password_hash'] = hashPassword($input['password']);
    }

    if (empty($fields)) {
        http_response_code(400);
        echo json_encode(['error' => 'No fields to update']);
        exit;
    }

    $fields[] = "updated_at = NOW()";
    $stmt = $pdo->prepare("UPDATE ocp_users SET " . implode(', ', $fields) . " WHERE id = :id AND deleted_at IS NULL");
    $stmt->execute($params);

    logAuditEvent('API_USER_UPDATE', 'ocp_user', $userId, true);
    echo json_encode(['success' => true]);
    exit;
}

if ($method === 'DELETE') {
    $userId = $_REQUEST['user_id'] ?? '';
    if (!$userId) {
        http_response_code(400);
        echo json_encode(['error' => 'User ID required']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE ocp_users SET deleted_at = NOW(), is_active = false WHERE id = :id");
    $stmt->execute([':id' => $userId]);

    logAuditEvent('API_USER_DELETE', 'ocp_user', $userId, true);
    echo json_encode(['success' => true]);
    exit;
}
