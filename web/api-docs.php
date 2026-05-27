<?php
/**
 * TSiSIP Control Panel — API Documentation
 * Documents available endpoints for integrations.
 */
require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';

requireAuth();
checkPasswordChange();
requireRole('devops');

logAuditEvent('CONFIG_VIEW', 'system', 'api-docs', true);

$endpoints = [
    [
        'method' => 'GET',
        'path' => '/common/mi-http.php',
        'description' => 'OpenSIPS MI HTTP JSON-RPC wrapper',
        'auth' => 'Session',
        'params' => 'method, params (JSON)',
        'example' => '{"method":"ds_list","params":[]}',
    ],
    [
        'method' => 'GET',
        'path' => '/common/export-csv.php',
        'description' => 'Export audit log as CSV',
        'auth' => 'Session',
        'params' => 'table, format=csv',
        'example' => '?table=ocp_audit_log&format=csv',
    ],
    [
        'method' => 'GET',
        'path' => '/common/export-json.php',
        'description' => 'Export audit log as JSON',
        'auth' => 'Session',
        'params' => 'table, format=json',
        'example' => '?table=ocp_audit_log&format=json',
    ],
    [
        'method' => 'GET',
        'path' => '/common/sse-stream.php',
        'description' => 'Server-Sent Events for real-time updates',
        'auth' => 'CSRF Token',
        'params' => 'token',
        'example' => '?token=<csrf>',
    ],
    [
        'method' => 'POST',
        'path' => '/common/bookmark-toggle.php',
        'description' => 'Toggle bookmark for current page',
        'auth' => 'Session',
        'params' => 'url, label, icon',
        'example' => '{"url":"gateway-health.php","label":"Gateway Health"}',
    ],
    [
        'method' => 'POST',
        'path' => '/common/save-dashboard.php',
        'description' => 'Save dashboard widget preferences',
        'auth' => 'Session',
        'params' => 'widgets (JSON)',
        'example' => '{"widgets":{"status":true,"management":false}}',
    ],
    [
        'method' => 'GET',
        'path' => '/api/health',
        'description' => 'Health check endpoint (future)',
        'auth' => 'None',
        'params' => 'None',
        'example' => 'Returns {status: "ok"}',
    ],
];

require_once __DIR__ . '/common/header.php';
?>
<div id="content" class="tsisip-dashboard">
    <h1><?php echo _('API Documentation'); ?></h1>

    <div class="tsisip-dashboard-section">
        <p><?php echo _('The following endpoints are available for integration and automation.'); ?></p>
    </div>

    <?php foreach ($endpoints as $ep): ?>
        <div class="tsisip-dashboard-card" style="margin-bottom:1rem;">
            <div class="tsisip-card-header">
                <h3>
                    <span class="tsisip-badge tsisip-badge--info"><?php echo $ep['method']; ?></span>
                    <code><?php echo htmlspecialchars($ep['path']); ?></code>
                </h3>
            </div>
            <div class="tsisip-card-body">
                <p><?php echo htmlspecialchars($ep['description']); ?></p>
                <div class="tsisip-data-row">
                    <span class="tsisip-data-label"><?php echo _('Authentication'); ?></span>
                    <span class="tsisip-data-value"><?php echo htmlspecialchars($ep['auth']); ?></span>
                </div>
                <div class="tsisip-data-row">
                    <span class="tsisip-data-label"><?php echo _('Parameters'); ?></span>
                    <span class="tsisip-data-value"><?php echo htmlspecialchars($ep['params']); ?></span>
                </div>
                <div class="tsisip-data-row">
                    <span class="tsisip-data-label"><?php echo _('Example'); ?></span>
                    <span class="tsisip-data-value"><code><?php echo htmlspecialchars($ep['example']); ?></code></span>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php require_once __DIR__ . '/common/footer.php'; ?>
