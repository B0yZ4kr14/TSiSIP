<?php
/**
 * TSiSIP Control Panel — UAC Registrant
 * Client registration provisioning for OpenSIPS UAC module
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

        if ($action === 'create' || $action === 'update') {
            $l_uuid  = trim($_POST['l_uuid'] ?? '');
            $l_user  = trim($_POST['l_username'] ?? '');
            $l_domain= trim($_POST['l_domain'] ?? '');
            $r_user  = trim($_POST['r_username'] ?? '');
            $r_domain= trim($_POST['r_domain'] ?? '');
            $auth_proxy = trim($_POST['auth_proxy'] ?? '');
            $expires = intval($_POST['expires'] ?? 3600);
            $flags   = intval($_POST['flags'] ?? 0);

            if ($l_uuid === '' || $l_user === '' || $r_user === '' || $auth_proxy === '') {
                $error = _('UUID, local username, remote username, and auth proxy are required.');
            } else {
                if ($action === 'create') {
                    try {
                        $stmt = $pdo->prepare(
                            'INSERT INTO uacreg (l_uuid, l_username, l_domain, r_username, r_domain, auth_proxy, expires, flags) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
                        );
                        $stmt->execute([$l_uuid, $l_user, $l_domain, $r_user, $r_domain, $auth_proxy, $expires, $flags]);
                        $success = _('UAC registration created successfully.');
                        logAuditEvent('UAC_CREATE', 'uac', $l_uuid, true);
                    } catch (PDOException $e) {
                        if (strpos($e->getMessage(), 'unique constraint') !== false) {
                            $error = _('A UAC registration with this UUID already exists.');
                        } else {
                            $error = _('Database error: ') . $e->getMessage();
                        }
                    }
                } else {
                    $id = $_POST['id'] ?? '';
                    try {
                        $stmt = $pdo->prepare(
                            'UPDATE uacreg SET l_uuid=?, l_username=?, l_domain=?, r_username=?, r_domain=?, auth_proxy=?, expires=?, flags=?, last_modified=NOW() WHERE id=?'
                        );
                        $stmt->execute([$l_uuid, $l_user, $l_domain, $r_user, $r_domain, $auth_proxy, $expires, $flags, $id]);
                        $success = _('UAC registration updated successfully.');
                        logAuditEvent('UAC_UPDATE', 'uac', $l_uuid, true);
                    } catch (PDOException $e) {
                        $error = _('Database error: ') . $e->getMessage();
                    }
                }
            }
        } elseif ($action === 'delete') {
            $id = $_POST['id'] ?? '';
            try {
                $stmt = $pdo->prepare('SELECT l_uuid FROM uacreg WHERE id=?');
                $stmt->execute([$id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $stmt = $pdo->prepare('DELETE FROM uacreg WHERE id=?');
                    $stmt->execute([$id]);
                    $success = _('UAC registration deleted successfully.');
                    logAuditEvent('UAC_DELETE', 'uac', $row['l_uuid'], true);
                }
            } catch (PDOException $e) {
                $error = _('Database error: ') . $e->getMessage();
            }
        }
    }
}

// --- Fetch list ---
$page   = max(1, intval($_GET['page'] ?? 1));
$search = trim($_GET['search'] ?? '');
$perPage = 25;

$where = [];
$params = [];
if ($search !== '') {
    $where[] = '(l_uuid ILIKE ? OR l_username ILIKE ? OR r_username ILIKE ? OR auth_proxy ILIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM uacreg $whereSql");
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));

$offset = ($page - 1) * $perPage;
$stmt = $pdo->prepare("SELECT * FROM uacreg $whereSql ORDER BY l_uuid LIMIT ? OFFSET ?");
$stmt->execute(array_merge($params, [$perPage, $offset]));
$regs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$csrfToken = generateCsrfToken();

// Fetch live UAC reg status for enable/disable state
$liveRegs = [];
$miRegs = miHttpCall('uac_reg_list');
if ($miRegs['success'] && is_array($miRegs['data'])) {
    $raw = $miRegs['data'];
    if (isset($raw['Gateways']) && is_array($raw['Gateways'])) {
        $raw = $raw['Gateways'];
    }
    foreach ($raw as $item) {
        if (is_array($item) && isset($item['l_uuid'])) {
            $liveRegs[$item['l_uuid']] = $item;
        }
    }
}

require_once __DIR__ . '/common/header.php';
?>

<div class="tsisip-page">
    <header class="tsisip-page-header">
        <h1 class="tsisip-page-title"><?php echo _('UAC Registrant'); ?></h1>
        <p class="tsisip-page-subtitle"><?php echo _('Client registration provisioning for OpenSIPS'); ?></p>
    </header>

    <?php if ($error): ?>
        <div class="tsisip-alert tsisip-alert--error" role="alert"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="tsisip-alert tsisip-alert--success" role="alert"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <section class="tsisip-section">
        <form method="get" class="tsisip-filter-bar">
            <input type="text" name="search" placeholder="<?php echo _('Search registrations...'); ?>"
                   value="<?php echo htmlspecialchars($search); ?>" class="tsisip-input">
            <button type="submit" class="tsisip-btn tsisip-btn--primary"><?php echo _('Filter'); ?></button>
            <a href="uac-registrant.php" class="tsisip-btn tsisip-btn--secondary"><?php echo _('Clear'); ?></a>
        </form>
    </section>

    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo _('Registrations'); ?></h2>
        <table class="tsisip-table">
            <thead>
                <tr>
                    <th><?php echo _('UUID'); ?></th>
                    <th><?php echo _('Local User'); ?></th>
                    <th><?php echo _('Remote User'); ?></th>
                    <th><?php echo _('Auth Proxy'); ?></th>
                    <th><?php echo _('Expires'); ?></th>
                    <th><?php echo _('Flags'); ?></th>
                    <th><?php echo _('Actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($regs)): ?>
                    <tr><td colspan="7" class="tsisip-empty"><?php echo _('No registrations found.'); ?></td></tr>
                <?php else: ?>
                    <?php foreach ($regs as $r): ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($r['l_uuid']); ?></code></td>
                            <td><?php echo htmlspecialchars($r['l_username'] . '@' . $r['l_domain']); ?></td>
                            <td><?php echo htmlspecialchars($r['r_username'] . '@' . $r['r_domain']); ?></td>
                            <td><code><?php echo htmlspecialchars($r['auth_proxy']); ?></code></td>
                            <td><?php echo $r['expires']; ?>s</td>
                            <td><?php echo $r['flags']; ?></td>
                            <td>
                                <?php if (isDevOpsOrHigher()): ?>
                                    <button type="button" class="tsisip-btn tsisip-btn--secondary tsisip-btn--sm btn-uac-refresh"
                                            data-uuid="<?php echo htmlspecialchars($r['l_uuid'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo _('Refresh'); ?>
                                    </button>
                                    <?php
                                    $live = $liveRegs[$r['l_uuid']] ?? null;
                                    $isEnabled = $live ? !(isset($live['disabled']) && $live['disabled']) : true;
                                    ?>
                                    <button type="button" class="tsisip-btn tsisip-btn--<?php echo $isEnabled ? 'danger' : 'success'; ?> tsisip-btn--sm btn-uac-toggle"
                                            data-uuid="<?php echo htmlspecialchars($r['l_uuid'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-state="<?php echo $isEnabled ? 'on' : 'off'; ?>">
                                        <?php echo $isEnabled ? _('Disable') : _('Enable'); ?>
                                    </button>
                                <?php endif; ?>
                                <form method="post" style="display:inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                                    <button type="submit" class="tsisip-btn tsisip-btn--danger tsisip-btn--sm"
                                            onclick="return confirm('<?php echo _('Delete this registration?'); ?>')">
                                        <?php echo _('Delete'); ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php renderPagination($page, $totalPages, $totalRows, $perPage, 'uac-registrant.php', ['search' => $search]); ?>
    </section>

    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo _('Add Registration'); ?></h2>
        <form method="post" class="tsisip-form">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="action" value="create">
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('UUID'); ?></label>
                <input type="text" name="l_uuid" required class="tsisip-input">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Local Username'); ?></label>
                <input type="text" name="l_username" required class="tsisip-input">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Local Domain'); ?></label>
                <input type="text" name="l_domain" class="tsisip-input">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Remote Username'); ?></label>
                <input type="text" name="r_username" required class="tsisip-input">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Remote Domain'); ?></label>
                <input type="text" name="r_domain" required class="tsisip-input">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Auth Proxy'); ?></label>
                <input type="text" name="auth_proxy" required class="tsisip-input" placeholder="sip:proxy.example.com:5060">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Expires (seconds)'); ?></label>
                <input type="number" name="expires" value="3600" class="tsisip-input">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Flags'); ?></label>
                <input type="number" name="flags" value="0" class="tsisip-input">
            </div>
            <button type="submit" class="tsisip-btn tsisip-btn--primary"><?php echo _('Create'); ?></button>
        </form>
    </section>
</div>

<script>
<?php if (isDevOpsOrHigher()): ?>
document.querySelectorAll('.btn-uac-refresh').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        btn.disabled = true;
        TSiSIPMi.action('uac_reg_refresh', [btn.dataset.uuid], function() {
            btn.disabled = false;
        }, function() {
            btn.disabled = false;
        });
    });
});
document.querySelectorAll('.btn-uac-toggle').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        var isOn = btn.dataset.state === 'on';
        var cmd = isOn ? 'uac_reg_disable' : 'uac_reg_enable';
        btn.disabled = true;
        TSiSIPMi.action(cmd, [btn.dataset.uuid], function() {
            btn.disabled = false;
            btn.dataset.state = isOn ? 'off' : 'on';
            btn.textContent = isOn ? <?php echo json_encode(_('Enable')); ?> : <?php echo json_encode(_('Disable')); ?>;
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
