<?php
/**
 * TSiSIP Control Panel — Config Table
 * Runtime configuration via DB (cfgutils module, OCP 9.3.5+)
 * Maps to OpenSIPS 3.6 config table
 */

require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';
require_once __DIR__ . '/common/pagination.php';

requireAuth();
checkPasswordChange();
requireRole('devops');

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
            $name  = trim($_POST['name'] ?? '');
            $value = trim($_POST['value'] ?? '');
            $category = trim($_POST['category'] ?? 'general');
            $description = trim($_POST['description'] ?? '');

            if ($name === '' || $value === '') {
                $error = _('Name and value are required.');
            } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
                $error = _('Name must contain only letters, numbers, and underscores.');
            } else {
                if ($action === 'create') {
                    try {
                        $stmt = $pdo->prepare(
                            'INSERT INTO config (name, value, category, description) VALUES (?, ?, ?, ?)'
                        );
                        $stmt->execute([$name, $value, $category, $description]);
                        $success = _('Config entry created successfully.');
                        logAuditEvent('CONFIG_CREATE', 'config', $name, true, ['category' => $category]);
                    } catch (PDOException $e) {
                        if (strpos($e->getMessage(), 'unique constraint') !== false) {
                            $error = _('A config entry with this name already exists.');
                        } else {
                            $error = _('Database error: ') . $e->getMessage();
                        }
                    }
                } else {
                    $id = $_POST['id'] ?? '';
                    try {
                        $stmt = $pdo->prepare(
                            'UPDATE config SET name=?, value=?, category=?, description=?, updated_at=NOW() WHERE id=?'
                        );
                        $stmt->execute([$name, $value, $category, $description, $id]);
                        $success = _('Config entry updated successfully.');
                        logAuditEvent('CONFIG_UPDATE', 'config', $name, true, ['category' => $category]);
                    } catch (PDOException $e) {
                        $error = _('Database error: ') . $e->getMessage();
                    }
                }
            }
        } elseif ($action === 'delete') {
            $id = $_POST['id'] ?? '';
            try {
                $stmt = $pdo->prepare('SELECT name FROM config WHERE id=?');
                $stmt->execute([$id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $stmt = $pdo->prepare('DELETE FROM config WHERE id=?');
                    $stmt->execute([$id]);
                    $success = _('Config entry deleted successfully.');
                    logAuditEvent('CONFIG_DELETE', 'config', $row['name'], true);
                }
            } catch (PDOException $e) {
                $error = _('Database error: ') . $e->getMessage();
            }
        }
    }
}

// --- Fetch list ---
$categoryFilter = $_GET['category'] ?? '';
$page   = max(1, intval($_GET['page'] ?? 1));
$search = trim($_GET['search'] ?? '');
$perPage = 25;

$where = [];
$params = [];
if ($categoryFilter !== '') {
    $where[] = 'category = ?';
    $params[] = $categoryFilter;
}
if ($search !== '') {
    $where[] = '(name ILIKE ? OR value ILIKE ? OR description ILIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM config $whereSql");
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));

$offset = ($page - 1) * $perPage;
$stmt = $pdo->prepare("SELECT * FROM config $whereSql ORDER BY category, name LIMIT ? OFFSET ?");
$stmt->execute(array_merge($params, [$perPage, $offset]));
$configs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Categories for filter
$catStmt = $pdo->query('SELECT DISTINCT category FROM config ORDER BY category');
$categories = $catStmt->fetchAll(PDO::FETCH_COLUMN);

$csrfToken = generateCsrfToken();
require_once __DIR__ . '/common/header.php';
?>

<div class="tsisip-page">
    <header class="tsisip-page-header">
        <h1 class="tsisip-page-title"><?php echo _('Config Table'); ?></h1>
        <p class="tsisip-page-subtitle"><?php echo _('Runtime configuration via database (cfgutils module)'); ?></p>
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
            <select name="category" class="tsisip-select">
                <option value=""><?php echo _('All Categories'); ?></option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $cat === $categoryFilter ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="tsisip-btn tsisip-btn--primary"><?php echo _('Filter'); ?></button>
            <a href="config-table.php" class="tsisip-btn tsisip-btn--secondary"><?php echo _('Clear'); ?></a>
        </form>
    </section>

    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo _('Configuration Entries'); ?></h2>
        <table class="tsisip-table">
            <thead>
                <tr>
                    <th><?php echo _('Name'); ?></th>
                    <th><?php echo _('Value'); ?></th>
                    <th><?php echo _('Category'); ?></th>
                    <th><?php echo _('Description'); ?></th>
                    <th><?php echo _('Updated'); ?></th>
                    <th><?php echo _('Actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($configs)): ?>
                    <tr><td colspan="6" class="tsisip-empty"><?php echo _('No config entries found.'); ?></td></tr>
                <?php else: ?>
                    <?php foreach ($configs as $cfg): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($cfg['name']); ?></td>
                            <td><code><?php echo htmlspecialchars($cfg['value']); ?></code></td>
                            <td><span class="tsisip-badge"><?php echo htmlspecialchars($cfg['category']); ?></span></td>
                            <td><?php echo htmlspecialchars($cfg['description'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($cfg['updated_at']); ?></td>
                            <td>
                                <form method="post" style="display:inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $cfg['id']; ?>">
                                    <button type="submit" class="tsisip-btn tsisip-btn--danger tsisip-btn--sm"
                                            onclick="return confirm('<?php echo _('Delete this entry?'); ?>')">
                                        <?php echo _('Delete'); ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php renderPagination($page, $totalPages, $totalRows, $perPage, 'config-table.php', ['search' => $search, 'category' => $categoryFilter]); ?>
    </section>

    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo _('Add Config Entry'); ?></h2>
        <form method="post" class="tsisip-form">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="action" value="create">
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Name'); ?></label>
                <input type="text" name="name" required class="tsisip-input" pattern="[a-zA-Z0-9_]+">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Value'); ?></label>
                <textarea name="value" required class="tsisip-input" rows="3"></textarea>
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Category'); ?></label>
                <input type="text" name="category" value="general" class="tsisip-input">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Description'); ?></label>
                <input type="text" name="description" class="tsisip-input">
            </div>
            <button type="submit" class="tsisip-btn tsisip-btn--primary"><?php echo _('Create'); ?></button>
        </form>
    </section>
</div>

<?php require_once __DIR__ . '/common/footer.php'; ?>
