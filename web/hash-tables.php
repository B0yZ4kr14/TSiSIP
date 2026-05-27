<?php
/**
 * TSiSIP Control Panel — Hash Tables
 * View and manage OpenSIPS hash tables via MI.
 */
require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';
require_once __DIR__ . '/common/mi-http.php';

requireAuth();
checkPasswordChange();

$pageTitle = _('Hash Tables');

$selectedTable = trim($_GET['table'] ?? '');
$entries = [];
$miData = ['success' => false, 'error' => null, 'data' => null];

try {
    if ($selectedTable !== '') {
        $result = miHttpCall('htable_dump', [$selectedTable]);
        $miData = $result;
        if ($result['success'] && is_array($result['data'])) {
            $raw = $result['data'];
            if (isset($raw['Entries']) && is_array($raw['Entries'])) {
                $entries = $raw['Entries'];
            } elseif (isset($raw['entries']) && is_array($raw['entries'])) {
                $entries = $raw['entries'];
            } elseif (isset($raw[0]) && is_array($raw[0])) {
                $entries = $raw;
            } else {
                foreach ($raw as $key => $val) {
                    if (is_array($val)) {
                        $entries[] = $val;
                    } elseif (!is_array($val)) {
                        $entries[] = ['key' => $key, 'value' => $val];
                    }
                }
            }
        }
    }
} catch (Exception $e) {
    $miData['error'] = $e->getMessage();
}

require_once __DIR__ . '/common/header.php';
?>
<div class="tsisip-page">
    <div class="tsisip-page-header">
        <h1 class="tsisip-page-title"><?php echo $pageTitle; ?></h1>
        <div class="tsisip-actions">
            <button type="button" class="tsisip-btn tsisip-btn-secondary" onclick="TSiSIPMi.exportData('htable_dump', [], 'csv')"><?php echo _('Export CSV'); ?></button>
            <button type="button" class="tsisip-btn tsisip-btn-secondary" onclick="TSiSIPMi.exportData('htable_dump', [], 'json')"><?php echo _('Export JSON'); ?></button>
        </div>
    </div>

    <section class="tsisip-section">
        <form method="get" class="tsisip-filter-bar">
            <select name="table" class="tsisip-select" onchange="document.getElementById('htable-custom').value=this.value">
                <option value=""><?php echo _('Select hash table...'); ?></option>
                <option value="dlg" <?php echo $selectedTable === 'dlg' ? 'selected' : ''; ?>>dlg</option>
                <option value="dispatcher" <?php echo $selectedTable === 'dispatcher' ? 'selected' : ''; ?>>dispatcher</option>
                <option value="rtpengine" <?php echo $selectedTable === 'rtpengine' ? 'selected' : ''; ?>>rtpengine</option>
                <option value="trunks" <?php echo $selectedTable === 'trunks' ? 'selected' : ''; ?>>trunks</option>
            </select>
            <input type="text" name="table" id="htable-custom" class="tsisip-input" placeholder="<?php echo _('Or type table name...'); ?>"
                   value="<?php echo htmlspecialchars($selectedTable); ?>" style="min-width:200px;">
            <button type="submit" class="tsisip-btn tsisip-btn--primary"><?php echo _('Load'); ?></button>
        </form>
    </section>

    <?php if ($selectedTable !== ''): ?>
        <?php if (!$miData["success"]): ?>
        <?php echo miErrorBanner($miData["error"] ?? _("Unknown")); ?>
        <?php else: ?>
            <section class="tsisip-section">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
                    <h2 class="tsisip-section-title"><?php echo htmlspecialchars($selectedTable); ?></h2>
                    <?php if (isDevOpsOrHigher()): ?>
                        <button type="button" id="btn-htable-flush" class="tsisip-btn tsisip-btn--danger"
                                data-table="<?php echo htmlspecialchars($selectedTable, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo _('Flush Table'); ?>
                        </button>
                    <?php endif; ?>
                </div>
                <input type="text" id="htable-filter" class="tsisip-input" placeholder="<?php echo _('Filter entries...'); ?>"
                       style="margin-bottom:12px;max-width:300px;">
                <div style="overflow-x:auto;">
                    <table class="tsisip-table dataTable" data-tsisip-sortable id="htable-entries">
                        <thead>
                            <tr>
                                <th><?php echo _('Key'); ?></th>
                                <th><?php echo _('Value'); ?></th>
                                <th><?php echo _('Type'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($entries)): ?>
                                <tr>
                                    <td colspan="3" class="tsisip-empty"><?php echo _('No entries found.'); ?></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($entries as $e): ?>
                                    <?php if (!is_array($e)) continue; ?>
                                    <tr>
                                        <td><code><?php echo htmlspecialchars($e['key'] ?? $e['Key'] ?? 'N/A'); ?></code></td>
                                        <td><code><?php echo htmlspecialchars((string) ($e['value'] ?? $e['Value'] ?? '—')); ?></code></td>
                                        <td><?php echo htmlspecialchars($e['type'] ?? $e['Type'] ?? '—'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
(function() {
    'use strict';
    // Filter entries
    const filterInput = document.getElementById('htable-filter');
    const table = document.getElementById('htable-entries');
    if (filterInput && table) {
        filterInput.addEventListener('input', function() {
            const term = this.value.toLowerCase();
            table.querySelectorAll('tbody tr').forEach(function(row) {
                const text = row.textContent.toLowerCase();
                row.style.display = text.indexOf(term) !== -1 ? '' : 'none';
            });
        });
    }

    <?php if (isDevOpsOrHigher()): ?>
    const flushBtn = document.getElementById('btn-htable-flush');
    if (flushBtn) {
        flushBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const tableName = flushBtn.dataset.table;
            if (!tableName) return;
            if (!confirm(<?php echo json_encode(_('Flush all entries in this table?')); ?>)) return;
            flushBtn.disabled = true;
            TSiSIPMi.action('htable_flush', [tableName], function() {
                flushBtn.disabled = false;
                setTimeout(function() { location.reload(); }, 600);
            }, function() {
                flushBtn.disabled = false;
            });
        });
    }
    <?php endif; ?>
})();
</script>

<?php require_once __DIR__ . '/common/footer.php'; ?>
