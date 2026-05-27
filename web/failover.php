<?php
/**
 * TSiSIP Control Panel — Manual Failover Trigger
 * Privileged interface to trigger dispatcher failover operations.
 */
require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';
require_once __DIR__ . '/common/mi-http.php';

requireAuth();
checkPasswordChange();
requireRole('admin'); // Only admin can trigger failover

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = _('Invalid CSRF token.');
    } else {
        $action = $_POST['action'] ?? '';
        $setId = intval($_POST['setid'] ?? 0);

        if ($action === 'ds_reload') {
            $result = miHttpCall('ds_reload');
            if ($result['success']) {
                $success = _('Dispatcher table reloaded successfully.');
                logAuditEvent('FAILOVER', 'dispatcher', 'ds_reload', true);
            } else {
                $error = _('Failed to reload dispatcher: ') . htmlspecialchars($result['error'] ?? _('Unknown error'));
                logAuditEvent('FAILOVER', 'dispatcher', 'ds_reload', false, ['error' => $result['error'] ?? '']);
            }
        } elseif ($action === 'ds_set_state' && $setId > 0) {
            $destination = trim($_POST['destination'] ?? '');
            $state = intval($_POST['state'] ?? 0);
            if ($destination === '') {
                $error = _('Destination is required.');
            } else {
                $result = miHttpCall('ds_set_state', [$state, $setId, $destination]);
                if ($result['success']) {
                    $success = sprintf(_('Dispatcher state set to %d for %s in set %d.'), $state, htmlspecialchars($destination), $setId);
                    logAuditEvent('FAILOVER', 'dispatcher', $destination, true, ['setid' => $setId, 'state' => $state]);
                } else {
                    $error = _('Failed to set dispatcher state: ') . htmlspecialchars($result['error'] ?? _('Unknown error'));
                    logAuditEvent('FAILOVER', 'dispatcher', $destination, false, ['setid' => $setId, 'error' => $result['error'] ?? '']);
                }
            }
        } else {
            $error = _('Invalid action or missing parameters.');
        }
    }
}

// Fetch dispatcher sets for the form
$sets = [];
$dsResult = miHttpCall('ds_list');
if ($dsResult['success'] && is_array($dsResult['data'])) {
    $rawDs = $dsResult['data'];
    foreach ($rawDs as $key => $setEntries) {
        if (is_array($setEntries)) {
            foreach ($setEntries as $entry) {
                if (is_array($entry)) {
                    $entry['setid'] = $entry['setid'] ?? $entry['SET'] ?? $key;
                    $sets[] = $entry;
                }
            }
        }
    }
}

require_once __DIR__ . '/common/header.php';
?>
<div id="content" class="tsisip-dashboard">
    <h1><?php echo _('Manual Failover Trigger'); ?></h1>

    <div class="tsisip-dashboard-section">
        <p class="tsisip-text-muted">
            <?php echo _('Use with caution. These operations affect live SIP traffic routing.'); ?>
        </p>
    </div>

    <?php if ($error): ?>
        <div class="tsisip-badge tsisip-badge-error" role="alert"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="tsisip-badge tsisip-badge-success" role="status"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo _('Dispatcher Reload'); ?></h2>
        <form method="POST" action="" class="tsisip-form">
            <?php echo csrfInput(); ?>
            <input type="hidden" name="action" value="ds_reload">
            <p><?php echo _('Reload the dispatcher table from the database without restarting OpenSIPS.'); ?></p>
            <button type="submit" class="tsisip-btn tsisip-btn-primary"
                    onclick="return confirm('<?php echo _('Reload dispatcher table?'); ?>');">
                <?php echo _('Reload Dispatcher'); ?>
            </button>
        </form>
    </section>

    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo _('Set Dispatcher State'); ?></h2>
        <form method="POST" action="" class="tsisip-form">
            <?php echo csrfInput(); ?>
            <input type="hidden" name="action" value="ds_set_state">
            <div style="display:flex;flex-wrap:wrap;gap:1rem;align-items:flex-end;">
                <div class="tsisip-form-group">
                    <label for="setid"><?php echo _('Set ID'); ?></label>
                    <input type="number" id="setid" name="setid" class="tsisip-input" value="1" min="1" required style="width:100px">
                </div>
                <div class="tsisip-form-group">
                    <label for="destination"><?php echo _('Destination'); ?></label>
                    <input type="text" id="destination" name="destination" class="tsisip-input" placeholder="sip:pbx.example.com:5060" required style="width:280px">
                </div>
                <div class="tsisip-form-group">
                    <label for="state"><?php echo _('State'); ?></label>
                    <select id="state" name="state" class="tsisip-input" style="width:120px">
                        <option value="0"><?php echo _('Active (0)'); ?></option>
                        <option value="1"><?php echo _('Inactive (1)'); ?></option>
                        <option value="2"><?php echo _('Probing (2)'); ?></option>
                        <option value="3"><?php echo _('Disabled (3)'); ?></option>
                    </select>
                </div>
                <button type="submit" class="tsisip-btn tsisip-btn-primary"
                        onclick="return confirm('<?php echo _('Change dispatcher state? This affects live traffic.'); ?>');">
                    <?php echo _('Apply State'); ?>
                </button>
            </div>
        </form>
    </section>

    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo _('Current Dispatcher Targets'); ?></h2>
        <?php if (empty($sets)): ?>
            <div class="tsisip-badge tsisip-badge--info"><?php echo _('No dispatcher data or MI unreachable.'); ?></div>
        <?php else: ?>
            <table class="tsisip-table dataTable">
                <thead>
                    <tr>
                        <th><?php echo _('Set'); ?></th>
                        <th><?php echo _('Destination'); ?></th>
                        <th><?php echo _('State'); ?></th>
                        <th><?php echo _('Weight'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sets as $s): ?>
                        <tr>
                            <td><?php echo htmlspecialchars((string) ($s['setid'] ?? $s['SET'] ?? 'N/A')); ?></td>
                            <td><code><?php echo htmlspecialchars($s['destination'] ?? $s['URI'] ?? $s['TARGET'] ?? 'N/A'); ?></code></td>
                            <td>
                                <?php
                                $flags = $s['state'] ?? $s['FLAGS'] ?? '';
                                $isActive = is_string($flags)
                                    ? (stripos($flags, 'A') !== false || stripos($flags, 'P') !== false)
                                    : (bool)$flags;
                                ?>
                                <span class="tsisip-badge tsisip-badge--<?php echo $isActive ? 'success' : 'danger'; ?>">
                                    <?php echo $isActive ? _('Active') : _('Inactive'); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars((string) ($s['weight'] ?? $s['WEIGHT'] ?? '—')); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</div>
<?php require_once __DIR__ . '/common/footer.php'; ?>
