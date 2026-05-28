<?php
/**
 * TSiSIP Control Panel — IP Whitelist (permissions module)
 * CRUD for the address table (OpenSIPS permissions module trusted gateway IPs).
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = _('Invalid CSRF token.');
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'create') {
            $grp = intval($_POST['grp'] ?? 0);
            $ip = trim($_POST['ip'] ?? '');
            $mask = intval($_POST['mask'] ?? 32);
            $port = intval($_POST['port'] ?? 0);
            $proto = trim($_POST['proto'] ?? 'any');
            if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP)) {
                $error = _('Valid IP address is required.');
            } else {
                try {
                    $stmt = $pdo->prepare("INSERT INTO address (grp, ip, mask, port, proto) VALUES (:grp, :ip, :mask, :port, :proto)");
                    $stmt->execute([':grp' => $grp, ':ip' => $ip, ':mask' => $mask, ':port' => $port, ':proto' => $proto]);
                    $success = _('Address added successfully.');
                    logAuditEvent('ADDRESS_CREATE', 'address', $ip, true);
                } catch (PDOException $e) {
                    $error = _('Failed to add address: ') . $e->getMessage();
                }
            }
        } elseif ($action === 'delete') {
            $id = $_POST['id'] ?? '';
            if ($id !== '') {
                try {
                    $stmt = $pdo->prepare("DELETE FROM address WHERE id = :id");
                    $stmt->execute([':id' => $id]);
                    $success = _('Address deleted successfully.');
                    logAuditEvent('ADDRESS_DELETE', 'address', $id, true);
                } catch (PDOException $e) {
                    $error = _('Failed to delete address: ') . $e->getMessage();
                }
            }
        }
    }
}

$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 25;
$pagination = getPagination($page, $perPage);

$countStmt = $pdo->query("SELECT COUNT(*) FROM address");
$totalItems = (int) $countStmt->fetchColumn();

$listStmt = $pdo->prepare("SELECT id, grp, ip, mask, port, proto FROM address ORDER BY grp, ip LIMIT :limit OFFSET :offset");
$listStmt->bindValue(':limit', $pagination['limit'], PDO::PARAM_INT);
$listStmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
$listStmt->execute();
$entries = $listStmt->fetchAll();

require_once __DIR__ . '/common/header.php';
?>
<div id="content" class="tsisip-dashboard">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
        <h2><?php echo _('IP Whitelist'); ?></h2>
        <?php if (isDevOpsOrHigher()): ?>
            <button id="btn-address-reload" class="tsisip-btn tsisip-btn-primary"><?php echo _('Reload Address Table'); ?></button>
        <?php endif; ?>
    </div>
    <p class="tsisip-text-muted"><?php echo _('Trusted gateway IPs for the OpenSIPS permissions module (check_source_address).'); ?></p>

    <?php if ($error): ?>
        <div class="tsisip-alert tsisip-alert--error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="tsisip-alert tsisip-alert--success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="tsisip-dashboard-section">
        <h3><?php echo _('Add Trusted IP'); ?></h3>
        <form method="POST" action="" class="tsisip-form">
            <?php echo csrfInput(); ?>
            <input type="hidden" name="action" value="create">
            <div class="tsisip-form-group">
                <label for="ip"><?php echo _('IP Address'); ?></label>
                <input type="text" id="ip" name="ip" class="tsisip-input" required placeholder="192.0.2.1">
            </div>
            <div class="tsisip-form-group">
                <label for="mask"><?php echo _('CIDR Mask'); ?></label>
                <input type="number" id="mask" name="mask" class="tsisip-input" value="32" min="0" max="128">
            </div>
            <div class="tsisip-form-group">
                <label for="grp"><?php echo _('Group ID'); ?></label>
                <input type="number" id="grp" name="grp" class="tsisip-input" value="0" min="0">
            </div>
            <div class="tsisip-form-group">
                <label for="port"><?php echo _('Port (0 = any)'); ?></label>
                <input type="number" id="port" name="port" class="tsisip-input" value="0" min="0" max="65535">
            </div>
            <div class="tsisip-form-group">
                <label for="proto"><?php echo _('Protocol'); ?></label>
                <select id="proto" name="proto" class="tsisip-input">
                    <option value="any">any</option>
                    <option value="udp">UDP</option>
                    <option value="tcp">TCP</option>
                    <option value="tls">TLS</option>
                    <option value="ws">WS</option>
                    <option value="wss">WSS</option>
                </select>
            </div>
            <button type="submit" class="tsisip-btn tsisip-btn-primary"><?php echo _('Add Address'); ?></button>
        </form>
    </div>

    <div class="tsisip-dashboard-section">
        <h3><?php echo _('Trusted Addresses'); ?> (<?php echo $totalItems; ?>)</h3>
        <table class="dataTable tsisip-table">
            <thead>
                <tr><th><?php echo _('Group'); ?></th><th><?php echo _('IP'); ?></th><th><?php echo _('Mask'); ?></th><th><?php echo _('Port'); ?></th><th><?php echo _('Proto'); ?></th><th><?php echo _('Actions'); ?></th></tr>
            </thead>
            <tbody>
                <?php foreach ($entries as $e): ?>
                <tr>
                    <td><?php echo htmlspecialchars($e['grp']); ?></td>
                    <td><?php echo htmlspecialchars($e['ip']); ?></td>
                    <td><?php echo htmlspecialchars($e['mask']); ?></td>
                    <td><?php echo $e['port'] ? htmlspecialchars($e['port']) : _('any'); ?></td>
                    <td><?php echo htmlspecialchars($e['proto']); ?></td>
                    <td class="tsisip-actions-column">
                        <form method="POST" action="" style="display:inline" onsubmit="return confirm('<?php echo _('Delete this address?'); ?>');">
                            <?php echo csrfInput(); ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($e['id'], ENT_QUOTES); ?>">
                            <button type="submit" class="tsisip-btn tsisip-btn-danger"><?php echo _('Delete'); ?></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php echo renderPagination($page, $totalItems, $perPage, 'address.php'); ?>
    </div>
</div>
<script>
<?php if (isDevOpsOrHigher()): ?>
TSiSIPMi.attachReload('#btn-address-reload', 'address_reload');
<?php endif; ?>
</script>
<?php require_once __DIR__ . '/common/footer.php'; ?>
