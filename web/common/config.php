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
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', '1');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- i18n / Gettext Setup ---
$ocpLocale = isset($_SESSION['lang']) ? $_SESSION['lang'] : (getenv('OCP_DEFAULT_LANG') ?: 'en_US');
$validLocales = ['en_US', 'es_ES', 'pt_BR'];
if (!in_array($ocpLocale, $validLocales, true)) {
    $ocpLocale = 'en_US';
}
putenv('LC_ALL=' . $ocpLocale);
setlocale(LC_ALL, $ocpLocale . '.UTF-8', $ocpLocale);
$localeDir = __DIR__ . '/../tsisip/locale';
bindtextdomain('tsisip', $localeDir);
bind_textdomain_codeset('tsisip', 'UTF-8');
textdomain('tsisip');

require_once __DIR__ . '/audit.php';
require_once __DIR__ . '/csrf.php';

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
        logAuditEvent('LOGIN', 'ocp_user', $username, false, ['reason' => 'User not found']);
        return null;
    }

    if ($user['locked_until'] !== null) {
        $lockedUntil = new DateTime($user['locked_until']);
        $now = new DateTime('now', new DateTimeZone('UTC'));
        if ($lockedUntil > $now) {
            logAuditEvent('LOGIN', 'ocp_user', $username, false, ['reason' => 'Account locked']);
            return null;
        }
    }

    if (!$user['enabled']) {
        logAuditEvent('LOGIN', 'ocp_user', $username, false, ['reason' => 'Account disabled']);
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
        logAuditEvent('LOGIN', 'ocp_user', $username, false, ['reason' => 'Invalid password']);
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
    logAuditEvent('LOGIN', 'ocp_user', $user['username'], true);

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
    if (isSessionInvalidated()) {
        logout();
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

/**
 * Read the trunk credential encryption key from container secrets.
 */
function getTrunkCredKey(): string {
    $key = '';
    $secretPath = '/tmp/trunk_cred_key';
    if (!file_exists($secretPath)) {
        $secretPath = '/run/secrets/trunk_cred_key';
    }
    if (file_exists($secretPath) && is_readable($secretPath)) {
        $key = rtrim(file_get_contents($secretPath), "\r\n");
    }
    if ($key === '') {
        $key = getenv('TRUNK_CRED_KEY') ?: '';
    }
    return $key;
}

function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

/**
 * Record an active session in the database.
 */
function recordSession(string $userId): void {
    try {
        $pdo = getDb();
        $token = session_id();
        if ($token === '') return;
        $pdo->prepare(
            "INSERT INTO ocp_user_sessions (user_id, session_token, ip_address, user_agent, created_at, last_activity)
             VALUES (:uid, :tok, :ip, :ua, NOW(), NOW())
             ON CONFLICT (session_token) DO UPDATE SET
                 last_activity = NOW(),
                 invalidated_at = NULL"
        )->execute([
            ':uid' => $userId,
            ':tok' => $token,
            ':ip'  => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            ':ua'  => $_SERVER['HTTP_USER_AGENT'] ?? '',
        ]);
    } catch (Exception $e) {
        error_log('Failed to record session: ' . $e->getMessage());
    }
}

/**
 * Check if current session has been invalidated.
 */
function isSessionInvalidated(): bool {
    try {
        $token = session_id();
        if ($token === '') return false;
        $pdo = getDb();
        $stmt = $pdo->prepare(
            "SELECT invalidated_at FROM ocp_user_sessions WHERE session_token = :tok"
        );
        $stmt->execute([':tok' => $token]);
        $row = $stmt->fetch();
        return $row && $row['invalidated_at'] !== null;
    } catch (Exception $e) {
        error_log('Failed to check session invalidation: ' . $e->getMessage());
        return false;
    }
}

/**
 * Logout and invalidate session.
 */
function logout(): void {
    try {
        $token = session_id();
        if ($token !== '') {
            $pdo = getDb();
            $pdo->prepare(
                "UPDATE ocp_user_sessions SET invalidated_at = NOW() WHERE session_token = :tok"
            )->execute([':tok' => $token]);
        }
    } catch (Exception $e) {
        error_log('Failed to invalidate session: ' . $e->getMessage());
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Strict',
        ]);
    }
    session_destroy();
}
