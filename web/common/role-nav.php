<?php
/**
 * TSiSIP Control Panel — Role-Based Navigation Sidebar
 */

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

$allowedPages = isset($roleNav[$userRole]) ? $roleNav[$userRole] : $roleNav['readonly'];

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<nav id="sidebar" class="tsisip-sidebar" aria-label="<?php echo _('Main navigation'); ?>">
    <ul class="tsisip-nav-list" role="menubar">
        <li class="tsisip-nav-item<?php echo $currentPage === 'dashboard' ? ' is-active' : ''; ?>" role="none">
            <a href="dashboard.php" class="tsisip-nav-link" role="menuitem"><?php echo _('Dashboard'); ?></a>
        </li>
        <li class="tsisip-nav-item<?php echo $currentPage === 'wiki' ? ' is-active' : ''; ?>" role="none">
            <a href="wiki.php" class="tsisip-nav-link" role="menuitem"><?php echo _('Wiki'); ?></a>
        </li>
        <?php foreach ($allowedPages as $page): ?>
            <?php if (isset($navLabels[$page])): ?>
                <li class="tsisip-nav-item<?php echo ($currentPage === 'wiki' && isset($_GET['page']) && $_GET['page'] === $page) ? ' is-active' : ''; ?>" role="none">
                    <a href="wiki.php?page=<?php echo htmlspecialchars($page, ENT_QUOTES, 'UTF-8'); ?>" class="tsisip-nav-link" role="menuitem"><?php echo htmlspecialchars($navLabels[$page], ENT_QUOTES, 'UTF-8'); ?></a>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>
</nav>
