<?php
/**
 * TSiSIP Admin API — Configuration
 */

// Database connection via Docker secrets
define('DB_HOST', getenv('DB_HOST') ?: 'postgres');
define('DB_NAME', getenv('DB_NAME') ?: 'opensips');
define('DB_USER', getenv('DB_USER') ?: 'opensips');

function getDbPassword(): string {
    $secretPath = '/run/secrets/db_password';
    if (file_exists($secretPath)) {
        return trim(file_get_contents($secretPath));
    }
    return getenv('DB_PASSWORD') ?: '';
}

function getServiceSecret(): string {
    $secretPath = '/run/secrets/proxy_api_secret';
    if (file_exists($secretPath)) {
        return trim(file_get_contents($secretPath));
    }
    return getenv('PROXY_API_SECRET') ?: '';
}

function validateServiceSecret(): void {
    $header = $_SERVER['HTTP_X_PROXY_SECRET'] ?? '';
    $expected = getServiceSecret();
    if ($expected === '' || $header !== $expected) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Forbidden: invalid service secret']);
        exit;
    }
}

function getPdo(): PDO {
    $dsn = 'pgsql:host=' . DB_HOST . ';dbname=' . DB_NAME;
    $pdo = new PDO($dsn, DB_USER, getDbPassword(), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    return $pdo;
}
