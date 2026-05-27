<?php
/**
 * TSiSIP Control Panel — Subscriber Statistics
 * Aggregated subscriber metrics from PostgreSQL.
 */
require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';

requireAuth();
checkPasswordChange();
requireRole('devops');

logAuditEvent('CONFIG_VIEW', 'system', 'subscriber-stats', true);

$pdo = getDb();

// --- Aggregates ---
$totalStmt = $pdo->query("SELECT COUNT(*) FROM subscriber");
$totalSubscribers = (int) $totalStmt->fetchColumn();

$activeStmt = $pdo->query("SELECT COUNT(*) FROM subscriber WHERE datetime_created > NOW() - INTERVAL '30 days'");
$active30d = (int) $activeStmt->fetchColumn();

$tenantStmt = $pdo->query("SELECT COUNT(DISTINCT tenant_id) FROM subscriber WHERE tenant_id IS NOT NULL");
$tenantCount = (int) $tenantStmt->fetchColumn();

$domainStmt = $pdo->query("SELECT domain, COUNT(*) as cnt FROM subscriber GROUP BY domain ORDER BY cnt DESC LIMIT 10");
$topDomains = $domainStmt->fetchAll();

require_once __DIR__ . '/common/header.php';
?>
<div id="content" class="tsisip-dashboard">
    <h1><?php echo _('Subscriber Statistics'); ?></h1>

    <div class="tsisip-dashboard-section">
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:16px;margin-bottom:24px;">
            <div class="tsisip-metric-card" style="background:var(--tsisip-surface-card);border:1px solid var(--tsisip-border-subtle);border-radius:8px;padding:16px;text-align:center;">
                <div style="font-size:var(--tsisip-text-sm);color:var(--tsisip-text-secondary);margin-bottom:8px;"><?php echo _('Total Subscribers'); ?></div>
                <div style="font-size:1.75rem;font-weight:700;color:var(--tsisip-primary-blue);"><?php echo $totalSubscribers; ?></div>
            </div>
            <div class="tsisip-metric-card" style="background:var(--tsisip-surface-card);border:1px solid var(--tsisip-border-subtle);border-radius:8px;padding:16px;text-align:center;">
                <div style="font-size:var(--tsisip-text-sm);color:var(--tsisip-text-secondary);margin-bottom:8px;"><?php echo _('Active (30d)'); ?></div>
                <div style="font-size:1.75rem;font-weight:700;color:var(--tsisip-primary-blue);"><?php echo $active30d; ?></div>
            </div>
            <div class="tsisip-metric-card" style="background:var(--tsisip-surface-card);border:1px solid var(--tsisip-border-subtle);border-radius:8px;padding:16px;text-align:center;">
                <div style="font-size:var(--tsisip-text-sm);color:var(--tsisip-text-secondary);margin-bottom:8px;"><?php echo _('Tenants'); ?></div>
                <div style="font-size:1.75rem;font-weight:700;color:var(--tsisip-primary-blue);"><?php echo $tenantCount; ?></div>
            </div>
        </div>
    </div>

    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo _('Top Domains'); ?></h2>
        <?php if (empty($topDomains)): ?>
            <div class="tsisip-badge tsisip-badge--info"><?php echo _('No subscriber data.'); ?></div>
        <?php else: ?>
            <table class="tsisip-table dataTable">
                <thead>
                    <tr>
                        <th><?php echo _('Domain'); ?></th>
                        <th><?php echo _('Subscribers'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topDomains as $d): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($d['domain']); ?></td>
                            <td><?php echo (int) $d['cnt']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</div>
<?php require_once __DIR__ . '/common/footer.php'; ?>
