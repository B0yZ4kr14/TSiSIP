<?php
/**
 * TSiSIP Control Panel — SIP Dialog Viewer
 * Read-only view of active SIP dialogs from the OpenSIPS dialog table.
 */

require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';
require_once __DIR__ . '/common/pagination.php';
require_once __DIR__ . '/common/mi-http.php';

requireAuth();
checkPasswordChange();
requireRole('devops');

$pdo = getDb();

/**
 * Map dialog state integer to a human-readable label.
 */
function dialogStateLabel(int $state): string {
    $map = [
        1 => _('Early'),
        2 => _('Confirmed'),
        3 => _('Terminated'),
        4 => _('Deleted'),
    ];
    return $map[$state] ?? _('Unknown');
}

/**
 * Format elapsed seconds as a human-readable HH:MM:SS duration.
 */
function formatDuration(int $seconds): string {
    if ($seconds < 0) {
        $seconds = 0;
    }
    $hours   = (int) floor($seconds / 3600);
    $minutes = (int) floor(($seconds % 3600) / 60);
    $secs    = $seconds % 60;
    return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
}

$page    = max(1, intval($_GET['page'] ?? 1));
$perPage = 25;
$pagination = getPagination($page, $perPage);

// Count total dialogs
try {
    $countStmt = $pdo->query('SELECT COUNT(*) FROM dialog');
    $totalItems = (int) $countStmt->fetchColumn();
} catch (PDOException $e) {
    error_log('TSiSIP dialog count query failed: ' . $e->getMessage());
    $totalItems = 0;
}

// Fetch dialog list
$dialogs = [];
try {
    $listStmt = $pdo->prepare(
        'SELECT hash_entry, hash_id, callid, from_uri, to_uri, state, start_time, timeout
         FROM dialog
         ORDER BY start_time DESC
         LIMIT :limit OFFSET :offset'
    );
    $listStmt->bindValue(':limit', $pagination['limit'], PDO::PARAM_INT);
    $listStmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
    $listStmt->execute();
    $dialogs = $listStmt->fetchAll();
} catch (PDOException $e) {
    error_log('TSiSIP dialog list query failed: ' . $e->getMessage());
}

$now = time();

require_once __DIR__ . '/common/header.php';
?>
<div id="content">
    <h2><?php echo _('SIP Dialogs'); ?></h2>

    <div class="tsisip-dashboard-section">
        <p class="tsisip-text-muted"><?php echo _('This is a read-only view of active SIP dialogs.'); ?></p>
    </div>

    <!-- Live active dialogs via MI HTTP -->
    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo _('Live Active Dialogs'); ?></h2>
        <?php
        $miDlg = miHttpCall('dlg_list');
        if (!$miDlg['success']):
        ?>
            <div class="tsisip-badge tsisip-badge--warning" role="alert">
                <?php echo _('MI unavailable: ') . htmlspecialchars($miDlg['error']); ?>
            </div>
        <?php else:
            $dlgData = $miDlg['data'] ?? [];
            if (!is_array($dlgData)) {
                $dlgData = [];
            }
            if (isset($dlgData['Dialogs']) && is_array($dlgData['Dialogs'])) {
                $dlgData = $dlgData['Dialogs'];
            }
            if (empty($dlgData)):
        ?>
            <div class="tsisip-badge tsisip-badge--info"><?php echo _('No live dialog data returned by OpenSIPS.'); ?></div>
        <?php else: ?>
            <table class="tsisip-table dataTable">
                <thead>
                    <tr>
                        <th><?php echo _('Call-ID'); ?></th>
                        <th><?php echo _('From'); ?></th>
                        <th><?php echo _('To'); ?></th>
                        <th><?php echo _('State'); ?></th>
                        <th><?php echo _('Start Time'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dlgData as $dlg): ?>
                        <?php if (!is_array($dlg)) continue; ?>
                        <tr>
                            <td><?php echo htmlspecialchars($dlg['callid'] ?? $dlg['call_id'] ?? $dlg['CALLID'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($dlg['from_uri'] ?? $dlg['from'] ?? $dlg['FROM'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($dlg['to_uri'] ?? $dlg['to'] ?? $dlg['TO'] ?? 'N/A'); ?></td>
                            <td>
                                <?php
                                $state = $dlg['state'] ?? $dlg['STATE'] ?? 'N/A';
                                $stateLabel = is_numeric($state) ? dialogStateLabel((int)$state) : htmlspecialchars((string)$state);
                                $badgeClass = 'tsisip-badge';
                                if (is_numeric($state)) {
                                    if ((int)$state === 2) $badgeClass = 'tsisip-badge tsisip-badge-success';
                                    elseif ((int)$state === 1) $badgeClass = 'tsisip-badge tsisip-badge-warning';
                                    elseif ((int)$state === 3 || (int)$state === 4) $badgeClass = 'tsisip-badge tsisip-badge-error';
                                }
                                ?>
                                <span class="<?php echo htmlspecialchars($badgeClass, ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo $stateLabel; ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($dlg['start_time'] ?? $dlg['startTime'] ?? $dlg['START_TIME'] ?? '—'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; endif; ?>
    </section>

    <div class="tsisip-dashboard-section">
        <h3><?php echo _('Active Dialogs'); ?> (<?php echo $totalItems; ?>)</h3>
        <table class="dataTable tsisip-table">
            <thead>
                <tr>
                    <th><?php echo _('Call-ID'); ?></th>
                    <th><?php echo _('From'); ?></th>
                    <th><?php echo _('To'); ?></th>
                    <th><?php echo _('State'); ?></th>
                    <th><?php echo _('Duration'); ?></th>
                    <th><?php echo _('Timeout'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dialogs as $d): ?>
                <tr>
                    <td><?php echo htmlspecialchars($d['callid'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($d['from_uri'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($d['to_uri'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>
                        <?php
                        $state = (int) ($d['state'] ?? 0);
                        $stateLabel = dialogStateLabel($state);
                        $badgeClass = 'tsisip-badge';
                        if ($state === 2) {
                            $badgeClass = 'tsisip-badge tsisip-badge-success';
                        } elseif ($state === 1) {
                            $badgeClass = 'tsisip-badge tsisip-badge-warning';
                        } elseif ($state === 3 || $state === 4) {
                            $badgeClass = 'tsisip-badge tsisip-badge-error';
                        }
                        ?>
                        <span class="<?php echo htmlspecialchars($badgeClass, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars($stateLabel, ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </td>
                    <td>
                        <?php
                        $startTime = (int) ($d['start_time'] ?? 0);
                        if ($startTime > 0) {
                            echo htmlspecialchars(formatDuration($now - $startTime), ENT_QUOTES, 'UTF-8');
                        } else {
                            echo '-';
                        }
                        ?>
                    </td>
                    <td><?php echo htmlspecialchars((string) ($d['timeout'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($dialogs)): ?>
                <tr>
                    <td colspan="6" class="tsisip-text-center tsisip-text-muted">
                        <?php echo _('No active dialogs found.'); ?>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php echo renderPagination($page, $totalItems, $perPage, 'dialog.php'); ?>
    </div>
</div>
<?php require_once __DIR__ . '/common/footer.php'; ?>
