<?php
/**
 * TSiSIP Control Panel — Role-Based Navigation Sidebar
 * Includes both system management pages and wiki pages.
 */

// Admin-only tool pages
$adminPages = [];
if ($userRole === 'admin' || $userRole === 'devops') {
    $adminPages = [
        'subscribers'  => _('Subscribers'),
        'cdr-viewer'   => _('CDR Viewer'),
        'dispatcher'   => _('Dispatcher Targets'),
        'rtpengine'    => _('RTPengine Sessions'),
        'audit-log'    => _('Audit Log'),
    ];
}

$roleNav = [
    'admin'    => ['system-overview', 'administrators', 'devops-sip', 'runbooks-troubleshooting', 'security-compliance', 'developers'],
    'devops'   => ['system-overview', 'devops-sip', 'runbooks-troubleshooting', 'security-compliance'],
    'dentist'  => ['system-overview', 'operators-users', 'dentists'],
    'assistant'=> ['system-overview', 'operators-users', 'assistants'],
    'user'     => ['system-overview', 'operators-users'],
    'readonly' => ['system-overview', 'operators-users'],
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

/* ------------------------------------------------------------------
 * System pages visible to admin + devops
 * ------------------------------------------------------------------ */
$systemPages = [];
if ($userRole === 'admin' || $userRole === 'devops') {
    $systemPages = [
        'dispatcher' => _('Dispatcher Targets'),
        'rtpengine'  => _('RTPengine Sessions'),
    ];
}

$allowedPages = isset($roleNav[$userRole]) ? $roleNav[$userRole] : $roleNav['readonly'];

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$currentWikiPage = isset($_GET['page']) ? $_GET['page'] : '';
?>
<nav id="sidebar" class="tsisip-sidebar" aria-label="<?php echo _('Main navigation'); ?>">
    <ul class="tsisip-nav-list" role="menubar">
        <li class="tsisip-nav-item<?php echo $currentPage === 'dashboard' ? ' is-active' : ''; ?>" role="none">
            <a href="dashboard.php" class="tsisip-nav-link" role="menuitem"><?php echo _('Dashboard'); ?></a>
        </li>

        <?php if (!empty($systemPages)): ?>
            <li class="tsisip-nav-heading" role="none">
                <span class="tsisip-nav-heading-text"><?php echo _('System'); ?></span>
            </li>
            <?php foreach ($systemPages as $sysPage => $sysLabel): ?>
                <li class="tsisip-nav-item<?php echo $currentPage === $sysPage ? ' is-active' : ''; ?>" role="none">
                    <a href="<?php echo htmlspecialchars($sysPage, ENT_QUOTES, 'UTF-8'); ?>.php"
                       class="tsisip-nav-link" role="menuitem">
                        <?php echo htmlspecialchars($sysLabel, ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!empty($adminPages)): ?>
            <li class="tsisip-nav-heading" role="none">
                <span class="tsisip-nav-heading-text"><?php echo _('Administration'); ?></span>
            </li>
            <?php foreach ($adminPages as $apage => $alabel): ?>
                <li class="tsisip-nav-item<?php echo $currentPage === $apage ? ' is-active' : ''; ?>" role="none">
                    <a href="<?php echo htmlspecialchars($apage, ENT_QUOTES, 'UTF-8'); ?>.php"
                       class="tsisip-nav-link" role="menuitem">
                        <?php echo htmlspecialchars($alabel, ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>

        <li class="tsisip-nav-heading" role="none">
            <span class="tsisip-nav-heading-text"><?php echo _('Wiki'); ?></span>
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
