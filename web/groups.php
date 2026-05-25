<?php
/**
 * TSiSIP Control Panel — Groups
 * Group-based ACL for SIP subscribers (grp module)
 */

require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';
require_once __DIR__ . '/common/pagination.php';

requireAuth();
checkPasswordChange();

$pdo = getDb();
$error = '';
$success = '';

// --- Handle mutating operations ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = _('Invalid CSRF token.');
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create' || $action === 'update') {
            $id   = $_POST['id'] ?? null;
            $user = trim($_POST['username'] ?? '');
            $dom  = trim($_POST['domain'] ?? '');
            $grp  = trim($_POST['grp'] ?? '');

            if ($user === '' || $grp === '') {
                $error = _('Username and group name are required.');
            } else {
                try {
                    if ($action === 'create') {
                        $stmt = $pdo->prepare(
                            'INSERT INTO grp (username, domain, grp) VALUES (?, ?, ?)'
                        );
                        $stmt->execute([$user, $dom, $grp]);
                        $success = _('Group membership created successfully.');
                        logAuditEvent('GROUP_CREATE', 'groups', "$user@$dom", true, ['grp' => $grp]);
                    } else {
                        $stmt = $pdo->prepare(
                            'UPDATE grp SET username=?, domain=?, grp=? WHERE id=?'
                        );
                        $stmt->execute([$user, $dom, $grp, $id]);
                        $success = _('Group membership updated successfully.');
                        logAuditEvent('GROUP_UPDATE', 'groups', "$user@$dom", true, ['grp' => $grp]);
                    }
                } catch (PDOException $e) {
                    $error = _('Database error: ') . $e->getMessage();
                }
            }
        } elseif ($action === 'delete') {
            $id = $_POST['id'] ?? '';
            try {
                $stmt = $pdo->prepare('SELECT username, domain, grp FROM grp WHERE id=?');
                $stmt->execute([$id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $stmt = $pdo->prepare('DELETE FROM grp WHERE id=?');
                    $stmt->execute([$id]);
                    $success = _('Group membership deleted successfully.');
                    logAuditEvent('GROUP_DELETE', 'groups', $row['username'].'@'.$row['domain'], true, ['grp' => $row['grp']]);
                }
            } catch (PDOException $e) {
                $error = _('Database error: ') . $e->getMessage();
            }
        }
    }
}

// --- Fetch list ---
$page   = max(1, intval($_GET['page'] ?? 1));
$search = trim($_GET['search'] ?? '');
$perPage = 25;

$where = [];
$params = [];
if ($search !== '') {
    $where[] = '(username ILIKE ? OR domain ILIKE ? OR grp ILIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM grp $whereSql");
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));

$offset = ($page - 1) * $perPage;
$stmt = $pdo->prepare("SELECT * FROM grp $whereSql ORDER BY grp, username LIMIT ? OFFSET ?");
$stmt->execute(array_merge($params, [$perPage, $offset]));
$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

$editRow = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM grp WHERE id=?');
    $stmt->execute([$_GET['edit']]);
    $editRow = $stmt->fetch(PDO::FETCH_ASSOC);
}

$csrfToken = generateCsrfToken();
require_once __DIR__ . '/common/header.php';
?>

<div class="tsisip-page">
    <header class="tsisip-page-header">
        <h1 class="tsisip-page-title"><?php echo _('Groups'); ?></h1>
        <p class="tsisip-page-subtitle"><?php echo _('Group-based ACL for SIP subscribers'); ?></p>
    </header>

    <?php if ($error): ?>
        <div class="tsisip-alert tsisip-alert--error" role="alert"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="tsisip-alert tsisip-alert--success" role="alert"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <section class="tsisip-section">
        <form method="get" class="tsisip-filter-bar">
            <input type="text" name="search" placeholder="<?php echo _('Search...'); ?>"
                   value="<?php echo htmlspecialchars($search); ?>" class="tsisip-input">
            <button type="submit" class="tsisip-btn tsisip-btn--primary"><?php echo _('Filter'); ?></button>
            <a href="groups.php" class="tsisip-btn tsisip-btn--secondary"><?php echo _('Clear'); ?></a>
        </form>
    </section>

    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo _('Group Memberships'); ?></h2>
        <table class="tsisip-table">
            <thead>
                <tr>
                    <th><?php echo _('Username'); ?></th>
                    <th><?php echo _('Domain'); ?></th>
                    <th><?php echo _('Group'); ?></th>
                    <th><?php echo _('Last Modified'); ?></th>
                    <th><?php echo _('Actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($groups)): ?>
                    <tr><td colspan="5" class="tsisip-empty"><?php echo _('No group memberships found.'); ?></td></tr>
                <?php else: ?>
                    <?php foreach ($groups as $g): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($g['username']); ?></td>
                            <td><?php echo htmlspecialchars($g['domain']); ?></td>
                            <td><span class="tsisip-tag tsisip-tag--info"><?php echo htmlspecialchars($g['grp']); ?></span></td>
                            <td><?php echo htmlspecialchars($g['last_modified']); ?></td>
                            <td>
                                <a href="?edit=<?php echo $g['id']; ?>" class="tsisip-btn tsisip-btn--sm"><?php echo _('Edit'); ?></a>
                                <form method="post" style="display:inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $g['id']; ?>">
                                    <button type="submit" class="tsisip-btn tsisip-btn--danger tsisip-btn--sm"
                                            onclick="return confirm('<?php echo _('Delete this membership?'); ?>')">
                                        <?php echo _('Delete'); ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php renderPagination($page, $totalPages, $totalRows, $perPage, 'groups.php', ['search' => $search]); ?>
    </section>

    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo $editRow ? _('Edit Membership') : _('Add Membership'); ?></h2>
        <form method="post" class="tsisip-form">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="action" value="<?php echo $editRow ? 'update' : 'create'; ?>">
            <?php if ($editRow): ?><input type="hidden" name="id" value="<?php echo $editRow['id']; ?>"><?php endif; ?>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Username'); ?></label>
                <input type="text" name="username" required value="<?php echo $editRow ? htmlspecialchars($editRow['username']) : ''; ?>" class="tsisip-input">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Domain'); ?></label>
                <input type="text" name="domain" value="<?php echo $editRow ? htmlspecialchars($editRow['domain']) : ''; ?>" class="tsisip-input" placeholder="tsiapp.io">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Group'); ?></label>
                <input type="text" name="grp" required value="<?php echo $editRow ? htmlspecialchars($editRow['grp']) : ''; ?>" class="tsisip-input" placeholder="local">
            </div>
            <button type="submit" class="tsisip-btn tsisip-btn--primary"><?php echo $editRow ? _('Update') : _('Create'); ?></button>
            <?php if ($editRow): ?><a href="groups.php" class="tsisip-btn tsisip-btn--secondary"><?php echo _('Cancel'); ?></a><?php endif; ?>
        </form>
    </section>
</div>

<?php require_once __DIR__ . '/common/footer.php'; ?>
