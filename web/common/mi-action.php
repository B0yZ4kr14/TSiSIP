<?php
/**
 * TSiSIP — Generic MI Action Handler
 *
 * Provides a secure AJAX endpoint for OpenSIPS MI mutation commands.
 * Validates CSRF, role, and command whitelist before executing.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/mi-http.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => _('Method not allowed')]);
    exit;
}

requireAuth();

$mutationCommands = [
    'address_reload',
    'dialplan_reload',
    'domain_reload',
    'dr_reload',
    'dlg_end_dlg',
    'sip_trace_start',
    'sip_trace_stop',
    'lb_status',
    'lb_reload',
    'lb_resize',
    'clusterer_set_state',
    'clusterer_ping',
    'rtpengine_enable',
    'rtpengine_disable',
    'rtpengine_reload',
    'uac_reg_refresh',
    'uac_reg_enable',
    'uac_reg_disable',
    'reset_statistics',
    'cc_agent_login',
    'cc_agent_logout',
    'cc_flow_status',
    'pike_check_ip',
    'ratelimit_reset',
    'htable_flush',
    'pres_refresh_watchers',
    'pua_refresh',
];

$readOnlyCommands = [
    'dr_gw_status',
    'dr_carrier_status',
    'sip_trace_status',
    'dlg_list',
    'dlg_profile_get_size',
    'dlg_profile_list',
    'dlg_match_info',
    'ds_list',
    'ds_get_active',
    'ds_ping_active',
    'rtpengine_show',
    'rtpengine_list',
    'uac_reg_list',
    'cc_list_agents',
    'cc_list_calls',
    'clusterer_list',
    'get_statistics',
    'list_sockets',
    'tls_list',
    'status_report',
    'pike_list',
    'ratelimit_status',
    'htable_dump',
    'nh_show_sockets',
    'nh_show_ping',
    'tcp_list',
    'list_blacklists',
    'list_timers',
    'ps',
    'ul_dump',
    'version',
    'which',
];

$whitelist = array_merge($mutationCommands, $readOnlyCommands);

$cmd = $_POST['cmd'] ?? '';
$paramsRaw = $_POST['params'] ?? '[]';
$csrfToken = $_POST['csrf_token'] ?? '';

if (!validateCsrfToken($csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => _('Invalid CSRF token')]);
    exit;
}

if (!in_array($cmd, $whitelist, true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => _('Command not whitelisted')]);
    exit;
}

if (in_array($cmd, $mutationCommands, true)) {
    requireRole('devops');
}

$params = json_decode($paramsRaw, true);
if (!is_array($params)) {
    $params = [];
}

$result = miHttpCall($cmd, $params);

$success = $result['success'];
$auditDetails = [
    'command' => $cmd,
    'params'  => $params,
    'error'   => $result['error'] ?? null,
];
logAuditEvent('MI_COMMAND', 'opensips', $cmd, $success, $auditDetails);

http_response_code($success ? 200 : 502);
echo json_encode($result);
