<?php
/**
 * TSiSIP Control Panel — Aliases Management
 * SIP alias provisioning for subscribers (alias_db module)
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($userRole === 'admin' || $userRole === 'devops' || $userRole === 'dentist' || $userRole === 'assistant' || $userRole === 'user')) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = _('Invalid CSRF token.');
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create') {
            $aliasUsername = trim($_POST['alias_username'] ?? '');
            $aliasDomain   = trim($_POST['alias_domain'] ?? '');
            $username      = trim($_POST['username'] ?? '');
            $domain        = trim($_POST['domain'] ?? '');

            if ($aliasUsername === '' || $username === '') {
                $error = _('Alias username and target username are required.');
            } else {
                try {
                    $stmt = $pdo->prepare(
                        'INSERT INTO aliases (alias_username, alias_domain, username, domain) VALUES (?, ?, ?, ?)'
                    );
                    $stmt->execute([$aliasUsername, $aliasDomain, $username, $domain]);
                    $success = _('Alias created successfully.');
                    logAuditEvent('ALIAS_CREATE', 'alias', $aliasUsername, true, ['target' => "$username@$domain"]);
                } catch (PDOException $e) {
                    $error = _('Database error: ') . $e->getMessage();
                }
            }
        } elseif ($action === 'delete') {
            $id = $_POST['id'] ?? '';
            try {
                $stmt = $pdo->prepare('SELECT alias_username FROM aliases WHERE id=?');
                $stmt->execute([$id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $stmt = $pdo->prepare('DELETE FROM aliases WHERE id=?');
                    $stmt->execute([$id]);
                    $success = _('Alias deleted successfully.');
                    logAuditEvent('ALIAS_DELETE', 'alias', $row['alias_username'], true);
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
    $where[] = '(alias_username ILIKE ? OR username ILIKE ? OR alias_domain ILIKE ? OR domain ILIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM aliases $whereSql");
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));

$offset = ($page - 1) * $perPage;
$stmt = $pdo->prepare("SELECT * FROM aliases $whereSql ORDER BY alias_username LIMIT ? OFFSET ?");
$stmt->execute(array_merge($params, [$perPage, $offset]));
$aliases = $stmt->fetchAll(PDO::FETCH_ASSOC);

$csrfToken = generateCsrfToken();
require_once __DIR__ . '/common/header.php';
?>

<div class="tsisip-page">
    <header class="tsisip-page-header">
        <h1 class="tsisip-page-title"><?php echo _('Aliases'); ?></h1>
        <p class="tsisip-page-subtitle"><?php echo _('SIP alias provisioning for subscribers'); ?></p>
    </header>

    <?php if ($error): ?>
        <div class="tsisip-alert tsisip-alert--error" role="alert"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="tsisip-alert tsisip-alert--success" role="alert"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <section class="tsisip-section">
        <form method="get" class="tsisip-filter-bar">
            <input type="text" name="search" placeholder="<?php echo _('Search aliases...'); ?>"
                   value="<?php echo htmlspecialchars($search); ?>" class="tsisip-input">
            <button type="submit" class="tsisip-btn tsisip-btn--primary"><?php echo _('Filter'); ?></button>
            <a href="aliases.php" class="tsisip-btn tsisip-btn--secondary"><?php echo _('Clear'); ?></a>
        </form>
    </section>

    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo _('Alias Entries'); ?></h2>
        <table class="tsisip-table">
            <thead>
                <tr>
                    <th><?php echo _('Alias'); ?></th>
                    <th><?php echo _('Alias Domain'); ?></th>
                    <th><?php echo _('Target User'); ?></th>
                    <th><?php echo _('Target Domain'); ?></th>
                    <th><?php echo _('Modified'); ?></th>
                    <?php if ($userRole !== 'readonly'): ?>
                        <th><?php echo _('Actions'); ?></th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($aliases)): ?>
                    <tr><td colspan="<?php echo $userRole === 'readonly' ? 5 : 6; ?>" class="tsisip-empty"><?php echo _('No aliases found.'); ?></td></tr>
                <?php else: ?>
                    <?php foreach ($aliases as $a): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($a['alias_username']); ?></td>
                            <td><?php echo htmlspecialchars($a['alias_domain']); ?></td>
                            <td><?php echo htmlspecialchars($a['username']); ?></td>
                            <td><?php echo htmlspecialchars($a['domain']); ?></td>
                            <td><?php echo htmlspecialchars($a['last_modified']); ?></td>
                            <?php if ($userRole !== 'readonly'): ?>
                                <td>
                                    <form method="post" style="display:inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
                                        <button type="submit" class="tsisip-btn tsisip-btn--danger tsisip-btn--sm"
                                                onclick="return confirm('<?php echo _('Delete this alias?'); ?>')">
                                            <?php echo _('Delete'); ?>
                                        </button>
                                    </form>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php renderPagination($page, $totalPages, $totalRows, $perPage, 'aliases.php', ['search' => $search]); ?>
    </section>

    <?php if ($userRole !== 'readonly'): ?>
    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo _('Add Alias'); ?></h2>
        <form method="post" class="tsisip-form">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="action" value="create">
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Alias Username'); ?></label>
                <input type="text" name="alias_username" required class="tsisip-input">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Alias Domain'); ?></label>
                <input type="text" name="alias_domain" class="tsisip-input" placeholder="<?php echo _('Optional'); ?>">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Target Username'); ?></label>
                <input type="text" name="username" required class="tsisip-input">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Target Domain'); ?></label>
                <input type="text" name="domain" class="tsisip-input" placeholder="<?php echo _('Optional'); ?>">
            </div>
            <button type="submit" class="tsisip-btn tsisip-btn--primary"><?php echo _('Create'); ?></button>
        </form>
    </section>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/common/footer.php'; ?>
