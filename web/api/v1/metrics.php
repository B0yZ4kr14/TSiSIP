<?php
/**
 * GET /api/v1/metrics
 * Returns current OpenSIPS metrics.
 */

require_once __DIR__ . '/../../common/mi-http.php';

$metrics = [
    'timestamp' => date('c'),
    'data' => [],
];

try {
    if (function_exists('miHttpCall')) {
        $stats = miHttpCall('get_statistics', ['all']);
        if (is_array($stats)) {
            $metrics['data'] = $stats;
        }
    }
} catch (Exception $e) {
    $metrics['error'] = $e->getMessage();
}

http_response_code(200);
echo json_encode($metrics, JSON_PRETTY_PRINT);
