<?php
/**
 * TSiSIP Control Panel — Dynamic Routing
 * LCR / dynamic routing management (drouting module)
 */

require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';
require_once __DIR__ . '/common/pagination.php';

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

        if ($action === 'create_gw' || $action === 'update_gw') {
            $id   = $_POST['id'] ?? null;
            $gwid = trim($_POST['gwid'] ?? '');
            $type = intval($_POST['type'] ?? 0);
            $addr = trim($_POST['address'] ?? '');
            $strip= trim($_POST['strip'] ?? '');
            $pri  = trim($_POST['pri_prefix'] ?? '');
            $attrs= trim($_POST['attrs'] ?? '');
            $probe= trim($_POST['probe_mode'] ?? 'none');
            $desc = trim($_POST['description'] ?? '');
            $enabled = isset($_POST['enabled']) ? 1 : 0;

            if ($gwid === '' || $addr === '') {
                $error = _('Gateway ID and address are required.');
            } else {
                try {
                    if ($action === 'create_gw') {
                        $stmt = $pdo->prepare(
                            'INSERT INTO dr_gateways (gwid, type, address, strip, pri_prefix, attrs, probe_mode, description, enabled)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
                        );
                        $stmt->execute([$gwid, $type, $addr, $strip, $pri, $attrs, $probe, $desc, $enabled]);
                        $success = _('Gateway created successfully.');
                        logAuditEvent('DR_GW_CREATE', 'dynamic-routing', $gwid, true, ['address' => $addr]);
                    } else {
                        $stmt = $pdo->prepare(
                            'UPDATE dr_gateways SET gwid=?, type=?, address=?, strip=?, pri_prefix=?, attrs=?, probe_mode=?, description=?, enabled=? WHERE id=?'
                        );
                        $stmt->execute([$gwid, $type, $addr, $strip, $pri, $attrs, $probe, $desc, $enabled, $id]);
                        $success = _('Gateway updated successfully.');
                        logAuditEvent('DR_GW_UPDATE', 'dynamic-routing', $gwid, true);
                    }
                } catch (PDOException $e) {
                    $error = _('Database error: ') . $e->getMessage();
                }
            }
        } elseif ($action === 'delete_gw') {
            $id = $_POST['id'] ?? '';
            try {
                $stmt = $pdo->prepare('SELECT gwid FROM dr_gateways WHERE id=?');
                $stmt->execute([$id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $stmt = $pdo->prepare('DELETE FROM dr_gateways WHERE id=?');
                    $stmt->execute([$id]);
                    $success = _('Gateway deleted successfully.');
                    logAuditEvent('DR_GW_DELETE', 'dynamic-routing', $row['gwid'], true);
                }
            } catch (PDOException $e) {
                $error = _('Database error: ') . $e->getMessage();
            }
        } elseif ($action === 'create_rule' || $action === 'update_rule') {
            $id  = $_POST['id'] ?? null;
            $grp = intval($_POST['group_id'] ?? 0);
            $pri = intval($_POST['priority'] ?? 0);
            $prefix = trim($_POST['prefix'] ?? '');
            $timet  = trim($_POST['timerec'] ?? '');
            $routeid= trim($_POST['routeid'] ?? '');
            $gwlist = trim($_POST['gwlist'] ?? '');
            $attrs  = trim($_POST['attrs'] ?? '');
            $desc   = trim($_POST['description'] ?? '');
            $enabled = isset($_POST['enabled']) ? 1 : 0;

            if ($gwlist === '') {
                $error = _('Gateway list is required.');
            } else {
                try {
                    if ($action === 'create_rule') {
                        $stmt = $pdo->prepare(
                            'INSERT INTO dr_rules (group_id, priority, prefix, timerec, routeid, gwlist, attrs, description, enabled)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
                        );
                        $stmt->execute([$grp, $pri, $prefix, $timet, $routeid, $gwlist, $attrs, $desc, $enabled]);
                        $success = _('Routing rule created successfully.');
                        logAuditEvent('DR_RULE_CREATE', 'dynamic-routing', "grp=$grp prefix=$prefix", true);
                    } else {
                        $stmt = $pdo->prepare(
                            'UPDATE dr_rules SET group_id=?, priority=?, prefix=?, timerec=?, routeid=?, gwlist=?, attrs=?, description=?, enabled=? WHERE id=?'
                        );
                        $stmt->execute([$grp, $pri, $prefix, $timet, $routeid, $gwlist, $attrs, $desc, $enabled, $id]);
                        $success = _('Routing rule updated successfully.');
                        logAuditEvent('DR_RULE_UPDATE', 'dynamic-routing', "grp=$grp prefix=$prefix", true);
                    }
                } catch (PDOException $e) {
                    $error = _('Database error: ') . $e->getMessage();
                }
            }
        } elseif ($action === 'delete_rule') {
            $id = $_POST['id'] ?? '';
            try {
                $stmt = $pdo->prepare('SELECT id FROM dr_rules WHERE id=?');
                $stmt->execute([$id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $stmt = $pdo->prepare('DELETE FROM dr_rules WHERE id=?');
                    $stmt->execute([$id]);
                    $success = _('Routing rule deleted successfully.');
                    logAuditEvent('DR_RULE_DELETE', 'dynamic-routing', "id=$id", true);
                }
            } catch (PDOException $e) {
                $error = _('Database error: ') . $e->getMessage();
            }
        }
    }
}

// --- Fetch gateways ---
$gwPage = max(1, intval($_GET['gw_page'] ?? 1));
$perPage = 25;
$countStmt = $pdo->query('SELECT COUNT(*) FROM dr_gateways');
$gwTotal = $countStmt->fetchColumn();
$gwPages = max(1, ceil($gwTotal / $perPage));
$stmt = $pdo->prepare('SELECT * FROM dr_gateways ORDER BY enabled DESC, gwid LIMIT ? OFFSET ?');
$stmt->execute([$perPage, ($gwPage - 1) * $perPage]);
$gateways = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Fetch rules ---
$rulePage = max(1, intval($_GET['rule_page'] ?? 1));
$countStmt = $pdo->query('SELECT COUNT(*) FROM dr_rules');
$ruleTotal = $countStmt->fetchColumn();
$rulePages = max(1, ceil($ruleTotal / $perPage));
$stmt = $pdo->prepare('SELECT * FROM dr_rules ORDER BY enabled DESC, group_id, priority DESC LIMIT ? OFFSET ?');
$stmt->execute([$perPage, ($rulePage - 1) * $perPage]);
$rules = $stmt->fetchAll(PDO::FETCH_ASSOC);

$editGw = null;
if (isset($_GET['edit_gw'])) {
    $stmt = $pdo->prepare('SELECT * FROM dr_gateways WHERE id=?');
    $stmt->execute([$_GET['edit_gw']]);
    $editGw = $stmt->fetch(PDO::FETCH_ASSOC);
}
$editRule = null;
if (isset($_GET['edit_rule'])) {
    $stmt = $pdo->prepare('SELECT * FROM dr_rules WHERE id=?');
    $stmt->execute([$_GET['edit_rule']]);
    $editRule = $stmt->fetch(PDO::FETCH_ASSOC);
}

$csrfToken = generateCsrfToken();
require_once __DIR__ . '/common/header.php';
?>

<div class="tsisip-page">
    <header class="tsisip-page-header">
        <h1 class="tsisip-page-title"><?php echo _('Dynamic Routing'); ?></h1>
        <p class="tsisip-page-subtitle"><?php echo _('LCR / dynamic routing gateway and rule management'); ?></p>
    </header>

    <?php if ($error): ?>
        <div class="tsisip-alert tsisip-alert--error" role="alert"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="tsisip-alert tsisip-alert--success" role="alert"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo _('Gateways'); ?></h2>
        <table class="tsisip-table">
            <thead>
                <tr>
                    <th><?php echo _('GWID'); ?></th>
                    <th><?php echo _('Type'); ?></th>
                    <th><?php echo _('Address'); ?></th>
                    <th><?php echo _('Strip'); ?></th>
                    <th><?php echo _('Prefix'); ?></th>
                    <th><?php echo _('Probe'); ?></th>
                    <th><?php echo _('Status'); ?></th>
                    <th><?php echo _('Actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($gateways)): ?>
                    <tr><td colspan="8" class="tsisip-empty"><?php echo _('No gateways found.'); ?></td></tr>
                <?php else: ?>
                    <?php foreach ($gateways as $g): ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($g['gwid']); ?></code></td>
                            <td><?php echo $g['type']; ?></td>
                            <td><code><?php echo htmlspecialchars($g['address']); ?></code></td>
                            <td><?php echo htmlspecialchars($g['strip']); ?></td>
                            <td><?php echo htmlspecialchars($g['pri_prefix']); ?></td>
                            <td><?php echo htmlspecialchars($g['probe_mode']); ?></td>
                            <td><?php echo $g['enabled'] ? '<span class="tsisip-tag tsisip-tag--success">'._('Enabled').'</span>' : '<span class="tsisip-tag tsisip-tag--muted">'._('Disabled').'</span>'; ?></td>
                            <td>
                                <a href="?edit_gw=<?php echo $g['id']; ?>" class="tsisip-btn tsisip-btn--sm"><?php echo _('Edit'); ?></a>
                                <form method="post" style="display:inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    <input type="hidden" name="action" value="delete_gw">
                                    <input type="hidden" name="id" value="<?php echo $g['id']; ?>">
                                    <button type="submit" class="tsisip-btn tsisip-btn--danger tsisip-btn--sm"
                                            onclick="return confirm('<?php echo _('Delete this gateway?'); ?>')">
                                        <?php echo _('Delete'); ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php renderPagination($gwPage, $gwPages, $gwTotal, $perPage, 'dynamic-routing.php', ['rule_page' => $rulePage], 'gw_page'); ?>
    </section>

    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo $editGw ? _('Edit Gateway') : _('Add Gateway'); ?></h2>
        <form method="post" class="tsisip-form">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="action" value="<?php echo $editGw ? 'update_gw' : 'create_gw'; ?>">
            <?php if ($editGw): ?><input type="hidden" name="id" value="<?php echo $editGw['id']; ?>"><?php endif; ?>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Gateway ID'); ?></label>
                <input type="text" name="gwid" required value="<?php echo $editGw ? htmlspecialchars($editGw['gwid']) : ''; ?>" class="tsisip-input">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Type'); ?></label>
                <input type="number" name="type" value="<?php echo $editGw ? intval($editGw['type']) : '0'; ?>" class="tsisip-input">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Address'); ?></label>
                <input type="text" name="address" required value="<?php echo $editGw ? htmlspecialchars($editGw['address']) : ''; ?>" class="tsisip-input" placeholder="sip:gw1.provider.com:5060">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Strip'); ?></label>
                <input type="text" name="strip" value="<?php echo $editGw ? htmlspecialchars($editGw['strip']) : ''; ?>" class="tsisip-input">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Prefix'); ?></label>
                <input type="text" name="pri_prefix" value="<?php echo $editGw ? htmlspecialchars($editGw['pri_prefix']) : ''; ?>" class="tsisip-input">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Attrs'); ?></label>
                <input type="text" name="attrs" value="<?php echo $editGw ? htmlspecialchars($editGw['attrs']) : ''; ?>" class="tsisip-input">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Probe Mode'); ?></label>
                <select name="probe_mode" class="tsisip-select">
                    <option value="none" <?php echo !$editGw || $editGw['probe_mode'] === 'none' ? 'selected' : ''; ?>><?php echo _('None'); ?></option>
                    <option value="on" <?php echo $editGw && $editGw['probe_mode'] === 'on' ? 'selected' : ''; ?>><?php echo _('On'); ?></option>
                    <option value="passive" <?php echo $editGw && $editGw['probe_mode'] === 'passive' ? 'selected' : ''; ?>><?php echo _('Passive'); ?></option>
                </select>
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Description'); ?></label>
                <input type="text" name="description" value="<?php echo $editGw ? htmlspecialchars($editGw['description']) : ''; ?>" class="tsisip-input">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-checkbox">
                    <input type="checkbox" name="enabled" <?php echo (!$editGw || $editGw['enabled']) ? 'checked' : ''; ?>>
                    <?php echo _('Enabled'); ?>
                </label>
            </div>
            <button type="submit" class="tsisip-btn tsisip-btn--primary"><?php echo $editGw ? _('Update') : _('Create'); ?></button>
            <?php if ($editGw): ?><a href="dynamic-routing.php" class="tsisip-btn tsisip-btn--secondary"><?php echo _('Cancel'); ?></a><?php endif; ?>
        </form>
    </section>

    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo _('Routing Rules'); ?></h2>
        <table class="tsisip-table">
            <thead>
                <tr>
                    <th><?php echo _('Group'); ?></th>
                    <th><?php echo _('Priority'); ?></th>
                    <th><?php echo _('Prefix'); ?></th>
                    <th><?php echo _('Time Rec'); ?></th>
                    <th><?php echo _('Route ID'); ?></th>
                    <th><?php echo _('GW List'); ?></th>
                    <th><?php echo _('Status'); ?></th>
                    <th><?php echo _('Actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rules)): ?>
                    <tr><td colspan="8" class="tsisip-empty"><?php echo _('No routing rules found.'); ?></td></tr>
                <?php else: ?>
                    <?php foreach ($rules as $r): ?>
                        <tr>
                            <td><?php echo $r['group_id']; ?></td>
                            <td><?php echo $r['priority']; ?></td>
                            <td><code><?php echo htmlspecialchars($r['prefix']); ?></code></td>
                            <td><?php echo htmlspecialchars($r['timerec']); ?></td>
                            <td><?php echo htmlspecialchars($r['routeid']); ?></td>
                            <td><code class="tsisip-code--sm"><?php echo htmlspecialchars(substr($r['gwlist'], 0, 40)); ?></code></td>
                            <td><?php echo $r['enabled'] ? '<span class="tsisip-tag tsisip-tag--success">'._('Enabled').'</span>' : '<span class="tsisip-tag tsisip-tag--muted">'._('Disabled').'</span>'; ?></td>
                            <td>
                                <a href="?edit_rule=<?php echo $r['id']; ?>" class="tsisip-btn tsisip-btn--sm"><?php echo _('Edit'); ?></a>
                                <form method="post" style="display:inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    <input type="hidden" name="action" value="delete_rule">
                                    <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                                    <button type="submit" class="tsisip-btn tsisip-btn--danger tsisip-btn--sm"
                                            onclick="return confirm('<?php echo _('Delete this rule?'); ?>')">
                                        <?php echo _('Delete'); ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php renderPagination($rulePage, $rulePages, $ruleTotal, $perPage, 'dynamic-routing.php', ['gw_page' => $gwPage], 'rule_page'); ?>
    </section>

    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo $editRule ? _('Edit Routing Rule') : _('Add Routing Rule'); ?></h2>
        <form method="post" class="tsisip-form">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="action" value="<?php echo $editRule ? 'update_rule' : 'create_rule'; ?>">
            <?php if ($editRule): ?><input type="hidden" name="id" value="<?php echo $editRule['id']; ?>"><?php endif; ?>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Group ID'); ?></label>
                <input type="number" name="group_id" value="<?php echo $editRule ? intval($editRule['group_id']) : '0'; ?>" class="tsisip-input">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Priority'); ?></label>
                <input type="number" name="priority" value="<?php echo $editRule ? intval($editRule['priority']) : '0'; ?>" class="tsisip-input">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Prefix'); ?></label>
                <input type="text" name="prefix" value="<?php echo $editRule ? htmlspecialchars($editRule['prefix']) : ''; ?>" class="tsisip-input" placeholder="39">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Time Recurrence'); ?></label>
                <input type="text" name="timerec" value="<?php echo $editRule ? htmlspecialchars($editRule['timerec']) : ''; ?>" class="tsisip-input" placeholder="weekday,9,17">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Route ID'); ?></label>
                <input type="text" name="routeid" value="<?php echo $editRule ? htmlspecialchars($editRule['routeid']) : ''; ?>" class="tsisip-input">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Gateway List'); ?></label>
                <input type="text" name="gwlist" required value="<?php echo $editRule ? htmlspecialchars($editRule['gwlist']) : ''; ?>" class="tsisip-input" placeholder="gw1,gw2=3">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Attrs'); ?></label>
                <input type="text" name="attrs" value="<?php echo $editRule ? htmlspecialchars($editRule['attrs']) : ''; ?>" class="tsisip-input">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Description'); ?></label>
                <input type="text" name="description" value="<?php echo $editRule ? htmlspecialchars($editRule['description']) : ''; ?>" class="tsisip-input">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-checkbox">
                    <input type="checkbox" name="enabled" <?php echo (!$editRule || $editRule['enabled']) ? 'checked' : ''; ?>>
                    <?php echo _('Enabled'); ?>
                </label>
            </div>
            <button type="submit" class="tsisip-btn tsisip-btn--primary"><?php echo $editRule ? _('Update') : _('Create'); ?></button>
            <?php if ($editRule): ?><a href="dynamic-routing.php" class="tsisip-btn tsisip-btn--secondary"><?php echo _('Cancel'); ?></a><?php endif; ?>
        </form>
    </section>
</div>

<?php require_once __DIR__ . '/common/footer.php'; ?>
