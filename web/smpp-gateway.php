<?php
/**
 * TSiSIP Control Panel — SMPP Gateway
 * SMS gateway management (smpp module)
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

        if ($action === 'create' || $action === 'update') {
            $id   = $_POST['id'] ?? null;
            $name = trim($_POST['name'] ?? '');
            $smsc = trim($_POST['ip'] ?? '');
            $port = intval($_POST['port'] ?? 2775);
            $sysType = trim($_POST['system_type'] ?? '');
            $sysId   = trim($_POST['system_id'] ?? '');
            $passwd  = $_POST['password'] ?? '';
            $srcAddr = trim($_POST['src_addr'] ?? '');
            $enabled = isset($_POST['enabled']) ? 1 : 0;

            if ($name === '' || $smsc === '' || $sysId === '') {
                $error = _('Name, SMSC IP and System ID are required.');
            } else {
                try {
                    if ($action === 'create') {
                        $stmt = $pdo->prepare(
                            'INSERT INTO smpp (name, ip, port, system_type, system_id, passwd, src_addr, enabled)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
                        );
                        $stmt->execute([$name, $smsc, $port, $sysType, $sysId, $passwd, $srcAddr, $enabled]);
                        $success = _('SMPP gateway created successfully.');
                        logAuditEvent('SMPP_CREATE', 'smpp-gateway', $name, true, ['smsc' => "$smsc:$port"]);
                    } else {
                        $stmt = $pdo->prepare(
                            'UPDATE smpp SET name=?, ip=?, port=?, system_type=?, system_id=?,
                             passwd=?, src_addr=?, enabled=? WHERE id=?'
                        );
                        $stmt->execute([$name, $smsc, $port, $sysType, $sysId, $passwd, $srcAddr, $enabled, $id]);
                        $success = _('SMPP gateway updated successfully.');
                        logAuditEvent('SMPP_UPDATE', 'smpp-gateway', $name, true);
                    }
                } catch (PDOException $e) {
                    $error = _('Database error: ') . $e->getMessage();
                }
            }
        } elseif ($action === 'delete') {
            $id = $_POST['id'] ?? '';
            try {
                $stmt = $pdo->prepare('SELECT name FROM smpp WHERE id=?');
                $stmt->execute([$id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $stmt = $pdo->prepare('DELETE FROM smpp WHERE id=?');
                    $stmt->execute([$id]);
                    $success = _('SMPP gateway deleted successfully.');
                    logAuditEvent('SMPP_DELETE', 'smpp-gateway', $row['name'], true);
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
    $where[] = '(name ILIKE ? OR ip ILIKE ? OR system_id ILIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM smpp $whereSql");
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));

$offset = ($page - 1) * $perPage;
$stmt = $pdo->prepare("SELECT * FROM smpp $whereSql ORDER BY enabled DESC, name LIMIT ? OFFSET ?");
$stmt->execute(array_merge($params, [$perPage, $offset]));
$gateways = $stmt->fetchAll(PDO::FETCH_ASSOC);

$editRow = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM smpp WHERE id=?');
    $stmt->execute([$_GET['edit']]);
    $editRow = $stmt->fetch(PDO::FETCH_ASSOC);
}

$csrfToken = generateCsrfToken();
require_once __DIR__ . '/common/header.php';
?>

<div class="tsisip-page">
    <header class="tsisip-page-header">
        <h1 class="tsisip-page-title"><?php echo _('SMPP Gateway'); ?></h1>
        <p class="tsisip-page-subtitle"><?php echo _('SMS gateway management'); ?></p>
    </header>

    <?php if ($error): ?>
        <div class="tsisip-alert tsisip-alert--error" role="alert"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="tsisip-alert tsisip-alert--success" role="alert"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <section class="tsisip-section">
        <form method="get" class="tsisip-filter-bar">
            <input type="text" name="search" placeholder="<?php echo _('Search...'); ?>"
                   value="<?php echo htmlspecialchars($search); ?>" class="tsisip-input">
            <button type="submit" class="tsisip-btn tsisip-btn--primary"><?php echo _('Filter'); ?></button>
            <a href="smpp-gateway.php" class="tsisip-btn tsisip-btn--secondary"><?php echo _('Clear'); ?></a>
        </form>
    </section>

    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo _('SMPP Gateways'); ?></h2>
        <table class="tsisip-table">
            <thead>
                <tr>
                    <th><?php echo _('Name'); ?></th>
                    <th><?php echo _('SMSC'); ?></th>
                    <th><?php echo _('System ID'); ?></th>
                    <th><?php echo _('Source Addr'); ?></th>
                    <th><?php echo _('Status'); ?></th>
                    <th><?php echo _('Actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($gateways)): ?>
                    <tr><td colspan="6" class="tsisip-empty"><?php echo _('No SMPP gateways found.'); ?></td></tr>
                <?php else: ?>
                    <?php foreach ($gateways as $g): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($g['name']); ?></td>
                            <td><code><?php echo htmlspecialchars($g['ip'] . ':' . $g['port']); ?></code></td>
                            <td><?php echo htmlspecialchars($g['system_id']); ?></td>
                            <td><?php echo htmlspecialchars($g['src_addr']); ?></td>
                            <td><?php echo $g['enabled'] ? '<span class="tsisip-tag tsisip-tag--success">'._('Enabled').'</span>' : '<span class="tsisip-tag tsisip-tag--muted">'._('Disabled').'</span>'; ?></td>
                            <td>
                                <a href="?edit=<?php echo $g['id']; ?>" class="tsisip-btn tsisip-btn--sm"><?php echo _('Edit'); ?></a>
                                <form method="post" style="display:inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    <input type="hidden" name="action" value="delete">
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
        <?php renderPagination($page, $totalPages, $totalRows, $perPage, 'smpp-gateway.php', ['search' => $search]); ?>
    </section>

    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo $editRow ? _('Edit SMPP Gateway') : _('Add SMPP Gateway'); ?></h2>
        <form method="post" class="tsisip-form">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="action" value="<?php echo $editRow ? 'update' : 'create'; ?>">
            <?php if ($editRow): ?><input type="hidden" name="id" value="<?php echo $editRow['id']; ?>"><?php endif; ?>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Name'); ?></label>
                <input type="text" name="name" required value="<?php echo $editRow ? htmlspecialchars($editRow['name']) : ''; ?>" class="tsisip-input">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('SMSC IP'); ?></label>
                <input type="text" name="ip" required value="<?php echo $editRow ? htmlspecialchars($editRow['ip']) : ''; ?>" class="tsisip-input" placeholder="10.0.0.1">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('SMSC Port'); ?></label>
                <input type="number" name="port" value="<?php echo $editRow ? intval($editRow['port']) : '2775'; ?>" class="tsisip-input">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('System Type'); ?></label>
                <input type="text" name="system_type" value="<?php echo $editRow ? htmlspecialchars($editRow['system_type']) : ''; ?>" class="tsisip-input">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('System ID'); ?></label>
                <input type="text" name="system_id" required value="<?php echo $editRow ? htmlspecialchars($editRow['system_id']) : ''; ?>" class="tsisip-input">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Password'); ?></label>
                <input type="password" name="password" class="tsisip-input">
                <?php if ($editRow): ?><small><?php echo _('Leave blank to keep existing.'); ?></small><?php endif; ?>
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Source Address'); ?></label>
                <input type="text" name="src_addr" value="<?php echo $editRow ? htmlspecialchars($editRow['src_addr']) : ''; ?>" class="tsisip-input">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-checkbox">
                    <input type="checkbox" name="enabled" <?php echo (!$editRow || $editRow['enabled']) ? 'checked' : ''; ?>>
                    <?php echo _('Enabled'); ?>
                </label>
            </div>
            <button type="submit" class="tsisip-btn tsisip-btn--primary"><?php echo $editRow ? _('Update') : _('Create'); ?></button>
            <?php if ($editRow): ?><a href="smpp-gateway.php" class="tsisip-btn tsisip-btn--secondary"><?php echo _('Cancel'); ?></a><?php endif; ?>
        </form>
    </section>
</div>

<?php require_once __DIR__ . '/common/footer.php'; ?>
