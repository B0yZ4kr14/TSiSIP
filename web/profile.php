<?php
/**
 * TSiSIP Control Panel — User Profile
 */
require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';

requireAuth();
checkPasswordChange();

$username = $_SESSION['username'] ?? 'unknown';
$role     = $_SESSION['role']     ?? 'unknown';
$theme    = $_SESSION['theme']    ?? 'light';
$lang     = $_SESSION['lang']     ?? 'en_US';

$pdo = getDb();
$stmt = $pdo->prepare("SELECT id, email, created_at, last_login FROM ocp_users WHERE username = :u");
$stmt->execute([':u' => $username]);
$userData = $stmt->fetch();

$email = $userData['email'] ?? '';

// Handle email update
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_email'])) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = _('Invalid CSRF token.');
    } else {
        $newEmail = trim($_POST['email'] ?? '');
        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $error = _('Invalid email address.');
        } else {
            $stmt = $pdo->prepare("UPDATE ocp_users SET email = :email, updated_at = NOW() WHERE id = :id");
            $stmt->execute([':email' => $newEmail, ':id' => $userData['id']]);
            $success = _('Email updated successfully.');
            $email = $newEmail;
            logAuditEvent('PROFILE_EMAIL_UPDATE', 'ocp_user', $username, true);
        }
    }
}

// Fetch last 10 login history entries
$loginHistory = [];
try {
    $stmt = $pdo->prepare(
        "SELECT login_time, ip_address, user_agent, result
         FROM ocp_login_log
         WHERE username = :username
         ORDER BY login_time DESC
         LIMIT 10"
    );
    $stmt->execute([':username' => $username]);
    $loginHistory = $stmt->fetchAll();
} catch (Exception $e) {
    // Table may not exist in older installs
}

logAuditEvent('CONFIG_VIEW', 'user', $username, true);

require_once __DIR__ . '/common/header.php';
?>
<div id="content" class="tsisip-dashboard">
    <h1><?php echo _('User Profile'); ?></h1>

    <div class="tsisip-dashboard-grid" style="grid-template-columns:1fr 2fr;">
        <!-- Left: User Info -->
        <div class="tsisip-dashboard-section">
            <h2 class="tsisip-section-title"><?php echo _('Account Information'); ?></h2>
            <div class="tsisip-data-row">
                <span class="tsisip-data-label"><?php echo _('Username'); ?></span>
                <span class="tsisip-data-value"><code><?php echo htmlspecialchars($username); ?></code></span>
            </div>
            <div class="tsisip-data-row">
                <span class="tsisip-data-label"><?php echo _('Role'); ?></span>
                <span class="tsisip-data-value">
                    <span class="tsisip-role-badge tsisip-role-badge--<?php echo htmlspecialchars($role); ?>">
                        <?php echo htmlspecialchars($role); ?>
                    </span>
                </span>
            </div>
            <div class="tsisip-data-row">
                <span class="tsisip-data-label"><?php echo _('Email'); ?></span>
                <span class="tsisip-data-value"><?php echo htmlspecialchars($email); ?></span>
            </div>
            <div class="tsisip-data-row">
                <span class="tsisip-data-label"><?php echo _('Member Since'); ?></span>
                <span class="tsisip-data-value"><?php echo htmlspecialchars((string) ($userData['created_at'] ?? '—')); ?></span>
            </div>
            <div class="tsisip-data-row">
                <span class="tsisip-data-label"><?php echo _('Last Login'); ?></span>
                <span class="tsisip-data-value"><?php echo htmlspecialchars((string) ($userData['last_login'] ?? '—')); ?></span>
            </div>
        </div>

        <!-- Right: Preferences & Actions -->
        <div class="tsisip-dashboard-section">
            <h2 class="tsisip-section-title"><?php echo _('Preferences'); ?></h2>

            <?php if ($error): ?>
                <div class="tsisip-alert tsisip-alert--error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="tsisip-alert tsisip-alert--success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <!-- Email Update -->
            <form method="post" class="tsisip-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken()); ?>">
                <div class="tsisip-form-group">
                    <label class="tsisip-form-label"><?php echo _('Email'); ?></label>
                    <input type="email" name="email" class="tsisip-input" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
                <button type="submit" name="update_email" class="tsisip-btn tsisip-btn-secondary"><?php echo _('Update Email'); ?></button>
            </form>

            <!-- Theme -->
            <div class="tsisip-form-group" style="margin-top:1rem;">
                <label class="tsisip-form-label"><?php echo _('Theme'); ?></label>
                <div class="tsisip-btn-group">
                    <a href="common/set-theme.php?theme=light&<?php echo http_build_query(['csrf_token' => generateCsrfToken()]); ?>"
                       class="tsisip-btn <?php echo $theme === 'light' ? 'tsisip-btn-primary' : 'tsisip-btn-outline'; ?>">
                        <?php echo _('Light'); ?>
                    </a>
                    <a href="common/set-theme.php?theme=dark&<?php echo http_build_query(['csrf_token' => generateCsrfToken()]); ?>"
                       class="tsisip-btn <?php echo $theme === 'dark' ? 'tsisip-btn-primary' : 'tsisip-btn-outline'; ?>">
                        <?php echo _('Dark'); ?>
                    </a>
                </div>
            </div>

            <!-- Language -->
            <div class="tsisip-form-group" style="margin-top:1rem;">
                <label class="tsisip-form-label"><?php echo _('Language'); ?></label>
                <div class="tsisip-btn-group">
                    <a href="common/set-language.php?lang=en_US&<?php echo http_build_query(['csrf_token' => generateCsrfToken()]); ?>"
                       class="tsisip-btn <?php echo $lang === 'en_US' ? 'tsisip-btn-primary' : 'tsisip-btn-outline'; ?>">
                        English
                    </a>
                    <a href="common/set-language.php?lang=es_ES&<?php echo http_build_query(['csrf_token' => generateCsrfToken()]); ?>"
                       class="tsisip-btn <?php echo $lang === 'es_ES' ? 'tsisip-btn-primary' : 'tsisip-btn-outline'; ?>">
                        Español
                    </a>
                    <a href="common/set-language.php?lang=pt_BR&<?php echo http_build_query(['csrf_token' => generateCsrfToken()]); ?>"
                       class="tsisip-btn <?php echo $lang === 'pt_BR' ? 'tsisip-btn-primary' : 'tsisip-btn-outline'; ?>">
                        Português
                    </a>
                </div>
            </div>

            <!-- Color Preset -->
            <div class="tsisip-form-group" style="margin-top:1rem;">
                <label class="tsisip-form-label"><?php echo _('Color Preset'); ?></label>
                <div class="tsisip-btn-group">
                    <button class="tsisip-btn tsisip-btn-outline" onclick="setPreset('default')" style="border-color:var(--tsisip-primary-blue)"><?php echo _('Default'); ?></button>
                    <button class="tsisip-btn tsisip-btn-outline" onclick="setPreset('ocean')" style="border-color:#0ea5e9"><?php echo _('Ocean'); ?></button>
                    <button class="tsisip-btn tsisip-btn-outline" onclick="setPreset('forest')" style="border-color:#10b981"><?php echo _('Forest'); ?></button>
                    <button class="tsisip-btn tsisip-btn-outline" onclick="setPreset('sunset')" style="border-color:#f97316"><?php echo _('Sunset'); ?></button>
                </div>
            </div>
            <script>
            function setPreset(preset) {
                document.documentElement.setAttribute('data-theme-preset', preset);
                fetch('/common/save-theme-preset.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({preset: preset})
                });
            }
            </script>

            <!-- Change Password -->
            <div class="tsisip-form-group" style="margin-top:2rem;">
                <a href="change-password.php" class="tsisip-btn tsisip-btn-warning">
                    <?php echo _('Change Password'); ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Two-Factor Authentication (Feature 037) -->
    <div class="tsisip-dashboard-section" style="margin-top:1.5rem;" id="mfa-section">
        <h2 class="tsisip-section-title"><?php echo _('Two-Factor Authentication'); ?></h2>
        <div id="mfa-status">
            <p class="tsisip-text-muted"><?php echo _('Loading MFA status...'); ?></p>
        </div>
        <div id="mfa-enroll" style="display:none;">
            <div id="mfa-qr" style="margin:1rem 0;"></div>
            <div class="tsisip-form-group">
                <label for="mfa-verify-code"><?php echo _('Enter code from app to confirm'); ?></label>
                <input type="text" id="mfa-verify-code" maxlength="6" pattern="[0-9]{6}" placeholder="000000" inputmode="numeric">
            </div>
            <div id="mfa-backup-codes" style="display:none; margin:1rem 0; padding:1rem; background:#f8f9fa; border-radius:4px;">
                <h3><?php echo _('Save these backup codes'); ?></h3>
                <p class="tsisip-text-muted"><?php echo _('Each code can only be used once. Store them securely.'); ?></p>
                <pre id="mfa-backup-list" style="font-family:monospace; font-size:1.1rem; line-height:1.8;"></pre>
            </div>
            <button class="tsisip-btn tsisip-btn--primary" onclick="completeMfaEnroll()"><?php echo _('Enable 2FA'); ?></button>
            <button class="tsisip-btn" onclick="cancelMfaEnroll()"><?php echo _('Cancel'); ?></button>
        </div>
        <div id="mfa-actions" style="display:none; margin-top:1rem;">
            <button class="tsisip-btn tsisip-btn--danger" onclick="disableMfa()"><?php echo _('Disable 2FA'); ?></button>
            <button class="tsisip-btn tsisip-btn--secondary" onclick="regenerateBackupCodes()"><?php echo _('Regenerate Backup Codes'); ?></button>
        </div>
    </div>

    <script>
    async function loadMfaStatus() {
        try {
            const res = await fetch('api/v1/mfa-status.php');
            const data = await res.json();
            const statusEl = document.getElementById('mfa-status');
            const enrollEl = document.getElementById('mfa-enroll');
            const actionsEl = document.getElementById('mfa-actions');
            if (data.enabled) {
                statusEl.innerHTML = '<span class="tsisip-badge tsisip-badge-success"><?php echo _('Enabled'); ?></span> <span class="tsisip-text-muted">(' + data.backup_codes_left + ' backup codes left)</span>';
                actionsEl.style.display = 'block';
            } else {
                statusEl.innerHTML = '<span class="tsisip-badge tsisip-badge-error"><?php echo _('Not Enabled'); ?></span>';
                statusEl.innerHTML += ' <button class="tsisip-btn tsisip-btn--primary" onclick="startMfaEnroll()"><?php echo _('Enable 2FA'); ?></button>';
            }
        } catch (e) {
            document.getElementById('mfa-status').innerHTML = '<span class="tsisip-text-muted"><?php echo _('Unable to load MFA status'); ?></span>';
        }
    }

    let mfaEnrollSecret = '';

    async function startMfaEnroll() {
        try {
            const res = await fetch('api/v1/mfa-enroll.php');
            const data = await res.json();
            if (data.error) { alert(data.error); return; }
            mfaEnrollSecret = data.secret;
            document.getElementById('mfa-qr').innerHTML = data.qr_svg + '<p class="tsisip-text-muted" style="margin-top:0.5rem;"><?php echo _('Manual entry:'); ?> <code>' + data.manual_entry + '</code></p>';
            document.getElementById('mfa-enroll').style.display = 'block';
            document.getElementById('mfa-status').style.display = 'none';
        } catch (e) {
            alert('<?php echo _('Failed to start enrollment'); ?>');
        }
    }

    async function completeMfaEnroll() {
        const code = document.getElementById('mfa-verify-code').value.trim();
        if (!/^\d{6}$/.test(code)) { alert('<?php echo _('Enter a 6-digit code'); ?>'); return; }
        try {
            const res = await fetch('api/v1/mfa-enroll.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({code: code})
            });
            const data = await res.json();
            if (data.success) {
                document.getElementById('mfa-backup-list').textContent = data.backup_codes.join('\n');
                document.getElementById('mfa-backup-codes').style.display = 'block';
                document.getElementById('mfa-enroll').style.display = 'none';
                loadMfaStatus();
                alert('<?php echo _('2FA enabled successfully! Save your backup codes.'); ?>');
            } else {
                alert(data.error || '<?php echo _('Verification failed'); ?>');
            }
        } catch (e) {
            alert('<?php echo _('Enrollment failed'); ?>');
        }
    }

    function cancelMfaEnroll() {
        document.getElementById('mfa-enroll').style.display = 'none';
        document.getElementById('mfa-status').style.display = 'block';
        mfaEnrollSecret = '';
    }

    async function disableMfa() {
        if (!confirm('<?php echo _('Disable 2FA? You will need to re-enroll.'); ?>')) return;
        const password = prompt('<?php echo _('Enter your password:'); ?>');
        if (!password) return;
        const code = prompt('<?php echo _('Enter current 2FA code:'); ?>');
        if (!code) return;
        try {
            const res = await fetch('api/v1/mfa-disable.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({password: password, code: code})
            });
            const data = await res.json();
            if (data.success) {
                alert('<?php echo _('2FA disabled'); ?>');
                loadMfaStatus();
            } else {
                alert(data.error || '<?php echo _('Failed to disable'); ?>');
            }
        } catch (e) {
            alert('<?php echo _('Request failed'); ?>');
        }
    }

    async function regenerateBackupCodes() {
        if (!confirm('<?php echo _('Regenerate backup codes? Old codes will be invalidated.'); ?>')) return;
        // Re-use enrollment endpoint with existing secret
        alert('<?php echo _('Please disable and re-enable 2FA to regenerate backup codes.'); ?>');
    }

    loadMfaStatus();
    </script>

    <!-- Login History -->
    <div class="tsisip-dashboard-section" style="margin-top:1.5rem;">
        <h2 class="tsisip-section-title"><?php echo _('Recent Login History'); ?></h2>
        <?php if (!empty($loginHistory)): ?>
            <table class="tsisip-table">
                <thead>
                    <tr>
                        <th><?php echo _('Time'); ?></th>
                        <th><?php echo _('IP Address'); ?></th>
                        <th><?php echo _('Result'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($loginHistory as $entry): ?>
                        <tr>
                            <td><?php echo htmlspecialchars(substr($entry['login_time'] ?? $entry['created_at'] ?? '-', 0, 16)); ?></td>
                            <td><code><?php echo htmlspecialchars($entry['ip_address'] ?? '-'); ?></code></td>
                            <td>
                                <?php if (($entry['result'] ?? '') === 'success'): ?>
                                    <span class="tsisip-badge tsisip-badge-success"><?php echo _('Success'); ?></span>
                                <?php else: ?>
                                    <span class="tsisip-badge tsisip-badge-error"><?php echo _('Failed'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="tsisip-text-muted"><?php echo _('No login history available.'); ?></p>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/common/footer.php'; ?>
