<?php
/**
 * TSiSIP Control Panel — Blacklists
 * View OpenSIPS blacklisted entries.
 */
require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';
require_once __DIR__ . '/common/mi-http.php';

requireAuth();
checkPasswordChange();

$pageTitle = _('Blacklists');

$entries = [];
$miData = ['success' => false, 'error' => null, 'data' => null];

try {
    $result = miHttpCall('list_blacklists', []);
    $miData = $result;
    if ($result['success'] && is_array($result['data'])) {
        $raw = $result['data'];
        if (isset($raw['Blacklists']) && is_array($raw['Blacklists'])) {
            $entries = $raw['Blacklists'];
        } elseif (isset($raw['blacklists']) && is_array($raw['blacklists'])) {
            $entries = $raw['blacklists'];
        } elseif (isset($raw[0]) && is_array($raw[0])) {
            $entries = $raw;
        } else {
            foreach ($raw as $key => $val) {
                if (is_array($val)) {
                    $entries[] = $val;
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

    <?php if (!$miData['success']): ?>
        <div class="tsisip-alert tsisip-alert--warning" role="alert">
            <?php echo _('MI Error:'); ?> <?php echo htmlspecialchars($miData['error'] ?? 'Unknown'); ?>
        </div>
    <?php else: ?>
        <section class="tsisip-section">
            <h2 class="tsisip-section-title"><?php echo _('Blacklisted Entries'); ?></h2>
            <div style="overflow-x:auto;">
                <table class="tsisip-table dataTable" data-tsisip-sortable>
                    <thead>
                        <tr>
                            <th><?php echo _('List Name'); ?></th>
                            <th><?php echo _('Entry'); ?></th>
                            <th><?php echo _('Reason'); ?></th>
                            <th><?php echo _('Expires'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($entries)): ?>
                            <tr>
                                <td colspan="4" class="tsisip-empty"><?php echo _('No blacklisted entries.'); ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($entries as $e): ?>
                                <?php if (!is_array($e)) continue; ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($e['list'] ?? $e['list_name'] ?? $e['LIST'] ?? 'default'); ?></code></td>
                                    <td><code><?php echo htmlspecialchars($e['entry'] ?? $e['ip'] ?? $e['ENTRY'] ?? 'N/A'); ?></code></td>
                                    <td><?php echo htmlspecialchars($e['reason'] ?? $e['REASON'] ?? '—'); ?></td>
                                    <td><?php echo htmlspecialchars($e['expires'] ?? $e['EXPIRES'] ?? '—'); ?></td>
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
