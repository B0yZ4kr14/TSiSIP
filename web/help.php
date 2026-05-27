<?php
/**
 * TSiSIP Control Panel — Help & Documentation
 * Contextual help page with links to wiki and documentation.
 */
require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';

requireAuth();
checkPasswordChange();

logAuditEvent('CONFIG_VIEW', 'system', 'help', true);

$helpTopics = [
    [
        'title' => _('Getting Started'),
        'icon' => 'book',
        'description' => _('Overview of the TSiSIP Control Panel and basic navigation.'),
        'links' => [
            ['url' => 'wiki.php?page=getting-started', 'label' => _('Getting Started Guide')],
            ['url' => 'wiki.php?page=dashboard', 'label' => _('Dashboard Overview')],
        ],
    ],
    [
        'title' => _('User Management'),
        'icon' => 'users',
        'description' => _('Managing users, roles, and permissions.'),
        'links' => [
            ['url' => 'wiki.php?page=user-management', 'label' => _('User Management Guide')],
            ['url' => 'wiki.php?page=roles', 'label' => _('Role Definitions')],
        ],
    ],
    [
        'title' => _('SIP Configuration'),
        'icon' => 'phone',
        'description' => _('OpenSIPS configuration, routing, and dispatcher management.'),
        'links' => [
            ['url' => 'wiki.php?page=opensips-config', 'label' => _('OpenSIPS Configuration')],
            ['url' => 'wiki.php?page=dispatcher', 'label' => _('Dispatcher Setup')],
            ['url' => 'wiki.php?page=header-routing', 'label' => _('Header Routing')],
        ],
    ],
    [
        'title' => _('Security'),
        'icon' => 'shield',
        'description' => _('Security settings, TLS, authentication, and audit logging.'),
        'links' => [
            ['url' => 'wiki.php?page=security', 'label' => _('Security Guide')],
            ['url' => 'wiki.php?page=tls', 'label' => _('TLS Configuration')],
            ['url' => 'wiki.php?page=audit', 'label' => _('Audit Logging')],
        ],
    ],
    [
        'title' => _('Monitoring'),
        'icon' => 'activity',
        'description' => _('Real-time monitoring, statistics, and alerting.'),
        'links' => [
            ['url' => 'wiki.php?page=monitoring', 'label' => _('Monitoring Guide')],
            ['url' => 'wiki.php?page=statistics', 'label' => _('Statistics Reference')],
            ['url' => 'wiki.php?page=alerts', 'label' => _('Alert Configuration')],
        ],
    ],
    [
        'title' => _('Troubleshooting'),
        'icon' => 'tool',
        'description' => _('Common issues and troubleshooting steps.'),
        'links' => [
            ['url' => 'wiki.php?page=troubleshooting', 'label' => _('Troubleshooting Guide')],
            ['url' => 'wiki.php?page=mi-commands', 'label' => _('MI Commands Reference')],
        ],
    ],
];

require_once __DIR__ . '/common/header.php';
?>
<div id="content" class="tsisip-dashboard">
    <h1><?php echo _('Help & Documentation'); ?></h1>

    <div class="tsisip-dashboard-section">
        <p class="tsisip-text-muted">
            <?php echo _('Find guides, references, and troubleshooting information for the TSiSIP platform.'); ?>
        </p>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;">
        <?php foreach ($helpTopics as $topic): ?>
            <div class="tsisip-dashboard-section" style="background:var(--tsisip-surface-card);border:1px solid var(--tsisip-border-subtle);border-radius:8px;padding:16px;">
                <h2 style="font-size:1.125rem;font-weight:600;margin-bottom:8px;">
                    <?php echo htmlspecialchars($topic['title']); ?>
                </h2>
                <p style="color:var(--tsisip-text-secondary);font-size:0.875rem;margin-bottom:12px;">
                    <?php echo htmlspecialchars($topic['description']); ?>
                </p>
                <ul style="list-style:none;padding:0;margin:0;">
                    <?php foreach ($topic['links'] as $link): ?>
                        <li style="margin-bottom:4px;">
                            <a href="<?php echo htmlspecialchars($link['url']); ?>" class="tsisip-header-link">
                                <?php echo htmlspecialchars($link['label']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php require_once __DIR__ . '/common/footer.php'; ?>
