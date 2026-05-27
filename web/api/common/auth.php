<?php
/**
 * TSiSIP REST API — Bearer Token Authentication
 * Feature 031
 */

require_once __DIR__ . '/../../common/config.php';

/**
 * Authenticate API request via Bearer token.
 * Returns ['valid' => bool, 'key_id' => string|null, 'scope' => string|null, 'error' => string|null]
 */
function authenticateApiRequest(): array {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!str_starts_with($authHeader, 'Bearer ')) {
        return ['valid' => false, 'key_id' => null, 'scope' => null, 'error' => 'Missing or invalid Authorization header'];
    }

    $token = substr($authHeader, 7);
    if (strlen($token) < 32) {
        return ['valid' => false, 'key_id' => null, 'scope' => null, 'error' => 'Invalid token format'];
    }

    try {
        $pdo = getDb();
        $stmt = $pdo->prepare(
            "SELECT id, key_hash, scope, expires_at, is_active
             FROM ocp_api_keys
             WHERE is_active = true AND deleted_at IS NULL"
        );
        $stmt->execute();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (password_verify($token, $row['key_hash'])) {
                if ($row['expires_at'] !== null && strtotime($row['expires_at']) < time()) {
                    return ['valid' => false, 'key_id' => null, 'scope' => null, 'error' => 'API key expired'];
                }

                // Update last_used_at
                $pdo->prepare("UPDATE ocp_api_keys SET last_used_at = NOW() WHERE id = :id")
                    ->execute([':id' => $row['id']]);

                return [
                    'valid' => true,
                    'key_id' => $row['id'],
                    'scope' => $row['scope'],
                    'error' => null,
                ];
            }
        }
    } catch (Exception $e) {
        error_log('API auth error: ' . $e->getMessage());
        return ['valid' => false, 'key_id' => null, 'scope' => null, 'error' => 'Authentication system error'];
    }

    return ['valid' => false, 'key_id' => null, 'scope' => null, 'error' => 'Invalid API key'];
}

/**
 * Require API authentication. Sends 401 JSON and exits if invalid.
 */
function requireApiAuth(): array {
    $auth = authenticateApiRequest();
    if (!$auth['valid']) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => $auth['error']]);
        exit;
    }
    return $auth;
}

/**
 * Require read-write scope. Sends 403 if key is read-only.
 */
function requireApiWriteScope(array $auth): void {
    if ($auth['scope'] !== 'readwrite') {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'This endpoint requires a read-write API key']);
        exit;
    }
}
