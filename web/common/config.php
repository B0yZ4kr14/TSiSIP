<?php
/**
 * TSiSIP Control Panel — Configuration & Database Connection
 *
 * Reads PostgreSQL credentials from environment variables or container secrets.
 */

// Detect HTTPS through reverse proxy (nginx sets X-Forwarded-Proto)
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
           (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

if ($isHttps) {
    ini_set('session.cookie_secure', '1');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Database Configuration ---
$dbHost = getenv('DB_HOST') ?: 'postgres';
$dbName = getenv('DB_NAME') ?: 'opensips';
$dbUser = getenv('DB_USER') ?: 'opensips';
$dbPass = '';

// Read credential from container secret mount if present
// OCP entrypoint copies secrets to /tmp with www-data-readable permissions
$secretPath = '/tmp/db_password';
if (!file_exists($secretPath)) {
    $secretPath = '/run/secrets/db_password';
}
if (file_exists($secretPath) && is_readable($secretPath)) {
    $dbPass = rtrim(file_get_contents($secretPath), "\r\n");
} else {
    $dbPass = getenv('DB_PASSWORD') ?: '';
}

// --- PDO Connection (lazy singleton) ---
function getDb(): PDO {
    global $dbHost, $dbName, $dbUser, $dbPass;
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "pgsql:host={$dbHost};dbname={$dbName};port=5432";
        $opts = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, $dbUser, $dbPass, $opts);
        } catch (PDOException $e) {
            error_log('TSiSIP OCP DB connection failed: ' . $e->getMessage());
            throw new Exception(_('Service temporarily unavailable. Please try again later.'));
        }
    }
    return $pdo;
}

// --- Auth Helpers ---

/**
 * Authenticate an OCP user against the PostgreSQL ocp_users table.
 */
function authenticateUser(string $username, string $password): ?array {
    $pdo = getDb();

    $stmt = $pdo->prepare(
        "SELECT id, username, email, password_hash, role, enabled, failed_attempts, locked_until, force_password_change
         FROM ocp_users
         WHERE LOWER(username) = LOWER(:username)
         LIMIT 1"
    );
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch();

    if (!$user) {
        return null;
    }

    if ($user['locked_until'] !== null) {
        $lockedUntil = new DateTime($user['locked_until']);
        $now = new DateTime('now', new DateTimeZone('UTC'));
        if ($lockedUntil > $now) {
            return null;
        }
    }

    if (!$user['enabled']) {
        return null;
    }

    if (!password_verify($password, $user['password_hash'])) {
        $pdo->prepare(
            "UPDATE ocp_users
             SET failed_attempts = failed_attempts + 1,
                 locked_until = CASE WHEN failed_attempts >= 4 THEN NOW() + INTERVAL '15 minutes' ELSE locked_until END,
                 updated_at = NOW()
             WHERE id = :id"
        )->execute([':id' => $user['id']]);

        logLoginAttempt($user['username'], 'failure', 'Invalid password');
        return null;
    }

    $pdo->prepare(
        "UPDATE ocp_users
         SET failed_attempts = 0,
             locked_until = NULL,
             last_login_at = NOW(),
             updated_at = NOW()
         WHERE id = :id"
    )->execute([':id' => $user['id']]);

    logLoginAttempt($user['username'], 'success');

    return [
        'id'       => $user['id'],
        'username' => $user['username'],
        'email'    => $user['email'],
        'role'     => $user['role'],
        'force_password_change' => (bool)$user['force_password_change'],
    ];
}

/**
 * Log an OCP authentication event.
 */
function logLoginAttempt(string $username, string $result, string $reason = ''): void {
    try {
        $pdo = getDb();
        $stmt = $pdo->prepare(
            "INSERT INTO ocp_login_log (username, source_ip, user_agent, result, reason)
             VALUES (:username, :ip, :ua, :result, :reason)"
        );
        $stmt->execute([
            ':username' => $username,
            ':ip'       => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            ':ua'       => $_SERVER['HTTP_USER_AGENT'] ?? '',
            ':result'   => $result,
            ':reason'   => $reason,
        ]);
    } catch (Exception $e) {
        error_log('Failed to write ocp_login_log: ' . $e->getMessage());
    }
}

/**
 * Require authentication. Redirects to login.php if not logged in.
 */
function requireAuth(): void {
    if (empty($_SESSION['ocp_user_id'])) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Redirect to change-password.php if the user must change their password.
 * Call this after requireAuth() on every page except change-password.php itself.
 */
function checkPasswordChange(): void {
    $currentPage = basename($_SERVER['PHP_SELF']);
    if ($currentPage === 'change-password.php') {
        return;
    }
    if (!empty($_SESSION['ocp_force_password_change'])) {
        header('Location: change-password.php');
        exit;
    }
}

/**
 * Require a specific role (or higher). Admins bypass all checks.
 */
function requireRole(string $role): void {
    requireAuth();
    $roleHierarchy = [
        'readonly'  => 0,
        'user'      => 1,
        'assistant' => 2,
        'dentist'   => 3,
        'devops'    => 4,
        'admin'     => 5,
    ];
    $userRole = $_SESSION['ocp_user_role'] ?? 'readonly';
    $requiredLevel = $roleHierarchy[$role] ?? 0;
    $userLevel = $roleHierarchy[$userRole] ?? 0;

    if ($userLevel < $requiredLevel) {
        http_response_code(403);
        echo '<p class="tsisip-badge tsisip-badge-error">' . _('Access denied.') . '</p>';
        exit;
    }
}

/**
 * Check if current user has at least devops level (admin or devops).
 */
function isDevOpsOrHigher(): bool {
    $roleHierarchy = [
        'readonly'  => 0,
        'user'      => 1,
        'assistant' => 2,
        'dentist'   => 3,
        'devops'    => 4,
        'admin'     => 5,
    ];
    $userRole = $_SESSION['ocp_user_role'] ?? 'readonly';
    return ($roleHierarchy[$userRole] ?? 0) >= 4;
}

/**
 * Check if current user is admin.
 */
function isAdmin(): bool {
    return ($_SESSION['ocp_user_role'] ?? '') === 'admin';
}
