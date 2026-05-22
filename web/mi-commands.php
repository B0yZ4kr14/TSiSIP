<?php
/**
 * TSiSIP Control Panel — OpenSIPS MI Command Runner
 *
 * Execute whitelisted MI commands via the OpenSIPS mi_http module.
 */

require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';

requireAuth();
checkPasswordChange();
requireRole('devops');

$miWhitelist = [
    'ds_reload'       => ['role' => 'devops', 'label' => _('Reload Dispatcher Sets')],
    'domain_reload'   => ['role' => 'devops', 'label' => _('Reload Domains')],
    'get_statistics'  => ['role' => 'devops', 'label' => _('Get Statistics')],
    'dlg_list'        => ['role' => 'devops', 'label' => _('List Active Dialogs')],
    'dlg_end_dlg'     => ['role' => 'admin',  'label' => _('Terminate Dialog')],
    'tls_reload'      => ['role' => 'admin',  'label' => _('Reload TLS Certificates')],
];

$roleHierarchy = [
    'readonly'  => 0,
    'user'      => 1,
    'assistant' => 2,
    'dentist'   => 3,
    'devops'    => 4,
    'admin'     => 5,
];

$userRole = $_SESSION['ocp_user_role'] ?? 'readonly';
$error = '';
$success = '';
$responseJson = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        $error = _('Invalid CSRF token.');
        logAuditEvent('MI_COMMAND', 'opensips', 'CSRF_FAILURE', false, ['reason' => 'Invalid CSRF token']);
    } else {
        $command = $_POST['command'] ?? '';

        if (!isset($miWhitelist[$command])) {
            http_response_code(403);
            $error = _('Command not in whitelist.');
            logAuditEvent('MI_COMMAND', 'opensips', $command, false, ['reason' => 'Not in whitelist']);
        } else {
            $requiredRole = $miWhitelist[$command]['role'];
            $requiredLevel = $roleHierarchy[$requiredRole] ?? 0;
            $userLevel = $roleHierarchy[$userRole] ?? 0;

            if ($userLevel < $requiredLevel) {
                http_response_code(403);
                $error = _('Insufficient role for this command.');
                logAuditEvent('MI_COMMAND', 'opensips', $command, false, [
                    'reason'    => 'Insufficient role',
                    'required'  => $requiredRole,
                    'user_role' => $userRole,
                ]);
            } else {
                $params = [];

                if ($command === 'dlg_end_dlg') {
                    $hashEntry = trim($_POST['hash_entry'] ?? '');
                    $hashId = trim($_POST['hash_id'] ?? '');
                    if ($hashEntry === '' || $hashId === '') {
                        $error = _('Hash entry and hash ID are required for dialog termination.');
                    } else {
                        $params = [$hashEntry, $hashId];
                    }
                } else {
                    $rawParams = trim($_POST['params'] ?? '');
                    if ($rawParams !== '') {
                        $decoded = json_decode($rawParams, true);
                        if (is_array($decoded)) {
                            $params = $decoded;
                        } else {
                            $params = array_map('trim', explode(',', $rawParams));
                        }
                    }
                }

                if ($error === '') {
                    $payload = [
                        'jsonrpc' => '2.0',
                        'method'  => $command,
                        'params'  => $params,
                        'id'      => 1,
                    ];

                    $jsonPayload = json_encode($payload);

                    $opts = [
                        'http' => [
                            'method'  => 'POST',
                            'header'  => "Content-Type: application/json\r\n",
                            'content' => $jsonPayload,
                            'timeout' => 10,
                        ],
                    ];

                    $context = stream_context_create($opts);
                    $miUrl = 'http://opensips:8888/mi';
                    $result = @file_get_contents($miUrl, false, $context);

                    if ($result === false) {
                        $error = _('Failed to connect to OpenSIPS MI endpoint.');
                        logAuditEvent('MI_COMMAND', 'opensips', $command, false, [
                            'params' => $params,
                            'reason' => 'Connection failed',
                        ]);
                    } else {
                        $responseJson = $result;
                        $success = _('Command executed successfully.');
                        logAuditEvent('MI_COMMAND', 'opensips', $command, true, [
                            'params'   => $params,
                            'response' => $result,
                        ]);
                    }
                }
            }
        }
    }
}

require_once __DIR__ . '/common/header.php';
?>
<div id="content">
    <h2><?php echo _('MI Command Runner'); ?></h2>

    <?php if ($error): ?>
        <div class="tsisip-badge tsisip-badge-error" role="alert"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="tsisip-badge tsisip-badge-success" role="status"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="tsisip-dashboard-section">
        <h3><?php echo _('Execute Command'); ?></h3>
        <form method="POST" action="" class="tsisip-form" id="mi-form">
            <?php echo csrfInput(); ?>

            <div class="tsisip-form-group">
                <label for="command"><?php echo _('Command'); ?></label>
                <select id="command" name="command" class="tsisip-input" required>
                    <option value=""><?php echo _('— Select —'); ?></option>
                    <?php foreach ($miWhitelist as $cmd => $meta): ?>
                        <?php
                        $requiredLevel = $roleHierarchy[$meta['role']] ?? 0;
                        $userLevel = $roleHierarchy[$userRole] ?? 0;
                        if ($userLevel >= $requiredLevel):
                        ?>
                            <option value="<?php echo htmlspecialchars($cmd); ?>">
                                <?php echo htmlspecialchars($meta['label'] . ' (' . $cmd . ')'); ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="dlg-fields" style="display:none;">
                <div class="tsisip-form-group">
                    <label for="hash_entry"><?php echo _('Hash Entry'); ?></label>
                    <input type="text" id="hash_entry" name="hash_entry" class="tsisip-input" placeholder="<?php echo _('e.g. 1234'); ?>">
                </div>
                <div class="tsisip-form-group">
                    <label for="hash_id"><?php echo _('Hash ID'); ?></label>
                    <input type="text" id="hash_id" name="hash_id" class="tsisip-input" placeholder="<?php echo _('e.g. 5678'); ?>">
                </div>
            </div>

            <div id="params-field" class="tsisip-form-group">
                <label for="params"><?php echo _('Parameters'); ?></label>
                <input type="text" id="params" name="params" class="tsisip-input" placeholder="<?php echo _('Optional JSON array or comma-separated values'); ?>">
            </div>

            <button type="submit" class="tsisip-btn tsisip-btn-primary"><?php echo _('Execute'); ?></button>
        </form>
    </div>

    <?php if ($responseJson !== ''): ?>
    <div class="tsisip-dashboard-section">
        <h3><?php echo _('Response'); ?></h3>
        <pre style="background:#0f172a;border:1px solid #1e293b;border-radius:6px;padding:1rem;overflow:auto;max-height:60vh;"><?php
            $decoded = json_decode($responseJson, true);
            if ($decoded !== null) {
                echo htmlspecialchars(json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            } else {
                echo htmlspecialchars($responseJson);
            }
        ?></pre>
    </div>
    <?php endif; ?>

    <div class="tsisip-dashboard-section">
        <h3><?php echo _('Available Commands'); ?></h3>
        <table class="dataTable tsisip-table">
            <thead>
                <tr>
                    <th><?php echo _('Command'); ?></th>
                    <th><?php echo _('Description'); ?></th>
                    <th><?php echo _('Required Role'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($miWhitelist as $cmd => $meta): ?>
                    <tr>
                        <td class="mono-cell"><?php echo htmlspecialchars($cmd); ?></td>
                        <td><?php echo htmlspecialchars($meta['label']); ?></td>
                        <td>
                            <span class="tsisip-badge tsisip-badge-<?php echo $meta['role'] === 'admin' ? 'error' : 'success'; ?>">
                                <?php echo htmlspecialchars(ucfirst($meta['role'])); ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.getElementById('command').addEventListener('change', function() {
    var dlgFields = document.getElementById('dlg-fields');
    var paramsField = document.getElementById('params-field');
    if (this.value === 'dlg_end_dlg') {
        dlgFields.style.display = 'block';
        paramsField.style.display = 'none';
    } else {
        dlgFields.style.display = 'none';
        paramsField.style.display = 'block';
    }
});
</script>

<?php require_once __DIR__ . '/common/footer.php'; ?>
