<?php
/**
 * TSiSIP Control Panel — Domain Management
 * Full CRUD on the OpenSIPS domain table.
 */

require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';
require_once __DIR__ . '/common/pagination.php';
require_once __DIR__ . '/common/validate-input.php';

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
            $domain = trim($_POST['domain'] ?? '');
            $did    = trim($_POST['did'] ?? '');

            $validationError = validateDomain($domain)
                ?: validateDomainDid($did);

            if ($validationError !== '') {
                $error = $validationError;
            } else {
                try {
                    $stmt = $pdo->prepare(
                        "INSERT INTO domain (domain, did) VALUES (:domain, :did)"
                    );
                    $stmt->execute([
                        ':domain' => $domain,
                        ':did'    => $did,
                    ]);
                    $success = _('Domain added.');
                    logAuditEvent('DOMAIN_CREATE', 'domain', $domain, true, [
                        'did' => $did,
                    ]);
                } catch (PDOException $e) {
                    logAuditEvent('DOMAIN_CREATE', 'domain', $domain, false, [
                        'reason' => $e->getMessage(),
                    ]);
                    $error = _('Failed to add domain: ') . $e->getMessage();
                }
            }
        } elseif ($action === 'update') {
            $id     = intval($_POST['id'] ?? 0);
            $domain = trim($_POST['domain'] ?? '');
            $did    = trim($_POST['did'] ?? '');

            $validationError = validateDomain($domain)
                ?: validateDomainDid($did);

            if ($id === 0) {
                $error = _('ID is required.');
            } elseif ($validationError !== '') {
                $error = $validationError;
            } else {
                try {
                    $stmt = $pdo->prepare(
                        "UPDATE domain SET domain = :domain, did = :did WHERE id = :id"
                    );
                    $stmt->execute([
                        ':id'     => $id,
                        ':domain' => $domain,
                        ':did'    => $did,
                    ]);
                    $success = _('Domain updated.');
                    logAuditEvent('DOMAIN_UPDATE', 'domain', $domain, true, [
                        'id'  => $id,
                        'did' => $did,
                    ]);
                } catch (PDOException $e) {
                    logAuditEvent('DOMAIN_UPDATE', 'domain', $domain, false, [
                        'id'     => $id,
                        'reason' => $e->getMessage(),
                    ]);
                    $error = _('Failed to update: ') . $e->getMessage();
                }
            }
        } elseif ($action === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            if ($id > 0) {
                $stmt = $pdo->prepare("DELETE FROM domain WHERE id = :id");
                $stmt->execute([':id' => $id]);
                $success = _('Domain deleted.');
                logAuditEvent('DOMAIN_DELETE', 'domain', (string)$id, true);
            }
        }
    }
}

// --- Fetch with pagination ---
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 25;
$pagination = getPagination($page, $perPage);

$countStmt = $pdo->query("SELECT COUNT(*) FROM domain");
$totalItems = (int) $countStmt->fetchColumn();

$listStmt = $pdo->prepare(
    "SELECT * FROM domain ORDER BY domain, id LIMIT :limit OFFSET :offset"
);
$listStmt->bindValue(':limit', $pagination['limit'], PDO::PARAM_INT);
$listStmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
$listStmt->execute();
$domains = $listStmt->fetchAll();

require_once __DIR__ . '/common/header.php';
?>
<div id="content">
    <h2><?php echo _('Domains'); ?></h2>

    <?php if ($error): ?>
        <div class="tsisip-badge tsisip-badge-error" role="alert"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="tsisip-badge tsisip-badge-success" role="status"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <!-- Create Form -->
    <div class="tsisip-dashboard-section">
        <h3><?php echo _('Add Domain'); ?></h3>
        <form method="POST" action="" class="tsisip-form">
            <?php echo csrfInput(); ?>
            <input type="hidden" name="action" value="create">
            <div style="display:flex;flex-wrap:wrap;gap:1rem">
                <div class="tsisip-form-group">
                    <label for="domain"><?php echo _('Domain'); ?></label>
                    <input type="text" id="domain" name="domain" class="tsisip-input" placeholder="example.com" required style="width:280px">
                </div>
                <div class="tsisip-form-group">
                    <label for="did"><?php echo _('DID'); ?></label>
                    <input type="text" id="did" name="did" class="tsisip-input" placeholder="" style="width:180px">
                </div>
            </div>
            <button type="submit" class="tsisip-btn tsisip-btn-primary"><?php echo _('Add'); ?></button>
        </form>
    </div>

    <!-- List -->
    <div class="tsisip-dashboard-section">
        <h3><?php echo _('Domains'); ?> (<?php echo $totalItems; ?>)</h3>
        <table class="dataTable tsisip-table">
            <thead>
                <tr>
                    <th><?php echo _('ID'); ?></th>
                    <th><?php echo _('Domain'); ?></th>
                    <th><?php echo _('DID'); ?></th>
                    <th><?php echo _('Last Modified'); ?></th>
                    <th><?php echo _('Actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($domains as $d): ?>
                <tr>
                    <td><?php echo $d['id']; ?></td>
                    <td><?php echo htmlspecialchars($d['domain']); ?></td>
                    <td><?php echo htmlspecialchars($d['did'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($d['last_modified'] ?? ''); ?></td>
                    <td class="tsisip-actions-column">
                        <button type="button" class="tsisip-btn tsisip-btn-secondary"
                                onclick="document.getElementById('edit-d<?php echo $d['id']; ?>').style.display='table-row'">
                            <?php echo _('Edit'); ?>
                        </button>
                        <form method="POST" action="" style="display:inline" onsubmit="return confirm('<?php echo _('Delete this domain?'); ?>')">
                            <?php echo csrfInput(); ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $d['id']; ?>">
                            <button type="submit" class="tsisip-btn tsisip-btn-secondary tsisip-btn-delete"><?php echo _('Delete'); ?></button>
                        </form>
                    </td>
                </tr>
                <tr id="edit-d<?php echo $d['id']; ?>" style="display:none">
                    <td colspan="5">
                        <form method="POST" action="" class="tsisip-form">
                            <?php echo csrfInput(); ?>
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" value="<?php echo $d['id']; ?>">
                            <div style="display:flex;flex-wrap:wrap;gap:1rem">
                                <div class="tsisip-form-group">
                                    <label><?php echo _('Domain'); ?></label>
                                    <input type="text" name="domain" class="tsisip-input" value="<?php echo htmlspecialchars($d['domain'], ENT_QUOTES); ?>" required style="width:280px">
                                </div>
                                <div class="tsisip-form-group">
                                    <label><?php echo _('DID'); ?></label>
                                    <input type="text" name="did" class="tsisip-input" value="<?php echo htmlspecialchars($d['did'] ?? '', ENT_QUOTES); ?>" style="width:180px">
                                </div>
                            </div>
                            <button type="submit" class="tsisip-btn tsisip-btn-primary"><?php echo _('Save'); ?></button>
                            <button type="button" class="tsisip-btn tsisip-btn-secondary"
                                    onclick="document.getElementById('edit-d<?php echo $d['id']; ?>').style.display='none'">
                                <?php echo _('Cancel'); ?>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php echo renderPagination($page, $totalItems, $perPage, 'domains.php'); ?>
    </div>
</div>
<?php require_once __DIR__ . '/common/footer.php'; ?>
