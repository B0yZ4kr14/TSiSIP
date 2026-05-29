<?php
/**
 * TSiSIP Control Panel — User Preferences Helper
 */

function getUserPreference(string $key, $default = null) {
    $pdo = getDb();
    $stmt = $pdo->prepare(
        "SELECT preference_value FROM ocp_user_preferences
         WHERE user_id = :uid AND preference_key = :key"
    );
    $stmt->execute([':uid' => $_SESSION['ocp_user_id'] ?? 0, ':key' => $key]);
    $row = $stmt->fetch();
    if ($row) {
        $val = json_decode($row['preference_value'], true);
        return $val !== null ? $val : $row['preference_value'];
    }
    return $default;
}

function setUserPreference(string $key, $value): void {
    $pdo = getDb();
    $stmt = $pdo->prepare(
        "INSERT INTO ocp_user_preferences (user_id, preference_key, preference_value, updated_at)
         VALUES (:uid, :key, :val, NOW())
         ON CONFLICT (user_id, preference_key) DO UPDATE SET
             preference_value = EXCLUDED.preference_value,
             updated_at = NOW()"
    );
    $stmt->execute([
        ':uid' => $_SESSION['ocp_user_id'] ?? 0,
        ':key' => $key,
        ':val' => is_string($value) ? $value : json_encode($value),
    ]);
}
