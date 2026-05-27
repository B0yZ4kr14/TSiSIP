<?php
/**
 * TSiSIP Control Panel — Call Center
 * Call center flows, agents, and queue management (callcenter module)
 */

require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';
require_once __DIR__ . '/common/pagination.php';
require_once __DIR__ . '/common/mi-http.php';

requireAuth();
checkPasswordChange();
requireRole('devops');

$pdo = getDb();
$error = '';
$success = '';

// --- Handle mutating operations ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = _('Invalid CSRF token.');
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create_flow' || $action === 'update_flow') {
            $id   = $_POST['id'] ?? null;
            $name = trim($_POST['flowid'] ?? '');
            $type = $_POST['skill'] ?? 'fifo';
            $desc = trim($_POST['cid'] ?? '');
            $enabled = isset($_POST['enabled']) ? 1 : 0;

            if ($name === '') {
                $error = _('Flow name is required.');
            } else {
                try {
                    if ($action === 'create_flow') {
                        $stmt = $pdo->prepare(
                            'INSERT INTO cc_flows (flowid, skill, cid, enabled)
                             VALUES (?, ?, ?, ?)'
                        );
                        $stmt->execute([$name, $type, $desc, $enabled]);
                        $success = _('Call flow created successfully.');
                        logAuditEvent('CC_FLOW_CREATE', 'call-center', $name, true);
                    } else {
                        $stmt = $pdo->prepare(
                            'UPDATE cc_flows SET flowid=?, skill=?, cid=?, enabled=? WHERE id=?'
                        );
                        $stmt->execute([$name, $type, $desc, $enabled, $id]);
                        $success = _('Call flow updated successfully.');
                        logAuditEvent('CC_FLOW_UPDATE', 'call-center', $name, true);
                    }
                } catch (PDOException $e) {
                    $error = _('Database error: ') . $e->getMessage();
                }
            }
        } elseif ($action === 'delete_flow') {
            $id = $_POST['id'] ?? '';
            try {
                $stmt = $pdo->prepare('SELECT flowid FROM cc_flows WHERE id=?');
                $stmt->execute([$id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $stmt = $pdo->prepare('DELETE FROM cc_flows WHERE id=?');
                    $stmt->execute([$id]);
                    $success = _('Call flow deleted successfully.');
                    logAuditEvent('CC_FLOW_DELETE', 'call-center', $row['flowid'], true);
                }
            } catch (PDOException $e) {
                $error = _('Database error: ') . $e->getMessage();
            }
        } elseif ($action === 'create_agent' || $action === 'update_agent') {
            $id   = $_POST['id'] ?? null;
            $loc  = trim($_POST['agentid'] ?? '');
            $flow = intval($_POST['priority'] ?? 0);
            $skl  = trim($_POST['skills'] ?? '');
            $log  = trim($_POST['logstate'] ?? 'out');
            $enabled = isset($_POST['enabled']) ? 1 : 0;

            if ($loc === '') {
                $error = _('Agent agentid is required.');
            } else {
                try {
                    if ($action === 'create_agent') {
                        $stmt = $pdo->prepare(
                            'INSERT INTO cc_agents (agentid, flowid, skills, logstate, enabled)
                             VALUES (?, ?, ?, ?, ?)'
                        );
                        $stmt->execute([$loc, $flow, $skl, $log, $enabled]);
                        $success = _('Agent created successfully.');
                        logAuditEvent('CC_AGENT_CREATE', 'call-center', $loc, true);
                    } else {
                        $stmt = $pdo->prepare(
                            'UPDATE cc_agents SET agentid=?, flowid=?, skills=?, logstate=?, enabled=? WHERE id=?'
                        );
                        $stmt->execute([$loc, $flow, $skl, $log, $enabled, $id]);
                        $success = _('Agent updated successfully.');
                        logAuditEvent('CC_AGENT_UPDATE', 'call-center', $loc, true);
                    }
                } catch (PDOException $e) {
                    $error = _('Database error: ') . $e->getMessage();
                }
            }
        } elseif ($action === 'delete_agent') {
            $id = $_POST['id'] ?? '';
            try {
                $stmt = $pdo->prepare('SELECT agentid FROM cc_agents WHERE id=?');
                $stmt->execute([$id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $stmt = $pdo->prepare('DELETE FROM cc_agents WHERE id=?');
                    $stmt->execute([$id]);
                    $success = _('Agent deleted successfully.');
                    logAuditEvent('CC_AGENT_DELETE', 'call-center', $row['agentid'], true);
                }
            } catch (PDOException $e) {
                $error = _('Database error: ') . $e->getMessage();
            }
        }
    }
}

// --- Fetch flows ---
$flowPage = max(1, intval($_GET['flow_page'] ?? 1));
$perPage = 25;
$countStmt = $pdo->query('SELECT COUNT(*) FROM cc_flows');
$flowTotal = $countStmt->fetchColumn();
$flowPages = max(1, ceil($flowTotal / $perPage));
$stmt = $pdo->prepare('SELECT * FROM cc_flows ORDER BY enabled DESC, flowid LIMIT ? OFFSET ?');
$stmt->execute([$perPage, ($flowPage - 1) * $perPage]);
$flows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Fetch agents ---
$agentPage = max(1, intval($_GET['agent_page'] ?? 1));
$countStmt = $pdo->query('SELECT COUNT(*) FROM cc_agents');
$agentTotal = $countStmt->fetchColumn();
$agentPages = max(1, ceil($agentTotal / $perPage));
$stmt = $pdo->prepare('SELECT a.*, f.flowid FROM cc_agents a LEFT JOIN cc_flows f ON a.flowid=f.flowid ORDER BY a.enabled DESC, a.agentid LIMIT ? OFFSET ?');
$stmt->execute([$perPage, ($agentPage - 1) * $perPage]);
$agents = $stmt->fetchAll(PDO::FETCH_ASSOC);

$editFlow = null;
if (isset($_GET['edit_flow'])) {
    $stmt = $pdo->prepare('SELECT * FROM cc_flows WHERE id=?');
    $stmt->execute([$_GET['edit_flow']]);
    $editFlow = $stmt->fetch(PDO::FETCH_ASSOC);
}
$editAgent = null;
if (isset($_GET['edit_agent'])) {
    $stmt = $pdo->prepare('SELECT * FROM cc_agents WHERE id=?');
    $stmt->execute([$_GET['edit_agent']]);
    $editAgent = $stmt->fetch(PDO::FETCH_ASSOC);
}

$csrfToken = generateCsrfToken();
require_once __DIR__ . '/common/header.php';
?>

<div class="tsisip-page">
    <header class="tsisip-page-header">
        <h1 class="tsisip-page-title"><?php echo _('Call Center'); ?></h1>
        <p class="tsisip-page-subtitle"><?php echo _('Call flows, agents, and queue management'); ?></p>
    </header>

    <?php if ($error): ?>
        <div class="tsisip-alert tsisip-alert--error" role="alert"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="tsisip-alert tsisip-alert--success" role="alert"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo _('Call Flows'); ?></h2>
        <table class="tsisip-table">
            <thead>
                <tr>
                    <th><?php echo _('Flow Name'); ?></th>
                    <th><?php echo _('Type'); ?></th>
                    <th><?php echo _('Description'); ?></th>
                    <th><?php echo _('Status'); ?></th>
                    <th><?php echo _('Actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($flows)): ?>
                    <tr><td colspan="5" class="tsisip-empty"><?php echo _('No call flows found.'); ?></td></tr>
                <?php else: ?>
                    <?php foreach ($flows as $f): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($f['flowid']); ?></td>
                            <td><?php echo htmlspecialchars(strtoupper($f['skill'])); ?></td>
                            <td><?php echo htmlspecialchars($f['cid']); ?></td>
                            <td><?php echo $f['enabled'] ? '<span class="tsisip-tag tsisip-tag--success">'._('Enabled').'</span>' : '<span class="tsisip-tag tsisip-tag--muted">'._('Disabled').'</span>'; ?></td>
                            <td>
                                <a href="?edit_flow=<?php echo $f['id']; ?>" class="tsisip-btn tsisip-btn--sm"><?php echo _('Edit'); ?></a>
                                <form method="post" style="display:inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    <input type="hidden" name="action" value="delete_flow">
                                    <input type="hidden" name="id" value="<?php echo $f['id']; ?>">
                                    <button type="submit" class="tsisip-btn tsisip-btn--danger tsisip-btn--sm"
                                            onclick="return confirm('<?php echo _('Delete this flow?'); ?>')">
                                        <?php echo _('Delete'); ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php renderPagination($flowPage, $flowPages, $flowTotal, $perPage, 'call-center.php', ['agent_page' => $agentPage], 'flow_page'); ?>
    </section>

    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo $editFlow ? _('Edit Call Flow') : _('Add Call Flow'); ?></h2>
        <form method="post" class="tsisip-form">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="action" value="<?php echo $editFlow ? 'update_flow' : 'create_flow'; ?>">
            <?php if ($editFlow): ?><input type="hidden" name="id" value="<?php echo $editFlow['id']; ?>"><?php endif; ?>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Flow Name'); ?></label>
                <input type="text" name="flowid" required value="<?php echo $editFlow ? htmlspecialchars($editFlow['flowid']) : ''; ?>" class="tsisip-input">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Flow Type'); ?></label>
                <select name="skill" class="tsisip-select">
                    <option value="fifo" <?php echo !$editFlow || $editFlow['skill'] === 'fifo' ? 'selected' : ''; ?>><?php echo _('FIFO'); ?></option>
                    <option value="lifo" <?php echo $editFlow && $editFlow['skill'] === 'lifo' ? 'selected' : ''; ?>><?php echo _('LIFO'); ?></option>
                    <option value="random" <?php echo $editFlow && $editFlow['skill'] === 'random' ? 'selected' : ''; ?>><?php echo _('Random'); ?></option>
                    <option value="round_robin" <?php echo $editFlow && $editFlow['skill'] === 'round_robin' ? 'selected' : ''; ?>><?php echo _('Round Robin'); ?></option>
                </select>
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Description'); ?></label>
                <input type="text" name="cid" value="<?php echo $editFlow ? htmlspecialchars($editFlow['cid']) : ''; ?>" class="tsisip-input">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-checkbox">
                    <input type="checkbox" name="enabled" <?php echo (!$editFlow || $editFlow['enabled']) ? 'checked' : ''; ?>>
                    <?php echo _('Enabled'); ?>
                </label>
            </div>
            <button type="submit" class="tsisip-btn tsisip-btn--primary"><?php echo $editFlow ? _('Update') : _('Create'); ?></button>
            <?php if ($editFlow): ?><a href="call-center.php" class="tsisip-btn tsisip-btn--secondary"><?php echo _('Cancel'); ?></a><?php endif; ?>
        </form>
    </section>

    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo _('Agents'); ?></h2>
        <table class="tsisip-table">
            <thead>
                <tr>
                    <th><?php echo _('Location'); ?></th>
                    <th><?php echo _('Flow'); ?></th>
                    <th><?php echo _('Skills'); ?></th>
                    <th><?php echo _('State'); ?></th>
                    <th><?php echo _('Status'); ?></th>
                    <th><?php echo _('Actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($agents)): ?>
                    <tr><td colspan="6" class="tsisip-empty"><?php echo _('No agents found.'); ?></td></tr>
                <?php else: ?>
                    <?php foreach ($agents as $a): ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($a['agentid']); ?></code></td>
                            <td><?php echo htmlspecialchars($a['flowid'] ?? _('Unassigned')); ?></td>
                            <td><?php echo htmlspecialchars($a['skills']); ?></td>
                            <td><?php echo htmlspecialchars(strtoupper($a['logstate'])); ?></td>
                            <td><?php echo $a['enabled'] ? '<span class="tsisip-tag tsisip-tag--success">'._('Enabled').'</span>' : '<span class="tsisip-tag tsisip-tag--muted">'._('Disabled').'</span>'; ?></td>
                            <td>
                                <?php if (isDevOpsOrHigher()): ?>
                                    <?php $agentLoggedIn = $a['logstate'] === 'in'; ?>
                                    <button type="button" class="tsisip-btn tsisip-btn--<?php echo $agentLoggedIn ? 'danger' : 'success'; ?> tsisip-btn--sm btn-cc-toggle"
                                            data-agent="<?php echo htmlspecialchars($a['agentid'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-state="<?php echo $agentLoggedIn ? 'on' : 'off'; ?>">
                                        <?php echo $agentLoggedIn ? _('Logout') : _('Login'); ?>
                                    </button>
                                <?php endif; ?>
                                <a href="?edit_agent=<?php echo $a['id']; ?>" class="tsisip-btn tsisip-btn--sm"><?php echo _('Edit'); ?></a>
                                <form method="post" style="display:inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    <input type="hidden" name="action" value="delete_agent">
                                    <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
                                    <button type="submit" class="tsisip-btn tsisip-btn--danger tsisip-btn--sm"
                                            onclick="return confirm('<?php echo _('Delete this agent?'); ?>')">
                                        <?php echo _('Delete'); ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php renderPagination($agentPage, $agentPages, $agentTotal, $perPage, 'call-center.php', ['flow_page' => $flowPage], 'agent_page'); ?>
    </section>

    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo $editAgent ? _('Edit Agent') : _('Add Agent'); ?></h2>
        <form method="post" class="tsisip-form">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="action" value="<?php echo $editAgent ? 'update_agent' : 'create_agent'; ?>">
            <?php if ($editAgent): ?><input type="hidden" name="id" value="<?php echo $editAgent['id']; ?>"><?php endif; ?>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Location (SIP URI)'); ?></label>
                <input type="text" name="agentid" required value="<?php echo $editAgent ? htmlspecialchars($editAgent['agentid']) : ''; ?>" class="tsisip-input" placeholder="sip:agent1@10.0.0.1:5060">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Call Flow'); ?></label>
                <select name="priority" class="tsisip-select">
                    <option value="0"><?php echo _('Unassigned'); ?></option>
                    <?php foreach ($flows as $f): ?>
                        <option value="<?php echo $f['id']; ?>" <?php echo $editAgent && $editAgent['priority'] == $f['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($f['flowid']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Skills'); ?></label>
                <input type="text" name="skills" value="<?php echo $editAgent ? htmlspecialchars($editAgent['skills']) : ''; ?>" class="tsisip-input" placeholder="sales, spanish, L2">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Login State'); ?></label>
                <select name="logstate" class="tsisip-select">
                    <option value="in" <?php echo $editAgent && $editAgent['logstate'] === 'in' ? 'selected' : ''; ?>><?php echo _('Logged In'); ?></option>
                    <option value="out" <?php echo !$editAgent || $editAgent['logstate'] === 'out' ? 'selected' : ''; ?>><?php echo _('Logged Out'); ?></option>
                </select>
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-checkbox">
                    <input type="checkbox" name="enabled" <?php echo (!$editAgent || $editAgent['enabled']) ? 'checked' : ''; ?>>
                    <?php echo _('Enabled'); ?>
                </label>
            </div>
            <button type="submit" class="tsisip-btn tsisip-btn--primary"><?php echo $editAgent ? _('Update') : _('Create'); ?></button>
            <?php if ($editAgent): ?><a href="call-center.php" class="tsisip-btn tsisip-btn--secondary"><?php echo _('Cancel'); ?></a><?php endif; ?>
        </form>
    </section>

    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo _('Call Flow Status'); ?></h2>
        <?php
        $miFlow = miHttpCall('cc_flow_status');
        if (!$miFlow['success']):
        ?>
            <div class="tsisip-badge tsisip-badge--warning" role="alert">
                <?php echo _('MI unavailable: ') . htmlspecialchars($miFlow['error']); ?>
            </div>
        <?php else:
            $flowData = $miFlow['data'] ?? [];
            if (!is_array($flowData)) {
                $flowData = [];
            }
            if (empty($flowData)):
        ?>
            <div class="tsisip-badge tsisip-badge--info"><?php echo _('No live call flow data returned by OpenSIPS.'); ?></div>
        <?php else: ?>
            <table class="tsisip-table">
                <thead>
                    <tr><th><?php echo _('Flow'); ?></th><th><?php echo _('Value'); ?></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($flowData as $key => $val): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($key); ?></td>
                            <td><code><?php echo htmlspecialchars(is_array($val) ? json_encode($val) : $val); ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; endif; ?>
    </section>

    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo _('Live Call Center Agents'); ?></h2>
        <?php
        $miAgents = miHttpCall('cc_list_agents');
        if (!$miAgents['success']):
        ?>
            <div class="tsisip-badge tsisip-badge--warning" role="alert">
                <?php echo _('MI unavailable: ') . htmlspecialchars($miAgents['error']); ?>
            </div>
        <?php else:
            $agentData = $miAgents['data'] ?? [];
            if (!is_array($agentData)) {
                $agentData = [];
            }
            if (empty($agentData)):
        ?>
            <div class="tsisip-badge tsisip-badge--info"><?php echo _('No live call center data returned by OpenSIPS.'); ?></div>
        <?php else: ?>
            <table class="tsisip-table">
                <thead>
                    <tr>
                        <th><?php echo _('Agent ID'); ?></th>
                        <th><?php echo _('Location'); ?></th>
                        <th><?php echo _('State'); ?></th>
                        <th><?php echo _('Skills'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($agentData as $a): ?>
                        <?php if (!is_array($a)) continue; ?>
                        <tr>
                            <td><?php echo htmlspecialchars($a['agentid'] ?? $a['id'] ?? 'N/A'); ?></td>
                            <td><code><?php echo htmlspecialchars($a['location'] ?? 'N/A'); ?></code></td>
                            <td><?php echo htmlspecialchars(strtoupper($a['logstate'] ?? 'N/A')); ?></td>
                            <td><?php echo htmlspecialchars($a['skills'] ?? '—'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; endif; ?>
    </section>
</div>

<script>
<?php if (isDevOpsOrHigher()): ?>
document.querySelectorAll('.btn-cc-toggle').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        var isOn = btn.dataset.state === 'on';
        var cmd = isOn ? 'cc_agent_logout' : 'cc_agent_login';
        btn.disabled = true;
        TSiSIPMi.action(cmd, [btn.dataset.agent], function() {
            btn.disabled = false;
            btn.dataset.state = isOn ? 'off' : 'on';
            btn.textContent = isOn ? <?php echo json_encode(_('Login')); ?> : <?php echo json_encode(_('Logout')); ?>;
            btn.classList.toggle('tsisip-btn--danger', !isOn);
            btn.classList.toggle('tsisip-btn--success', isOn);
        }, function() {
            btn.disabled = false;
        });
    });
});
<?php endif; ?>
</script>
<?php require_once __DIR__ . '/common/footer.php'; ?>
