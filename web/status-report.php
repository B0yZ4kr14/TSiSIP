<?php
/**
 * TSiSIP Control Panel — Status Report
 * OpenSIPS status identifiers (OCP 9.3.3+)
 */

require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/pagination.php';
require_once __DIR__ . '/common/mi-http.php';

requireAuth();
checkPasswordChange();

$pdo = getDb();

// --- Fetch list ---
$severityFilter = $_GET['severity'] ?? '';
$page   = max(1, intval($_GET['page'] ?? 1));
$search = trim($_GET['search'] ?? '');
$perPage = 25;

$where = [];
$params = [];
if ($severityFilter !== '') {
    $where[] = 'severity = ?';
    $params[] = $severityFilter;
}
if ($search !== '') {
    $where[] = '(identifier ILIKE ? OR details ILIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM status_report $whereSql");
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));

$offset = ($page - 1) * $perPage;
$stmt = $pdo->prepare("SELECT * FROM status_report $whereSql ORDER BY timestamp DESC LIMIT ? OFFSET ?");
$stmt->execute(array_merge($params, [$perPage, $offset]));
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Severity counts
$sevStmt = $pdo->query("SELECT severity, COUNT(*) as cnt FROM status_report GROUP BY severity ORDER BY severity");
$severityCounts = $sevStmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/common/header.php';
?>

<div class="tsisip-page">
    <header class="tsisip-page-header">
        <h1 class="tsisip-page-title"><?php echo _('Status Report'); ?></h1>
        <p class="tsisip-page-subtitle"><?php echo _('OpenSIPS status identifiers (OCP 9.3.3+)'); ?></p>
    </header>

    <section class="tsisip-section">
        <div class="tsisip-stats-grid">
            <?php foreach ($severityCounts as $sc): ?>
                <div class="tsisip-stat-card tsisip-stat-card--<?php echo htmlspecialchars($sc['severity']); ?>">
                    <span class="tsisip-stat-value"><?php echo $sc['cnt']; ?></span>
                    <span class="tsisip-stat-label"><?php echo htmlspecialchars(ucfirst($sc['severity'])); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="tsisip-section">
        <form method="get" class="tsisip-filter-bar">
            <input type="text" name="search" placeholder="<?php echo _('Search...'); ?>"
                   value="<?php echo htmlspecialchars($search); ?>" class="tsisip-input">
            <select name="severity" class="tsisip-select">
                <option value=""><?php echo _('All Severities'); ?></option>
                <option value="error" <?php echo $severityFilter === 'error' ? 'selected' : ''; ?>><?php echo _('Error'); ?></option>
                <option value="warning" <?php echo $severityFilter === 'warning' ? 'selected' : ''; ?>><?php echo _('Warning'); ?></option>
                <option value="info" <?php echo $severityFilter === 'info' ? 'selected' : ''; ?>><?php echo _('Info'); ?></option>
            </select>
            <button type="submit" class="tsisip-btn tsisip-btn--primary"><?php echo _('Filter'); ?></button>
            <a href="status-report.php" class="tsisip-btn tsisip-btn--secondary"><?php echo _('Clear'); ?></a>
        </form>
    </section>

    <!-- Real-time status via MI HTTP -->
    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo _('Live OpenSIPS Status'); ?></h2>
        <?php
        $miStatus = miHttpCall('status_report');
        if (!$miStatus['success']):
        ?>
            <div class="tsisip-badge tsisip-badge--warning" role="alert">
                <?php echo _('MI unavailable: ') . htmlspecialchars($miStatus['error']); ?>
            </div>
        <?php else:
            $statusData = $miStatus['data'] ?? [];
        ?>
            <table class="tsisip-table">
                <thead>
                    <tr><th><?php echo _('Identifier'); ?></th><th><?php echo _('Severity'); ?></th><th><?php echo _('Details'); ?></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($statusData as $s): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($s['identifier'] ?? '—'); ?></td>
                            <td>
                                <span class="tsisip-badge tsisip-badge--<?php echo strtolower($s['severity'] ?? 'info'); ?>">
                                    <?php echo htmlspecialchars($s['severity'] ?? '—'); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($s['details'] ?? '—'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($statusData)): ?>
                        <tr><td colspan="3" class="tsisip-empty"><?php echo _('No live status reports.'); ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>

    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo _('Status Reports'); ?></h2>
        <table class="tsisip-table">
            <thead>
                <tr>
                    <th><?php echo _('Timestamp'); ?></th>
                    <th><?php echo _('Identifier'); ?></th>
                    <th><?php echo _('Severity'); ?></th>
                    <th><?php echo _('Details'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reports)): ?>
                    <tr><td colspan="4" class="tsisip-empty"><?php echo _('No status reports found.'); ?></td></tr>
                <?php else: ?>
                    <?php foreach ($reports as $r): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($r['timestamp']); ?></td>
                            <td><code><?php echo htmlspecialchars($r['identifier']); ?></code></td>
                            <td>
                                <span class="tsisip-badge tsisip-badge--<?php echo htmlspecialchars($r['severity']); ?>">
                                    <?php echo htmlspecialchars(ucfirst($r['severity'])); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($r['details'] ?? ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php renderPagination($page, $totalPages, $totalRows, $perPage, 'status-report.php', ['search' => $search, 'severity' => $severityFilter]); ?>
    </section>
</div>

<?php require_once __DIR__ . '/common/footer.php'; ?>
