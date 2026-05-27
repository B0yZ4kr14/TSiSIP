<?php
/**
 * TSiSIP Control Panel — Global Search
 * Search across subscribers, audit logs, and dialogs.
 */
require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';

requireAuth();
checkPasswordChange();

logAuditEvent('CONFIG_VIEW', 'system', 'search', true);

$pdo = getDb();
$query = trim($_GET['q'] ?? '');
$results = [];

if ($query !== '') {
    logAuditEvent('SEARCH', 'system', $query, true);

    // Search subscribers
    $subStmt = $pdo->prepare(
        "SELECT id, username, domain, datetime_created, tenant_id
         FROM subscriber
         WHERE username ILIKE :q OR domain ILIKE :q
         LIMIT 10"
    );
    $subStmt->execute([':q' => '%' . $query . '%']);
    $results['subscribers'] = $subStmt->fetchAll();

    // Search audit logs
    $auditStmt = $pdo->prepare(
        "SELECT id, event_time, username, action, resource_type, resource_id
         FROM ocp_audit_log
         WHERE username ILIKE :q OR action ILIKE :q OR resource_type ILIKE :q
         ORDER BY event_time DESC
         LIMIT 10"
    );
    $auditStmt->execute([':q' => '%' . $query . '%']);
    $results['audit'] = $auditStmt->fetchAll();

    // Search dialogs
    $dlgStmt = $pdo->prepare(
        "SELECT hash_entry, hash_id, callid, from_uri, to_uri, state
         FROM dialog
         WHERE callid ILIKE :q OR from_uri ILIKE :q OR to_uri ILIKE :q
         LIMIT 10"
    );
    $dlgStmt->execute([':q' => '%' . $query . '%']);
    $results['dialogs'] = $dlgStmt->fetchAll();
}

require_once __DIR__ . '/common/header.php';
?>
<div id="content" class="tsisip-dashboard">
    <h1><?php echo _('Global Search'); ?></h1>

    <div class="tsisip-dashboard-section">
        <form method="GET" action="" class="tsisip-form" style="display:flex;gap:1rem;">
            <div class="tsisip-form-group" style="flex:1;">
                <input type="text" name="q" class="tsisip-input" value="<?php echo htmlspecialchars($query); ?>"
                       placeholder="<?php echo _('Search subscribers, audit logs, dialogs...'); ?>"
                       style="width:100%;" autofocus>
            </div>
            <button type="submit" class="tsisip-btn tsisip-btn-primary"><?php echo _('Search'); ?></button>
        </form>
    </div>

    <?php if ($query !== ''): ?>
        <!-- Subscribers -->
        <section class="tsisip-section">
            <h2 class="tsisip-section-title">
                <?php echo _('Subscribers'); ?> (<?php echo count($results['subscribers'] ?? []); ?>)
            </h2>
            <?php if (empty($results['subscribers'])): ?>
                <div class="tsisip-badge tsisip-badge--info"><?php echo _('No subscribers found.'); ?></div>
            <?php else: ?>
                <table class="tsisip-table dataTable">
                    <thead>
                        <tr>
                            <th><?php echo _('Username'); ?></th>
                            <th><?php echo _('Domain'); ?></th>
                            <th><?php echo _('Tenant'); ?></th>
                            <th><?php echo _('Created'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results['subscribers'] as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                <td><?php echo htmlspecialchars($row['domain']); ?></td>
                                <td><?php echo htmlspecialchars((string) ($row['tenant_id'] ?? '—')); ?></td>
                                <td><?php echo htmlspecialchars((string) ($row['datetime_created'] ?? '—')); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

        <!-- Audit Logs -->
        <section class="tsisip-section">
            <h2 class="tsisip-section-title">
                <?php echo _('Audit Logs'); ?> (<?php echo count($results['audit'] ?? []); ?>)
            </h2>
            <?php if (empty($results['audit'])): ?>
                <div class="tsisip-badge tsisip-badge--info"><?php echo _('No audit logs found.'); ?></div>
            <?php else: ?>
                <table class="tsisip-table dataTable">
                    <thead>
                        <tr>
                            <th><?php echo _('Time'); ?></th>
                            <th><?php echo _('User'); ?></th>
                            <th><?php echo _('Action'); ?></th>
                            <th><?php echo _('Resource'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results['audit'] as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars((string) ($row['event_time'] ?? '—')); ?></td>
                                <td><?php echo htmlspecialchars($row['username'] ?? '—'); ?></td>
                                <td><?php echo htmlspecialchars($row['action'] ?? '—'); ?></td>
                                <td><?php echo htmlspecialchars(($row['resource_type'] ?? '') . ' / ' . ($row['resource_id'] ?? '')); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

        <!-- Dialogs -->
        <section class="tsisip-section">
            <h2 class="tsisip-section-title">
                <?php echo _('Dialogs'); ?> (<?php echo count($results['dialogs'] ?? []); ?>)
            </h2>
            <?php if (empty($results['dialogs'])): ?>
                <div class="tsisip-badge tsisip-badge--info"><?php echo _('No dialogs found.'); ?></div>
            <?php else: ?>
                <table class="tsisip-table dataTable">
                    <thead>
                        <tr>
                            <th><?php echo _('Call-ID'); ?></th>
                            <th><?php echo _('From'); ?></th>
                            <th><?php echo _('To'); ?></th>
                            <th><?php echo _('State'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results['dialogs'] as $row): ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($row['callid'] ?? '—'); ?></code></td>
                                <td><?php echo htmlspecialchars($row['from_uri'] ?? '—'); ?></td>
                                <td><?php echo htmlspecialchars($row['to_uri'] ?? '—'); ?></td>
                                <td><?php echo htmlspecialchars((string) ($row['state'] ?? '—')); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/common/footer.php'; ?>
