<?php
/**
 * TSiSIP Control Panel — User Location (usrloc)
 * AoR search, live contacts, and DB vs live comparison.
 */
require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';
require_once __DIR__ . '/common/mi-http.php';

requireAuth();
checkPasswordChange();

$pageTitle = _('User Location');

$aor = trim($_GET['aor'] ?? '');
$contacts = [];
$miData = ['success' => false, 'error' => null, 'data' => null];

try {
    if ($aor !== '') {
        $result = miHttpCall('ul_dump', [$aor]);
    } else {
        $result = miHttpCall('ul_dump', []);
    }
    $miData = $result;
    if ($result['success'] && is_array($result['data'])) {
        $raw = $result['data'];
        if (isset($raw['Contacts']) && is_array($raw['Contacts'])) {
            $contacts = $raw['Contacts'];
        } elseif (isset($raw['contacts']) && is_array($raw['contacts'])) {
            $contacts = $raw['contacts'];
        } elseif (isset($raw[0]) && is_array($raw[0])) {
            $contacts = $raw;
        } else {
            foreach ($raw as $key => $val) {
                if (is_array($val)) {
                    $contacts[] = $val;
                }
            }
        }
    }
} catch (Exception $e) {
    $miData['error'] = $e->getMessage();
}

// DB subscriber count
$pdo = getDb();
$dbCount = 0;
try {
    $dbCount = (int) $pdo->query('SELECT COUNT(*) FROM subscriber')->fetchColumn();
} catch (PDOException $e) {
    error_log('TSiSIP usrloc DB count failed: ' . $e->getMessage());
}

$liveCount = count($contacts);

require_once __DIR__ . '/common/header.php';
?>
<div class="tsisip-page">
    <div class="tsisip-page-header">
        <h1 class="tsisip-page-title"><?php echo $pageTitle; ?></h1>
    </div>

    <section class="tsisip-section">
        <form method="get" class="tsisip-filter-bar">
            <input type="text" name="aor" class="tsisip-input" placeholder="<?php echo _('Search AoR (user@domain)...'); ?>"
                   value="<?php echo htmlspecialchars($aor); ?>">
            <button type="submit" class="tsisip-btn tsisip-btn--primary"><?php echo _('Search'); ?></button>
            <a href="usrloc.php" class="tsisip-btn tsisip-btn--secondary"><?php echo _('Clear'); ?></a>
        </form>
    </section>

    <section class="tsisip-section">
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:16px;margin-bottom:16px;">
            <div class="tsisip-metric-card" style="background:var(--tsisip-surface-card);border:1px solid var(--tsisip-border-subtle);border-radius:8px;padding:16px;text-align:center;">
                <div style="font-size:var(--tsisip-text-sm);color:var(--tsisip-text-secondary);margin-bottom:8px;"><?php echo _('DB Subscribers'); ?></div>
                <div style="font-size:1.75rem;font-weight:700;color:var(--tsisip-primary-blue);"><?php echo number_format($dbCount); ?></div>
            </div>
            <div class="tsisip-metric-card" style="background:var(--tsisip-surface-card);border:1px solid var(--tsisip-border-subtle);border-radius:8px;padding:16px;text-align:center;">
                <div style="font-size:var(--tsisip-text-sm);color:var(--tsisip-text-secondary);margin-bottom:8px;"><?php echo _('Live Contacts'); ?></div>
                <div style="font-size:1.75rem;font-weight:700;color:var(--tsisip-primary-blue);"><?php echo number_format($liveCount); ?></div>
            </div>
            <div class="tsisip-metric-card" style="background:var(--tsisip-surface-card);border:1px solid var(--tsisip-border-subtle);border-radius:8px;padding:16px;text-align:center;">
                <div style="font-size:var(--tsisip-text-sm);color:var(--tsisip-text-secondary);margin-bottom:8px;"><?php echo _('Difference'); ?></div>
                <div style="font-size:1.75rem;font-weight:700;color:<?php echo $liveCount < $dbCount ? 'var(--tsisip-warning)' : 'var(--tsisip-success)'; ?>;"><?php echo number_format($dbCount - $liveCount); ?></div>
            </div>
        </div>
    </section>

    <?php if (!$miData['success']): ?>
        <div class="tsisip-alert tsisip-alert--warning" role="alert">
            <?php echo _('MI Error:'); ?> <?php echo htmlspecialchars($miData['error'] ?? 'Unknown'); ?>
        </div>
    <?php else: ?>
        <section class="tsisip-section">
            <h2 class="tsisip-section-title"><?php echo _('Contacts'); ?></h2>
            <div style="overflow-x:auto;">
                <table class="tsisip-table dataTable" data-tsisip-sortable>
                    <thead>
                        <tr>
                            <th><?php echo _('Contact'); ?></th>
                            <th><?php echo _('Expires'); ?></th>
                            <th><?php echo _('Path'); ?></th>
                            <th><?php echo _('Flags'); ?></th>
                            <th><?php echo _('Received'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($contacts)): ?>
                            <tr>
                                <td colspan="5" class="tsisip-empty"><?php echo _('No contacts found.'); ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($contacts as $c): ?>
                                <?php if (!is_array($c)) continue; ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($c['contact'] ?? $c['CONTACT'] ?? 'N/A'); ?></code></td>
                                    <td><?php echo htmlspecialchars($c['expires'] ?? $c['EXPIRES'] ?? '—'); ?></td>
                                    <td><code><?php echo htmlspecialchars($c['path'] ?? $c['PATH'] ?? '—'); ?></code></td>
                                    <td><?php echo htmlspecialchars($c['flags'] ?? $c['FLAGS'] ?? '—'); ?></td>
                                    <td><code><?php echo htmlspecialchars($c['received'] ?? $c['RECEIVED'] ?? '—'); ?></code></td>
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
