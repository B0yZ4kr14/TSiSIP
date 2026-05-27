<?php
/**
 * TSiSIP Control Panel — TCP Connections
 * TCP connection list with state filter.
 */
require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';
require_once __DIR__ . '/common/mi-http.php';

requireAuth();
checkPasswordChange();
requireRole('devops');

$pageTitle = _('TCP Connections');

$stateFilter = trim($_GET['state'] ?? '');
$connections = [];
$miData = ['success' => false, 'error' => null, 'data' => null];

try {
    $result = miHttpCall('tcp_list', []);
    $miData = $result;
    if ($result['success'] && is_array($result['data'])) {
        $raw = $result['data'];
        if (isset($raw['Connections']) && is_array($raw['Connections'])) {
            $connections = $raw['Connections'];
        } elseif (isset($raw['connections']) && is_array($raw['connections'])) {
            $connections = $raw['connections'];
        } elseif (isset($raw[0]) && is_array($raw[0])) {
            $connections = $raw;
        } else {
            foreach ($raw as $key => $val) {
                if (is_array($val)) {
                    $connections[] = $val;
                }
            }
        }
    }
} catch (Exception $e) {
    $miData['error'] = $e->getMessage();
}

$allStates = [];
foreach ($connections as $c) {
    if (is_array($c)) {
        $st = strtolower((string) ($c['state'] ?? $c['STATE'] ?? 'unknown'));
        if ($st && !in_array($st, $allStates, true)) {
            $allStates[] = $st;
        }
    }
}
sort($allStates);

$filtered = $connections;
if ($stateFilter !== '') {
    $filtered = array_filter($connections, function ($c) use ($stateFilter) {
        if (!is_array($c)) return false;
        $st = strtolower((string) ($c['state'] ?? $c['STATE'] ?? ''));
        return $st === strtolower($stateFilter);
    });
}

require_once __DIR__ . '/common/header.php';
?>
<div class="tsisip-page">
    <div class="tsisip-page-header">
        <h1 class="tsisip-page-title"><?php echo $pageTitle; ?></h1>
    </div>

    <section class="tsisip-section">
        <form method="get" class="tsisip-filter-bar">
            <select name="state" class="tsisip-select">
                <option value=""><?php echo _('All States'); ?></option>
                <?php foreach ($allStates as $st): ?>
                    <option value="<?php echo htmlspecialchars($st); ?>" <?php echo strtolower($stateFilter) === $st ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars(ucfirst($st)); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="tsisip-btn tsisip-btn--primary"><?php echo _('Filter'); ?></button>
            <a href="tcp-connections.php" class="tsisip-btn tsisip-btn--secondary"><?php echo _('Clear'); ?></a>
        </form>
    </section>

    <section class="tsisip-section">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
            <h2 class="tsisip-section-title"><?php echo _('Connections'); ?></h2>
            <span class="tsisip-badge tsisip-badge-info"><?php echo sprintf(_('Total: %d'), count($filtered)); ?></span>
        </div>
        <?php if (!$miData['success']): ?>
            <div class="tsisip-alert tsisip-alert--warning" role="alert">
                <?php echo _('MI Error:'); ?> <?php echo htmlspecialchars($miData['error'] ?? 'Unknown'); ?>
            </div>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="tsisip-table dataTable" data-tsisip-sortable>
                    <thead>
                        <tr>
                            <th><?php echo _('ID'); ?></th>
                            <th><?php echo _('State'); ?></th>
                            <th><?php echo _('Peer'); ?></th>
                            <th><?php echo _('Local'); ?></th>
                            <th><?php echo _('Type'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($filtered)): ?>
                            <tr>
                                <td colspan="5" class="tsisip-empty"><?php echo _('No TCP connections found.'); ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($filtered as $c): ?>
                                <?php if (!is_array($c)) continue; ?>
                                <?php
                                $state = strtolower((string) ($c['state'] ?? $c['STATE'] ?? 'unknown'));
                                $badgeClass = 'tsisip-badge';
                                if ($state === 'established' || $state === 'connected') {
                                    $badgeClass = 'tsisip-badge tsisip-badge-success';
                                } elseif ($state === 'closed' || $state === 'error') {
                                    $badgeClass = 'tsisip-badge tsisip-badge-error';
                                } elseif ($state === 'waiting' || $state === 'syn_sent') {
                                    $badgeClass = 'tsisip-badge tsisip-badge-warning';
                                }
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars((string) ($c['id'] ?? $c['ID'] ?? '—')); ?></td>
                                    <td>
                                        <span class="<?php echo htmlspecialchars($badgeClass, ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars(ucfirst($state)); ?>
                                        </span>
                                    </td>
                                    <td><code><?php echo htmlspecialchars($c['peer'] ?? $c['PEER'] ?? '—'); ?></code></td>
                                    <td><code><?php echo htmlspecialchars($c['local'] ?? $c['LOCAL'] ?? '—'); ?></code></td>
                                    <td><?php echo htmlspecialchars($c['type'] ?? $c['TYPE'] ?? '—'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php require_once __DIR__ . '/common/footer.php'; ?>
