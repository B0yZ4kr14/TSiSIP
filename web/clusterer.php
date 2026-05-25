<?php
/**
 * TSiSIP Control Panel — Clusterer
 * OpenSIPS built-in clustering provisioning (clusterer module)
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
            $clusterId = intval($_POST['cluster_id'] ?? 0);
            $nodeId    = intval($_POST['node_id'] ?? 0);
            $url       = trim($_POST['url'] ?? '');
            $state     = intval($_POST['state'] ?? 1);
            $noPing    = intval($_POST['no_ping_retries'] ?? 3);
            $priority  = intval($_POST['priority'] ?? 0);
            $sipAddr   = trim($_POST['sip_addr'] ?? '');
            $flags     = intval($_POST['flags'] ?? 0);
            $desc      = trim($_POST['description'] ?? '');

            if ($clusterId < 1 || $nodeId < 1 || $url === '') {
                $error = _('Cluster ID, Node ID, and URL are required.');
            } else {
                if ($action === 'create') {
                    try {
                        $stmt = $pdo->prepare(
                            'INSERT INTO clusterer (cluster_id, node_id, url, state, no_ping_retries, priority, sip_addr, flags, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
                        );
                        $stmt->execute([$clusterId, $nodeId, $url, $state, $noPing, $priority, $sipAddr, $flags, $desc]);
                        $success = _('Cluster node created successfully.');
                        logAuditEvent('CLUSTER_CREATE', 'clusterer', "cluster=$clusterId node=$nodeId", true);
                    } catch (PDOException $e) {
                        if (strpos($e->getMessage(), 'unique constraint') !== false) {
                            $error = _('A node with this cluster/node ID already exists.');
                        } else {
                            $error = _('Database error: ') . $e->getMessage();
                        }
                    }
                } else {
                    $id = $_POST['id'] ?? '';
                    try {
                        $stmt = $pdo->prepare(
                            'UPDATE clusterer SET cluster_id=?, node_id=?, url=?, state=?, no_ping_retries=?, priority=?, sip_addr=?, flags=?, description=?, last_modified=NOW() WHERE id=?'
                        );
                        $stmt->execute([$clusterId, $nodeId, $url, $state, $noPing, $priority, $sipAddr, $flags, $desc, $id]);
                        $success = _('Cluster node updated successfully.');
                        logAuditEvent('CLUSTER_UPDATE', 'clusterer', "cluster=$clusterId node=$nodeId", true);
                    } catch (PDOException $e) {
                        $error = _('Database error: ') . $e->getMessage();
                    }
                }
            }
        } elseif ($action === 'delete') {
            $id = $_POST['id'] ?? '';
            try {
                $stmt = $pdo->prepare('SELECT cluster_id, node_id FROM clusterer WHERE id=?');
                $stmt->execute([$id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $stmt = $pdo->prepare('DELETE FROM clusterer WHERE id=?');
                    $stmt->execute([$id]);
                    $success = _('Cluster node deleted successfully.');
                    logAuditEvent('CLUSTER_DELETE', 'clusterer', "cluster=" . $row['cluster_id'] . " node=" . $row['node_id'], true);
                }
            } catch (PDOException $e) {
                $error = _('Database error: ') . $e->getMessage();
            }
        }
    }
}

// --- Fetch list ---
$clusterFilter = $_GET['cluster'] ?? '';
$page   = max(1, intval($_GET['page'] ?? 1));
$search = trim($_GET['search'] ?? '');
$perPage = 25;

$where = [];
$params = [];
if ($clusterFilter !== '') {
    $where[] = 'cluster_id = ?';
    $params[] = $clusterFilter;
}
if ($search !== '') {
    $where[] = '(url ILIKE ? OR sip_addr ILIKE ? OR description ILIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM clusterer $whereSql");
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));

$offset = ($page - 1) * $perPage;
$stmt = $pdo->prepare("SELECT * FROM clusterer $whereSql ORDER BY cluster_id, node_id LIMIT ? OFFSET ?");
$stmt->execute(array_merge($params, [$perPage, $offset]));
$nodes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Cluster IDs for filter
$clStmt = $pdo->query('SELECT DISTINCT cluster_id FROM clusterer ORDER BY cluster_id');
$clusters = $clStmt->fetchAll(PDO::FETCH_COLUMN);

$csrfToken = generateCsrfToken();
require_once __DIR__ . '/common/header.php';
?>

<div class="tsisip-page">
    <header class="tsisip-page-header">
        <h1 class="tsisip-page-title"><?php echo _('Clusterer'); ?></h1>
        <p class="tsisip-page-subtitle"><?php echo _('OpenSIPS built-in clustering provisioning'); ?></p>
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
            <select name="cluster" class="tsisip-select">
                <option value=""><?php echo _('All Clusters'); ?></option>
                <?php foreach ($clusters as $cl): ?>
                    <option value="<?php echo $cl; ?>" <?php echo $cl == $clusterFilter ? 'selected' : ''; ?>>Cluster <?php echo $cl; ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="tsisip-btn tsisip-btn--primary"><?php echo _('Filter'); ?></button>
            <a href="clusterer.php" class="tsisip-btn tsisip-btn--secondary"><?php echo _('Clear'); ?></a>
        </form>
    </section>

    <!-- Real-time cluster status via MI HTTP -->
    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo _('Live Cluster Status'); ?></h2>
        <?php
        $miCluster = miHttpCall('clusterer_list');
        if (!$miCluster['success']):
        ?>
            <div class="tsisip-badge tsisip-badge--warning" role="alert">
                <?php echo _('MI unavailable: ') . htmlspecialchars($miCluster['error']); ?>
            </div>
        <?php else:
            $clusterData = $miCluster['data'] ?? [];
            if (empty($clusterData)):
        ?>
            <div class="tsisip-badge tsisip-badge--info"><?php echo _('No live cluster data returned by OpenSIPS.'); ?></div>
        <?php else: ?>
            <table class="tsisip-table">
                <thead>
                    <tr>
                        <th><?php echo _('Cluster'); ?></th>
                        <th><?php echo _('Node'); ?></th>
                        <th><?php echo _('URL'); ?></th>
                        <th><?php echo _('State'); ?></th>
                        <th><?php echo _('Priority'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clusterData as $c): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($c['cluster_id'] ?? '—'); ?></td>
                            <td><?php echo htmlspecialchars($c['node_id'] ?? '—'); ?></td>
                            <td><code><?php echo htmlspecialchars($c['url'] ?? '—'); ?></code></td>
                            <td>
                                <span class="tsisip-badge tsisip-badge--<?php echo ($c['state'] ?? 0) ? 'success' : 'danger'; ?>">
                                    <?php echo ($c['state'] ?? 0) ? _('Active') : _('Inactive'); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($c['priority'] ?? '—'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; endif; ?>
    </section>

    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo _('Cluster Nodes'); ?></h2>
        <table class="tsisip-table">
            <thead>
                <tr>
                    <th><?php echo _('Cluster'); ?></th>
                    <th><?php echo _('Node'); ?></th>
                    <th><?php echo _('URL'); ?></th>
                    <th><?php echo _('State'); ?></th>
                    <th><?php echo _('Priority'); ?></th>
                    <th><?php echo _('SIP Address'); ?></th>
                    <th><?php echo _('Actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($nodes)): ?>
                    <tr><td colspan="7" class="tsisip-empty"><?php echo _('No cluster nodes found.'); ?></td></tr>
                <?php else: ?>
                    <?php foreach ($nodes as $n): ?>
                        <tr>
                            <td><?php echo $n['cluster_id']; ?></td>
                            <td><?php echo $n['node_id']; ?></td>
                            <td><code><?php echo htmlspecialchars($n['url']); ?></code></td>
                            <td>
                                <span class="tsisip-badge tsisip-badge--<?php echo $n['state'] ? 'success' : 'danger'; ?>">
                                    <?php echo $n['state'] ? _('Active') : _('Inactive'); ?>
                                </span>
                            </td>
                            <td><?php echo $n['priority']; ?></td>
                            <td><code><?php echo htmlspecialchars($n['sip_addr'] ?? ''); ?></code></td>
                            <td>
                                <form method="post" style="display:inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $n['id']; ?>">
                                    <button type="submit" class="tsisip-btn tsisip-btn--danger tsisip-btn--sm"
                                            onclick="return confirm('<?php echo _('Delete this node?'); ?>')">
                                        <?php echo _('Delete'); ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php renderPagination($page, $totalPages, $totalRows, $perPage, 'clusterer.php', ['search' => $search, 'cluster' => $clusterFilter]); ?>
    </section>

    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo _('Add Cluster Node'); ?></h2>
        <form method="post" class="tsisip-form">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="action" value="create">
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Cluster ID'); ?></label>
                <input type="number" name="cluster_id" required class="tsisip-input" min="1">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Node ID'); ?></label>
                <input type="number" name="node_id" required class="tsisip-input" min="1">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('URL'); ?></label>
                <input type="text" name="url" required class="tsisip-input" placeholder="bin:10.0.0.1:5555">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('State'); ?></label>
                <select name="state" class="tsisip-select">
                    <option value="1"><?php echo _('Active'); ?></option>
                    <option value="0"><?php echo _('Inactive'); ?></option>
                </select>
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('No-Ping Retries'); ?></label>
                <input type="number" name="no_ping_retries" value="3" class="tsisip-input" min="0">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Priority'); ?></label>
                <input type="number" name="priority" value="0" class="tsisip-input">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('SIP Address'); ?></label>
                <input type="text" name="sip_addr" class="tsisip-input" placeholder="sip:10.0.0.1:5060">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Flags'); ?></label>
                <input type="number" name="flags" value="0" class="tsisip-input">
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
