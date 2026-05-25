<?php
/**
 * TSiSIP Control Panel — Sockets Management
 * Dynamic socket provisioning via database (OCP 9.3.6+)
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
            $proto   = trim($_POST['proto'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $port    = intval($_POST['port'] ?? 0);
            $options = trim($_POST['options'] ?? '');
            $description = trim($_POST['description'] ?? '');

            $validProtos = ['udp', 'tcp', 'tls', 'ws', 'wss', 'sctp', 'hep'];
            if (!in_array(strtolower($proto), $validProtos)) {
                $error = _('Invalid protocol. Valid: udp, tcp, tls, ws, wss, sctp, hep');
            } elseif ($address === '' || $port < 1 || $port > 65535) {
                $error = _('Valid address and port (1-65535) are required.');
            } else {
                if ($action === 'create') {
                    try {
                        $stmt = $pdo->prepare(
                            'INSERT INTO sockets (proto, address, port, options, description) VALUES (?, ?, ?, ?, ?)'
                        );
                        $stmt->execute([$proto, $address, $port, $options, $description]);
                        $success = _('Socket created successfully.');
                        logAuditEvent('SOCKET_CREATE', 'socket', "$proto:$address:$port", true);
                    } catch (PDOException $e) {
                        $error = _('Database error: ') . $e->getMessage();
                    }
                } else {
                    $id = $_POST['id'] ?? '';
                    try {
                        $stmt = $pdo->prepare(
                            'UPDATE sockets SET proto=?, address=?, port=?, options=?, description=?, last_modified=NOW() WHERE id=?'
                        );
                        $stmt->execute([$proto, $address, $port, $options, $description, $id]);
                        $success = _('Socket updated successfully.');
                        logAuditEvent('SOCKET_UPDATE', 'socket', "$proto:$address:$port", true);
                    } catch (PDOException $e) {
                        $error = _('Database error: ') . $e->getMessage();
                    }
                }
            }
        } elseif ($action === 'delete') {
            $id = $_POST['id'] ?? '';
            try {
                $stmt = $pdo->prepare('SELECT proto, address, port FROM sockets WHERE id=?');
                $stmt->execute([$id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $stmt = $pdo->prepare('DELETE FROM sockets WHERE id=?');
                    $stmt->execute([$id]);
                    $success = _('Socket deleted successfully.');
                    logAuditEvent('SOCKET_DELETE', 'socket', $row['proto'] . ':' . $row['address'] . ':' . $row['port'], true);
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
    $where[] = '(proto ILIKE ? OR address ILIKE ? OR description ILIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM sockets $whereSql");
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));

$offset = ($page - 1) * $perPage;
$stmt = $pdo->prepare("SELECT * FROM sockets $whereSql ORDER BY proto, address LIMIT ? OFFSET ?");
$stmt->execute(array_merge($params, [$perPage, $offset]));
$sockets = $stmt->fetchAll(PDO::FETCH_ASSOC);

$csrfToken = generateCsrfToken();
require_once __DIR__ . '/common/header.php';
?>

<div class="tsisip-page">
    <header class="tsisip-page-header">
        <h1 class="tsisip-page-title"><?php echo _('Sockets Management'); ?></h1>
        <p class="tsisip-page-subtitle"><?php echo _('Dynamic socket provisioning via database (OCP 9.3.6+)'); ?></p>
    </header>

    <?php if ($error): ?>
        <div class="tsisip-alert tsisip-alert--error" role="alert"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="tsisip-alert tsisip-alert--success" role="alert"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <section class="tsisip-section">
        <form method="get" class="tsisip-filter-bar">
            <input type="text" name="search" placeholder="<?php echo _('Search sockets...'); ?>"
                   value="<?php echo htmlspecialchars($search); ?>" class="tsisip-input">
            <button type="submit" class="tsisip-btn tsisip-btn--primary"><?php echo _('Filter'); ?></button>
            <a href="sockets-management.php" class="tsisip-btn tsisip-btn--secondary"><?php echo _('Clear'); ?></a>
        </form>
    </section>

    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo _('Socket Definitions'); ?></h2>
        <table class="tsisip-table">
            <thead>
                <tr>
                    <th><?php echo _('Protocol'); ?></th>
                    <th><?php echo _('Address'); ?></th>
                    <th><?php echo _('Port'); ?></th>
                    <th><?php echo _('Options'); ?></th>
                    <th><?php echo _('Description'); ?></th>
                    <th><?php echo _('Modified'); ?></th>
                    <th><?php echo _('Actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($sockets)): ?>
                    <tr><td colspan="7" class="tsisip-empty"><?php echo _('No sockets found.'); ?></td></tr>
                <?php else: ?>
                    <?php foreach ($sockets as $s): ?>
                        <tr>
                            <td><span class="tsisip-badge"><?php echo htmlspecialchars(strtoupper($s['proto'])); ?></span></td>
                            <td><code><?php echo htmlspecialchars($s['address']); ?></code></td>
                            <td><?php echo $s['port']; ?></td>
                            <td><code><?php echo htmlspecialchars($s['options'] ?? ''); ?></code></td>
                            <td><?php echo htmlspecialchars($s['description'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($s['last_modified']); ?></td>
                            <td>
                                <form method="post" style="display:inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                                    <button type="submit" class="tsisip-btn tsisip-btn--danger tsisip-btn--sm"
                                            onclick="return confirm('<?php echo _('Delete this socket?'); ?>')">
                                        <?php echo _('Delete'); ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php renderPagination($page, $totalPages, $totalRows, $perPage, 'sockets-management.php', ['search' => $search]); ?>
    </section>

    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo _('Add Socket'); ?></h2>
        <form method="post" class="tsisip-form">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="action" value="create">
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Protocol'); ?></label>
                <select name="proto" required class="tsisip-select">
                    <option value="udp">UDP</option>
                    <option value="tcp">TCP</option>
                    <option value="tls">TLS</option>
                    <option value="ws">WS</option>
                    <option value="wss">WSS</option>
                    <option value="sctp">SCTP</option>
                    <option value="hep">HEP</option>
                </select>
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Address'); ?></label>
                <input type="text" name="address" required class="tsisip-input" placeholder="0.0.0.0">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Port'); ?></label>
                <input type="number" name="port" required class="tsisip-input" min="1" max="65535" placeholder="5060">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Options'); ?></label>
                <input type="text" name="options" class="tsisip-input" placeholder="<?php echo _('Optional'); ?>">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Description'); ?></label>
                <input type="text" name="description" class="tsisip-input">
            </div>
            <button type="submit" class="tsisip-btn tsisip-btn--primary"><?php echo _('Create'); ?></button>
        </form>
    </section>
</div>

<?php require_once __DIR__ . '/common/footer.php'; ?>
