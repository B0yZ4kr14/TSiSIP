<?php
/**
 * TSiSIP Control Panel — Dispatcher Management
 * Full CRUD on the OpenSIPS dispatcher table.
 */

require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';
require_once __DIR__ . '/common/pagination.php';
require_once __DIR__ . '/common/mi-http.php';

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
            $setid       = intval($_POST['setid'] ?? 1);
            $destination = trim($_POST['destination'] ?? '');
            $state       = intval($_POST['state'] ?? 0);
            $weight      = intval($_POST['weight'] ?? 0);
            $priority    = intval($_POST['priority'] ?? 0);
            $attrs       = trim($_POST['attrs'] ?? '');
            $description = trim($_POST['description'] ?? '');

            if ($destination === '') {
                $error = _('Destination is required.');
            } elseif (!str_starts_with($destination, 'sip:')) {
                $error = _('Destination must start with sip:');
            } else {
                try {
                    $stmt = $pdo->prepare(
                        "INSERT INTO dispatcher (setid, destination, state, weight, priority, attrs, description)
                         VALUES (:setid, :destination, :state, :weight, :priority, :attrs, :description)"
                    );
                    $stmt->execute([
                        ':setid'       => $setid,
                        ':destination' => $destination,
                        ':state'       => $state,
                        ':weight'      => $weight,
                        ':priority'    => $priority,
                        ':attrs'       => $attrs,
                        ':description' => $description,
                    ]);
                    $success = _('Dispatcher destination added.');
                    logAuditEvent('DISPATCHER_CREATE', 'dispatcher', $destination, true, [
                        'setid'       => $setid,
                        'state'       => $state,
                        'weight'      => $weight,
                        'priority'    => $priority,
                        'description' => $description,
                    ]);
                } catch (PDOException $e) {
                    $error = _('Failed to add destination: ') . $e->getMessage();
                }
            }
        } elseif ($action === 'update') {
            $id          = intval($_POST['id'] ?? 0);
            $setid       = intval($_POST['setid'] ?? 1);
            $destination = trim($_POST['destination'] ?? '');
            $state       = intval($_POST['state'] ?? 0);
            $weight      = intval($_POST['weight'] ?? 0);
            $priority    = intval($_POST['priority'] ?? 0);
            $attrs       = trim($_POST['attrs'] ?? '');
            $description = trim($_POST['description'] ?? '');

            if ($id === 0 || $destination === '') {
                $error = _('ID and destination are required.');
            } else {
                try {
                    $stmt = $pdo->prepare(
                        "UPDATE dispatcher SET
                         setid = :setid, destination = :destination, state = :state,
                         weight = :weight, priority = :priority, attrs = :attrs, description = :description
                         WHERE id = :id"
                    );
                    $stmt->execute([
                        ':id'          => $id,
                        ':setid'       => $setid,
                        ':destination' => $destination,
                        ':state'       => $state,
                        ':weight'      => $weight,
                        ':priority'    => $priority,
                        ':attrs'       => $attrs,
                        ':description' => $description,
                    ]);
                    $success = _('Dispatcher destination updated.');
                    logAuditEvent('DISPATCHER_UPDATE', 'dispatcher', $destination, true, [
                        'id'          => $id,
                        'setid'       => $setid,
                        'state'       => $state,
                        'weight'      => $weight,
                        'priority'    => $priority,
                        'description' => $description,
                    ]);
                } catch (PDOException $e) {
                    $error = _('Failed to update: ') . $e->getMessage();
                }
            }
        } elseif ($action === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            if ($id > 0) {
                $stmt = $pdo->prepare("DELETE FROM dispatcher WHERE id = :id");
                $stmt->execute([':id' => $id]);
                $success = _('Dispatcher destination deleted.');
                logAuditEvent('DISPATCHER_DELETE', 'dispatcher', (string)$id, true);
            }
        } elseif ($action === 'toggle') {
            $id    = intval($_POST['id'] ?? 0);
            $state = intval($_POST['state'] ?? 0);
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE dispatcher SET state = :state WHERE id = :id");
                $stmt->execute([':id' => $id, ':state' => $state]);
                $success = _('State toggled.');
                logAuditEvent('DISPATCHER_TOGGLE', 'dispatcher', (string)$id, true, [
                    'state' => $state,
                ]);
            }
        }
    }
}

// --- Fetch with pagination ---
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 25;
$pagination = getPagination($page, $perPage);

$countStmt = $pdo->query("SELECT COUNT(*) FROM dispatcher");
$totalItems = (int) $countStmt->fetchColumn();

$listStmt = $pdo->prepare(
    "SELECT * FROM dispatcher ORDER BY setid, priority DESC, id LIMIT :limit OFFSET :offset"
);
$listStmt->bindValue(':limit', $pagination['limit'], PDO::PARAM_INT);
$listStmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
$listStmt->execute();
$destinations = $listStmt->fetchAll();

require_once __DIR__ . '/common/header.php';
?>
<div id="content">
    <h2><?php echo _('Dispatcher Targets'); ?></h2>

    <?php if ($error): ?>
        <div class="tsisip-badge tsisip-badge-error" role="alert"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="tsisip-badge tsisip-badge-success" role="status"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <!-- Live dispatcher status via MI HTTP -->
    <section class="tsisip-section">
        <h2 class="tsisip-section-title"><?php echo _('Live Dispatcher Status'); ?></h2>
        <?php
        $miDs = miHttpCall('ds_list');
        if (!$miDs['success']):
        ?>
            <div class="tsisip-badge tsisip-badge--warning" role="alert">
                <?php echo _('MI unavailable: ') . htmlspecialchars($miDs['error']); ?>
            </div>
        <?php else:
            $rawDs = $miDs['data'] ?? [];
            $dsData = [];
            if (is_array($rawDs)) {
                foreach ($rawDs as $key => $setEntries) {
                    if (is_array($setEntries)) {
                        foreach ($setEntries as $entry) {
                            if (is_array($entry)) {
                                $setId = 'N/A';
                                if (is_numeric($key)) {
                                    $setId = $key;
                                } elseif (preg_match('/\d+/', (string)$key, $m)) {
                                    $setId = $m[0];
                                }
                                $entry['setid'] = $entry['setid'] ?? $entry['SET'] ?? $setId;
                                $dsData[] = $entry;
                            }
                        }
                    }
                }
                if (empty($dsData) && isset($rawDs[0]) && is_array($rawDs[0])) {
                    $dsData = $rawDs;
                }
            }
            if (empty($dsData)):
        ?>
            <div class="tsisip-badge tsisip-badge--info"><?php echo _('No live dispatcher data returned by OpenSIPS.'); ?></div>
        <?php else: ?>
            <table class="tsisip-table dataTable">
                <thead>
                    <tr>
                        <th><?php echo _('Set ID'); ?></th>
                        <th><?php echo _('Destination'); ?></th>
                        <th><?php echo _('State'); ?></th>
                        <th><?php echo _('Weight'); ?></th>
                        <th><?php echo _('Probing'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dsData as $d): ?>
                        <tr>
                            <td><?php echo htmlspecialchars((string) ($d['setid'] ?? $d['SET'] ?? 'N/A')); ?></td>
                            <td><code><?php echo htmlspecialchars($d['destination'] ?? $d['URI'] ?? $d['TARGET'] ?? 'N/A'); ?></code></td>
                            <td>
                                <?php
                                $flags = $d['state'] ?? $d['FLAGS'] ?? '';
                                $isActive = is_string($flags) ? (stripos($flags, 'A') !== false || stripos($flags, 'P') !== false) : (bool)$flags;
                                ?>
                                <span class="tsisip-badge tsisip-badge--<?php echo $isActive ? 'success' : 'danger'; ?>">
                                    <?php echo $isActive ? _('Active') : _('Inactive'); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars((string) ($d['weight'] ?? $d['WEIGHT'] ?? '—')); ?></td>
                            <td>
                                <?php
                                $probing = $d['probing'] ?? $d['PROBING'] ?? '';
                                $isProbing = is_string($probing) ? (stripos($probing, 'Yes') !== false || stripos($probing, 'On') !== false) : (bool)$probing;
                                ?>
                                <span class="tsisip-badge tsisip-badge--<?php echo $isProbing ? 'warning' : 'neutral'; ?>">
                                    <?php echo $isProbing ? _('Yes') : _('No'); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; endif; ?>
    </section>

    <!-- Create Form -->
    <div class="tsisip-dashboard-section">
        <h3><?php echo _('Add Destination'); ?></h3>
        <form method="POST" action="" class="tsisip-form">
            <?php echo csrfInput(); ?>
            <input type="hidden" name="action" value="create" aria-label="hidden">
            <div style="display:flex;flex-wrap:wrap;gap:1rem">
                <div class="tsisip-form-group">
                    <label for="setid"><?php echo _('Set ID'); ?></label>
                    <input type="number" id="setid" name="setid" class="tsisip-input" value="1" min="1" required style="width:80px">
                </div>
                <div class="tsisip-form-group">
                    <label for="destination"><?php echo _('Destination'); ?></label>
                    <input type="text" id="destination" name="destination" class="tsisip-input" placeholder="sip:pbx.example.com:5060" required style="width:280px">
                </div>
                <div class="tsisip-form-group">
                    <label for="weight"><?php echo _('Weight'); ?></label>
                    <input type="number" id="weight" name="weight" class="tsisip-input" value="50" min="0" style="width:80px">
                </div>
                <div class="tsisip-form-group">
                    <label for="priority"><?php echo _('Priority'); ?></label>
                    <input type="number" id="priority" name="priority" class="tsisip-input" value="0" style="width:80px">
                </div>
                <div class="tsisip-form-group">
                    <label for="description"><?php echo _('Description'); ?></label>
                    <input type="text" id="description" name="description" class="tsisip-input" placeholder="PBX Node" style="width:180px">
                </div>
                <div class="tsisip-form-group">
                    <label for="attrs"><?php echo _('Attrs'); ?></label>
                    <input type="text" id="attrs" name="attrs" class="tsisip-input" placeholder="" style="width:120px">
                </div>
                <div class="tsisip-form-group">
                    <label for="state"><?php echo _('State'); ?></label>
                    <select id="state" name="state" class="tsisip-input" style="width:100px">
                        <option value="0"><?php echo _('Active'); ?></option>
                        <option value="1"><?php echo _('Inactive'); ?></option>
                    </select>
                </div>
            </div>
            <button type="submit" class="tsisip-btn tsisip-btn-primary"><?php echo _('Add'); ?></button>
        </form>
    </div>

    <!-- List -->
    <div class="tsisip-dashboard-section">
        <h3><?php echo _('Destinations'); ?> (<?php echo $totalItems; ?>)</h3>
        <table class="dataTable tsisip-table">
            <thead>
                <tr>
                    <th><?php echo _('Set'); ?></th>
                    <th><?php echo _('Destination'); ?></th>
                    <th><?php echo _('State'); ?></th>
                    <th><?php echo _('Weight'); ?></th>
                    <th><?php echo _('Priority'); ?></th>
                    <th><?php echo _('Description'); ?></th>
                    <th><?php echo _('Actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($destinations as $d): ?>
                <tr>
                    <td><?php echo $d['setid']; ?></td>
                    <td class="mono-cell"><?php echo htmlspecialchars($d['destination']); ?></td>
                    <td>
                        <?php if ($d['state'] == 0): ?>
                            <span class="tsisip-badge tsisip-badge-success"><?php echo _('Active'); ?></span>
                        <?php else: ?>
                            <span class="tsisip-badge tsisip-badge-error"><?php echo _('Inactive'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $d['weight']; ?></td>
                    <td><?php echo $d['priority']; ?></td>
                    <td><?php echo htmlspecialchars($d['description'] ?? ''); ?></td>
                    <td class="tsisip-actions-column">
                        <form method="POST" action="" style="display:inline">
                            <?php echo csrfInput(); ?>
                            <input type="hidden" name="action" value="toggle" aria-label="hidden">
                            <input type="hidden" name="id" aria-label="hidden" value="<?php echo $d['id']; ?>">
                            <input type="hidden" name="state" aria-label="hidden" value="<?php echo $d['state'] == 0 ? 1 : 0; ?>">
                            <button type="submit" class="tsisip-btn tsisip-btn-secondary">
                                <?php echo $d['state'] == 0 ? _('Deactivate') : _('Activate'); ?>
                            </button>
                        </form>
                        <button type="button" class="tsisip-btn tsisip-btn-secondary"
                                onclick="document.getElementById('edit-d<?php echo $d['id']; ?>').style.display='table-row'">
                            <?php echo _('Edit'); ?>
                        </button>
                        <form method="POST" action="" style="display:inline" onsubmit="return confirm('<?php echo _('Delete this destination?'); ?>')">
                            <?php echo csrfInput(); ?>
                            <input type="hidden" name="action" value="delete" aria-label="hidden">
                            <input type="hidden" name="id" aria-label="hidden" value="<?php echo $d['id']; ?>">
                            <button type="submit" class="tsisip-btn tsisip-btn-secondary tsisip-btn-delete"><?php echo _('Delete'); ?></button>
                        </form>
                    </td>
                </tr>
                <tr id="edit-d<?php echo $d['id']; ?>" style="display:none">
                    <td colspan="7">
                        <form method="POST" action="" class="tsisip-form">
                            <?php echo csrfInput(); ?>
                            <input type="hidden" name="action" value="update" aria-label="hidden">
                            <input type="hidden" name="id" aria-label="hidden" value="<?php echo $d['id']; ?>">
                            <div style="display:flex;flex-wrap:wrap;gap:1rem">
                                <div class="tsisip-form-group">
                                    <label><?php echo _('Set ID'); ?></label>
                                    <input type="number" id="edit-setid-<?php echo $d['id']; ?>" name="setid" class="tsisip-input" value="<?php echo $d['setid']; ?>" min="1" required style="width:80px">
                                </div>
                                <div class="tsisip-form-group">
                                    <label><?php echo _('Destination'); ?></label>
                                    <input type="text" id="edit-destination-<?php echo $d['id']; ?>" name="destination" class="tsisip-input" value="<?php echo htmlspecialchars($d['destination'], ENT_QUOTES); ?>" required style="width:280px">
                                </div>
                                <div class="tsisip-form-group">
                                    <label><?php echo _('Weight'); ?></label>
                                    <input type="number" id="edit-weight-<?php echo $d['id']; ?>" name="weight" class="tsisip-input" value="<?php echo $d['weight']; ?>" min="0" style="width:80px">
                                </div>
                                <div class="tsisip-form-group">
                                    <label><?php echo _('Priority'); ?></label>
                                    <input type="number" id="edit-priority-<?php echo $d['id']; ?>" name="priority" class="tsisip-input" value="<?php echo $d['priority']; ?>" style="width:80px">
                                </div>
                                <div class="tsisip-form-group">
                                    <label><?php echo _('Description'); ?></label>
                                    <input type="text" id="edit-description-<?php echo $d['id']; ?>" name="description" class="tsisip-input" value="<?php echo htmlspecialchars($d['description'] ?? '', ENT_QUOTES); ?>" style="width:180px">
                                </div>
                                <div class="tsisip-form-group">
                                    <label><?php echo _('Attrs'); ?></label>
                                    <input type="text" id="edit-attrs-<?php echo $d['id']; ?>" name="attrs" class="tsisip-input" value="<?php echo htmlspecialchars($d['attrs'] ?? '', ENT_QUOTES); ?>" style="width:120px">
                                </div>
                                <div class="tsisip-form-group">
                                    <label><?php echo _('State'); ?></label>
                                    <select name="state" class="tsisip-input" style="width:100px">
                                        <option value="0" <?php echo $d['state'] == 0 ? 'selected' : ''; ?>><?php echo _('Active'); ?></option>
                                        <option value="1" <?php echo $d['state'] == 1 ? 'selected' : ''; ?>><?php echo _('Inactive'); ?></option>
                                    </select>
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
        <?php echo renderPagination($page, $totalItems, $perPage, 'dispatcher.php'); ?>
    </div>
</div>
<?php require_once __DIR__ . '/common/footer.php'; ?>
