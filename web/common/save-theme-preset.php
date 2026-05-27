<?php
/**
 * TSiSIP Control Panel — Save Theme Preset
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/csrf.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$preset = $input['preset'] ?? 'default';

$allowed = ['default', 'ocean', 'forest', 'sunset'];
if (!in_array($preset, $allowed)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid preset']);
    exit;
}

$_SESSION['theme_preset'] = $preset;
echo json_encode(['success' => true]);
