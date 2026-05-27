<?php
/**
 * GET /api/v1/metrics — Current MI statistics
 */

require_once __DIR__ . '/../../common/mi-http.php';

$metrics = [];

$stats = miHttpCall('get_statistics', ['all']);
if (!isset($stats['error'])) {
    foreach ($stats as $s) {
        if (isset($s['module']) && isset($s['name']) && isset($s['value'])) {
            $metrics[$s['module'] . '_' . $s['name']] = $s['value'];
        }
    }
}

$dialogs = miHttpCall('dlg_list', []);
$metrics['active_dialogs'] = is_array($dialogs) ? count($dialogs) : 0;

$gateways = miHttpCall('ds_list', []);
$metrics['gateways_total'] = is_array($gateways) ? count($gateways) : 0;

$rtpengine = miHttpCall('rtpengine_show', []);
$metrics['rtpengine_sessions'] = is_array($rtpengine) ? count($rtpengine) : 0;

$metrics['timestamp'] = date('c');

echo json_encode(['metrics' => $metrics]);
