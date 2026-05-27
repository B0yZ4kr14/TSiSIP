<?php
/**
 * /api/v1/users
 * GET: list users, POST: create user, PATCH: update, DELETE: soft delete
 */

$pdo = getDb();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->query(
        "SELECT id, username, email, role, enabled, force_password_change, last_login_at, created_at
         FROM ocp_users WHERE deleted_at IS NULL ORDER BY username"
    );
    $users = $stmt->fetchAll();
    http_response_code(200);
    echo json_encode(['data' => $users], JSON_PRETTY_PRINT);
    exit;
}

if ($method === 'POST') {
    requireApiAuth('read-write');
    $input = json_decode(file_get_contents('php://input'), true);
    // Basic validation
    $username = trim($input['username'] ?? '');
    $email = trim($input['email'] ?? '');
    $role = $input['role'] ?? 'readonly';
    $password = $input['password'] ?? '';
    if ($username === '' || $password === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Bad Request', 'message' => 'Username and password are required']);
        exit;
    }
    require_once __DIR__ . '/../../common/password-policy.php';
    $pwCheck = validatePassword($password);
    if (!$pwCheck['valid']) {
        http_response_code(400);
        echo json_encode(['error' => 'Bad Request', 'message' => implode(', ', $pwCheck['errors'])]);
        exit;
    }
    $hash = hashPassword($password);
    $stmt = $pdo->prepare(
        "INSERT INTO ocp_users (username, email, password_hash, role, enabled, force_password_change, created_at, updated_at, password_changed_at)
         VALUES (:uname, :email, :ph, :role, true, :fpc, NOW(), NOW(), NOW())"
    );
    $stmt->execute([
        ':uname' => $username,
        ':email' => $email,
        ':ph' => $hash,
        ':role' => $role,
        ':fpc' => !empty($input['force_password_change']),
    ]);
    $newId = $pdo->lastInsertId();
    recordPasswordHistory($pdo, $newId, $hash);
    logAuditEvent('API_USER_CREATE', 'ocp_user', $newId, true, ['role' => $role]);
    http_response_code(201);
    echo json_encode(['id' => $newId, 'username' => $username], JSON_PRETTY_PRINT);
    exit;
}

// PATCH / DELETE with ID
if ($resourceId !== null) {
    $userId = (int)$resourceId;
    if ($method === 'PATCH') {
        requireApiAuth('read-write');
        $input = json_decode(file_get_contents('php://input'), true);
        $fields = [];
        $params = [':id' => $userId];
        if (isset($input['email'])) {
            $fields[] = 'email = :email';
            $params[':email'] = $input['email'];
        }
        if (isset($input['role'])) {
            $fields[] = 'role = :role';
            $params[':role'] = $input['role'];
        }
        if (isset($input['enabled'])) {
            $fields[] = 'enabled = :enabled';
            $params[':enabled'] = $input['enabled'] ? 1 : 0;
        }
        if (!empty($input['password'])) {
            require_once __DIR__ . '/../../common/password-policy.php';
            $hash = hashPassword($input['password']);
            $fields[] = 'password_hash = :ph, password_changed_at = NOW()';
            $params[':ph'] = $hash;
            recordPasswordHistory($pdo, $userId, $hash);
        }
        if (!empty($fields)) {
            $sql = "UPDATE ocp_users SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = :id";
            $pdo->prepare($sql)->execute($params);
        }
        logAuditEvent('API_USER_UPDATE', 'ocp_user', $userId, true);
        http_response_code(200);
        echo json_encode(['id' => $userId], JSON_PRETTY_PRINT);
        exit;
    }
    if ($method === 'DELETE') {
        requireApiAuth('read-write');
        $pdo->prepare("UPDATE ocp_users SET deleted_at = NOW(), enabled = false WHERE id = :id")
            ->execute([':id' => $userId]);
        logAuditEvent('API_USER_DELETE', 'ocp_user', $userId, true);
        http_response_code(204);
        exit;
    }
}

http_response_code(405);
echo json_encode(['error' => 'Method Not Allowed']);
