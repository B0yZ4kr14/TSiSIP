<?php
/**
 * TSiSIP Control Panel — Trunk Status
 * Read-only view of trunk provider health and registration state.
 */

require_once __DIR__ . '/common/config.php';

requireAuth();
checkPasswordChange();
requireRole('devops');

$pdo = getDb();

$stmt = $pdo->query(
    "SELECT p.id, p.name, p.host, p.port, p.enabled, p.priority,
            d.state AS dispatcher_state,
            d.destination AS dispatcher_destination,
            r.state AS reg_state,
            r.last_register_succ,
            r.last_register_sent
     FROM sip_trunk_providers p
     LEFT JOIN dispatcher d ON d.setid = 100 AND d.description = 'Trunk: ' || p.name
     LEFT JOIN LATERAL (
         SELECT state, last_register_succ, last_register_sent
         FROM sip_trunk_registrations
         WHERE trunk_provider_id = p.id
         ORDER BY id DESC
         LIMIT 1
     ) r ON true
     ORDER BY p.priority, p.name"
);
$rows = $stmt->fetchAll();

require_once __DIR__ . '/common/header.php';
?>
<div id="content">
    <h2><?php echo _('Trunk Status'); ?></h2>

    <div class="tsisip-dashboard-section">
        <table class="dataTable tsisip-table">
            <thead>
                <tr>
                    <th><?php echo _('Provider'); ?></th>
                    <th><?php echo _('Host'); ?></th>
                    <th><?php echo _('Port'); ?></th>
                    <th><?php echo _('Enabled'); ?></th>
                    <th><?php echo _('Dispatcher Health'); ?></th>
                    <th><?php echo _('Registration'); ?></th>
                    <th><?php echo _('Last Register Success'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="7" class="tsisip-text-muted"><?php echo _('No trunk providers configured.'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['host']); ?></td>
                        <td><?php echo htmlspecialchars($row['port']); ?></td>
                        <td>
                            <?php if ($row['enabled']): ?>
                                <span class="tsisip-badge tsisip-badge-success"><?php echo _('Yes'); ?></span>
                            <?php else: ?>
                                <span class="tsisip-badge tsisip-badge-error"><?php echo _('No'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($row['dispatcher_state'] === null): ?>
                                <span class="tsisip-badge tsisip-badge-warning"><?php echo _('Unknown'); ?></span>
                            <?php elseif ($row['dispatcher_state'] == 0): ?>
                                <span class="tsisip-badge tsisip-badge-success"><?php echo _('Healthy'); ?></span>
                            <?php else: ?>
                                <span class="tsisip-badge tsisip-badge-error"><?php echo _('Unhealthy'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($row['reg_state'] === null): ?>
                                <span class="tsisip-badge"><?php echo _('N/A'); ?></span>
                            <?php elseif ($row['reg_state'] == 0): ?>
                                <span class="tsisip-badge tsisip-badge-success"><?php echo _('Registered'); ?></span>
                            <?php else: ?>
                                <span class="tsisip-badge tsisip-badge-error"><?php echo _('Not Registered'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['last_register_succ'] ?? _('N/A')); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/common/footer.php'; ?>
