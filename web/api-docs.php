<?php
/**
 * TSiSIP Control Panel — API Documentation
 * Feature 031
 */

require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';

requireAuth();
checkPasswordChange();

require_once __DIR__ . '/common/header.php';
?>
<div id="content">
    <h1><?php echo _('TSiSIP REST API Documentation'); ?></h1>

    <div class="tsisip-dashboard-section">
        <h2><?php echo _('Authentication'); ?></h2>
        <p><?php echo _('All API requests require a Bearer token in the Authorization header:'); ?></p>
        <pre><code>Authorization: Bearer &lt;your-api-key&gt;</code></pre>
        <p><?php echo _('API keys can be generated and managed from the'); ?> <a href="api-keys.php"><?php echo _('API Key Management'); ?></a> <?php echo _('page'); ?>.</p>
    </div>

    <div class="tsisip-dashboard-section">
        <h2><?php echo _('Rate Limiting'); ?></h2>
        <p><?php echo _('Requests are limited to 100 per minute per API key. Exceeding this limit returns HTTP 429.'); ?></p>
    </div>

    <div class="tsisip-dashboard-section">
        <h2><?php echo _('Endpoints'); ?></h2>

        <h3>GET /api/v1/status</h3>
        <p><?php echo _('Returns health status of all TSiSIP services.'); ?></p>
        <pre><code>{
  "status": {
    "opensips": "healthy",
    "rtpengine": "healthy",
    "postgres": "healthy",
    "pgbouncer": "healthy",
    "ocp": "healthy",
    "timestamp": "2026-05-27T12:00:00+00:00"
  }
}</code></pre>

        <h3>GET /api/v1/metrics</h3>
        <p><?php echo _('Returns current MI statistics and active counts.'); ?></p>
        <pre><code>{
  "metrics": {
    "active_dialogs": 42,
    "gateways_total": 3,
    "rtpengine_sessions": 15,
    "timestamp": "2026-05-27T12:00:00+00:00"
  }
}</code></pre>

        <h3>GET /api/v1/users</h3>
        <p><?php echo _('Returns a list of all active users.'); ?></p>
        <pre><code>{
  "users": [
    {
      "id": "...",
      "username": "admin",
      "email": "admin@tsiapp.io",
      "role": "admin",
      "enabled": true,
      "is_active": true
    }
  ]
}</code></pre>

        <h3>POST /api/v1/users</h3>
        <p><span class="tsisip-badge tsisip-badge-warning">read-write</span> <?php echo _('Creates a new user.'); ?></p>
        <pre><code>{
  "username": "newuser",
  "email": "user@example.com",
  "role": "readonly",
  "password": "SecurePass123"
}</code></pre>

        <h3>PATCH /api/v1/users/:id</h3>
        <p><span class="tsisip-badge tsisip-badge-warning">read-write</span> <?php echo _('Updates an existing user.'); ?></p>
        <pre><code>{
  "email": "new@example.com",
  "role": "devops",
  "is_active": true
}</code></pre>

        <h3>DELETE /api/v1/users/:id</h3>
        <p><span class="tsisip-badge tsisip-badge-warning">read-write</span> <?php echo _('Soft-deletes a user.'); ?></p>

        <h3>GET /api/v1/audit</h3>
        <p><?php echo _('Query the audit log with optional filters.'); ?></p>
        <pre><code>GET /api/v1/audit?action=LOGIN&user=admin&limit=50</code></pre>
    </div>

    <div class="tsisip-dashboard-section">
        <h2><?php echo _('Error Responses'); ?></h2>
        <table class="tsisip-table">
            <thead>
                <tr><th><?php echo _('Status'); ?></th><th><?php echo _('Meaning'); ?></th></tr>
            </thead>
            <tbody>
                <tr><td>400</td><td>Bad Request — invalid input</td></tr>
                <tr><td>401</td><td>Unauthorized — missing or invalid API key</td></tr>
                <tr><td>403</td><td>Forbidden — read-only key on write endpoint</td></tr>
                <tr><td>404</td><td>Not Found — endpoint does not exist</td></tr>
                <tr><td>429</td><td>Too Many Requests — rate limit exceeded</td></tr>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/common/footer.php'; ?>
