<?php
/**
 * TSiSIP Dispatcher Management — CRUD, changelog, rollback
 * Feature 035
 */
require_once __DIR__ . '/common/config.php';
requireAuth();
checkPasswordChange();

$userRole = $_SESSION['ocp_user_role'] ?? 'readonly';
$canEdit = in_array($userRole, ['admin', 'devops'], true);

logAuditEvent('CONFIG_VIEW', 'system', 'dispatcher', true);

// Fetch dispatcher destinations
$pdo = getDb();
$destinations = [];
try {
    $stmt = $pdo->query(
        "SELECT id, setid, destination, state, probe_mode, weight, priority, attrs, description
         FROM dispatcher ORDER BY setid, priority DESC, id"
    );
    $destinations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $destinations = [];
}

// Fetch recent changelog
$changelog = [];
try {
    $stmt = $pdo->query(
        "SELECT id, setid, destination, action, old_payload, new_payload, rollback_payload,
                changed_by, changed_at, reverted_at, revert_reason
         FROM dispatcher_changelog
         ORDER BY changed_at DESC LIMIT 100"
    );
    $changelog = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $changelog = [];
}

$pageTitle = _('Dispatcher Management');
require_once __DIR__ . '/common/header.php';
?>
<style>
.tsisip-dispatcher__toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1rem;
    align-items: center;
}
.tsisip-dispatcher__toolbar form {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}
.tsisip-state-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}
.tsisip-state-badge--active { background: #d4edda; color: #155724; }
.tsisip-state-badge--inactive { background: #f8d7da; color: #721c24; }
.tsisip-state-badge--probing { background: #fff3cd; color: #856404; }
.tsisip-state-badge--disabled { background: #e2e3e5; color: #383d41; }
.tsisip-changelog-row--reverted { opacity: 0.6; text-decoration: line-through; }
.tsisip-modal-overlay {
    position: fixed; inset: 0; background: rgba(0,0,0,0.5);
    display: none; justify-content: center; align-items: center; z-index: 1000;
}
.tsisip-modal-overlay.is-open { display: flex; }
.tsisip-modal {
    background: var(--tsisip-surface, #fff);
    border-radius: 8px;
    max-width: 600px; width: 90%;
    max-height: 90vh; overflow-y: auto;
    padding: 1.5rem;
}
.tsisip-modal h2 { margin-top: 0; }
.tsisip-form-group { margin-bottom: 1rem; }
.tsisip-form-group label { display: block; font-weight: 600; margin-bottom: 0.25rem; }
.tsisip-form-group input, .tsisip-form-group select, .tsisip-form-group textarea {
    width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px;
    font-family: inherit; font-size: 1rem;
}
.tsisip-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
@media (max-width: 480px) { .tsisip-form-row { grid-template-columns: 1fr; } }
.tsisip-section-toggle {
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 1.5rem;
    padding: 0.75rem;
    background: var(--tsisip-surface-2, #f8f9fa);
    border-radius: 4px;
}
.tsisip-section-toggle::before {
    content: '▶';
    font-size: 0.7rem;
    transition: transform 0.2s;
}
.tsisip-section-toggle.is-open::before { transform: rotate(90deg); }
.tsisip-section-body {
    display: none;
    margin-top: 0.5rem;
}
.tsisip-section-body.is-open { display: block; }
.tsisip-breadcrumb {
    font-size: 0.875rem;
    color: var(--tsisip-text-muted, #6c757d);
    margin-bottom: 0.5rem;
}
.tsisip-breadcrumb a { color: inherit; text-decoration: none; }
.tsisip-breadcrumb a:hover { text-decoration: underline; }
</style>

<div class="tsisip-breadcrumb">
    <a href="dashboard.php"><?php echo _('Dashboard'); ?></a> / <?php echo htmlspecialchars($pageTitle); ?>
</div>

<h1><?php echo htmlspecialchars($pageTitle); ?></h1>

<!-- Toolbar -->
<div class="tsisip-dispatcher__toolbar">
    <?php if ($canEdit): ?>
    <button class="tsisip-btn tsisip-btn--primary" id="btn-add" onclick="openModal('add')">
        <?php echo _('Add Destination'); ?>
    </button>
    <button class="tsisip-btn tsisip-btn--secondary" id="btn-reload" onclick="doReload()">
        <?php echo _('Reload MI'); ?>
    </button>
    <?php endif; ?>
    <a href="api/v1/dispatcher-export.php" class="tsisip-btn tsisip-btn--secondary" download>
        <?php echo _('Export CSV'); ?>
    </a>
    <?php if ($canEdit): ?>
    <form id="import-form" enctype="multipart/form-data" onsubmit="return doImport(event)">
        <?php echo csrfInput(); ?>
        <input type="file" name="csv" id="import-csv" accept=".csv" required style="font-size:0.85rem;">
        <button type="submit" class="tsisip-btn tsisip-btn--secondary">
            <?php echo _('Import CSV'); ?>
        </button>
    </form>
    <?php endif; ?>
    <span id="toolbar-msg" style="margin-left:auto;color:var(--tsisip-success,#28a745);"></span>
</div>

<!-- Destinations Table -->
<div class="tsisip-table-container">
    <table class="tsisip-table" id="dispatcher-table">
        <thead>
            <tr>
                <th><?php echo _('ID'); ?></th>
                <th><?php echo _('Set'); ?></th>
                <th><?php echo _('Destination'); ?></th>
                <th><?php echo _('State'); ?></th>
                <th><?php echo _('Probe'); ?></th>
                <th><?php echo _('Weight'); ?></th>
                <th><?php echo _('Priority'); ?></th>
                <th><?php echo _('Attrs'); ?></th>
                <th><?php echo _('Description'); ?></th>
                <?php if ($canEdit): ?><th><?php echo _('Actions'); ?></th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($destinations as $d): ?>
            <tr data-id="<?php echo (int)$d['id']; ?>">
                <td><?php echo (int)$d['id']; ?></td>
                <td><?php echo (int)$d['setid']; ?></td>
                <td><?php echo htmlspecialchars($d['destination']); ?></td>
                <td><?php echo stateBadge((int)$d['state']); ?></td>
                <td><?php echo probeBadge((int)$d['probe_mode']); ?></td>
                <td><?php echo htmlspecialchars((string)$d['weight']); ?></td>
                <td><?php echo (int)$d['priority']; ?></td>
                <td><?php echo htmlspecialchars($d['attrs'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($d['description'] ?? ''); ?></td>
                <?php if ($canEdit): ?>
                <td>
                    <button class="tsisip-btn tsisip-btn--sm" onclick='openEdit(<?php echo json_encode($d, JSON_HEX_TAG|JSON_HEX_APOS); ?>)'>
                        <?php echo _('Edit'); ?>
                    </button>
                    <button class="tsisip-btn tsisip-btn--sm" onclick="doProbe(<?php echo (int)$d['setid']; ?>, '<?php echo htmlspecialchars($d['destination'], ENT_QUOTES); ?>')">
                        <?php echo _('Probe'); ?>
                    </button>
                    <button class="tsisip-btn tsisip-btn--sm tsisip-btn--danger" onclick="doDelete(<?php echo (int)$d['id']; ?>)">
                        <?php echo _('Del'); ?>
                    </button>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($destinations)): ?>
            <tr><td colspan="<?php echo $canEdit ? 10 : 9; ?>" style="text-align:center;">
                <?php echo _('No dispatcher destinations configured.'); ?>
            </td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Changelog Section -->
<div class="tsisip-section-toggle" onclick="toggleSection('changelog')">
    <strong><?php echo _('Changelog & Rollback'); ?></strong>
    <span class="tsisip-text-muted">(<?php echo count($changelog); ?> <?php echo _('entries'); ?>)</span>
</div>
<div class="tsisip-section-body" id="changelog-body">
    <div class="tsisip-table-container" style="max-height:400px;overflow:auto;">
        <table class="tsisip-table">
            <thead>
                <tr>
                    <th><?php echo _('Time'); ?></th>
                    <th><?php echo _('Action'); ?></th>
                    <th><?php echo _('Set'); ?></th>
                    <th><?php echo _('Destination'); ?></th>
                    <th><?php echo _('By'); ?></th>
                    <th><?php echo _('Status'); ?></th>
                    <th><?php echo _('Actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($changelog as $c): ?>
                <tr class="<?php echo $c['reverted_at'] ? 'tsisip-changelog-row--reverted' : ''; ?>">
                    <td><?php echo htmlspecialchars($c['changed_at']); ?></td>
                    <td><code><?php echo htmlspecialchars($c['action']); ?></code></td>
                    <td><?php echo (int)$c['setid']; ?></td>
                    <td><?php echo htmlspecialchars($c['destination']); ?></td>
                    <td><?php echo htmlspecialchars($c['changed_by']); ?></td>
                    <td>
                        <?php if ($c['reverted_at']): ?>
                            <span class="tsisip-state-badge tsisip-state-badge--disabled"><?php echo _('Reverted'); ?></span>
                        <?php else: ?>
                            <span class="tsisip-state-badge tsisip-state-badge--active"><?php echo _('Active'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($canEdit && !$c['reverted_at'] && $c['rollback_payload']): ?>
                        <button class="tsisip-btn tsisip-btn--sm tsisip-btn--danger" onclick="openRollback(<?php echo (int)$c['id']; ?>)">
                            <?php echo _('Rollback'); ?>
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($changelog)): ?>
                <tr><td colspan="7" style="text-align:center;">
                    <?php echo _('No changelog entries yet.'); ?>
                </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="tsisip-modal-overlay" id="modal-overlay">
    <div class="tsisip-modal">
        <h2 id="modal-title"><?php echo _('Add Destination'); ?></h2>
        <form id="dispatcher-form" onsubmit="return saveDestination(event)">
            <?php echo csrfInput(); ?>
            <input type="hidden" name="id" id="form-id" value="">
            <div class="tsisip-form-row">
                <div class="tsisip-form-group">
                    <label for="form-setid"><?php echo _('Set ID'); ?></label>
                    <input type="number" name="setid" id="form-setid" min="1" required>
                </div>
                <div class="tsisip-form-group">
                    <label for="form-destination"><?php echo _('Destination URI'); ?></label>
                    <input type="text" name="destination" id="form-destination" placeholder="sip:host:port" required
                           pattern="^sip(s)?:[^\s]+$">
                </div>
            </div>
            <div class="tsisip-form-row">
                <div class="tsisip-form-group">
                    <label for="form-state"><?php echo _('State'); ?></label>
                    <select name="state" id="form-state">
                        <option value="0"><?php echo _('Active (0)'); ?></option>
                        <option value="1"><?php echo _('Inactive (1)'); ?></option>
                        <option value="2"><?php echo _('Probing (2)'); ?></option>
                        <option value="3"><?php echo _('Disabled (3)'); ?></option>
                    </select>
                </div>
                <div class="tsisip-form-group">
                    <label for="form-probe_mode"><?php echo _('Probe Mode'); ?></label>
                    <input type="number" name="probe_mode" id="form-probe_mode" min="0" max="2" value="0">
                </div>
            </div>
            <div class="tsisip-form-row">
                <div class="tsisip-form-group">
                    <label for="form-weight"><?php echo _('Weight'); ?></label>
                    <input type="number" name="weight" id="form-weight" min="0" value="1">
                </div>
                <div class="tsisip-form-group">
                    <label for="form-priority"><?php echo _('Priority'); ?></label>
                    <input type="number" name="priority" id="form-priority" value="0">
                </div>
            </div>
            <div class="tsisip-form-group">
                <label for="form-attrs"><?php echo _('Attributes'); ?></label>
                <input type="text" name="attrs" id="form-attrs" placeholder="rweight=50;cc=1">
            </div>
            <div class="tsisip-form-group">
                <label for="form-description"><?php echo _('Description'); ?></label>
                <input type="text" name="description" id="form-description">
            </div>
            <div style="display:flex;gap:0.5rem;justify-content:flex-end;">
                <button type="button" class="tsisip-btn" onclick="closeModal()"><?php echo _('Cancel'); ?></button>
                <button type="submit" class="tsisip-btn tsisip-btn--primary"><?php echo _('Save'); ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Rollback Confirmation Modal -->
<div class="tsisip-modal-overlay" id="rollback-overlay">
    <div class="tsisip-modal" style="max-width:450px;">
        <h2><?php echo _('Confirm Rollback'); ?></h2>
        <p><?php echo _('This will revert the dispatcher change and restore the previous state. The action will be logged in the changelog.'); ?></p>
        <div id="rollback-details" style="background:#f8f9fa;padding:1rem;border-radius:4px;margin:1rem 0;font-family:monospace;font-size:0.85rem;"></div>
        <div style="display:flex;gap:0.5rem;justify-content:flex-end;">
            <button type="button" class="tsisip-btn" onclick="closeRollback()"><?php echo _('Cancel'); ?></button>
            <button type="button" class="tsisip-btn tsisip-btn--danger" id="btn-confirm-rollback" onclick="executeRollback()">
                <?php echo _('Rollback'); ?>
            </button>
        </div>
    </div>
</div>

<?php
function stateBadge(int $state): string {
    $map = [
        0 => ['active', _('Active')],
        1 => ['inactive', _('Inactive')],
        2 => ['probing', _('Probing')],
        3 => ['disabled', _('Disabled')],
    ];
    [$cls, $label] = $map[$state] ?? ['disabled', _('Unknown')];
    return "<span class=\"tsisip-state-badge tsisip-state-badge--{$cls}\">" . htmlspecialchars($label) . "</span>";
}

function probeBadge(int $probeMode): string {
    $map = [
        0 => ['disabled', '○', _('Disabled')],
        1 => ['active', '●', _('Enabled')],
        2 => ['probing', '◐', _('On-Demand')],
    ];
    [$cls, $icon, $label] = $map[$probeMode] ?? ['disabled', '?', _('Unknown')];
    return "<span class=\"tsisip-state-badge tsisip-state-badge--{$cls}\" title=\"" . htmlspecialchars($label) . "\">{$icon}</span>";
}
?>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
let pendingRollbackId = null;

function toggleSection(id) {
    const body = document.getElementById(id + '-body');
    const toggle = document.querySelector('.tsisip-section-toggle');
    body.classList.toggle('is-open');
    toggle.classList.toggle('is-open');
}

function showMsg(text, isError) {
    const el = document.getElementById('toolbar-msg');
    el.textContent = text;
    el.style.color = isError ? 'var(--tsisip-error,#dc3545)' : 'var(--tsisip-success,#28a745)';
    setTimeout(() => el.textContent = '', 4000);
}

function openModal(mode) {
    document.getElementById('modal-title').textContent = mode === 'edit'
        ? <?php echo json_encode(_('Edit Destination')); ?> : <?php echo json_encode(_('Add Destination')); ?>;
    document.getElementById('modal-overlay').classList.add('is-open');
    if (mode === 'add') {
        document.getElementById('dispatcher-form').reset();
        document.getElementById('form-id').value = '';
    }
}

function openEdit(data) {
    openModal('edit');
    document.getElementById('form-id').value = data.id;
    document.getElementById('form-setid').value = data.setid;
    document.getElementById('form-destination').value = data.destination;
    document.getElementById('form-state').value = data.state;
    document.getElementById('form-probe_mode').value = data.probe_mode;
    document.getElementById('form-weight').value = data.weight;
    document.getElementById('form-priority').value = data.priority;
    document.getElementById('form-attrs').value = data.attrs || '';
    document.getElementById('form-description').value = data.description || '';
}

function closeModal() {
    document.getElementById('modal-overlay').classList.remove('is-open');
}

async function saveDestination(e) {
    e.preventDefault();
    const form = e.target;
    const id = document.getElementById('form-id').value;
    const method = id ? 'PUT' : 'POST';
    const body = new URLSearchParams(new FormData(form));
    if (id) body.append('id', id);

    try {
        const res = await fetch('api/v1/dispatcher-crud.php', {
            method: 'POST',
            headers: { 'X-CSRF-Token': csrfToken, 'X-HTTP-Method-Override': method },
            body: body
        });
        const data = await res.json();
        if (data.success) {
            showMsg(<?php echo json_encode(_('Saved successfully')); ?>);
            closeModal();
            location.reload();
        } else {
            showMsg(data.error || <?php echo json_encode(_('Save failed')); ?>, true);
        }
    } catch (err) {
        showMsg(err.message, true);
    }
    return false;
}

async function doDelete(id) {
    if (!confirm(<?php echo json_encode(_('Delete this destination?')); ?>)) return;
    try {
        const res = await fetch('api/v1/dispatcher-crud.php', {
            method: 'POST',
            headers: { 'X-CSRF-Token': csrfToken, 'X-HTTP-Method-Override': 'DELETE', 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id=' + encodeURIComponent(id)
        });
        const data = await res.json();
        if (data.success) {
            showMsg(<?php echo json_encode(_('Deleted successfully')); ?>);
            location.reload();
        } else {
            showMsg(data.error || <?php echo json_encode(_('Delete failed')); ?>, true);
        }
    } catch (err) {
        showMsg(err.message, true);
    }
}

async function doReload() {
    try {
        const res = await fetch('api/v1/dispatcher-reload.php', {
            method: 'POST',
            headers: { 'X-CSRF-Token': csrfToken }
        });
        const data = await res.json();
        if (data.success) {
            showMsg(<?php echo json_encode(_('MI reload triggered')); ?>);
        } else {
            showMsg(data.error || <?php echo json_encode(_('Reload failed')); ?>, true);
        }
    } catch (err) {
        showMsg(err.message, true);
    }
}

async function doProbe(setid, destination) {
    try {
        const res = await fetch('api/v1/dispatcher-probe.php', {
            method: 'POST',
            headers: { 'X-CSRF-Token': csrfToken, 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'setid=' + encodeURIComponent(setid) + '&destination=' + encodeURIComponent(destination)
        });
        const data = await res.json();
        if (data.success) {
            showMsg(<?php echo json_encode(_('Probe sent')); ?>);
        } else {
            showMsg(data.error || <?php echo json_encode(_('Probe failed')); ?>, true);
        }
    } catch (err) {
        showMsg(err.message, true);
    }
}

async function doImport(e) {
    e.preventDefault();
    const form = document.getElementById('import-form');
    const fd = new FormData(form);
    try {
        const res = await fetch('api/v1/dispatcher-import.php', {
            method: 'POST',
            headers: { 'X-CSRF-Token': csrfToken },
            body: fd
        });
        const data = await res.json();
        if (res.ok && data.imported !== undefined) {
            showMsg('Imported ' + data.imported + ' rows');
            if (data.errors && data.errors.length) console.warn('Import errors:', data.errors);
            location.reload();
        } else {
            showMsg(data.error || <?php echo json_encode(_('Import failed')); ?>, true);
        }
    } catch (err) {
        showMsg(err.message, true);
    }
    return false;
}

function openRollback(changelogId) {
    pendingRollbackId = changelogId;
    document.getElementById('rollback-details').textContent = 'Changelog #' + changelogId;
    document.getElementById('rollback-overlay').classList.add('is-open');
}

function closeRollback() {
    pendingRollbackId = null;
    document.getElementById('rollback-overlay').classList.remove('is-open');
}

async function executeRollback() {
    if (!pendingRollbackId) return;
    try {
        const res = await fetch('api/v1/dispatcher-rollback.php', {
            method: 'POST',
            headers: { 'X-CSRF-Token': csrfToken, 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'changelog_id=' + encodeURIComponent(pendingRollbackId)
        });
        const data = await res.json();
        if (data.success) {
            showMsg(<?php echo json_encode(_('Rollback successful')); ?>);
            closeRollback();
            location.reload();
        } else {
            showMsg(data.error || <?php echo json_encode(_('Rollback failed')); ?>, true);
        }
    } catch (err) {
        showMsg(err.message, true);
    }
}

// Close modals on overlay click
document.getElementById('modal-overlay').addEventListener('click', e => { if (e.target === e.currentTarget) closeModal(); });
document.getElementById('rollback-overlay').addEventListener('click', e => { if (e.target === e.currentTarget) closeRollback(); });
</script>

<?php require_once __DIR__ . '/common/footer.php'; ?>
