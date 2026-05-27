<?php
/**
 * TSiSIP Control Panel — Toggle Bookmark
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/csrf.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$url = $input['url'] ?? '';
$label = $input['label'] ?? '';
$icon = $input['icon'] ?? 'star';

if (!$url || !$label) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing url or label']);
    exit;
}

$pdo = getDb();
$userId = $_SESSION['user_id'] ?? 0;

// Check if already bookmarked
$check = $pdo->prepare("SELECT id FROM ocp_user_bookmarks WHERE user_id = :uid AND page_url = :url");
$check->execute([':uid' => $userId, ':url' => $url]);

if ($check->fetch()) {
    // Remove
    $del = $pdo->prepare("DELETE FROM ocp_user_bookmarks WHERE user_id = :uid AND page_url = :url");
    $del->execute([':uid' => $userId, ':url' => $url]);
    echo json_encode(['bookmarked' => false]);
} else {
    // Add
    $max = $pdo->prepare("SELECT COALESCE(MAX(sort_order), 0) + 1 AS next FROM ocp_user_bookmarks WHERE user_id = :uid");
    $max->execute([':uid' => $userId]);
    $sortOrder = $max->fetch()['next'] ?? 1;

    $ins = $pdo->prepare(
        "INSERT INTO ocp_user_bookmarks (user_id, page_url, page_label, icon, sort_order)
         VALUES (:uid, :url, :label, :icon, :sort)"
    );
    $ins->execute([
        ':uid' => $userId, ':url' => $url, ':label' => $label,
        ':icon' => $icon, ':sort' => $sortOrder,
    ]);
    echo json_encode(['bookmarked' => true]);
}
