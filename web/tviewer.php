<?php
/**
 * TSiSIP Control Panel — TViewer
 * Generic table viewer (tviewer module)
 */

require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';
require_once __DIR__ . '/common/pagination.php';

requireAuth();
checkPasswordChange();

$pdo = getDb();
$error = '';
$success = '';

// --- Handle schema operations ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = _('Invalid CSRF token.');
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create_schema' || $action === 'update_schema') {
            $id   = $_POST['id'] ?? null;
            $tbl  = trim($_POST['table_name'] ?? '');
            $cols = trim($_POST['columns'] ?? '');
            $pk   = trim($_POST['primary_key'] ?? '');
            $desc = trim($_POST['description'] ?? '');
            $enabled = isset($_POST['enabled']) ? 1 : 0;

            if ($tbl === '' || $cols === '') {
                $error = _('Table name and columns are required.');
            } else {
                try {
                    if ($action === 'create_schema') {
                        $stmt = $pdo->prepare(
                            'INSERT INTO tviewer_schemas (table_name, columns, primary_key, description, enabled)
                             VALUES (?, ?, ?, ?, ?)'
                        );
                        $stmt->execute([$tbl, $cols, $pk, $desc, $enabled]);
                        $success = _('Schema created successfully.');
                        logAuditEvent('TV_SCHEMA_CREATE', 'tviewer', $tbl, true);
                    } else {
                        $stmt = $pdo->prepare(
                            'UPDATE tviewer_schemas SET table_name=?, columns=?, primary_key=?, description=?, enabled=? WHERE id=?'
                        );
                        $stmt->execute([$tbl, $cols, $pk, $desc, $enabled, $id]);
                        $success = _('Schema updated successfully.');
                        logAuditEvent('TV_SCHEMA_UPDATE', 'tviewer', $tbl, true);
                    }
                } catch (PDOException $e) {
                    $error = _('Database error: ') . $e->getMessage();
                }
            }
        } elseif ($action === 'delete_schema') {
            $id = $_POST['id'] ?? '';
            try {
                $stmt = $pdo->prepare('SELECT table_name FROM tviewer_schemas WHERE id=?');
                $stmt->execute([$id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $stmt = $pdo->prepare('DELETE FROM tviewer_schemas WHERE id=?');
                    $stmt->execute([$id]);
                    $success = _('Schema deleted successfully.');
                    logAuditEvent('TV_SCHEMA_DELETE', 'tviewer', $row['table_name'], true);
                }
            } catch (PDOException $e) {
                $error = _('Database error: ') . $e->getMessage();
            }
        }
    }
}

// --- Fetch schemas ---
$stmt = $pdo->query('SELECT * FROM tviewer_schemas WHERE enabled=1 ORDER BY table_name');
$schemas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- View selected table ---
$viewSchema = null;
$viewData = [];
$viewPage = max(1, intval($_GET['view_page'] ?? 1));
$viewTotal = 0;
$viewPages = 1;
$perPage = 25;

if (isset($_GET['view'])) {
    $stmt = $pdo->prepare('SELECT * FROM tviewer_schemas WHERE id=? AND enabled=1');
    $stmt->execute([$_GET['view']]);
    $viewSchema = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($viewSchema) {
        $tbl = $viewSchema['table_name'];
        // Validate table name against whitelist to prevent injection
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $tbl)) {
            $error = _('Invalid table name.');
            $viewSchema = null;
        } else {
            $countStmt = $pdo->query("SELECT COUNT(*) FROM $tbl");
            $viewTotal = $countStmt->fetchColumn();
            $viewPages = max(1, ceil($viewTotal / $perPage));
            $offset = ($viewPage - 1) * $perPage;
            $cols = $viewSchema['columns'] === '*' ? '*' : implode(',', array_map(function($c) { return '"' . str_replace('"', '""', trim($c)) . '"'; }, explode(',', $viewSchema['columns'])));
            $stmt = $pdo->query("SELECT $cols FROM $tbl LIMIT $perPage OFFSET $offset");
            $viewData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
}

$editSchema = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM tviewer_schemas WHERE id=?');
    $stmt->execute([$_GET['edit']]);
    $editSchema = $stmt->fetch(PDO::FETCH_ASSOC);
}

$csrfToken = generateCsrfToken();
require_once __DIR__ . '/common/header.php';
?>

<div class="tsisip-page">
    <header class="tsisip-page-header">
        <h1 class="tsisip-page-title"><?php echo _('TViewer'); ?></h1>
        <p class="tsisip-page-subtitle"><?php echo _('Generic table viewer'); ?></p>
    </header>

    <?php if ($error): ?>
        <div class="tsisip-alert tsisip-alert--error" role="alert"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="tsisip-alert tsisip-alert--success" role="alert"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo _('Available Schemas'); ?></h2>
        <table class="tsisip-table">
            <thead>
                <tr>
                    <th><?php echo _('Table Name'); ?></th>
                    <th><?php echo _('Columns'); ?></th>
                    <th><?php echo _('Primary Key'); ?></th>
                    <th><?php echo _('Description'); ?></th>
                    <th><?php echo _('Actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($schemas)): ?>
                    <tr><td colspan="5" class="tsisip-empty"><?php echo _('No schemas configured.'); ?></td></tr>
                <?php else: ?>
                    <?php foreach ($schemas as $s): ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($s['table_name']); ?></code></td>
                            <td><code class="tsisip-code--sm"><?php echo htmlspecialchars($s['columns']); ?></code></td>
                            <td><?php echo htmlspecialchars($s['primary_key']); ?></td>
                            <td><?php echo htmlspecialchars($s['description']); ?></td>
                            <td>
                                <a href="?view=<?php echo $s['id']; ?>" class="tsisip-btn tsisip-btn--sm"><?php echo _('View'); ?></a>
                                <?php if ($userRole === 'admin' || $userRole === 'devops'): ?>
                                <a href="?edit=<?php echo $s['id']; ?>" class="tsisip-btn tsisip-btn--sm"><?php echo _('Edit'); ?></a>
                                <form method="post" style="display:inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    <input type="hidden" name="action" value="delete_schema">
                                    <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                                    <button type="submit" class="tsisip-btn tsisip-btn--danger tsisip-btn--sm"
                                            onclick="return confirm('<?php echo _('Delete this schema?'); ?>')">
                                        <?php echo _('Delete'); ?>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </section>

    <?php if ($viewSchema && !empty($viewData)): ?>
    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo _('Viewing: ') . htmlspecialchars($viewSchema['table_name']); ?></h2>
        <div style="overflow-x:auto">
        <table class="tsisip-table">
            <thead>
                <tr>
                    <?php foreach (array_keys($viewData[0]) as $col): ?>
                        <th><?php echo htmlspecialchars($col); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($viewData as $row): ?>
                    <tr>
                        <?php foreach ($row as $val): ?>
                            <td><code class="tsisip-code--sm"><?php echo htmlspecialchars((string)$val); ?></code></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php renderPagination($viewPage, $viewPages, $viewTotal, $perPage, 'tviewer.php', ['view' => $viewSchema['id']], 'view_page'); ?>
    </section>
    <?php endif; ?>

    <?php if ($userRole === 'admin' || $userRole === 'devops'): ?>
    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo $editSchema ? _('Edit Schema') : _('Add Schema'); ?></h2>
        <form method="post" class="tsisip-form">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="action" value="<?php echo $editSchema ? 'update_schema' : 'create_schema'; ?>">
            <?php if ($editSchema): ?><input type="hidden" name="id" value="<?php echo $editSchema['id']; ?>"><?php endif; ?>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Table Name'); ?></label>
                <input type="text" name="table_name" required value="<?php echo $editSchema ? htmlspecialchars($editSchema['table_name']) : ''; ?>" class="tsisip-input">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Columns'); ?></label>
                <input type="text" name="columns" required value="<?php echo $editSchema ? htmlspecialchars($editSchema['columns']) : '*'; ?>" class="tsisip-input" placeholder="* or col1,col2,col3">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Primary Key'); ?></label>
                <input type="text" name="primary_key" value="<?php echo $editSchema ? htmlspecialchars($editSchema['primary_key']) : 'id'; ?>" class="tsisip-input">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-label"><?php echo _('Description'); ?></label>
                <input type="text" name="description" value="<?php echo $editSchema ? htmlspecialchars($editSchema['description']) : ''; ?>" class="tsisip-input">
            </div>
            <div class="tsisip-form-row">
                <label class="tsisip-checkbox">
                    <input type="checkbox" name="enabled" <?php echo (!$editSchema || $editSchema['enabled']) ? 'checked' : ''; ?>>
                    <?php echo _('Enabled'); ?>
                </label>
            </div>
            <button type="submit" class="tsisip-btn tsisip-btn--primary"><?php echo $editSchema ? _('Update') : _('Create'); ?></button>
            <?php if ($editSchema): ?><a href="tviewer.php" class="tsisip-btn tsisip-btn--secondary"><?php echo _('Cancel'); ?></a><?php endif; ?>
        </form>
    </section>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/common/footer.php'; ?>
