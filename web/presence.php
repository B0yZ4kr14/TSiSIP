<?php
/**
 * TSiSIP Control Panel — Presence
 * Presentity and watcher tables with pres_refresh_watchers action.
 */
require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';
require_once __DIR__ . '/common/mi-http.php';

requireAuth();
checkPasswordChange();
requireRole('devops');

$pageTitle = _('Presence');

$error = '';
$success = '';

// Handle refresh action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'refresh_watchers') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = _('Invalid CSRF token.');
    } else {
        $presentityUri = trim($_POST['presentity_uri'] ?? '');
        $eventType = trim($_POST['event_type'] ?? 'presence');

        $params = [];
        if ($presentityUri !== '') {
            $params[] = $presentityUri;
            if ($eventType !== '') {
                $params[] = $eventType;
            }
        }

        $result = miHttpCall('pres_refresh_watchers', $params);
        if ($result['success']) {
            $success = _('Refresh command sent successfully.');
            logAuditEvent('MI_COMMAND', 'opensips', 'pres_refresh_watchers', true, [
                'params' => $params,
            ]);
        } else {
            $error = $result['error'] ?? _('Command failed.');
            logAuditEvent('MI_COMMAND', 'opensips', 'pres_refresh_watchers', false, [
                'params' => $params,
                'reason' => $error,
            ]);
        }
    }
}

// Query DB for presentity/watchers if tables exist
$pdo = getDb();
$presentities = [];
$watchers = [];
$tablesExist = false;

try {
    $tblStmt = $pdo->query("SELECT tablename FROM pg_tables WHERE schemaname = 'public' AND tablename IN ('presentity', 'active_watchers', 'watchers')");
    $existingTables = $tblStmt->fetchAll(PDO::FETCH_COLUMN);
    $tablesExist = !empty($existingTables);

    if (in_array('presentity', $existingTables, true)) {
        $presStmt = $pdo->query(
            "SELECT username, domain, event, etag, expires, datetime, body
             FROM presentity
             ORDER BY datetime DESC
             LIMIT 500"
        );
        $presentities = $presStmt->fetchAll();
    }

    if (in_array('active_watchers', $existingTables, true)) {
        $watchStmt = $pdo->query(
            "SELECT watcher_username, watcher_domain, presentity_uri, event, status, expires, datetime
             FROM active_watchers
             ORDER BY datetime DESC
             LIMIT 500"
        );
        $watchers = $watchStmt->fetchAll();
    } elseif (in_array('watchers', $existingTables, true)) {
        $watchStmt = $pdo->query(
            "SELECT watcher_username, watcher_domain, presentity_uri, event, status, expires
             FROM watchers
             ORDER BY presentity_uri
             LIMIT 500"
        );
        $watchers = $watchStmt->fetchAll();
    }
} catch (PDOException $e) {
    error_log('TSiSIP presence DB query failed: ' . $e->getMessage());
}

require_once __DIR__ . '/common/header.php';
?>
<div class="tsisip-page">
    <div class="tsisip-page-header">
        <h1 class="tsisip-page-title"><?php echo $pageTitle; ?></h1>
        <div class="tsisip-actions">
            <button type="button" class="tsisip-btn tsisip-btn-secondary" onclick="TSiSIPMi.exportData('pres_refresh_watchers', [], 'csv')"><?php echo _('Export CSV'); ?></button>
            <button type="button" class="tsisip-btn tsisip-btn-secondary" onclick="TSiSIPMi.exportData('pres_refresh_watchers', [], 'json')"><?php echo _('Export JSON'); ?></button>
        </div>
    </div>

    <?php if ($error): ?>
        <?php echo miErrorBanner($error); ?>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="tsisip-alert tsisip-alert--success" role="status"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if (isDevOpsOrHigher()): ?>
    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo _('Refresh Watchers'); ?></h2>
        <form method="POST" action="" class="tsisip-form">
            <?php echo csrfInput(); ?>
            <input type="hidden" name="action" value="refresh_watchers">
            <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
                <div class="tsisip-form-group" style="flex:1;min-width:240px;">
                    <label for="presentity_uri"><?php echo _('Presentity URI'); ?></label>
                    <input type="text" id="presentity_uri" name="presentity_uri" class="tsisip-input"
                           placeholder="<?php echo _('sip:user@domain (optional)'); ?>">
                </div>
                <div class="tsisip-form-group" style="flex:1;min-width:180px;">
                    <label for="event_type"><?php echo _('Event Type'); ?></label>
                    <input type="text" id="event_type" name="event_type" class="tsisip-input" value="presence">
                </div>
                <div class="tsisip-form-group">
                    <button type="submit" class="tsisip-btn tsisip-btn--primary">
                        <?php echo _('Refresh Watchers'); ?>
                    </button>
                </div>
            </div>
        </form>
    </section>
    <?php endif; ?>

    <?php if (!$tablesExist): ?>
        <div class="tsisip-alert tsisip-alert--info" role="note">
            <?php echo _('Presence tables are not present in the database. Presence module may not be loaded or tables have not been created.'); ?>
        </div>
    <?php else: ?>
        <section class="tsisip-section">
            <h2 class="tsisip-section-title"><?php echo _('Presentities'); ?> (<?php echo count($presentities); ?>)</h2>
            <?php if (empty($presentities)): ?>
                <p class="tsisip-text-muted"><?php echo _('No presentities found.'); ?></p>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table class="tsisip-table dataTable" data-tsisip-sortable>
                        <thead>
                            <tr>
                                <th><?php echo _('Username'); ?></th>
                                <th><?php echo _('Domain'); ?></th>
                                <th><?php echo _('Event'); ?></th>
                                <th><?php echo _('ETag'); ?></th>
                                <th><?php echo _('Expires'); ?></th>
                                <th><?php echo _('Updated'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($presentities as $p): ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($p['username'] ?? '—'); ?></code></td>
                                    <td><?php echo htmlspecialchars($p['domain'] ?? '—'); ?></td>
                                    <td><?php echo htmlspecialchars($p['event'] ?? '—'); ?></td>
                                    <td><code><?php echo htmlspecialchars($p['etag'] ?? '—'); ?></code></td>
                                    <td><?php echo htmlspecialchars($p['expires'] ?? '—'); ?></td>
                                    <td><?php echo htmlspecialchars($p['datetime'] ?? '—'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="tsisip-section">
            <h2 class="tsisip-section-title"><?php echo _('Watchers'); ?> (<?php echo count($watchers); ?>)</h2>
            <?php if (empty($watchers)): ?>
                <p class="tsisip-text-muted"><?php echo _('No watchers found.'); ?></p>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table class="tsisip-table dataTable" data-tsisip-sortable>
                        <thead>
                            <tr>
                                <th><?php echo _('Watcher'); ?></th>
                                <th><?php echo _('Presentity URI'); ?></th>
                                <th><?php echo _('Event'); ?></th>
                                <th><?php echo _('Status'); ?></th>
                                <th><?php echo _('Expires'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($watchers as $w): ?>
                                <?php
                                $status = strtolower((string) ($w['status'] ?? 'unknown'));
                                $badgeClass = 'tsisip-badge tsisip-badge-info';
                                if ($status === 'active' || $status === 'pending') {
                                    $badgeClass = 'tsisip-badge tsisip-badge-success';
                                } elseif ($status === 'terminated') {
                                    $badgeClass = 'tsisip-badge tsisip-badge-error';
                                }
                                ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars(($w['watcher_username'] ?? '') . '@' . ($w['watcher_domain'] ?? '')); ?></code></td>
                                    <td><code><?php echo htmlspecialchars($w['presentity_uri'] ?? '—'); ?></code></td>
                                    <td><?php echo htmlspecialchars($w['event'] ?? '—'); ?></td>
                                    <td>
                                        <span class="<?php echo htmlspecialchars($badgeClass, ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars(ucfirst($status)); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($w['expires'] ?? '—'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/common/footer.php'; ?>
