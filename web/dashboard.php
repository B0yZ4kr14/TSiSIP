<?php
/**
 * TSiSIP Control Panel — Dashboard
 * Role-aware landing page after login with system management links.
 */
require_once __DIR__ . '/common/config.php';
requireAuth();
checkPasswordChange();
logAuditEvent('CONFIG_VIEW', 'system', 'dashboard', true);
require_once __DIR__ . '/common/header.php';

$roleLabels = [
    'admin'     => _('Administrator'),
    'devops'    => _('DevOps Engineer'),
    'dentist'   => _('Dentist'),
    'assistant' => _('Assistant'),
    'user'      => _('User'),
    'readonly'  => _('Read-Only User'),
];

$displayRole = isset($roleLabels[$userRole]) ? $roleLabels[$userRole] : $roleLabels['readonly'];

/* ------------------------------------------------------------------
 * System Management links (admin + devops)
 * ------------------------------------------------------------------ */
$systemLinks = [];
if ($userRole === 'admin' || $userRole === 'devops') {
    $systemLinks = [
        ['url' => 'dispatcher.php',   'label' => _('Dispatcher Targets'),   'icon' => 'route'],
        ['url' => 'rtpengine.php',    'label' => _('RTPengine Sessions'),   'icon' => 'broadcast'],
        ['url' => 'audit-log.php',    'label' => _('Audit Log & Compliance'), 'icon' => 'shield'],
        ['url' => 'dialplan.php',     'label' => _('Dialplan'),             'icon' => 'list'],
        ['url' => 'domains.php',      'label' => _('SIP Domains'),          'icon' => 'globe'],
        ['url' => 'dialog.php',       'label' => _('Active Dialogs'),       'icon' => 'phone'],
        ['url' => 'mi-commands.php',  'label' => _('MI Commands'),          'icon' => 'terminal'],
        ['url' => 'statistics.php',   'label' => _('Statistics'),           'icon' => 'chart'],
        ['url' => 'tls-management.php', 'label' => _('TLS Certificates'),    'icon' => 'lock'],
        ['url' => 'gateway-health.php', 'label' => _('Gateway Health'),       'icon' => 'heart'],
        ['url' => 'call-queue.php',     'label' => _('Live Call Queue'),      'icon' => 'queue'],
        ['url' => 'topology.php',       'label' => _('Network Topology'),     'icon' => 'network'],
        ['url' => 'failover.php',       'label' => _('Manual Failover'),      'icon' => 'switch'],
        ['url' => 'alert-history.php',  'label' => _('Alert History'),        'icon' => 'bell'],
        ['url' => 'rtpengine-status.php', 'label' => _('RTPengine Status'),       'icon' => 'broadcast'],
        ['url' => 'subscriber-stats.php', 'label' => _('Subscriber Statistics'),  'icon' => 'users'],
        ['url' => 'system-config.php',  'label' => _('System Configuration'),    'icon' => 'sliders'],
        ['url' => 'help.php',           'label' => _('Help & Documentation'),   'icon' => 'help-circle'],
    ['url' => 'search.php',       'label' => _('Global Search'),       'icon' => 'search'],
    ];
}

/* ------------------------------------------------------------------
 * Wiki / Documentation links (all roles)
 * ------------------------------------------------------------------ */
$wikiLinks = [];
if (isset($roleNav[$userRole])) {
    foreach ($roleNav[$userRole] as $page) {
        if (isset($navLabels[$page])) {
            $wikiLinks[] = [
                'url'   => 'wiki.php?page=' . urlencode($page),
                'label' => $navLabels[$page],
            ];
        }
    }
}
?>
<div id="content" class="tsisip-dashboard">
    <h1>
        <?php echo _('Welcome'); ?>,
        <span class="tsisip-dashboard-role"><?php echo htmlspecialchars($displayRole, ENT_QUOTES, 'UTF-8'); ?></span>
    </h1>

    <?php if (!empty($systemLinks)): ?>
    <div class="tsisip-dashboard-section">
        <h2><?php echo _('System Management'); ?></h2>
        <div class="tsisip-dashboard-links">
            <?php foreach ($systemLinks as $link): ?>
                <a href="<?php echo htmlspecialchars($link['url'], ENT_QUOTES, 'UTF-8'); ?>"
                   class="tsisip-btn tsisip-btn-primary">
                    <?php echo htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="tsisip-dashboard-section">
        <h2><?php echo _('Documentation & Wiki'); ?></h2>
        <?php if (!empty($wikiLinks)): ?>
            <div class="tsisip-dashboard-links">
                <?php foreach ($wikiLinks as $link): ?>
                    <a href="<?php echo htmlspecialchars($link['url'], ENT_QUOTES, 'UTF-8'); ?>"
                       class="tsisip-btn tsisip-btn-secondary">
                        <?php echo htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="tsisip-text-muted"><?php echo _('No wiki links available for your role.'); ?></p>
        <?php endif; ?>
    </div>

    <div class="tsisip-dashboard-section">
        <h2><?php echo _('System Status'); ?></h2>
        <p><?php echo _('TSiSIP Control Panel is operational.'); ?></p>
        <ul class="tsisip-status-list">
            <li><span class="tsisip-status-dot tsisip-status-dot--ok"></span> OpenSIPS SIP Proxy</li>
            <li><span class="tsisip-status-dot tsisip-status-dot--ok"></span> RTPengine Media Relay</li>
            <li><span class="tsisip-status-dot tsisip-status-dot--ok"></span> PostgreSQL Database</li>
            <li><span class="tsisip-status-dot tsisip-status-dot--ok"></span> OCP Web Interface</li>
        </ul>
        <p class="tsisip-hint">
            <?php echo _('Feature 020 tools are now available: Dialog Viewer, MI Commands, Statistics, Dialplan, Domains, TLS Management, Gateway Health, Live Call Queue, Network Topology, Manual Failover, Alert History, RTPengine Status, Subscriber Statistics, System Configuration, and Help.'); ?>
        </p>
    </div>
</div>
<?php require_once __DIR__ . '/common/footer.php'; ?>
