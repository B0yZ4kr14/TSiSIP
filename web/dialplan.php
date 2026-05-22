<?php
/**
 * TSiSIP Control Panel — Dialplan Management
 * Full CRUD on the OpenSIPS dialplan table.
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

        if ($action === 'create') {
            $pr          = intval($_POST['pr'] ?? 0);
            $match_op    = intval($_POST['match_op'] ?? 0);
            $match_exp   = trim($_POST['match_exp'] ?? '');
            $match_flags = intval($_POST['match_flags'] ?? 0);
            $subst_exp   = trim($_POST['subst_exp'] ?? '');
            $repl_exp    = trim($_POST['repl_exp'] ?? '');
            $attrs       = trim($_POST['attrs'] ?? '');

            if ($match_exp === '') {
                $error = _('Match expression is required.');
            } else {
                try {
                    $stmt = $pdo->prepare(
                        "INSERT INTO dialplan (pr, match_op, match_exp, match_flags, subst_exp, repl_exp, attrs)
                         VALUES (:pr, :match_op, :match_exp, :match_flags, :subst_exp, :repl_exp, :attrs)"
                    );
                    $stmt->execute([
                        ':pr'          => $pr,
                        ':match_op'    => $match_op,
                        ':match_exp'   => $match_exp,
                        ':match_flags' => $match_flags,
                        ':subst_exp'   => $subst_exp,
                        ':repl_exp'    => $repl_exp,
                        ':attrs'       => $attrs,
                    ]);
                    $success = _('Dialplan rule added.');
                    logAuditEvent('DIALPLAN_CREATE', 'dialplan', $match_exp, true, [
                        'pr'          => $pr,
                        'match_op'    => $match_op,
                        'match_flags' => $match_flags,
                        'subst_exp'   => $subst_exp,
                        'repl_exp'    => $repl_exp,
                        'attrs'       => $attrs,
                    ]);
                } catch (PDOException $e) {
                    $error = _('Failed to add dialplan rule: ') . $e->getMessage();
                }
            }
        } elseif ($action === 'update') {
            $id          = intval($_POST['id'] ?? 0);
            $pr          = intval($_POST['pr'] ?? 0);
            $match_op    = intval($_POST['match_op'] ?? 0);
            $match_exp   = trim($_POST['match_exp'] ?? '');
            $match_flags = intval($_POST['match_flags'] ?? 0);
            $subst_exp   = trim($_POST['subst_exp'] ?? '');
            $repl_exp    = trim($_POST['repl_exp'] ?? '');
            $attrs       = trim($_POST['attrs'] ?? '');

            if ($id === 0 || $match_exp === '') {
                $error = _('ID and match expression are required.');
            } else {
                try {
                    $stmt = $pdo->prepare(
                        "UPDATE dialplan SET
                         pr = :pr, match_op = :match_op, match_exp = :match_exp,
                         match_flags = :match_flags, subst_exp = :subst_exp,
                         repl_exp = :repl_exp, attrs = :attrs
                         WHERE id = :id"
                    );
                    $stmt->execute([
                        ':id'          => $id,
                        ':pr'          => $pr,
                        ':match_op'    => $match_op,
                        ':match_exp'   => $match_exp,
                        ':match_flags' => $match_flags,
                        ':subst_exp'   => $subst_exp,
                        ':repl_exp'    => $repl_exp,
                        ':attrs'       => $attrs,
                    ]);
                    $success = _('Dialplan rule updated.');
                    logAuditEvent('DIALPLAN_UPDATE', 'dialplan', $match_exp, true, [
                        'id'          => $id,
                        'pr'          => $pr,
                        'match_op'    => $match_op,
                        'match_flags' => $match_flags,
                        'subst_exp'   => $subst_exp,
                        'repl_exp'    => $repl_exp,
                        'attrs'       => $attrs,
                    ]);
                } catch (PDOException $e) {
                    $error = _('Failed to update: ') . $e->getMessage();
                }
            }
        } elseif ($action === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            if ($id > 0) {
                $stmt = $pdo->prepare("DELETE FROM dialplan WHERE id = :id");
                $stmt->execute([':id' => $id]);
                $success = _('Dialplan rule deleted.');
                logAuditEvent('DIALPLAN_DELETE', 'dialplan', (string)$id, true);
            }
        }
    }
}

// --- Fetch with pagination ---
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 25;
$pagination = getPagination($page, $perPage);

$countStmt = $pdo->query("SELECT COUNT(*) FROM dialplan");
$totalItems = (int) $countStmt->fetchColumn();

$listStmt = $pdo->prepare(
    "SELECT * FROM dialplan ORDER BY pr, id LIMIT :limit OFFSET :offset"
);
$listStmt->bindValue(':limit', $pagination['limit'], PDO::PARAM_INT);
$listStmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
$listStmt->execute();
$rules = $listStmt->fetchAll();

require_once __DIR__ . '/common/header.php';
?>
<div id="content">
    <h2><?php echo _('Dialplan Rules'); ?></h2>

    <?php if ($error): ?>
        <div class="tsisip-badge tsisip-badge-error" role="alert"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="tsisip-badge tsisip-badge-success" role="status"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <!-- Create Form -->
    <div class="tsisip-dashboard-section">
        <h3><?php echo _('Add Rule'); ?></h3>
        <form method="POST" action="" class="tsisip-form">
            <?php echo csrfInput(); ?>
            <input type="hidden" name="action" value="create">
            <div style="display:flex;flex-wrap:wrap;gap:1rem">
                <div class="tsisip-form-group">
                    <label for="pr"><?php echo _('Priority'); ?></label>
                    <input type="number" id="pr" name="pr" class="tsisip-input" value="0" style="width:80px">
                </div>
                <div class="tsisip-form-group">
                    <label for="match_op"><?php echo _('Match Op'); ?></label>
                    <input type="number" id="match_op" name="match_op" class="tsisip-input" value="0" min="0" style="width:90px">
                </div>
                <div class="tsisip-form-group">
                    <label for="match_exp"><?php echo _('Match Expression'); ?></label>
                    <input type="text" id="match_exp" name="match_exp" class="tsisip-input" placeholder="^0[1-9].*" required style="width:240px">
                </div>
                <div class="tsisip-form-group">
                    <label for="match_flags"><?php echo _('Match Flags'); ?></label>
                    <input type="number" id="match_flags" name="match_flags" class="tsisip-input" value="0" min="0" style="width:90px">
                </div>
                <div class="tsisip-form-group">
                    <label for="subst_exp"><?php echo _('Subst Expression'); ?></label>
                    <input type="text" id="subst_exp" name="subst_exp" class="tsisip-input" placeholder="^0(.*)" style="width:200px">
                </div>
                <div class="tsisip-form-group">
                    <label for="repl_exp"><?php echo _('Replacement'); ?></label>
                    <input type="text" id="repl_exp" name="repl_exp" class="tsisip-input" placeholder="1" style="width:160px">
                </div>
                <div class="tsisip-form-group">
                    <label for="attrs"><?php echo _('Attrs'); ?></label>
                    <input type="text" id="attrs" name="attrs" class="tsisip-input" placeholder="" style="width:140px">
                </div>
            </div>
            <button type="submit" class="tsisip-btn tsisip-btn-primary"><?php echo _('Add'); ?></button>
        </form>
    </div>

    <!-- List -->
    <div class="tsisip-dashboard-section">
        <h3><?php echo _('Rules'); ?> (<?php echo $totalItems; ?>)</h3>
        <table class="dataTable tsisip-table">
            <thead>
                <tr>
                    <th><?php echo _('ID'); ?></th>
                    <th><?php echo _('Priority'); ?></th>
                    <th><?php echo _('Match Op'); ?></th>
                    <th><?php echo _('Match Expression'); ?></th>
                    <th><?php echo _('Match Flags'); ?></th>
                    <th><?php echo _('Subst Expression'); ?></th>
                    <th><?php echo _('Replacement'); ?></th>
                    <th><?php echo _('Attrs'); ?></th>
                    <th><?php echo _('Actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rules as $r): ?>
                <tr>
                    <td><?php echo $r['id']; ?></td>
                    <td><?php echo $r['pr']; ?></td>
                    <td><?php echo $r['match_op']; ?></td>
                    <td class="mono-cell"><?php echo htmlspecialchars($r['match_exp']); ?></td>
                    <td><?php echo $r['match_flags']; ?></td>
                    <td class="mono-cell"><?php echo htmlspecialchars($r['subst_exp']); ?></td>
                    <td class="mono-cell"><?php echo htmlspecialchars($r['repl_exp']); ?></td>
                    <td><?php echo htmlspecialchars($r['attrs']); ?></td>
                    <td class="tsisip-actions-column">
                        <button type="button" class="tsisip-btn tsisip-btn-secondary"
                                onclick="document.getElementById('edit-d<?php echo $r['id']; ?>').style.display='table-row'">
                            <?php echo _('Edit'); ?>
                        </button>
                        <form method="POST" action="" style="display:inline" onsubmit="return confirm('<?php echo _('Delete this rule?'); ?>')">
                            <?php echo csrfInput(); ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                            <button type="submit" class="tsisip-btn tsisip-btn-secondary tsisip-btn-delete"><?php echo _('Delete'); ?></button>
                        </form>
                    </td>
                </tr>
                <tr id="edit-d<?php echo $r['id']; ?>" style="display:none">
                    <td colspan="9">
                        <form method="POST" action="" class="tsisip-form">
                            <?php echo csrfInput(); ?>
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                            <div style="display:flex;flex-wrap:wrap;gap:1rem">
                                <div class="tsisip-form-group">
                                    <label><?php echo _('Priority'); ?></label>
                                    <input type="number" name="pr" class="tsisip-input" value="<?php echo $r['pr']; ?>" style="width:80px">
                                </div>
                                <div class="tsisip-form-group">
                                    <label><?php echo _('Match Op'); ?></label>
                                    <input type="number" name="match_op" class="tsisip-input" value="<?php echo $r['match_op']; ?>" min="0" style="width:90px">
                                </div>
                                <div class="tsisip-form-group">
                                    <label><?php echo _('Match Expression'); ?></label>
                                    <input type="text" name="match_exp" class="tsisip-input" value="<?php echo htmlspecialchars($r['match_exp'], ENT_QUOTES); ?>" required style="width:240px">
                                </div>
                                <div class="tsisip-form-group">
                                    <label><?php echo _('Match Flags'); ?></label>
                                    <input type="number" name="match_flags" class="tsisip-input" value="<?php echo $r['match_flags']; ?>" min="0" style="width:90px">
                                </div>
                                <div class="tsisip-form-group">
                                    <label><?php echo _('Subst Expression'); ?></label>
                                    <input type="text" name="subst_exp" class="tsisip-input" value="<?php echo htmlspecialchars($r['subst_exp'], ENT_QUOTES); ?>" style="width:200px">
                                </div>
                                <div class="tsisip-form-group">
                                    <label><?php echo _('Replacement'); ?></label>
                                    <input type="text" name="repl_exp" class="tsisip-input" value="<?php echo htmlspecialchars($r['repl_exp'], ENT_QUOTES); ?>" style="width:160px">
                                </div>
                                <div class="tsisip-form-group">
                                    <label><?php echo _('Attrs'); ?></label>
                                    <input type="text" name="attrs" class="tsisip-input" value="<?php echo htmlspecialchars($r['attrs'], ENT_QUOTES); ?>" style="width:140px">
                                </div>
                            </div>
                            <button type="submit" class="tsisip-btn tsisip-btn-primary"><?php echo _('Save'); ?></button>
                            <button type="button" class="tsisip-btn tsisip-btn-secondary"
                                    onclick="document.getElementById('edit-d<?php echo $r['id']; ?>').style.display='none'">
                                <?php echo _('Cancel'); ?>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php echo renderPagination($page, $totalItems, $perPage, 'dialplan.php'); ?>
    </div>
</div>
<?php require_once __DIR__ . '/common/footer.php'; ?>
