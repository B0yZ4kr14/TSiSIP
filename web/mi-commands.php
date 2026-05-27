<?php
/**
 * TSiSIP Control Panel — OpenSIPS MI Command Runner
 *
 * Execute whitelisted MI commands via the OpenSIPS mi_http module.
 */

require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';
require_once __DIR__ . '/common/mi-http.php';

requireAuth();
checkPasswordChange();
requireRole('devops');

$roleHierarchy = [
    'readonly'  => 0,
    'user'      => 1,
    'assistant' => 2,
    'dentist'   => 3,
    'devops'    => 4,
    'admin'     => 5,
];

$userRole = $_SESSION['ocp_user_role'] ?? 'readonly';
$userLevel = $roleHierarchy[$userRole] ?? 0;

$categories = [
    'provisioning' => _('Provisioning'),
    'dialog'       => _('Dialog'),
    'siptrace'     => _('SIP Trace'),
    'loadbalancer' => _('Load Balancer'),
    'clusterer'    => _('Clusterer'),
    'media'        => _('Media'),
    'uac'          => _('UAC Registrant'),
    'statistics'   => _('Statistics'),
    'callcenter'   => _('Call Center'),
    'security'     => _('Security'),
    'ratelimit'    => _('Rate Limit'),
    'hashtable'    => _('Hash Tables'),
    'nathelper'    => _('NAT Helper'),
    'tcp'          => _('TCP'),
    'blacklist'    => _('Blacklists'),
    'timer'        => _('Timers'),
    'process'      => _('Processes'),
    'usrloc'       => _('User Location'),
    'version'      => _('Version'),
    'presence'     => _('Presence'),
];

$miWhitelist = [
    // Provisioning
    'address_reload'     => ['role' => 'devops', 'label' => _('Reload Addresses'),       'category' => 'provisioning', 'params' => []],
    'dialplan_reload'    => ['role' => 'devops', 'label' => _('Reload Dialplan'),        'category' => 'provisioning', 'params' => []],
    'domain_reload'      => ['role' => 'devops', 'label' => _('Reload Domains'),         'category' => 'provisioning', 'params' => []],
    'dr_reload'          => ['role' => 'devops', 'label' => _('Reload Dynamic Routing'), 'category' => 'provisioning', 'params' => []],
    'dr_gw_status'       => ['role' => 'devops', 'label' => _('Gateway Status'),         'category' => 'provisioning', 'params' => [['name' => 'gw_id', 'type' => 'text', 'placeholder' => _('Gateway ID (optional)')]]],
    'dr_carrier_status'  => ['role' => 'devops', 'label' => _('Carrier Status'),         'category' => 'provisioning', 'params' => [['name' => 'carrier_id', 'type' => 'text', 'placeholder' => _('Carrier ID (optional)')]]],
    'ds_reload'          => ['role' => 'devops', 'label' => _('Reload Dispatcher Sets'), 'category' => 'provisioning', 'params' => []],
    'tls_reload'         => ['role' => 'admin',  'label' => _('Reload TLS Certificates'),'category' => 'provisioning', 'params' => []],

    // Dialog
    'dlg_list'           => ['role' => 'devops', 'label' => _('List Active Dialogs'),    'category' => 'dialog', 'params' => []],
    'dlg_end_dlg'        => ['role' => 'admin',  'label' => _('Terminate Dialog'),       'category' => 'dialog', 'params' => [['name' => 'hash_entry', 'type' => 'text', 'placeholder' => _('Hash Entry')], ['name' => 'hash_id', 'type' => 'text', 'placeholder' => _('Hash ID')]]],
    'dlg_profile_get_size' => ['role' => 'devops', 'label' => _('Dialog Profile Size'),  'category' => 'dialog', 'params' => [['name' => 'profile', 'type' => 'text', 'placeholder' => _('Profile name')], ['name' => 'value', 'type' => 'text', 'placeholder' => _('Value (optional)')]]],
    'dlg_profile_list'   => ['role' => 'devops', 'label' => _('Dialog Profile List'),    'category' => 'dialog', 'params' => [['name' => 'profile', 'type' => 'text', 'placeholder' => _('Profile name')], ['name' => 'value', 'type' => 'text', 'placeholder' => _('Value (optional)')]]],
    'dlg_match_info'     => ['role' => 'devops', 'label' => _('Dialog Match Info'),      'category' => 'dialog', 'params' => [['name' => 'callid', 'type' => 'text', 'placeholder' => _('Call-ID')]]],

    // SIP Trace
    'sip_trace_start'    => ['role' => 'devops', 'label' => _('Start SIP Trace'),        'category' => 'siptrace', 'params' => [['name' => 'destination', 'type' => 'text', 'placeholder' => _('Destination')], ['name' => 'type', 'type' => 'text', 'placeholder' => _('Type (optional)')]]],
    'sip_trace_stop'     => ['role' => 'devops', 'label' => _('Stop SIP Trace'),         'category' => 'siptrace', 'params' => [['name' => 'destination', 'type' => 'text', 'placeholder' => _('Destination')], ['name' => 'type', 'type' => 'text', 'placeholder' => _('Type (optional)')]]],
    'sip_trace_status'   => ['role' => 'devops', 'label' => _('SIP Trace Status'),       'category' => 'siptrace', 'params' => []],

    // Load Balancer
    'lb_status'          => ['role' => 'devops', 'label' => _('LB Destination Status'),  'category' => 'loadbalancer', 'params' => [['name' => 'dst_id', 'type' => 'text', 'placeholder' => _('Destination ID')]]],
    'lb_reload'          => ['role' => 'devops', 'label' => _('Reload Load Balancer'),   'category' => 'loadbalancer', 'params' => []],
    'lb_resize'          => ['role' => 'devops', 'label' => _('LB Resize Capacity'),     'category' => 'loadbalancer', 'params' => [['name' => 'dst_id', 'type' => 'text', 'placeholder' => _('Destination ID')], ['name' => 'capacity', 'type' => 'text', 'placeholder' => _('New capacity')]]],

    // Clusterer
    'clusterer_set_state'=> ['role' => 'admin',  'label' => _('Set Cluster Node State'), 'category' => 'clusterer', 'params' => [['name' => 'cluster_id', 'type' => 'text', 'placeholder' => _('Cluster ID')], ['name' => 'node_id', 'type' => 'text', 'placeholder' => _('Node ID')], ['name' => 'state', 'type' => 'text', 'placeholder' => _('New state')]]],
    'clusterer_ping'     => ['role' => 'devops', 'label' => _('Ping Cluster Node'),      'category' => 'clusterer', 'params' => [['name' => 'cluster_id', 'type' => 'text', 'placeholder' => _('Cluster ID')], ['name' => 'node_id', 'type' => 'text', 'placeholder' => _('Node ID')]]],

    // Media
    'rtpengine_enable'   => ['role' => 'devops', 'label' => _('Enable RTPengine'),       'category' => 'media', 'params' => [['name' => 'setid', 'type' => 'text', 'placeholder' => _('Set ID')], ['name' => 'url', 'type' => 'text', 'placeholder' => _('URL')]]],
    'rtpengine_disable'  => ['role' => 'devops', 'label' => _('Disable RTPengine'),      'category' => 'media', 'params' => [['name' => 'setid', 'type' => 'text', 'placeholder' => _('Set ID')], ['name' => 'url', 'type' => 'text', 'placeholder' => _('URL')]]],
    'rtpengine_reload'   => ['role' => 'devops', 'label' => _('Reload RTPengine'),       'category' => 'media', 'params' => []],
    'rtpengine_show'     => ['role' => 'devops', 'label' => _('Show RTPengine'),         'category' => 'media', 'params' => [['name' => 'setid', 'type' => 'text', 'placeholder' => _('Set ID (optional)')]]],
    'rtpengine_list'     => ['role' => 'devops', 'label' => _('List RTPengine Nodes'),   'category' => 'media', 'params' => []],

    // UAC Registrant
    'uac_reg_refresh'    => ['role' => 'devops', 'label' => _('Refresh UAC Registration'), 'category' => 'uac', 'params' => [['name' => 'reg_id', 'type' => 'text', 'placeholder' => _('Registration ID')]]],
    'uac_reg_enable'     => ['role' => 'devops', 'label' => _('Enable UAC Registration'),  'category' => 'uac', 'params' => [['name' => 'reg_id', 'type' => 'text', 'placeholder' => _('Registration ID')]]],
    'uac_reg_disable'    => ['role' => 'devops', 'label' => _('Disable UAC Registration'), 'category' => 'uac', 'params' => [['name' => 'reg_id', 'type' => 'text', 'placeholder' => _('Registration ID')]]],
    'uac_reg_list'       => ['role' => 'devops', 'label' => _('List UAC Registrations'),   'category' => 'uac', 'params' => []],

    // Statistics
    'get_statistics'     => ['role' => 'devops', 'label' => _('Get Statistics'),         'category' => 'statistics', 'params' => [['name' => 'filter', 'type' => 'text', 'placeholder' => _('Filter (optional, e.g. shm:)')]]],
    'reset_statistics'   => ['role' => 'admin',  'label' => _('Reset Statistics'),       'category' => 'statistics', 'params' => [['name' => 'filter', 'type' => 'text', 'placeholder' => _('Filter (optional)')]]],

    // Call Center
    'cc_agent_login'     => ['role' => 'devops', 'label' => _('CC Agent Login'),         'category' => 'callcenter', 'params' => [['name' => 'agent_id', 'type' => 'text', 'placeholder' => _('Agent ID')], ['name' => 'state', 'type' => 'text', 'placeholder' => _('State (e.g. 1)')]]],
    'cc_agent_logout'    => ['role' => 'devops', 'label' => _('CC Agent Logout'),        'category' => 'callcenter', 'params' => [['name' => 'agent_id', 'type' => 'text', 'placeholder' => _('Agent ID')]]],
    'cc_flow_status'     => ['role' => 'devops', 'label' => _('CC Flow Status'),         'category' => 'callcenter', 'params' => [['name' => 'flow_id', 'type' => 'text', 'placeholder' => _('Flow ID')]]],
    'cc_list_agents'     => ['role' => 'devops', 'label' => _('CC List Agents'),         'category' => 'callcenter', 'params' => []],
    'cc_list_calls'      => ['role' => 'devops', 'label' => _('CC List Calls'),          'category' => 'callcenter', 'params' => []],

    // Security
    'pike_list'          => ['role' => 'devops', 'label' => _('Pike Blocked IPs'),       'category' => 'security', 'params' => []],
    'pike_check_ip'      => ['role' => 'devops', 'label' => _('Pike Check IP'),          'category' => 'security', 'params' => [['name' => 'ip', 'type' => 'text', 'placeholder' => _('IP Address')]]],

    // Rate Limit
    'ratelimit_status'   => ['role' => 'devops', 'label' => _('Rate Limit Status'),      'category' => 'ratelimit', 'params' => []],
    'ratelimit_reset'    => ['role' => 'devops', 'label' => _('Rate Limit Reset'),       'category' => 'ratelimit', 'params' => [['name' => 'pipe', 'type' => 'text', 'placeholder' => _('Pipe name')]]],

    // Hash Tables
    'htable_dump'        => ['role' => 'devops', 'label' => _('Hash Table Dump'),        'category' => 'hashtable', 'params' => [['name' => 'table', 'type' => 'text', 'placeholder' => _('Table name')]]],
    'htable_flush'       => ['role' => 'admin',  'label' => _('Hash Table Flush'),       'category' => 'hashtable', 'params' => [['name' => 'table', 'type' => 'text', 'placeholder' => _('Table name')]]],

    // NAT Helper
    'nh_show_sockets'    => ['role' => 'devops', 'label' => _('NAT Helper Sockets'),     'category' => 'nathelper', 'params' => []],
    'nh_show_ping'       => ['role' => 'devops', 'label' => _('NAT Helper Ping Stats'),  'category' => 'nathelper', 'params' => []],

    // TCP
    'tcp_list'           => ['role' => 'devops', 'label' => _('TCP Connections'),        'category' => 'tcp', 'params' => []],

    // Blacklists
    'list_blacklists'    => ['role' => 'devops', 'label' => _('List Blacklists'),        'category' => 'blacklist', 'params' => []],

    // Timers
    'list_timers'        => ['role' => 'devops', 'label' => _('List Timers'),            'category' => 'timer', 'params' => []],

    // Processes
    'ps'                 => ['role' => 'devops', 'label' => _('Process List'),           'category' => 'process', 'params' => []],

    // User Location
    'ul_dump'            => ['role' => 'devops', 'label' => _('USRLoc Dump'),            'category' => 'usrloc', 'params' => [['name' => 'aor', 'type' => 'text', 'placeholder' => _('AoR (optional)')]]],

    // Version
    'version'            => ['role' => 'user',   'label' => _('Version'),                'category' => 'version', 'params' => []],
    'which'              => ['role' => 'user',   'label' => _('Loaded Modules'),         'category' => 'version', 'params' => []],

    // Presence
    'pres_refresh_watchers' => ['role' => 'devops', 'label' => _('Refresh Presence Watchers'), 'category' => 'presence', 'params' => [['name' => 'uri', 'type' => 'text', 'placeholder' => _('Presentity URI (optional)')], ['name' => 'event', 'type' => 'text', 'placeholder' => _('Event type (optional)')]]],
    'pua_refresh'        => ['role' => 'devops', 'label' => _('Refresh PUA'),            'category' => 'presence', 'params' => []],
];

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
                $schema = $miWhitelist[$command]['params'] ?? [];

                foreach ($schema as $paramDef) {
                    $pName = $paramDef['name'];
                    $pVal = trim($_POST['param_' . $pName] ?? '');
                    if ($pVal !== '') {
                        $params[] = $pVal;
                    }
                }

                // Fallback: allow free-form params if schema is empty and raw params provided
                if (empty($params) && empty($schema)) {
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
                <input type="text" id="command-search" class="tsisip-input" placeholder="<?php echo _('Search commands...'); ?>" style="margin-bottom:8px;">
                <select id="command" name="command" class="tsisip-input" required>
                    <option value=""><?php echo _('— Select —'); ?></option>
                    <?php foreach ($categories as $catKey => $catLabel): ?>
                        <?php
                        $catCommands = array_filter($miWhitelist, function ($meta) use ($catKey) {
                            return ($meta['category'] ?? '') === $catKey;
                        });
                        if (empty($catCommands)) continue;
                        ?>
                        <optgroup label="<?php echo htmlspecialchars($catLabel); ?>">
                            <?php foreach ($catCommands as $cmd => $meta): ?>
                                <?php
                                $requiredLevel = $roleHierarchy[$meta['role']] ?? 0;
                                if ($userLevel >= $requiredLevel):
                                ?>
                                    <option value="<?php echo htmlspecialchars($cmd); ?>"
                                            data-category="<?php echo htmlspecialchars($catKey); ?>"
                                            data-params="<?php echo htmlspecialchars(json_encode($meta['params'] ?? []), ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($meta['label'] . ' (' . $cmd . ')'); ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="dynamic-params"></div>

            <div id="params-field" class="tsisip-form-group" style="display:none;">
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
        <div class="tsisip-form-group">
            <input type="text" id="table-search" class="tsisip-input" placeholder="<?php echo _('Filter commands...'); ?>">
        </div>
        <div style="overflow-x:auto;">
            <table class="dataTable tsisip-table" id="command-table">
                <thead>
                    <tr>
                        <th><?php echo _('Category'); ?></th>
                        <th><?php echo _('Command'); ?></th>
                        <th><?php echo _('Description'); ?></th>
                        <th><?php echo _('Required Role'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($miWhitelist as $cmd => $meta): ?>
                        <tr data-category="<?php echo htmlspecialchars($meta['category'] ?? ''); ?>">
                            <td><?php echo htmlspecialchars($categories[$meta['category'] ?? ''] ?? $meta['category'] ?? '—'); ?></td>
                            <td class="mono-cell"><?php echo htmlspecialchars($cmd); ?></td>
                            <td><?php echo htmlspecialchars($meta['label']); ?></td>
                            <td>
                                <span class="tsisip-badge tsisip-badge-<?php echo $meta['role'] === 'admin' ? 'error' : ($meta['role'] === 'devops' ? 'success' : 'info'); ?>">
                                    <?php echo htmlspecialchars(ucfirst($meta['role'])); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';
    var commandSelect = document.getElementById('command');
    var commandSearch = document.getElementById('command-search');
    var dynamicParams = document.getElementById('dynamic-params');
    var paramsField = document.getElementById('params-field');
    var tableSearch = document.getElementById('table-search');
    var commandTable = document.getElementById('command-table');

    function buildParamFields(params) {
        dynamicParams.innerHTML = '';
        if (!params || params.length === 0) {
            paramsField.style.display = 'block';
            return;
        }
        paramsField.style.display = 'none';
        params.forEach(function(p) {
            var group = document.createElement('div');
            group.className = 'tsisip-form-group';
            var label = document.createElement('label');
            label.textContent = p.name;
            label.setAttribute('for', 'param_' + p.name);
            var input = document.createElement('input');
            input.type = p.type || 'text';
            input.id = 'param_' + p.name;
            input.name = 'param_' + p.name;
            input.className = 'tsisip-input';
            if (p.placeholder) {
                input.placeholder = p.placeholder;
            }
            group.appendChild(label);
            group.appendChild(input);
            dynamicParams.appendChild(group);
        });
    }

    commandSelect.addEventListener('change', function() {
        var selected = this.options[this.selectedIndex];
        var params = [];
        try {
            params = JSON.parse(selected.getAttribute('data-params') || '[]');
        } catch (e) {}
        buildParamFields(params);
    });

    commandSearch.addEventListener('input', function() {
        var term = this.value.toLowerCase();
        var opts = commandSelect.querySelectorAll('option');
        opts.forEach(function(opt) {
            if (opt.value === '') return;
            var text = (opt.textContent || '').toLowerCase();
            var category = (opt.getAttribute('data-category') || '').toLowerCase();
            opt.style.display = (text.indexOf(term) !== -1 || category.indexOf(term) !== -1) ? '' : 'none';
        });
        // Also filter optgroups
        commandSelect.querySelectorAll('optgroup').forEach(function(og) {
            var visible = og.querySelectorAll('option[style="display: none;"]').length;
            var total = og.querySelectorAll('option').length;
            og.style.display = (visible === total) ? 'none' : '';
        });
    });

    if (tableSearch && commandTable) {
        tableSearch.addEventListener('input', function() {
            var term = this.value.toLowerCase();
            commandTable.querySelectorAll('tbody tr').forEach(function(row) {
                var text = row.textContent.toLowerCase();
                row.style.display = text.indexOf(term) !== -1 ? '' : 'none';
            });
        });
    }
})();
</script>

<?php require_once __DIR__ . '/common/footer.php'; ?>
