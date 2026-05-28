<?php
/**
 * TSiSIP Control Panel — Header Routing Rules
 * CRUD for the header_routing_rules table (tenant-scoped header-based routing metadata).
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
            $tenantId = $_POST['tenant_id'] ?? '00000000-0000-0000-0000-000000000000';
            $headerName = trim($_POST['header_name'] ?? '');
            $matchValue = trim($_POST['match_value'] ?? '');
            $dispatcherSetid = intval($_POST['dispatcher_setid'] ?? 1);
            $priority = intval($_POST['priority'] ?? 100);
            $enabled = isset($_POST['enabled']) ? true : false;
            if ($headerName === '' || $matchValue === '') {
                $error = _('Header name and match value are required.');
            } else {
                try {
                    $stmt = $pdo->prepare(
                        "INSERT INTO header_routing_rules (tenant_id, header_name, match_value, dispatcher_setid, priority, enabled)
                         VALUES (:tenant_id, :header_name, :match_value, :dispatcher_setid, :priority, :enabled)"
                    );
                    $stmt->execute([
                        ':tenant_id' => $tenantId, ':header_name' => $headerName, ':match_value' => $matchValue,
                        ':dispatcher_setid' => $dispatcherSetid, ':priority' => $priority, ':enabled' => $enabled
                    ]);
                    $success = _('Rule created successfully.');
                    logAuditEvent('HEADER_ROUTING_CREATE', 'header_routing', $headerName, true);
                } catch (PDOException $e) {
                    $error = _('Failed to create rule: ') . $e->getMessage();
                }
            }
        } elseif ($action === 'delete') {
            $id = $_POST['id'] ?? '';
            if ($id !== '') {
                try {
                    $stmt = $pdo->prepare("DELETE FROM header_routing_rules WHERE id = :id");
                    $stmt->execute([':id' => $id]);
                    $success = _('Rule deleted successfully.');
                    logAuditEvent('HEADER_ROUTING_DELETE', 'header_routing', $id, true);
                } catch (PDOException $e) {
                    $error = _('Failed to delete rule: ') . $e->getMessage();
                }
            }
        }
    }
}

$tenants = $pdo->query("SELECT id, name, sip_domain FROM tenants ORDER BY name")->fetchAll();

$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 25;
$pagination = getPagination($page, $perPage);

$countStmt = $pdo->query("SELECT COUNT(*) FROM header_routing_rules");
$totalItems = (int) $countStmt->fetchColumn();

$listStmt = $pdo->prepare(
    "SELECT h.id, h.header_name, h.match_value, h.dispatcher_setid, h.priority, h.enabled, h.created_at, t.name AS tenant_name
     FROM header_routing_rules h LEFT JOIN tenants t ON h.tenant_id = t.id ORDER BY h.priority DESC, h.created_at DESC LIMIT :limit OFFSET :offset"
);
$listStmt->bindValue(':limit', $pagination['limit'], PDO::PARAM_INT);
$listStmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
$listStmt->execute();
$rules = $listStmt->fetchAll();

require_once __DIR__ . '/common/header.php';
?>
<div id="content" class="tsisip-dashboard">
    <h2><?php echo _('Header Routing Rules'); ?></h2>
    <p class="tsisip-text-muted"><?php echo _('Tenant-scoped header-based routing metadata for dynamic SIP request dispatching.'); ?></p>

    <?php if ($error): ?>
        <div class="tsisip-alert tsisip-alert--error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="tsisip-alert tsisip-alert--success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="tsisip-dashboard-section">
        <h3><?php echo _('Add Rule'); ?></h3>
        <form method="POST" action="" class="tsisip-form">
            <?php echo csrfInput(); ?>
            <input type="hidden" name="action" value="create">
            <div class="tsisip-form-group">
                <label for="tenant_id"><?php echo _('Tenant'); ?></label>
                <select id="tenant_id" name="tenant_id" class="tsisip-input">
                    <?php foreach ($tenants as $t): ?>
                        <option value="<?php echo htmlspecialchars($t['id'], ENT_QUOTES); ?>">
                            <?php echo htmlspecialchars($t['name'] . ' (' . $t['sip_domain'] . ')', ENT_QUOTES); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="tsisip-form-group">
                <label for="header_name"><?php echo _('Header Name'); ?></label>
                <input type="text" id="header_name" name="header_name" class="tsisip-input" required placeholder="X-Route-Key">
            </div>
            <div class="tsisip-form-group">
                <label for="match_value"><?php echo _('Match Value'); ?></label>
                <input type="text" id="match_value" name="match_value" class="tsisip-input" required>
            </div>
            <div class="tsisip-form-group">
                <label for="dispatcher_setid"><?php echo _('Dispatcher Set ID'); ?></label>
                <input type="number" id="dispatcher_setid" name="dispatcher_setid" class="tsisip-input" value="1" min="1">
            </div>
            <div class="tsisip-form-group">
                <label for="priority"><?php echo _('Priority'); ?></label>
                <input type="number" id="priority" name="priority" class="tsisip-input" value="100" min="1">
            </div>
            <div class="tsisip-form-group">
                <label>
                    <input type="checkbox" name="enabled" checked>
                    <?php echo _('Enabled'); ?>
                </label>
            </div>
            <button type="submit" class="tsisip-btn tsisip-btn-primary"><?php echo _('Add Rule'); ?></button>
        </form>
    </div>

    <div class="tsisip-dashboard-section">
        <h3><?php echo _('Rules'); ?> (<?php echo $totalItems; ?>)</h3>
        <table class="dataTable tsisip-table">
            <thead>
                <tr>
                    <th><?php echo _('Tenant'); ?></th>
                    <th><?php echo _('Header'); ?></th>
                    <th><?php echo _('Match'); ?></th>
                    <th><?php echo _('Set ID'); ?></th>
                    <th><?php echo _('Priority'); ?></th>
                    <th><?php echo _('Status'); ?></th>
                    <th><?php echo _('Actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rules as $r): ?>
                <tr>
                    <td><?php echo htmlspecialchars($r['tenant_name'] ?? _('Default')); ?></td>
                    <td><?php echo htmlspecialchars($r['header_name']); ?></td>
                    <td><?php echo htmlspecialchars($r['match_value']); ?></td>
                    <td><?php echo htmlspecialchars($r['dispatcher_setid']); ?></td>
                    <td><?php echo htmlspecialchars($r['priority']); ?></td>
                    <td>
                        <?php if ($r['enabled']): ?>
                            <span class="tsisip-badge tsisip-badge-success"><?php echo _('Active'); ?></span>
                        <?php else: ?>
                            <span class="tsisip-badge tsisip-badge-error"><?php echo _('Disabled'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="tsisip-actions-column">
                        <form method="POST" action="" style="display:inline" onsubmit="return confirm('<?php echo _('Delete this rule?'); ?>');">
                            <?php echo csrfInput(); ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($r['id'], ENT_QUOTES); ?>">
                            <button type="submit" class="tsisip-btn tsisip-btn-danger"><?php echo _('Delete'); ?></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php echo renderPagination($page, $totalItems, $perPage, 'header-routing.php'); ?>
    </div>
</div>
<?php require_once __DIR__ . '/common/footer.php'; ?>
