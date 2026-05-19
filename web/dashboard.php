<?php
/**
 * TSiSIP Control Panel — Dashboard
 * Role-aware landing page after login.
 */
session_start();
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

$quickLinks = [];
if (isset($roleNav[$userRole])) {
    foreach ($roleNav[$userRole] as $page) {
        if (isset($navLabels[$page])) {
            $quickLinks[] = [
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

    <div class="tsisip-dashboard-section">
        <h2><?php echo _('Quick Links'); ?></h2>
        <?php if (!empty($quickLinks)): ?>
            <div class="tsisip-dashboard-links">
                <?php foreach ($quickLinks as $link): ?>
                    <a href="<?php echo htmlspecialchars($link['url'], ENT_QUOTES, 'UTF-8'); ?>" class="tsisip-btn tsisip-btn-secondary">
                        <?php echo htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="tsisip-text-muted"><?php echo _('No quick links available for your role.'); ?></p>
        <?php endif; ?>
    </div>

    <div class="tsisip-dashboard-section">
        <h2><?php echo _('System Status'); ?></h2>
        <p><?php echo _('TSiSIP Control Panel is operational.'); ?></p>
    </div>
</div>
<?php require_once __DIR__ . '/common/footer.php'; ?>
