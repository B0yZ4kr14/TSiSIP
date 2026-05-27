<?php
/**
 * TSiSIP Control Panel — Topology Hiding
 * Inferred topology hiding status from active dialogs.
 */
require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';
require_once __DIR__ . '/common/mi-http.php';

requireAuth();
checkPasswordChange();

$pageTitle = _('Topology Hiding');

$dialogs = [];
$miData = ['success' => false, 'error' => null, 'data' => null];

try {
    $result = miHttpCall('dlg_list', []);
    $miData = $result;
    if ($result['success'] && is_array($result['data'])) {
        $raw = $result['data'];
        if (isset($raw['Dialogs']) && is_array($raw['Dialogs'])) {
            $dialogs = $raw['Dialogs'];
        } elseif (isset($raw[0]) && is_array($raw[0])) {
            $dialogs = $raw;
        } else {
            foreach ($raw as $key => $val) {
                if (is_array($val)) {
                    $dialogs[] = $val;
                }
            }
        }
    }
} catch (Exception $e) {
    $miData['error'] = $e->getMessage();
}

require_once __DIR__ . '/common/header.php';
?>
<div class="tsisip-page">
    <div class="tsisip-page-header">
        <h1 class="tsisip-page-title"><?php echo $pageTitle; ?></h1>
    </div>

    <div class="tsisip-alert tsisip-alert--info" role="note">
        <?php echo _('Topology hiding status is inferred from dialog data as there is no dedicated MI command for the topology_hiding module.'); ?>
    </div>

    <?php if (!$miData["success"]): ?>
        <?php echo miErrorBanner($miData["error"] ?? _("Unknown")); ?>
    <?php else: ?>
        <section class="tsisip-section">
            <h2 class="tsisip-section-title"><?php echo _('Dialogs with Topology Hiding'); ?></h2>
            <div style="overflow-x:auto;">
                <table class="tsisip-table dataTable" data-tsisip-sortable>
                    <thead>
                        <tr>
                            <th><?php echo _('Call-ID'); ?></th>
                            <th><?php echo _('From'); ?></th>
                            <th><?php echo _('To'); ?></th>
                            <th><?php echo _('State'); ?></th>
                            <th><?php echo _('Topology Hidden'); ?></th>
                            <th><?php echo _('Duration'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dialogs)): ?>
                            <tr>
                                <td colspan="6" class="tsisip-empty"><?php echo _('No active dialogs found.'); ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($dialogs as $dlg): ?>
                                <?php if (!is_array($dlg)) continue; ?>
                                <?php
                                $callid = $dlg['callid'] ?? $dlg['call_id'] ?? $dlg['CALLID'] ?? 'N/A';
                                $from   = $dlg['from_uri'] ?? $dlg['from'] ?? $dlg['FROM'] ?? 'N/A';
                                $to     = $dlg['to_uri'] ?? $dlg['to'] ?? $dlg['TO'] ?? 'N/A';
                                $state  = (int) ($dlg['state'] ?? $dlg['STATE'] ?? 0);
                                $stateLabel = match ($state) {
                                    1 => _('Early'),
                                    2 => _('Confirmed'),
                                    3 => _('Terminated'),
                                    4 => _('Deleted'),
                                    default => _('Unknown'),
                                };
                                $badgeClass = 'tsisip-badge';
                                if ($state === 2) {
                                    $badgeClass = 'tsisip-badge tsisip-badge-success';
                                } elseif ($state === 1) {
                                    $badgeClass = 'tsisip-badge tsisip-badge-warning';
                                } elseif ($state === 3 || $state === 4) {
                                    $badgeClass = 'tsisip-badge tsisip-badge-error';
                                }

                                // Infer topology hiding from flags or attributes
                                $flags = strtolower((string) ($dlg['flags'] ?? $dlg['FLAGS'] ?? ''));
                                $attrs = json_encode($dlg);
                                $isHidden = stripos($flags, 'topology') !== false
                                         || stripos($attrs, 'topology') !== false
                                         || stripos($attrs, 'hidden') !== false
                                         || stripos($attrs, 'th') !== false;

                                $thBadge = $isHidden
                                    ? '<span class="tsisip-badge tsisip-badge-success">' . _('Yes') . '</span>'
                                    : '<span class="tsisip-badge tsisip-badge--neutral">' . _('No') . '</span>';

                                $startTime = (int) ($dlg['start_time'] ?? $dlg['startTime'] ?? 0);
                                $duration = $startTime > 0 ? gmdate('H:i:s', time() - $startTime) : '—';
                                ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($callid); ?></code></td>
                                    <td><?php echo htmlspecialchars($from); ?></td>
                                    <td><?php echo htmlspecialchars($to); ?></td>
                                    <td>
                                        <span class="<?php echo htmlspecialchars($badgeClass, ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars($stateLabel); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $thBadge; ?></td>
                                    <td><?php echo htmlspecialchars($duration); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/common/footer.php'; ?>
