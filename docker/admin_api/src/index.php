<?php
/**
 * TSiSIP Admin API — Router
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/subscriber-api.php';

// Rate limiting: simple file-based counter
function checkRateLimit(string $action): bool {
    $limit = match($action) {
        'create' => (int)(getenv('SUBSCRIBER_CREATE_RATE_LIMIT') ?: 10),
        'update' => (int)(getenv('SUBSCRIBER_UPDATE_RATE_LIMIT') ?: 30),
        'delete' => (int)(getenv('SUBSCRIBER_DELETE_RATE_LIMIT') ?: 10),
        default => 10,
    };

    $key = 'rate_' . $action . '_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $file = sys_get_temp_dir() . '/' . md5($key) . '.rate';
    $now = time();
    $window = 60;

    $data = ['count' => 0, 'reset' => $now + $window];
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true) ?: $data;
        if ($data['reset'] < $now) {
            $data = ['count' => 0, 'reset' => $now + $window];
        }
    }

    $data['count']++;
    file_put_contents($file, json_encode($data));

    return $data['count'] <= $limit;
}

// Validate service secret first
validateServiceSecret();

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(['error' => 'Method not allowed'], 405);
}

// Parse JSON body
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    sendJson(['error' => 'Invalid JSON body'], 400);
}

$action = $input['action'] ?? '';
if (!in_array($action, ['create', 'update', 'delete'], true)) {
    sendJson(['error' => 'Invalid action. Use create, update, or delete.'], 400);
}

// Rate limiting
if (!checkRateLimit($action)) {
    sendJson(['error' => 'Rate limit exceeded. Try again later.'], 429);
}

// Route to handler
match ($action) {
    'create' => handleSubscriberCreate($input['data'] ?? []),
    'update' => handleSubscriberUpdate($input['data'] ?? []),
    'delete' => handleSubscriberDelete($input['data'] ?? []),
};
