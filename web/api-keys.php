<?php
/**
 * TSiSIP Control Panel — API Key Management
 * Feature 031
 */

require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';

requireAuth();
checkPasswordChange();
requireRole('admin');

$pdo = getDb();
$flash = getFlash();
$error = '';
$success = '';

// Generate new key
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = _('Invalid CSRF token.');
    } else {
        $name = trim($_POST['name'] ?? '');
        $scope = $_POST['scope'] ?? 'readonly';
        if ($name === '') {
            $error = _('Key name is required.');
        } elseif (!in_array($scope, ['readonly', 'readwrite'], true)) {
            $error = _('Invalid scope.');
        } else {
            $rawKey = bin2hex(random_bytes(32));
            $hash = password_hash($rawKey, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare(
                "INSERT INTO ocp_api_keys (name, key_hash, scope, created_by, created_at, is_active)
                 VALUES (:name, :key_hash, :scope, :created_by, NOW(), true)"
            );
            $stmt->execute([
                ':name' => $name,
                ':key_hash' => $hash,
                ':scope' => $scope,
                ':created_by' => $_SESSION['ocp_user_id'] ?? null,
            ]);
            $success = _('API key generated. Copy it now — it will not be shown again: ') . $rawKey;
            logAuditEvent('API_KEY_CREATE', 'api_key', $name, true);
        }
    }
}

// Revoke key
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['revoke'])) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = _('Invalid CSRF token.');
    } else {
        $keyId = $_POST['key_id'] ?? '';
        $stmt = $pdo->prepare("UPDATE ocp_api_keys SET is_active = false, deleted_at = NOW() WHERE id = :id");
        $stmt->execute([':id' => $keyId]);
        $success = _('API key revoked.');
        logAuditEvent('API_KEY_REVOKE', 'api_key', $keyId, true);
    }
}

// List keys
$stmt = $pdo->query(
    "SELECT k.id, k.name, k.scope, k.created_at, k.expires_at, k.last_used_at, k.is_active, u.username as created_by
     FROM ocp_api_keys k
     LEFT JOIN ocp_users u ON k.created_by = u.id
     WHERE k.deleted_at IS NULL
     ORDER BY k.created_at DESC"
);
$keys = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/common/header.php';
?>
<div id="content">
    <h1><?php echo _('API Key Management'); ?></h1>

    <?php if ($error): ?>
        <div class="tsisip-alert tsisip-alert--error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="tsisip-alert tsisip-alert--success">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>
    <?php if ($flash): ?>
        <div class="tsisip-alert tsisip-alert--<?php echo htmlspecialchars($flash['type']); ?>">
            <?php echo htmlspecialchars($flash['message']); ?>
        </div>
    <?php endif; ?>

    <!-- Generate Form -->
    <div class="tsisip-dashboard-section" style="max-width:500px;">
        <h2><?php echo _('Generate New Key'); ?></h2>
        <form method="post" class="tsisip-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken()); ?>">
            <div class="tsisip-form-group">
                <label class="tsisip-form-label"><?php echo _('Key Name'); ?></label>
                <input type="text" name="name" class="tsisip-input" placeholder="e.g. Monitoring System" required>
            </div>
            <div class="tsisip-form-group">
                <label class="tsisip-form-label"><?php echo _('Scope'); ?></label>
                <select name="scope" class="tsisip-input">
                    <option value="readonly"><?php echo _('Read-only'); ?></option>
                    <option value="readwrite"><?php echo _('Read & Write'); ?></option>
                </select>
            </div>
            <button type="submit" name="generate" class="tsisip-btn tsisip-btn-primary"><?php echo _('Generate Key'); ?></button>
        </form>
    </div>

    <!-- Keys Table -->
    <div class="tsisip-dashboard-section" style="margin-top:1.5rem;">
        <h2><?php echo _('Active Keys'); ?></h2>
        <table class="tsisip-table">
            <thead>
                <tr>
                    <th><?php echo _('Name'); ?></th>
                    <th><?php echo _('Scope'); ?></th>
                    <th><?php echo _('Created By'); ?></th>
                    <th><?php echo _('Created'); ?></th>
                    <th><?php echo _('Last Used'); ?></th>
                    <th><?php echo _('Status'); ?></th>
                    <th><?php echo _('Actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($keys as $k): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($k['name']); ?></td>
                        <td><span class="tsisip-badge tsisip-badge-<?php echo $k['scope'] === 'readwrite' ? 'warning' : 'info'; ?>"><?php echo htmlspecialchars($k['scope']); ?></span></td>
                        <td><?php echo htmlspecialchars($k['created_by'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars(substr($k['created_at'], 0, 16)); ?></td>
                        <td><?php echo $k['last_used_at'] ? htmlspecialchars(substr($k['last_used_at'], 0, 16)) : '-'; ?></td>
                        <td>
                            <?php if ($k['is_active']): ?>
                                <span class="tsisip-badge tsisip-badge-success"><?php echo _('Active'); ?></span>
                            <?php else: ?>
                                <span class="tsisip-badge tsisip-badge-error"><?php echo _('Revoked'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($k['is_active']): ?>
                                <form method="post" style="display:inline;" onsubmit="return confirm('<?php echo _('Revoke this key?'); ?>');">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken()); ?>">
                                    <input type="hidden" name="key_id" value="<?php echo $k['id']; ?>">
                                    <button type="submit" name="revoke" class="tsisip-btn tsisip-btn-sm tsisip-btn-error"><?php echo _('Revoke'); ?></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($keys)): ?>
                    <tr><td colspan="7" class="tsisip-text-muted"><?php echo _('No API keys found.'); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div style="margin-top:1rem;">
        <a href="api-docs.php" class="tsisip-btn tsisip-btn-outline"><?php echo _('API Documentation'); ?></a>
    </div>
</div>
<?php require_once __DIR__ . '/common/footer.php'; ?>
