<?php
/**
 * TSiSIP Control Panel — Role-Based Navigation Sidebar
 * Full OCP v9.3.6 parity: 32 modules across 6 groups
 */

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$currentWikiPage = isset($_GET['page']) ? $_GET['page'] : '';

// Role hierarchy for access checks
$isAdmin   = ($userRole === 'admin');
$isDevOps  = ($userRole === 'devops');
$isDentist = ($userRole === 'dentist');
$isAssist  = ($userRole === 'assistant');
$isUser    = ($userRole === 'user');
$isReadOnly = ($userRole === 'readonly');

// -------------------------------------------------------------
// 1. SIP USERS (subscriber management)
// -------------------------------------------------------------
$sipUserPages = [
    'subscribers'      => _('Subscribers'),
    'subscriber-stats' => _('Subscriber Stats'),
    'aliases'          => _('Aliases'),
    'groups'           => _('Groups'),
    'userblacklist'    => _('User Blacklist'),
];
// Role filter: readonly sees list only (handled in each module)
$sipUserVisible = ($isAdmin || $isDevOps || $isDentist || $isAssist || $isUser || $isReadOnly);

// -------------------------------------------------------------
// 2. SYSTEM (OpenSIPS provisioning & monitoring)
// -------------------------------------------------------------
$systemPages = [
    'dashboard'         => _('Dashboard'),
    'address'           => _('Addresses'),
    'call-center'       => _('Call Center'),
    'call-queue'        => _('Call Queue'),
    'cdr-viewer'        => _('CDR Viewer'),
    'clusterer'         => _('Clusterer'),
    'config-table'      => _('Config Table'),
    'dialog'            => _('Active Dialogs'),
    'dialplan'          => _('Dialplan'),
    'dispatcher'        => _('Dispatcher'),
    'domains'           => _('Domains'),
    'dynamic-routing'   => _('Dynamic Routing'),
    'failover'          => _('Failover'),
    'gateway-health'    => _('Gateway Health'),
    'keepalived'        => _('Keepalived'),
    'load-balancer'     => _('Load Balancer'),
    'mi-commands'       => _('MI Commands'),
    'monit'             => _('Monit'),
    'rtpengine'         => _('RTPEngine'),
    'rtpengine-status'  => _('RTPengine Status'),
    'rtpproxy'          => _('RTPProxy'),
    'search'            => _('Search'),
    'siptrace'          => _('SIPtrace'),
    'smpp-gateway'      => _('SMPP Gateway'),
    'sockets-management'=> _('Sockets Management'),
    'cache-manager'     => _('Cache Manager'),
    'system-health'     => _('System Health'),
    'topology'          => _('Network Topology'),
    'topology-hiding'   => _('Topology Hiding'),
    'tviewer'           => _('TViewer'),
    'statistics'        => _('Statistics'),
    'status-report'     => _('Status Report'),
    'tls-management'    => _('TLS Certificates'),
    'uac-registrant'    => _('UAC Registrant'),
];
// MI Commands restricted to devops/admin
$systemVisible = ($isAdmin || $isDevOps || $isDentist || $isAssist || $isUser || $isReadOnly);

// -------------------------------------------------------------
// 3. TRUNKING (TSiSIP-specific)
// -------------------------------------------------------------
$trunkPages = [
    'trunk-providers' => _('Trunk Providers'),
    'trunk-dids'      => _('DID Mappings'),
    'trunk-status'    => _('Trunk Status'),
];
$trunkVisible = ($isAdmin || $isDevOps || $isDentist || $isAssist);

// -------------------------------------------------------------
// 4. RUNTIME (live system status)
// -------------------------------------------------------------
$runtimePages = [
    'memory-status'    => _('Memory Status'),
    'processes'        => _('Processes'),
    'tcp-connections'  => _('TCP Connections'),
    'usrloc'           => _('USRLoc Live'),
    'blacklists'       => _('Blacklists'),
    'timers'           => _('Timers'),
    'version'          => _('Version'),
];
$runtimeVisible = ($isAdmin || $isDevOps || $isDentist || $isAssist || $isUser || $isReadOnly);

// -------------------------------------------------------------
// 5. SECURITY (DDoS & rate limiting)
// -------------------------------------------------------------
$securityPages = [
    'pike-monitor' => _('Pike Monitor'),
    'ratelimit'    => _('Rate Limit'),
];
$securityVisible = ($isAdmin || $isDevOps || $isDentist || $isAssist || $isUser || $isReadOnly);

// -------------------------------------------------------------
// 6. NAT & PRESENCE
// -------------------------------------------------------------
$natPresencePages = [
    'nat-helper'      => _('NAT Helper'),
    'topology-hiding' => _('Topology Hiding'),
    'presence'        => _('Presence'),
];
$natPresenceVisible = ($isAdmin || $isDevOps || $isDentist || $isAssist || $isUser || $isReadOnly);

// -------------------------------------------------------------
// 7. ADVANCED (low-level inspection)
// -------------------------------------------------------------
$advancedPages = [
    'hash-tables'  => _('Hash Tables'),
    'avp-inspector'=> _('AVP Inspector'),
];
$advancedVisible = ($isAdmin || $isDevOps || $isDentist || $isAssist || $isUser || $isReadOnly);

// -------------------------------------------------------------
// 8. ADMINISTRATION (tenants, routing, users, audit, wiki)
// -------------------------------------------------------------
$adminPages = [
    'tenants'         => _('Tenants'),
    'header-routing'  => _('Header Routing'),
    'users'           => _('Admin Users'),
    'api-keys'        => _('API Keys'),
    'api-docs'        => _('API Docs'),
    'audit-log'       => _('Audit Log'),
    'audit-export'    => _('Audit Export'),
    'backup-status'   => _('Backup Status'),
    'alert-history'   => _('Alert History'),
    'feedback-list'   => _('Feedback List'),
    'reports'         => _('Reports'),
    'scheduled-tasks' => _('Scheduled Tasks'),
    'system-config'   => _('System Config'),
    'system-events'   => _('System Events'),
    'system-logs'     => _('System Logs'),
    'gateway-health'  => _('Gateway Health'),
    'health'          => _('Health Check'),
    'healthcheck-audit' => _('Healthcheck Audit'),
];
$adminVisible = ($isAdmin || $isDevOps);

// -------------------------------------------------------------
// 5. WIKI / DOCUMENTATION (role-scoped)
// -------------------------------------------------------------
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

        <!-- Dashboard (always visible) -->
        <li class="tsisip-nav-item<?php echo $currentPage === 'dashboard' ? ' is-active' : ''; ?>" role="none">
            <a href="dashboard.php" class="tsisip-nav-link" role="menuitem"><?php echo _('Dashboard'); ?></a>
        </li>

        <!-- SIP Users -->
        <?php if ($sipUserVisible && !empty($sipUserPages)): ?>
            <li class="tsisip-nav-heading" role="none">
                <span class="tsisip-nav-heading-text"><?php echo _('SIP Users'); ?></span>
            </li>
            <?php foreach ($sipUserPages as $page => $label): ?>
                <li class="tsisip-nav-item<?php echo $currentPage === $page ? ' is-active' : ''; ?>" role="none">
                    <a href="<?php echo htmlspecialchars($page, ENT_QUOTES, 'UTF-8'); ?>.php"
                       class="tsisip-nav-link" role="menuitem">
                        <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- System -->
        <?php if ($systemVisible && !empty($systemPages)): ?>
            <li class="tsisip-nav-heading" role="none">
                <span class="tsisip-nav-heading-text"><?php echo _('System'); ?></span>
            </li>
            <?php foreach ($systemPages as $page => $label): ?>
                <?php
                // MI Commands restricted
                if ($page === 'mi-commands' && !($isAdmin || $isDevOps)) continue;
                ?>
                <li class="tsisip-nav-item<?php echo $currentPage === $page ? ' is-active' : ''; ?>" role="none">
                    <a href="<?php echo htmlspecialchars($page, ENT_QUOTES, 'UTF-8'); ?>.php"
                       class="tsisip-nav-link" role="menuitem">
                        <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Runtime -->
        <?php if ($runtimeVisible && !empty($runtimePages)): ?>
            <li class="tsisip-nav-heading" role="none">
                <span class="tsisip-nav-heading-text"><?php echo _('Runtime'); ?></span>
            </li>
            <?php foreach ($runtimePages as $page => $label): ?>
                <?php
                if (($page === 'tcp-connections' || $page === 'timers') && !($isAdmin || $isDevOps)) continue;
                ?>
                <li class="tsisip-nav-item<?php echo $currentPage === $page ? ' is-active' : ''; ?>" role="none">
                    <a href="<?php echo htmlspecialchars($page, ENT_QUOTES, 'UTF-8'); ?>.php"
                       class="tsisip-nav-link" role="menuitem">
                        <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Security -->
        <?php if ($securityVisible && !empty($securityPages)): ?>
            <li class="tsisip-nav-heading" role="none">
                <span class="tsisip-nav-heading-text"><?php echo _('Security'); ?></span>
            </li>
            <?php foreach ($securityPages as $page => $label): ?>
                <?php if (!($isAdmin || $isDevOps)) continue; ?>
                <li class="tsisip-nav-item<?php echo $currentPage === $page ? ' is-active' : ''; ?>" role="none">
                    <a href="<?php echo htmlspecialchars($page, ENT_QUOTES, 'UTF-8'); ?>.php"
                       class="tsisip-nav-link" role="menuitem">
                        <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- NAT & Presence -->
        <?php if ($natPresenceVisible && !empty($natPresencePages)): ?>
            <li class="tsisip-nav-heading" role="none">
                <span class="tsisip-nav-heading-text"><?php echo _('NAT & Presence'); ?></span>
            </li>
            <?php foreach ($natPresencePages as $page => $label): ?>
                <?php if ($page === 'presence' && !($isAdmin || $isDevOps)) continue; ?>
                <li class="tsisip-nav-item<?php echo $currentPage === $page ? ' is-active' : ''; ?>" role="none">
                    <a href="<?php echo htmlspecialchars($page, ENT_QUOTES, 'UTF-8'); ?>.php"
                       class="tsisip-nav-link" role="menuitem">
                        <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Advanced -->
        <?php if ($advancedVisible && !empty($advancedPages)): ?>
            <li class="tsisip-nav-heading" role="none">
                <span class="tsisip-nav-heading-text"><?php echo _('Advanced'); ?></span>
            </li>
            <?php foreach ($advancedPages as $page => $label): ?>
                <li class="tsisip-nav-item<?php echo $currentPage === $page ? ' is-active' : ''; ?>" role="none">
                    <a href="<?php echo htmlspecialchars($page, ENT_QUOTES, 'UTF-8'); ?>.php"
                       class="tsisip-nav-link" role="menuitem">
                        <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Trunking -->
        <?php if ($trunkVisible && !empty($trunkPages)): ?>
            <li class="tsisip-nav-heading" role="none">
                <span class="tsisip-nav-heading-text"><?php echo _('Trunking'); ?></span>
            </li>
            <?php foreach ($trunkPages as $page => $label): ?>
                <li class="tsisip-nav-item<?php echo $currentPage === $page ? ' is-active' : ''; ?>" role="none">
                    <a href="<?php echo htmlspecialchars($page, ENT_QUOTES, 'UTF-8'); ?>.php"
                       class="tsisip-nav-link" role="menuitem">
                        <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Administration -->
        <?php if ($adminVisible && !empty($adminPages)): ?>
            <li class="tsisip-nav-heading" role="none">
                <span class="tsisip-nav-heading-text"><?php echo _('Administration'); ?></span>
            </li>
            <?php foreach ($adminPages as $page => $label): ?>
                <li class="tsisip-nav-item<?php echo $currentPage === $page ? ' is-active' : ''; ?>" role="none">
                    <a href="<?php echo htmlspecialchars($page, ENT_QUOTES, 'UTF-8'); ?>.php"
                       class="tsisip-nav-link" role="menuitem">
                        <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Account -->
        <li class="tsisip-nav-heading" role="none">
            <span class="tsisip-nav-heading-text"><?php echo _('Account'); ?></span>
        </li>
        <li class="tsisip-nav-item<?php echo $currentPage === 'profile' ? ' is-active' : ''; ?>" role="none">
            <a href="profile.php" class="tsisip-nav-link" role="menuitem">
                <?php echo _('Profile'); ?>
            </a>
        </li>
        <li class="tsisip-nav-item<?php echo $currentPage === 'notes' ? ' is-active' : ''; ?>" role="none">
            <a href="notes.php" class="tsisip-nav-link" role="menuitem">
                <?php echo _('My Notes'); ?>
            </a>
        </li>
        <li class="tsisip-nav-item<?php echo $currentPage === 'feedback' ? ' is-active' : ''; ?>" role="none">
            <a href="feedback.php" class="tsisip-nav-link" role="menuitem">
                <?php echo _('Feedback'); ?>
            </a>
        </li>
        <li class="tsisip-nav-item<?php echo $currentPage === 'help' ? ' is-active' : ''; ?>" role="none">
            <a href="help.php" class="tsisip-nav-link" role="menuitem">
                <?php echo _('Help'); ?>
            </a>
        </li>
        <li class="tsisip-nav-item<?php echo $currentPage === 'about' ? ' is-active' : ''; ?>" role="none">
            <a href="about.php" class="tsisip-nav-link" role="menuitem">
                <?php echo _('About'); ?>
            </a>
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
