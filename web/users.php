<?php
/**
 * TSiSIP Control Panel — User Management
 * Admin-only user CRUD page.
 */
require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';
require_once __DIR__ . '/common/password-policy.php';
requireAuth();
requireRole('admin');
logAuditEvent('CONFIG_VIEW', 'system', 'users', true);

$pdo = getDb();
$flash = getFlash();

// Build query
$where = ["deleted_at IS NULL"];
$params = [];

$search = $_GET['search'] ?? '';
if ($search !== '') {
    $where[] = "(LOWER(username) LIKE LOWER(:search) OR LOWER(email) LIKE LOWER(:search))";
    $params[':search'] = '%' . $search . '%';
}

$roleFilter = $_GET['role'] ?? '';
if ($roleFilter !== '' && in_array($roleFilter, ['admin','devops','dentist','assistant','user','readonly'], true)) {
    $where[] = "role = :role";
    $params[':role'] = $roleFilter;
}

$statusFilter = $_GET['status'] ?? '';
if ($statusFilter === 'active') {
    $where[] = "enabled = true";
} elseif ($statusFilter === 'inactive') {
    $where[] = "enabled = false";
}

$orderBy = 'username';
$validSort = ['username','email','role','last_login_at','enabled'];
$sort = $_GET['sort'] ?? 'username';
if (in_array($sort, $validSort, true)) {
    $orderBy = $sort;
}
$dir = ($_GET['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';

$sql = "SELECT id, username, email, role, enabled, force_password_change, last_login_at, created_at
        FROM ocp_users WHERE " . implode(' AND ', $where) . " ORDER BY {$orderBy} {$dir}";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$roleLabels = [
    'admin'     => _('Administrator'),
    'devops'    => _('DevOps Engineer'),
    'dentist'   => _('Dentist'),
    'assistant' => _('Assistant'),
    'user'      => _('User'),
    'readonly'  => _('Read-Only User'),
];

require_once __DIR__ . '/common/header.php';
?>
<div id="content">
    <h1><?php echo _('User Management'); ?></h1>

    <?php if ($flash): ?>
        <div class="tsisip-alert tsisip-alert--<?php echo htmlspecialchars($flash['type']); ?>">
            <?php echo htmlspecialchars($flash['message']); ?>
        </div>
    <?php endif; ?>

    <div style="display:flex;gap:12px;margin-bottom:16px;flex-wrap:wrap;">
        <a href="user-edit.php" class="tsisip-btn tsisip-btn-primary">+ <?php echo _('Add User'); ?></a>
        <form method="get" style="display:flex;gap:8px;flex-wrap:wrap;">
            <input type="text" name="search" placeholder="<?php echo _('Search username or email'); ?>"
                   value="<?php echo htmlspecialchars($search); ?>" class="tsisip-input">
            <select name="role" class="tsisip-input">
                <option value=""><?php echo _('All Roles'); ?></option>
                <?php foreach ($roleLabels as $r => $l): ?>
                    <option value="<?php echo $r; ?>" <?php echo $roleFilter === $r ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($l); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="status" class="tsisip-input">
                <option value=""><?php echo _('All Statuses'); ?></option>
                <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>><?php echo _('Active'); ?></option>
                <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>><?php echo _('Inactive'); ?></option>
            </select>
            <button type="submit" class="tsisip-btn tsisip-btn-secondary"><?php echo _('Filter'); ?></button>
            <a href="users.php" class="tsisip-btn tsisip-btn-outline"><?php echo _('Reset'); ?></a>
        </form>
    </div>

    <table class="tsisip-table">
        <thead>
            <tr>
                <th><a href="?sort=username&dir=<?php echo $dir==='ASC'?'desc':'asc'; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($roleFilter); ?>&status=<?php echo urlencode($statusFilter); ?>"><?php echo _('Username'); ?></a></th>
                <th><a href="?sort=email&dir=<?php echo $dir==='ASC'?'desc':'asc'; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($roleFilter); ?>&status=<?php echo urlencode($statusFilter); ?>"><?php echo _('Email'); ?></a></th>
                <th><a href="?sort=role&dir=<?php echo $dir==='ASC'?'desc':'asc'; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($roleFilter); ?>&status=<?php echo urlencode($statusFilter); ?>"><?php echo _('Role'); ?></a></th>
                <th><?php echo _('Status'); ?></th>
                <th><?php echo _('Force PW Change'); ?></th>
                <th><a href="?sort=last_login_at&dir=<?php echo $dir==='ASC'?'desc':'asc'; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($roleFilter); ?>&status=<?php echo urlencode($statusFilter); ?>"><?php echo _('Last Login'); ?></a></th>
                <th><?php echo _('Actions'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?php echo htmlspecialchars($u['username']); ?></td>
                    <td><?php echo htmlspecialchars($u['email'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($roleLabels[$u['role']] ?? $u['role']); ?></td>
                    <td>
                        <?php if ($u['enabled']): ?>
                            <span class="tsisip-badge tsisip-badge-success"><?php echo _('Active'); ?></span>
                        <?php else: ?>
                            <span class="tsisip-badge tsisip-badge-error"><?php echo _('Inactive'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $u['force_password_change'] ? _('Yes') : _('No'); ?></td>
                    <td><?php echo $u['last_login_at'] ? htmlspecialchars(substr($u['last_login_at'], 0, 16)) : '-'; ?></td>
                    <td>
                        <a href="user-edit.php?id=<?php echo $u['id']; ?>" class="tsisip-btn tsisip-btn-sm tsisip-btn-outline"><?php echo _('Edit'); ?></a>
                        <?php if ($u['id'] !== ($_SESSION['ocp_user_id'] ?? '')): ?>
                            <form method="post" action="user-delete.php" style="display:inline;" onsubmit="return confirm('<?php echo _('Delete user'); ?> <?php echo htmlspecialchars($u['username']); ?>?');">
                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken()); ?>">
                                <button type="submit" class="tsisip-btn tsisip-btn-sm tsisip-btn-error"><?php echo _('Delete'); ?></button>
                            </form>
                        <?php else: ?>
                            <span class="tsisip-text-muted">(<?php echo _('You'); ?>)</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($users)): ?>
                <tr><td colspan="7" class="tsisip-text-muted"><?php echo _('No users found.'); ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/common/footer.php'; ?>
