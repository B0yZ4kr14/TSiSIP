<?php
/**
 * TSiSIP — OpenSIPS MI HTTP Helper
 *
 * Provides a reusable wrapper for JSON-RPC calls to the OpenSIPS mi_http module.
 * Used by real-time status modules (Clusterer, SIPtrace, Status Report, Sockets).
 */

/**
 * Call an OpenSIPS MI command via the HTTP JSON-RPC interface.
 *
 * @param string $method  MI command name (e.g. 'clusterer_list', 'sip_trace_status')
 * @param array  $params  Optional positional parameters
 * @return array  ['success' => bool, 'data' => mixed, 'error' => string|null]
 */
function miHttpCall(string $method, array $params = []): array
{
    $miUrl = getenv('OPENSIPS_MI_URL') ?: 'http://opensips:8888/mi';

    $payload = [
        'jsonrpc' => '2.0',
        'method'  => $method,
        'params'  => $params,
        'id'      => 1,
    ];

    $opts = [
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\n",
            'content' => json_encode($payload),
            'timeout' => 10,
        ],
    ];

    $context = stream_context_create($opts);
    $result  = @file_get_contents($miUrl, false, $context);

    if ($result === false) {
        return [
            'success' => false,
            'data'    => null,
            'error'   => _('Failed to connect to OpenSIPS MI endpoint at ') . $miUrl,
        ];
    }

    $decoded = json_decode($result, true);
    if (!is_array($decoded)) {
        return [
            'success' => false,
            'data'    => null,
            'error'   => _('Invalid JSON response from OpenSIPS MI.'),
        ];
    }

    if (isset($decoded['error'])) {
        $errMsg = is_array($decoded['error']) ? ($decoded['error']['message'] ?? json_encode($decoded['error'])) : $decoded['error'];
        return [
            'success' => false,
            'data'    => null,
            'error'   => $errMsg,
        ];
    }

    return [
        'success' => true,
        'data'    => $decoded['result'] ?? $decoded,
        'error'   => null,
    ];
}

/**
 * Check whether the OpenSIPS MI HTTP endpoint is reachable.
 *
 * @return bool
 */
function miHttpAvailable(): bool
{
    $miUrl = getenv('OPENSIPS_MI_URL') ?: 'http://opensips:8888/mi';
    $result = @file_get_contents($miUrl, false, stream_context_create([
        'http' => ['method' => 'GET', 'timeout' => 2],
    ]));
    return $result !== false;
}
