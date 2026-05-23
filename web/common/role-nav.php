<?php
/**
 * TSiSIP Control Panel — Role-Based Navigation Sidebar
 * Separated into: Dashboard, Monitoring, Configuration, System, Operations, Documentation, Account
 */

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$currentWikiPage = isset($_GET['page']) ? $_GET['page'] : '';

// ---------------------------------------------------------------
// 1. MONITORING (read-only dashboards)
// ---------------------------------------------------------------
$monitoringPages = [];
if ($userRole === 'admin' || $userRole === 'devops') {
    $monitoringPages = [
        'statistics'      => _('Statistics'),
        'dialog'          => _('Active Dialogs'),
        'trunk-status'    => _('Trunk Status'),
        'cdr-viewer'      => _('CDR Viewer'),
        'audit-log'       => _('Audit Log'),
    ];
}

// ---------------------------------------------------------------
// 2. CONFIGURATION (CRUD on OpenSIPS tables)
// ---------------------------------------------------------------
$configPages = [];
if ($userRole === 'admin' || $userRole === 'devops') {
    $configPages = [
        'subscribers'     => _('Subscribers'),
        'tenants'         => _('Tenants'),
        'domains'         => _('Domains'),
        'dialplan'        => _('Dialplan'),
        'dispatcher'      => _('Dispatcher Targets'),
        'trunk-providers' => _('Trunk Providers'),
        'trunk-dids'      => _('DID Mappings'),
        'header-routing'  => _('Header Routing'),
        'userblacklist'   => _('User Blacklist'),
        'address'         => _('IP Whitelist'),
    ];
}

// ---------------------------------------------------------------
// 3. SYSTEM (runtime control, certs, MI)
// ---------------------------------------------------------------
$systemPages = [];
if ($userRole === 'admin' || $userRole === 'devops') {
    $systemPages = [
        'rtpengine'       => _('RTPengine Sessions'),
        'mi-commands'     => _('MI Commands'),
        'tls-management'  => _('TLS Certificates'),
        'users'           => _('OCP Users'),
    ];
}

// ---------------------------------------------------------------
// 4. WIKI / DOCUMENTATION (role-scoped)
// ---------------------------------------------------------------
$roleNav = [
    'admin'     => ['system-overview', 'administrators', 'devops-sip', 'runbooks-troubleshooting', 'security-compliance', 'developers'],
    'devops'    => ['system-overview', 'devops-sip', 'runbooks-troubleshooting', 'security-compliance'],
    'dentist'   => ['system-overview', 'operators-users', 'dentists'],
    'assistant' => ['system-overview', 'operators-users', 'assistants'],
    'user'      => ['system-overview', 'operators-users'],
    'readonly'  => ['system-overview', 'operators-users'],
];

$navLabels = [
    'system-overview'           => _('System Overview'),
    'administrators'            => _('Administrators'),
    'devops-sip'                => _('DevOps SIP'),
    'runbooks-troubleshooting'  => _('Runbooks & Troubleshooting'),
    'security-compliance'       => _('Security & Compliance'),
    'developers'                => _('Developers'),
    'operators-users'           => _('Operators & Users'),
    'dentists'                  => _('Dentists'),
    'assistants'                => _('Assistants'),
];

$allowedPages = isset($roleNav[$userRole]) ? $roleNav[$userRole] : $roleNav['readonly'];
?>
<nav id="sidebar" class="tsisip-sidebar" aria-label="<?php echo _('Main navigation'); ?>">
    <ul class="tsisip-nav-list" role="menubar">

        <!-- Dashboard -->
        <li class="tsisip-nav-item<?php echo $currentPage === 'dashboard' ? ' is-active' : ''; ?>" role="none">
            <a href="dashboard.php" class="tsisip-nav-link" role="menuitem"><?php echo _('Dashboard'); ?></a>
        </li>

        <!-- Monitoring -->
        <?php if (!empty($monitoringPages)): ?>
            <li class="tsisip-nav-heading" role="none">
                <span class="tsisip-nav-heading-text"><?php echo _('Monitoring'); ?></span>
            </li>
            <?php foreach ($monitoringPages as $page => $label): ?>
                <li class="tsisip-nav-item<?php echo $currentPage === $page ? ' is-active' : ''; ?>" role="none">
                    <a href="<?php echo htmlspecialchars($page, ENT_QUOTES, 'UTF-8'); ?>.php"
                       class="tsisip-nav-link" role="menuitem">
                        <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Configuration -->
        <?php if (!empty($configPages)): ?>
            <li class="tsisip-nav-heading" role="none">
                <span class="tsisip-nav-heading-text"><?php echo _('Configuration'); ?></span>
            </li>
            <?php foreach ($configPages as $page => $label): ?>
                <li class="tsisip-nav-item<?php echo $currentPage === $page ? ' is-active' : ''; ?>" role="none">
                    <a href="<?php echo htmlspecialchars($page, ENT_QUOTES, 'UTF-8'); ?>.php"
                       class="tsisip-nav-link" role="menuitem">
                        <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- System -->
        <?php if (!empty($systemPages)): ?>
            <li class="tsisip-nav-heading" role="none">
                <span class="tsisip-nav-heading-text"><?php echo _('System'); ?></span>
            </li>
            <?php foreach ($systemPages as $page => $label): ?>
                <li class="tsisip-nav-item<?php echo $currentPage === $page ? ' is-active' : ''; ?>" role="none">
                    <a href="<?php echo htmlspecialchars($page, ENT_QUOTES, 'UTF-8'); ?>.php"
                       class="tsisip-nav-link" role="menuitem">
                        <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Wiki / Documentation -->
        <li class="tsisip-nav-heading" role="none">
            <span class="tsisip-nav-heading-text"><?php echo _('Documentation'); ?></span>
        </li>
        <li class="tsisip-nav-item<?php echo $currentPage === 'wiki' && $currentWikiPage === '' ? ' is-active' : ''; ?>" role="none">
            <a href="wiki.php" class="tsisip-nav-link" role="menuitem"><?php echo _('Wiki Home'); ?></a>
        </li>
        <?php foreach ($allowedPages as $page): ?>
            <?php if (isset($navLabels[$page])): ?>
                <li class="tsisip-nav-item<?php echo ($currentPage === 'wiki' && $currentWikiPage === $page) ? ' is-active' : ''; ?>" role="none">
                    <a href="wiki.php?page=<?php echo htmlspecialchars($page, ENT_QUOTES, 'UTF-8'); ?>"
                       class="tsisip-nav-link" role="menuitem">
                        <?php echo htmlspecialchars($navLabels[$page], ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>

        <!-- Account -->
        <li class="tsisip-nav-heading" role="none">
            <span class="tsisip-nav-heading-text"><?php echo _('Account'); ?></span>
        </li>
        <li class="tsisip-nav-item<?php echo $currentPage === 'change-password' ? ' is-active' : ''; ?>" role="none">
            <a href="change-password.php" class="tsisip-nav-link" role="menuitem">
                <?php echo _('Change Passphrase'); ?>
            </a>
        </li>
        <li class="tsisip-nav-item" role="none">
            <a href="logout.php" class="tsisip-nav-link" role="menuitem">
                <?php echo _('Sign Out'); ?>
            </a>
        </li>
    </ul>
</nav>
