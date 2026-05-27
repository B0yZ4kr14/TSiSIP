<?php
/**
 * TSiSIP REST API Router
 * Feature 031
 */

require_once __DIR__ . '/common/auth.php';
require_once __DIR__ . '/common/rate-limit.php';

header('Content-Type: application/json');

// Parse request
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rtrim($uri, '/');

// Normalize URI: strip any /TSiSIP prefix and /api prefix so routes work
// both behind nginx reverse-proxy (/TSiSIP/api/...) and direct (/api/...)
$uri = preg_replace('#^/TSiSIP/api#', '', $uri);
$uri = preg_replace('#^/api#', '', $uri);
$uri = rtrim($uri, '/');
if ($uri === '') {
    $uri = '/';
}

// Route table
$routes = [
    'GET /v1/status' => 'handlers/status.php',
    'GET /v1/metrics' => 'handlers/metrics.php',
    'GET /v1/users' => 'handlers/users.php',
    'POST /v1/users' => 'handlers/users.php',
    'PATCH /v1/users' => 'handlers/users.php',
    'DELETE /v1/users' => 'handlers/users.php',
    'GET /v1/audit' => 'handlers/audit.php',
];

$routeKey = $method . ' ' . $uri;

// Handle PATCH/DELETE with ID pattern
if (preg_match('#^(PATCH|DELETE) /v1/users/([^/]+)$#', $method . ' ' . $uri, $matches)) {
    $_REQUEST['user_id'] = $matches[2];
    $routeKey = $matches[1] . ' /v1/users';
}

if (!isset($routes[$routeKey])) {
    http_response_code(404);
    echo json_encode(['error' => 'Endpoint not found']);
    exit;
}

$auth = requireApiAuth();
enforceRateLimit($auth['key_id']);

// Load handler
$handlerFile = __DIR__ . '/' . $routes[$routeKey];
if (!file_exists($handlerFile)) {
    http_response_code(501);
    echo json_encode(['error' => 'Handler not implemented']);
    exit;
}

require_once $handlerFile;
