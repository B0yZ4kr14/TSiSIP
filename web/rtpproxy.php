<?php
/**
 * TSiSIP Control Panel — RTPProxy
 * Legacy RTP proxy instance management (rtpproxy module)
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

        if ($action === 'create') {
            $sock   = trim($_POST['rtpproxy_sock'] ?? '');
            $setId  = intval($_POST['set_id'] ?? 0);

            if ($sock === '') {
                $error = _('RTPProxy socket is required.');
            } else {
                try {
                    $stmt = $pdo->prepare(
                        'INSERT INTO rtpproxy_sockets (rtpproxy_sock, set_id) VALUES (?, ?)'
                    );
                    $stmt->execute([$sock, $setId]);
                    $success = _('RTPProxy instance created successfully.');
                    logAuditEvent('RTPPROXY_CREATE', 'rtpproxy', $sock, true, ['set_id' => $setId]);
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'unique constraint') !== false) {
                        $error = _('This socket already exists.');
                    } else {
                        $error = _('Database error: ') . $e->getMessage();
                    }
                }
            }
        } elseif ($action === 'delete') {
            $id = $_POST['id'] ?? '';
            try {
                $stmt = $pdo->prepare('SELECT rtpproxy_sock FROM rtpproxy_sockets WHERE id=?');
                $stmt->execute([$id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $stmt = $pdo->prepare('DELETE FROM rtpproxy_sockets WHERE id=?');
                    $stmt->execute([$id]);
                    $success = _('RTPProxy instance deleted successfully.');
                    logAuditEvent('RTPPROXY_DELETE', 'rtpproxy', $row['rtpproxy_sock'], true);
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
    $where[] = 'rtpproxy_sock ILIKE ?';
    $params[] = "%$search%";
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM rtpproxy_sockets $whereSql");
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));

$offset = ($page - 1) * $perPage;
$stmt = $pdo->prepare("SELECT * FROM rtpproxy_sockets $whereSql ORDER BY set_id, rtpproxy_sock LIMIT ? OFFSET ?");
$stmt->execute(array_merge($params, [$perPage, $offset]));
$proxies = $stmt->fetchAll(PDO::FETCH_ASSOC);

$csrfToken = generateCsrfToken();
require_once __DIR__ . '/common/header.php';
?>

<div class="tsisip-page">
    <header class="tsisip-page-header">
        <h1 class="tsisip-page-title"><?php echo _('RTPProxy'); ?></h1>
        <p class="tsisip-page-subtitle"><?php echo _('Legacy RTP proxy instance management'); ?></p>
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
            <a href="rtpproxy.php" class="tsisip-btn tsisip-btn--secondary"><?php echo _('Clear'); ?></a>
        </form>
    </section>

    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo _('RTPProxy Instances'); ?></h2>
        <table class="tsisip-table">
            <thead>
                <tr>
                    <th><?php echo _('Set ID'); ?></th>
                    <th><?php echo _('Socket'); ?></th>
                    <th><?php echo _('Actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($proxies)): ?>
                    <tr><td colspan="3" class="tsisip-empty"><?php echo _('No RTPProxy instances found.'); ?></td></tr>
                <?php else: ?>
                    <?php foreach ($proxies as $p): ?>
                        <tr>
                            <td><?php echo $p['set_id']; ?></td>
                            <td><code><?php echo htmlspecialchars($p['rtpproxy_sock']); ?></code></td>
                            <td>
                                <form method="post" style="display:inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                    <button type="submit" class="tsisip-btn tsisip-btn--danger tsisip-btn--sm"
                                            onclick="return confirm('<?php echo _('Delete this instance?'); ?>')">
                                        <?php echo _('Delete'); ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php renderPagination($page, $totalPages, $totalRows, $perPage, 'rtpproxy.php', ['search' => $search]); ?>
    </section>

    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo _('Add RTPProxy Instance'); ?></h2>
        <form method="post" class="tsisip-form">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="action" value="create">
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Socket'); ?></label>
                <input type="text" name="rtpproxy_sock" required class="tsisip-input" placeholder="udp:10.0.0.1:7722">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Set ID'); ?></label>
                <input type="number" name="set_id" value="0" class="tsisip-input">
            </div>
            <button type="submit" class="tsisip-btn tsisip-btn--primary"><?php echo _('Create'); ?></button>
        </form>
    </section>
</div>

<?php require_once __DIR__ . '/common/footer.php'; ?>
