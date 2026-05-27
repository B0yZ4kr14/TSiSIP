<?php
/**
 * TSiSIP Password Policy Library
 * Feature 030: OCP User Management & RBAC
 */

/**
 * Validate password against complexity policy.
 * Returns ['valid' => bool, 'errors' => string[]]
 */
function validatePassword(string $password): array {
    $errors = [];

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter.';
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter.';
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one digit.';
    }

    return [
        'valid' => empty($errors),
        'errors' => $errors,
    ];
}

/**
 * Hash password with bcrypt (cost 12).
 */
function hashPassword(string $password): string {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Check if password was used in the last N changes.
 */
function isPasswordInHistory(PDO $pdo, int $userId, string $password, int $historyLimit = 5): bool {
    $stmt = $pdo->prepare(
        "SELECT password_hash FROM ocp_password_history
         WHERE user_id = :uid
         ORDER BY changed_at DESC
         LIMIT :limit"
    );
    $stmt->execute([':uid' => $userId, ':limit' => $historyLimit]);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (password_verify($password, $row['password_hash'])) {
            return true;
        }
    }
    return false;
}

/**
 * Record password change in history.
 */
function recordPasswordHistory(PDO $pdo, int $userId, string $hash): void {
    $stmt = $pdo->prepare(
        "INSERT INTO ocp_password_history (user_id, password_hash, changed_at)
         VALUES (:uid, :hash, NOW())"
    );
    $stmt->execute([':uid' => $userId, ':hash' => $hash]);
}

/**
 * Check if user's password has expired (default 90 days).
 */
function isPasswordExpired(PDO $pdo, int $userId, int $maxAgeDays = 90): bool {
    $stmt = $pdo->prepare(
        "SELECT password_changed_at FROM ocp_users WHERE id = :uid"
    );
    $stmt->execute([':uid' => $userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || empty($row['password_changed_at'])) {
        return false; // No record yet
    }

    $changed = new DateTime($row['password_changed_at']);
    $now = new DateTime();
    $interval = $now->diff($changed);
    return $interval->days >= $maxAgeDays;
}
