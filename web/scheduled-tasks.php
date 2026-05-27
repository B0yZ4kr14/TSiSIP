<?php
/**
 * TSiSIP Control Panel — Scheduled Tasks Status
 */
require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';

requireAuth();
checkPasswordChange();
requireRole('admin');

logAuditEvent('CONFIG_VIEW', 'system', 'scheduled-tasks', true);

$tasks = [
    [
        'name' => 'Database Backup',
        'script' => 'scripts/backup-db.sh',
        'schedule' => 'Daily at 2:00 AM',
        'last_run' => '—',
        'status' => 'pending',
    ],
    [
        'name' => 'System Monitor',
        'script' => 'scripts/monitor.sh',
        'schedule' => 'Every 5 minutes',
        'last_run' => '—',
        'status' => 'pending',
    ],
    [
        'name' => 'Daily Maintenance',
        'script' => 'scripts/ocp-maintenance.sh',
        'schedule' => 'Daily at 3:00 AM',
        'last_run' => '—',
        'status' => 'pending',
    ],
];

// Check last run from logs
$logDir = __DIR__ . '/../logs';
foreach ($tasks as &$task) {
    $logPattern = $logDir . '/*' . strtolower(str_replace(' ', '-', $task['name'])) . '*';
    $logs = glob($logPattern);
    if (!empty($logs)) {
        usort($logs, fn($a, $b) => filemtime($b) - filemtime($a));
        $task['last_run'] = date('Y-m-d H:i:s', filemtime($logs[0]));
        $task['status'] = 'completed';
    }
}
unset($task);

require_once __DIR__ . '/common/header.php';
?>
<div id="content" class="tsisip-dashboard">
    <h1><?php echo _('Scheduled Tasks'); ?></h1>

    <div class="tsisip-dashboard-section">
        <table class="tsisip-table dataTable">
            <thead>
                <tr>
                    <th><?php echo _('Task'); ?></th>
                    <th><?php echo _('Schedule'); ?></th>
                    <th><?php echo _('Last Run'); ?></th>
                    <th><?php echo _('Status'); ?></th>
                    <th><?php echo _('Script'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tasks as $task): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($task['name']); ?></td>
                        <td><?php echo htmlspecialchars($task['schedule']); ?></td>
                        <td><?php echo htmlspecialchars($task['last_run']); ?></td>
                        <td>
                            <span class="tsisip-badge tsisip-badge--<?php echo $task['status']; ?>">
                                <?php echo _($task['status']); ?>
                            </span>
                        </td>
                        <td><code><?php echo htmlspecialchars($task['script']); ?></code></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="tsisip-dashboard-section">
        <h2 class="tsisip-section-title"><?php echo _('Cron Setup'); ?></h2>
        <pre style="background:var(--tsisip-bg-secondary);padding:1rem;border-radius:8px;overflow-x:auto;"><code># Backup daily at 2 AM
0 2 * * * /path/to/scripts/backup-db.sh

# Monitor every 5 minutes
*/5 * * * * /path/to/scripts/monitor.sh

# Maintenance daily at 3 AM
0 3 * * * /path/to/scripts/ocp-maintenance.sh</code></pre>
    </div>
</div>
<?php require_once __DIR__ . '/common/footer.php'; ?>
