<?php
/**
 * TSiSIP Control Panel — Subscriber Management
 * Full CRUD on the OpenSIPS subscriber table with HA1 generation.
 */

require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';
require_once __DIR__ . '/common/pagination.php';
require_once __DIR__ . '/common/ha1-generator.php';

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
            $username = trim($_POST['username'] ?? '');
            $domain   = trim($_POST['domain'] ?? '');
            $password = $_POST['password'] ?? '';
            $tenantId = $_POST['tenant_id'] ?? '00000000-0000-0000-0000-000000000000';
            $enabled  = isset($_POST['enabled']) ? true : false;

            if ($username === '' || $domain === '' || $password === '') {
                $error = _('Username, domain, and password are required.');
            } elseif (strlen($password) < 8) {
                $error = _('Password must be at least 8 characters.');
            } else {
                $hashes = generateHa1Hashes($username, $domain, $password);
                try {
                    $stmt = $pdo->prepare(
                        "INSERT INTO subscriber
                         (username, domain, ha1, ha1_sha256, ha1_sha512t256, password, email_address, tenant_id, routing_group, enabled)
                         VALUES (:username, :domain, :ha1, :ha1_sha256, :ha1_sha512t256, '', :email, :tenant_id, 1, :enabled)"
                    );
                    $stmt->execute([
                        ':username'      => $username,
                        ':domain'        => $domain,
                        ':ha1'           => $hashes['ha1'],
                        ':ha1_sha256'    => $hashes['ha1_sha256'],
                        ':ha1_sha512t256'=> $hashes['ha1_sha512t256'],
                        ':email'         => $_POST['email'] ?? '',
                        ':tenant_id'     => $tenantId,
                        ':enabled'       => $enabled,
                    ]);
                    $success = _('Subscriber created successfully.');
                } catch (PDOException $e) {
                    $error = _('Failed to create subscriber: ') . $e->getMessage();
                }
            }
        } elseif ($action === 'update') {
            $id       = $_POST['id'] ?? '';
            $username = trim($_POST['username'] ?? '');
            $domain   = trim($_POST['domain'] ?? '');
            $password = $_POST['password'] ?? '';
            $tenantId = $_POST['tenant_id'] ?? '00000000-0000-0000-0000-000000000000';
            $enabled  = isset($_POST['enabled']) ? true : false;

            if ($id === '' || $username === '' || $domain === '') {
                $error = _('ID, username, and domain are required.');
            } else {
                try {
                    if ($password !== '') {
                        $hashes = generateHa1Hashes($username, $domain, $password);
                        $stmt = $pdo->prepare(
                            "UPDATE subscriber SET
                             username = :username,
                             domain = :domain,
                             ha1 = :ha1,
                             ha1_sha256 = :ha1_sha256,
                             ha1_sha512t256 = :ha1_sha512t256,
                             email_address = :email,
                             tenant_id = :tenant_id,
                             enabled = :enabled,
                             modified_at = NOW()
                             WHERE id = :id"
                        );
                        $stmt->execute([
                            ':id'            => $id,
                            ':username'      => $username,
                            ':domain'        => $domain,
                            ':ha1'           => $hashes['ha1'],
                            ':ha1_sha256'    => $hashes['ha1_sha256'],
                            ':ha1_sha512t256'=> $hashes['ha1_sha512t256'],
                            ':email'         => $_POST['email'] ?? '',
                            ':tenant_id'     => $tenantId,
                            ':enabled'       => $enabled,
                        ]);
                    } else {
                        $stmt = $pdo->prepare(
                            "UPDATE subscriber SET
                             username = :username,
                             domain = :domain,
                             email_address = :email,
                             tenant_id = :tenant_id,
                             enabled = :enabled,
                             modified_at = NOW()
                             WHERE id = :id"
                        );
                        $stmt->execute([
                            ':id'        => $id,
                            ':username'  => $username,
                            ':domain'    => $domain,
                            ':email'     => $_POST['email'] ?? '',
                            ':tenant_id' => $tenantId,
                            ':enabled'   => $enabled,
                        ]);
                    }
                    $success = _('Subscriber updated successfully.');
                } catch (PDOException $e) {
                    $error = _('Failed to update subscriber: ') . $e->getMessage();
                }
            }
        } elseif ($action === 'toggle') {
            $id = $_POST['id'] ?? '';
            $enabled = $_POST['enabled'] ?? '0';
            if ($id !== '') {
                $stmt = $pdo->prepare("UPDATE subscriber SET enabled = :enabled, modified_at = NOW() WHERE id = :id");
                $stmt->execute([':id' => $id, ':enabled' => ($enabled === '1' ? true : false)]);
                $success = _('Subscriber status updated.');
            }
        }
    }
}

// --- Fetch tenants for dropdown ---
$tenants = $pdo->query("SELECT id, name, sip_domain FROM tenants ORDER BY name")->fetchAll();

// --- Fetch subscribers with pagination ---
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 25;
$pagination = getPagination($page, $perPage);

// Tenant filter (admin sees all, devops sees only their accessible tenants)
$tenantFilter = '';
$params = [];
if (!isAdmin() && $userRole === 'devops') {
    // Devops sees only default tenant for now (can be refined later)
    $tenantFilter = "WHERE tenant_id = :tenant_id";
    $params[':tenant_id'] = '00000000-0000-0000-0000-000000000000';
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM subscriber $tenantFilter");
$countStmt->execute($params);
$totalItems = (int) $countStmt->fetchColumn();

$listStmt = $pdo->prepare(
    "SELECT s.id, s.username, s.domain, s.email_address, s.enabled,
            s.tenant_id, t.name AS tenant_name
     FROM subscriber s
     LEFT JOIN tenants t ON s.tenant_id = t.id
     $tenantFilter
     ORDER BY s.id DESC
     LIMIT :limit OFFSET :offset"
);
foreach ($params as $k => $v) {
    $listStmt->bindValue($k, $v);
}
$listStmt->bindValue(':limit', $pagination['limit'], PDO::PARAM_INT);
$listStmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
$listStmt->execute();
$subscribers = $listStmt->fetchAll();

require_once __DIR__ . '/common/header.php';
?>
<div id="content">
    <h2><?php echo _('Subscriber Management'); ?></h2>

    <?php if ($error): ?>
        <div class="tsisip-badge tsisip-badge-error" role="alert"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="tsisip-badge tsisip-badge-success" role="status"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <!-- Create Form -->
    <div class="tsisip-dashboard-section">
        <h3><?php echo _('Add New Subscriber'); ?></h3>
        <form method="POST" action="" class="tsisip-form">
            <?php echo csrfInput(); ?>
            <input type="hidden" name="action" value="create">
            <div class="tsisip-form-group">
                <label for="username"><?php echo _('Username'); ?></label>
                <input type="text" id="username" name="username" class="tsisip-input" required>
            </div>
            <div class="tsisip-form-group">
                <label for="domain"><?php echo _('Domain'); ?></label>
                <input type="text" id="domain" name="domain" class="tsisip-input" value="sip.tsisip.local" required>
            </div>
            <div class="tsisip-form-group">
                <label for="password"><?php echo _('Password'); ?></label>
                <input type="password" id="password" name="password" class="tsisip-input" required minlength="8">
            </div>
            <div class="tsisip-form-group">
                <label for="email"><?php echo _('Email'); ?></label>
                <input type="email" id="email" name="email" class="tsisip-input">
            </div>
            <div class="tsisip-form-group">
                <label for="tenant_id"><?php echo _('Tenant'); ?></label>
                <select id="tenant_id" name="tenant_id" class="tsisip-input">
                    <?php foreach ($tenants as $t): ?>
                        <option value="<?php echo htmlspecialchars($t['id'], ENT_QUOTES); ?>">
                            <?php echo htmlspecialchars($t['name'] . ' (' . $t['sip_domain'] . ')', ENT_QUOTES); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="tsisip-form-group">
                <label>
                    <input type="checkbox" name="enabled" checked>
                    <?php echo _('Enabled'); ?>
                </label>
            </div>
            <button type="submit" class="tsisip-btn tsisip-btn-primary"><?php echo _('Create Subscriber'); ?></button>
        </form>
    </div>

    <!-- List -->
    <div class="tsisip-dashboard-section">
        <h3><?php echo _('Subscribers'); ?> (<?php echo $totalItems; ?>)</h3>
        <table class="dataTable tsisip-table">
            <thead>
                <tr>
                    <th><?php echo _('Username'); ?></th>
                    <th><?php echo _('Domain'); ?></th>
                    <th><?php echo _('Tenant'); ?></th>
                    <th><?php echo _('Email'); ?></th>
                    <th><?php echo _('Status'); ?></th>
                    <th><?php echo _('Actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($subscribers as $sub): ?>
                <tr>
                    <td><?php echo htmlspecialchars($sub['username']); ?></td>
                    <td><?php echo htmlspecialchars($sub['domain']); ?></td>
                    <td><?php echo htmlspecialchars($sub['tenant_name'] ?? _('Default')); ?></td>
                    <td><?php echo htmlspecialchars($sub['email_address'] ?? ''); ?></td>
                    <td>
                        <?php if ($sub['enabled']): ?>
                            <span class="tsisip-badge tsisip-badge-success"><?php echo _('Active'); ?></span>
                        <?php else: ?>
                            <span class="tsisip-badge tsisip-badge-error"><?php echo _('Disabled'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="tsisip-actions-column">
                        <form method="POST" action="" style="display:inline">
                            <?php echo csrfInput(); ?>
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($sub['id'], ENT_QUOTES); ?>">
                            <input type="hidden" name="enabled" value="<?php echo $sub['enabled'] ? '0' : '1'; ?>">
                            <button type="submit" class="tsisip-btn tsisip-btn-secondary">
                                <?php echo $sub['enabled'] ? _('Disable') : _('Enable'); ?>
                            </button>
                        </form>
                        <button type="button" class="tsisip-btn tsisip-btn-secondary"
                                onclick="document.getElementById('edit-<?php echo htmlspecialchars($sub['id'], ENT_QUOTES); ?>').style.display='block'">
                            <?php echo _('Edit'); ?>
                        </button>
                    </td>
                </tr>
                <tr id="edit-<?php echo htmlspecialchars($sub['id'], ENT_QUOTES); ?>" style="display:none">
                    <td colspan="6">
                        <form method="POST" action="" class="tsisip-form">
                            <?php echo csrfInput(); ?>
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($sub['id'], ENT_QUOTES); ?>">
                            <div class="tsisip-form-group">
                                <label><?php echo _('Username'); ?></label>
                                <input type="text" name="username" class="tsisip-input" value="<?php echo htmlspecialchars($sub['username'], ENT_QUOTES); ?>" required>
                            </div>
                            <div class="tsisip-form-group">
                                <label><?php echo _('Domain'); ?></label>
                                <input type="text" name="domain" class="tsisip-input" value="<?php echo htmlspecialchars($sub['domain'], ENT_QUOTES); ?>" required>
                            </div>
                            <div class="tsisip-form-group">
                                <label><?php echo _('Password (leave blank to keep)'); ?></label>
                                <input type="password" name="password" class="tsisip-input" minlength="8">
                            </div>
                            <div class="tsisip-form-group">
                                <label><?php echo _('Email'); ?></label>
                                <input type="email" name="email" class="tsisip-input" value="<?php echo htmlspecialchars($sub['email_address'] ?? '', ENT_QUOTES); ?>">
                            </div>
                            <div class="tsisip-form-group">
                                <label><?php echo _('Tenant'); ?></label>
                                <select name="tenant_id" class="tsisip-input">
                                    <?php foreach ($tenants as $t): ?>
                                        <option value="<?php echo htmlspecialchars($t['id'], ENT_QUOTES); ?>"
                                            <?php echo $t['id'] === $sub['tenant_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($t['name'] . ' (' . $t['sip_domain'] . ')', ENT_QUOTES); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="tsisip-form-group">
                                <label>
                                    <input type="checkbox" name="enabled" <?php echo $sub['enabled'] ? 'checked' : ''; ?>>
                                    <?php echo _('Enabled'); ?>
                                </label>
                            </div>
                            <button type="submit" class="tsisip-btn tsisip-btn-primary"><?php echo _('Save Changes'); ?></button>
                            <button type="button" class="tsisip-btn tsisip-btn-secondary"
                                    onclick="document.getElementById('edit-<?php echo htmlspecialchars($sub['id'], ENT_QUOTES); ?>').style.display='none'">
                                <?php echo _('Cancel'); ?>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php echo renderPagination($page, $totalItems, $perPage, 'subscribers.php'); ?>
    </div>
</div>
<?php require_once __DIR__ . '/common/footer.php'; ?>
