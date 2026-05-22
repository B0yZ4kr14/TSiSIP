<?php
/**
 * TSiSIP Control Panel — TLS Certificate Management
 * View and reload OpenSIPS TLS certificates via MI HTTP.
 */

require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';

requireAuth();
checkPasswordChange();
requireRole('devops');

$miEndpoint = 'http://opensips:8888/mi';
$error = '';
$success = '';

/**
 * Call an OpenSIPS MI command via HTTP JSON-RPC.
 *
 * @param string $endpoint The MI HTTP endpoint.
 * @param string $method   The MI command name.
 * @return array|null      The result payload, or null on failure.
 */
function callMiCommand(string $endpoint, string $method): ?array {
    $payload = json_encode([
        'jsonrpc' => '2.0',
        'method'  => $method,
        'id'      => uniqid('tsisip_', true),
    ]);

    $context = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($payload),
            ],
            'content' => $payload,
            'timeout' => 10,
        ],
    ]);

    $response = @file_get_contents($endpoint, false, $context);
    if ($response === false) {
        return null;
    }

    $data = json_decode($response, true);
    if (!is_array($data)) {
        return null;
    }

    if (isset($data['error'])) {
        return null;
    }

    return $data['result'] ?? null;
}

// --- Handle reload action ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'reload') {
        if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
            $error = _('Invalid CSRF token.');
            logAuditEvent('TLS_RELOAD', 'opensips', 'tls', false, ['reason' => 'Invalid CSRF token']);
        } else {
            requireRole('admin');

            $result = callMiCommand($miEndpoint, 'tls_reload');
            if ($result !== null) {
                $success = _('TLS certificates reloaded successfully.');
                logAuditEvent('TLS_RELOAD', 'opensips', 'tls', true);
            } else {
                $error = _('Failed to reload TLS certificates.');
                logAuditEvent('TLS_RELOAD', 'opensips', 'tls', false, ['reason' => 'MI command failed']);
            }
        }
    }
}

// --- Fetch certificate list ---
$certList = [];
$miResult = callMiCommand($miEndpoint, 'tls_list');
if ($miResult !== null && isset($miResult['Domains']) && is_array($miResult['Domains'])) {
    $certList = $miResult['Domains'];
} elseif ($miResult !== null && is_array($miResult)) {
    // Fallback for alternative response shapes
    $certList = $miResult;
}

require_once __DIR__ . '/common/header.php';
?>
<div id="content">
    <h2><?php echo _('TLS Certificate Management'); ?></h2>

    <?php if ($error): ?>
        <div class="tsisip-badge tsisip-badge-error" role="alert"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="tsisip-badge tsisip-badge-success" role="status"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="tsisip-dashboard-section">
        <h3><?php echo _('Loaded Certificates'); ?></h3>

        <?php if (empty($certList)): ?>
            <p><?php echo _('No TLS certificates loaded or unable to reach OpenSIPS MI endpoint.'); ?></p>
        <?php else: ?>
            <table class="dataTable tsisip-table">
                <thead>
                    <tr>
                        <th><?php echo _('ID'); ?></th>
                        <th><?php echo _('Domain'); ?></th>
                        <th><?php echo _('Certificate File'); ?></th>
                        <th><?php echo _('Key File'); ?></th>
                        <th><?php echo _('CA File'); ?></th>
                        <th><?php echo _('Verify Cert'); ?></th>
                        <th><?php echo _('Verify Depth'); ?></th>
                        <th><?php echo _('CRL Check All'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($certList as $cert): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($cert['id'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($cert['domain'] ?? ''); ?></td>
                            <td class="mono-cell"><?php echo htmlspecialchars($cert['cert_file'] ?? ''); ?></td>
                            <td class="mono-cell"><?php echo htmlspecialchars($cert['key_file'] ?? ''); ?></td>
                            <td class="mono-cell"><?php echo htmlspecialchars($cert['ca_file'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($cert['verify_cert'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($cert['verify_depth'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($cert['crl_check_all'] ?? ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <?php if (isAdmin()): ?>
        <div class="tsisip-dashboard-section">
            <h3><?php echo _('Administrative Actions'); ?></h3>
            <form method="POST" action="" class="tsisip-form">
                <?php echo csrfInput(); ?>
                <input type="hidden" name="action" value="reload">
                <button type="submit" class="tsisip-btn tsisip-btn-primary" onclick="return confirm('<?php echo _('Reload all TLS certificates? This will apply any certificate file changes immediately.'); ?>')">
                    <?php echo _('Reload TLS Certificates'); ?>
                </button>
            </form>
        </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/common/footer.php'; ?>
