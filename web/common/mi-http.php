<?php
/**
 * TSiSIP — OpenSIPS MI HTTP Helper
 *
 * Provides a reusable wrapper for JSON-RPC calls to the OpenSIPS mi_http module.
 * Used by real-time status modules (Clusterer, SIPtrace, Status Report, Sockets).
 *
 * Features:
 * - In-memory response cache (5s TTL) to avoid duplicate MI calls per request.
 * - Circuit breaker: after 3 consecutive failures within 30s, blocks calls for 60s.
 * - Configurable timeouts via environment variable.
 */

/** @var array<string, array{data: mixed, expires: int}> */
static $miCache = [];

/** Circuit breaker state */
static $cbFailures = 0;
static $cbLastFailure = 0;
static $cbOpenUntil = 0;

const CB_FAILURE_THRESHOLD = 3;
const CB_WINDOW_SECONDS = 30;
const CB_OPEN_SECONDS = 60;
const CACHE_TTL_SECONDS = 5;
const CACHE_MAX_ENTRIES = 100;

/**
 * Check whether the OpenSIPS MI HTTP endpoint is reachable.
 *
 * @return bool
 */
function miHttpAvailable(): bool
{
    $miUrl = getenv('OPENSIPS_MI_URL') ?: 'http://opensips:8888/mi';
    $payload = json_encode([
        'jsonrpc' => '2.0',
        'method'  => 'version',
        'params'  => [],
        'id'      => 1,
    ]);
    $result = @file_get_contents($miUrl, false, stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\n",
            'content' => $payload,
            'timeout' => 2,
        ],
    ]));
    return $result !== false;
}

/**
 * Call an OpenSIPS MI command via the HTTP JSON-RPC interface.
 *
 * @param string $method  MI command name (e.g. 'clusterer_list', 'sip_trace_status')
 * @param array  $params  Optional positional parameters
 * @return array  ['success' => bool, 'data' => mixed, 'error' => string|null]
 */
function miHttpCall(string $method, array $params = []): array
{
    global $miCache, $cbFailures, $cbLastFailure, $cbOpenUntil;

    $now = time();

    // Circuit breaker check
    if ($cbOpenUntil > $now) {
        return [
            'success' => false,
            'data'    => null,
            'error'   => _('OpenSIPS MI endpoint temporarily unavailable (circuit breaker open).'),
        ];
    }

    // Reset failure window if outside window
    if ($cbLastFailure > 0 && ($now - $cbLastFailure) > CB_WINDOW_SECONDS) {
        $cbFailures = 0;
    }

    // Cache key
    $cacheKey = $method . ':' . md5(serialize($params));
    if (isset($miCache[$cacheKey]) && $miCache[$cacheKey]['expires'] > $now) {
        return [
            'success' => true,
            'data'    => $miCache[$cacheKey]['data'],
            'error'   => null,
        ];
    }

    $miUrl = getenv('OPENSIPS_MI_URL') ?: 'http://opensips:8888/mi';
    $timeout = (int) (getenv('OPENSIPS_MI_TIMEOUT') ?: 10);

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
            'timeout' => $timeout,
        ],
    ];

    $context = stream_context_create($opts);
    $result  = @file_get_contents($miUrl, false, $context);

    if ($result === false) {
        $cbFailures++;
        $cbLastFailure = $now;
        if ($cbFailures >= CB_FAILURE_THRESHOLD) {
            $cbOpenUntil = $now + CB_OPEN_SECONDS;
        }
        return [
            'success' => false,
            'data'    => null,
            'error'   => _('Failed to connect to OpenSIPS MI endpoint at ') . $miUrl,
        ];
    }

    $decoded = json_decode($result, true);
    if (!is_array($decoded)) {
        $cbFailures++;
        $cbLastFailure = $now;
        if ($cbFailures >= CB_FAILURE_THRESHOLD) {
            $cbOpenUntil = $now + CB_OPEN_SECONDS;
        }
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

    // Success — reset circuit breaker and cache result
    $cbFailures = 0;
    $cbLastFailure = 0;
    $cbOpenUntil = 0;

    $data = $decoded['result'] ?? $decoded;
    // Evict oldest entries if cache exceeds max size (LRU-ish: clear half on overflow)
    if (count($miCache) >= CACHE_MAX_ENTRIES) {
        $miCache = array_slice($miCache, (int)(CACHE_MAX_ENTRIES / 2), null, true);
    }
    $miCache[$cacheKey] = [
        'data'    => $data,
        'expires' => $now + CACHE_TTL_SECONDS,
    ];

    return [
        'success' => true,
        'data'    => $data,
        'error'   => null,
    ];
}
