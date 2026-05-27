<?php
/**
 * TSiSIP Control Panel — AVP Inspector
 * Read-only display of AVP (Attribute-Value Pair) data from avpops module tables.
 */
require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';

requireAuth();
checkPasswordChange();

$pageTitle = _('AVP Inspector');

$pdo = getDb();
$avps = [];
$tableName = '';
$tablesFound = [];

// Discover available AVP-related tables
$knownAvpTables = ['usr_preferences', 'avpops', 'avp_table'];
try {
    $placeholders = implode(',', array_fill(0, count($knownAvpTables), '?'));
    $tblStmt = $pdo->prepare(
        "SELECT tablename FROM pg_tables WHERE schemaname = 'public' AND tablename IN ($placeholders)"
    );
    $tblStmt->execute($knownAvpTables);
    $tablesFound = $tblStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log('TSiSIP AVP table discovery failed: ' . $e->getMessage());
}

// If no known tables, try to find any table with 'avp' in the name
try {
    $fallbackStmt = $pdo->query(
        "SELECT tablename FROM pg_tables WHERE schemaname = 'public' AND tablename LIKE '%avp%'"
    );
    $fallbackTables = $fallbackStmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($fallbackTables as $ft) {
        if (!in_array($ft, $tablesFound, true)) {
            $tablesFound[] = $ft;
        }
    }
} catch (PDOException $e) {
    error_log('TSiSIP AVP fallback discovery failed: ' . $e->getMessage());
}

// Allow user to select a discovered table
$selectedTable = trim($_GET['table'] ?? '');
if ($selectedTable !== '' && in_array($selectedTable, $tablesFound, true)) {
    $tableName = $selectedTable;
} elseif (!empty($tablesFound)) {
    $tableName = $tablesFound[0];
}

// Query selected table
if ($tableName !== '') {
    try {
        $colStmt = $pdo->prepare(
            "SELECT column_name FROM information_schema.columns WHERE table_schema = 'public' AND table_name = ?"
        );
        $colStmt->execute([$tableName]);
        $columns = $colStmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($columns)) {
            $colList = implode(', ', array_map(function ($c) { return '"' . $c . '"'; }, $columns));
            $dataStmt = $pdo->query(
                "SELECT $colList FROM \"$tableName\" ORDER BY 1 LIMIT 500"
            );
            $avps = $dataStmt->fetchAll();
        }
    } catch (PDOException $e) {
        error_log('TSiSIP AVP query failed: ' . $e->getMessage());
    }
}

require_once __DIR__ . '/common/header.php';
?>
<div class="tsisip-page">
    <div class="tsisip-page-header">
        <h1 class="tsisip-page-title"><?php echo $pageTitle; ?></h1>
    </div>

    <?php if (empty($tablesFound)): ?>
        <div class="tsisip-alert tsisip-alert--info" role="note">
            <?php echo _('No AVP tables found in the database. The avpops module may not be loaded or tables have not been created.'); ?>
        </div>
    <?php else: ?>
        <section class="tsisip-section">
            <form method="get" class="tsisip-filter-bar">
                <select name="table" class="tsisip-select" onchange="this.form.submit()">
                    <?php foreach ($tablesFound as $t): ?>
                        <option value="<?php echo htmlspecialchars($t); ?>" <?php echo $t === $tableName ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($t); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </section>

        <section class="tsisip-section">
            <h2 class="tsisip-section-title"><?php echo htmlspecialchars($tableName); ?> (<?php echo count($avps); ?>)</h2>
            <?php if (empty($avps)): ?>
                <p class="tsisip-text-muted"><?php echo _('No AVP records found.'); ?></p>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table class="tsisip-table dataTable" data-tsisip-sortable>
                        <thead>
                            <tr>
                                <?php foreach (array_keys($avps[0]) as $col): ?>
                                    <th><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $col))); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($avps as $row): ?>
                                <tr>
                                    <?php foreach ($row as $val): ?>
                                        <td>
                                            <?php if (is_null($val)): ?>
                                                <span class="tsisip-text-muted">NULL</span>
                                            <?php else: ?>
                                                <code><?php echo htmlspecialchars((string) $val); ?></code>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/common/footer.php'; ?>
