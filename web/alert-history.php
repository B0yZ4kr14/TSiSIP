<?php
/**
 * TSiSIP Control Panel — Alert History View
 * Displays historical alerts from the auth_audit_log table.
 */
require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';
require_once __DIR__ . '/common/pagination.php';

requireAuth();
checkPasswordChange();
requireRole('devops');

logAuditEvent('CONFIG_VIEW', 'system', 'alert-history', true);

$pdo = getDb();
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 25;
$pagination = getPagination($page, $perPage);

// Filter by severity if provided
$severity = $_GET['severity'] ?? '';
$validSeverities = ['critical', 'warning', 'info'];

$whereClause = "WHERE event_type LIKE 'ALERT_%'";
$params = [];
if (in_array($severity, $validSeverities, true)) {
    $whereClause .= " AND severity = :severity";
    $params[':severity'] = $severity;
}

// Count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM auth_audit_log {$whereClause}");
$countStmt->execute($params);
$totalItems = (int) $countStmt->fetchColumn();

// Fetch
$stmt = $pdo->prepare(
    "SELECT id, event_time, event_type, source_ip, details, severity
     FROM auth_audit_log
     {$whereClause}
     ORDER BY event_time DESC
     LIMIT :limit OFFSET :offset"
);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':limit', $pagination['limit'], PDO::PARAM_INT);
$stmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
$stmt->execute();
$alerts = $stmt->fetchAll();

require_once __DIR__ . '/common/header.php';
?>
<div id="content" class="tsisip-dashboard">
    <h1><?php echo _('Alert History'); ?></h1>

    <div class="tsisip-dashboard-section">
        <form method="GET" action="" style="display:flex;gap:1rem;align-items:flex-end;flex-wrap:wrap;">
            <div class="tsisip-form-group">
                <label for="severity"><?php echo _('Severity'); ?></label>
                <select id="severity" name="severity" class="tsisip-input" onchange="this.form.submit()">
                    <option value=""><?php echo _('All'); ?></option>
                    <option value="critical" <?php echo $severity === 'critical' ? 'selected' : ''; ?>><?php echo _('Critical'); ?></option>
                    <option value="warning" <?php echo $severity === 'warning' ? 'selected' : ''; ?>><?php echo _('Warning'); ?></option>
                    <option value="info" <?php echo $severity === 'info' ? 'selected' : ''; ?>><?php echo _('Info'); ?></option>
                </select>
            </div>
            <button type="submit" class="tsisip-btn tsisip-btn-secondary"><?php echo _('Filter'); ?></button>
        </form>
    </div>

    <section class="tsisip-section">
        <table class="tsisip-table dataTable">
            <thead>
                <tr>
                    <th><?php echo _('Time'); ?></th>
                    <th><?php echo _('Type'); ?></th>
                    <th><?php echo _('Source IP'); ?></th>
                    <th><?php echo _('Severity'); ?></th>
                    <th><?php echo _('Details'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($alerts as $alert): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($alert['event_time']); ?></td>
                        <td><?php echo htmlspecialchars($alert['event_type']); ?></td>
                        <td><?php echo htmlspecialchars($alert['source_ip'] ?? 'N/A'); ?></td>
                        <td>
                            <?php
                            $sev = $alert['severity'] ?? 'info';
                            $badgeClass = 'tsisip-badge';
                            if ($sev === 'critical') $badgeClass = 'tsisip-badge tsisip-badge-error';
                            elseif ($sev === 'warning') $badgeClass = 'tsisip-badge tsisip-badge-warning';
                            elseif ($sev === 'info') $badgeClass = 'tsisip-badge tsisip-badge-info';
                            ?>
                            <span class="<?php echo htmlspecialchars($badgeClass, ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars(ucfirst($sev)); ?>
                            </span>
                        </td>
                        <td><pre style="margin:0;white-space:pre-wrap;"><?php echo htmlspecialchars($alert['details'] ?? ''); ?></pre></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($alerts)): ?>
                    <tr>
                        <td colspan="5" class="tsisip-text-center tsisip-text-muted">
                            <?php echo _('No alerts found.'); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php echo renderPagination($page, $totalItems, $perPage, 'alert-history.php'); ?>
    </section>
</div>
<?php require_once __DIR__ . '/common/footer.php'; ?>
