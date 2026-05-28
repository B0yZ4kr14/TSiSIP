<?php
/**
 * dispatcher-export.php — Export dispatcher table as CSV
 * Feature 035
 */

require_once __DIR__ . '/../../common/config.php';

requireAuth();
checkPasswordChange();

$pdo = getDb();
$stmt = $pdo->query("SELECT setid, destination, state, probe_mode, weight, priority, attrs, description FROM dispatcher ORDER BY setid, priority DESC, id");

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="dispatcher-export-' . date('Y-m-d') . '.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['setid', 'destination', 'state', 'probe_mode', 'weight', 'priority', 'attrs', 'description']);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($out, $row);
}
fclose($out);
