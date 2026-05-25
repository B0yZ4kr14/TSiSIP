<?php
/**
 * TSiSIP OCP — Subscriber Proxy Client
 *
 * Delegates subscriber CREATE/UPDATE/DELETE to the Admin API proxy layer.
 * Communicates over the internal Docker network.
 */

/**
 * Call the subscriber proxy (Admin API) to perform a mutation.
 *
 * @param string $action   One of: create, update, delete
 * @param array  $params   Action-specific parameters
 * @return array           ['success' => bool, 'error' => ?string]
 */
function callSubscriberProxy(string $action, array $params): array {
    $proxyUrl = getenv('ADMIN_API_URL') ?: 'http://admin_api:8080/index.php';
    $secretPath = '/run/secrets/proxy_api_secret';
    $proxySecret = file_exists($secretPath) ? trim(file_get_contents($secretPath)) : (getenv('PROXY_API_SECRET') ?: '');

    $payload = [
        'action' => $action,
        'data'   => $params,
    ];

    $ch = curl_init($proxyUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'X-Proxy-Secret: ' . $proxySecret,
        ],
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError !== '') {
        return [
            'success' => false,
            'error'   => _('Subscriber service temporarily unavailable. Please try again later.'),
        ];
    }

    if ($httpCode === 429) {
        return [
            'success' => false,
            'error'   => _('Too many subscriber changes. Please wait a moment and try again.'),
        ];
    }

    if ($httpCode === 403) {
        return [
            'success' => false,
            'error'   => _('Access denied to subscriber service.'),
        ];
    }

    if ($httpCode !== 200) {
        return [
            'success' => false,
            'error'   => _('Subscriber operation failed. Please contact support.'),
        ];
    }

    $decoded = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            'success' => false,
            'error'   => _('Invalid response from subscriber service.'),
        ];
    }

    if (isset($decoded['success']) && $decoded['success'] === true) {
        return ['success' => true, 'error' => null];
    }

    return [
        'success' => false,
        'error'   => $decoded['error'] ?? $decoded['errors'][0] ?? _('Subscriber operation failed.'),
    ];
}
