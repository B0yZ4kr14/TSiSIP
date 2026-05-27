<?php
/**
 * TSiSIP REST API — Rate Limiting
 * Feature 031
 * Simple per-API-key rate limit using APCu or file-based fallback.
 */

function checkRateLimit(string $keyId, int $maxRequests = 100, int $windowSeconds = 60): bool {
    $cacheKey = 'tsisip_api_rl_' . $keyId;

    // Try APCu first
    if (function_exists('apcu_inc')) {
        $current = apcu_fetch($cacheKey);
        if ($current === false) {
            apcu_store($cacheKey, 1, $windowSeconds);
            return true;
        }
        if ($current >= $maxRequests) {
            return false;
        }
        apcu_inc($cacheKey);
        return true;
    }

    // File-based fallback
    $tmpDir = sys_get_temp_dir() . '/tsisip_ratelimit';
    if (!is_dir($tmpDir)) {
        mkdir($tmpDir, 0750, true);
    }
    $file = $tmpDir . '/' . preg_replace('/[^a-zA-Z0-9_-]/', '', $cacheKey) . '.json';

    $now = time();
    $data = ['count' => 0, 'window_start' => $now];
    if (file_exists($file)) {
        $raw = @file_get_contents($file);
        if ($raw) {
            $decoded = json_decode($raw, true);
            if ($decoded && ($decoded['window_start'] ?? 0) > ($now - $windowSeconds)) {
                $data = $decoded;
            }
        }
    }

    if ($data['count'] >= $maxRequests) {
        return false;
    }

    $data['count']++;
    file_put_contents($file, json_encode($data), LOCK_EX);
    return true;
}

function enforceRateLimit(string $keyId): void {
    if (!checkRateLimit($keyId)) {
        http_response_code(429);
        header('Content-Type: application/json');
        header('Retry-After: 60');
        echo json_encode(['error' => 'Rate limit exceeded. Try again in 60 seconds.']);
        exit;
    }
}
