<?php
/**
 * TSiSIP Control Panel — Memory Status
 * Real-time OpenSIPS pkg and shm memory usage with SSE auto-refresh.
 */
require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';
require_once __DIR__ . '/common/mi-http.php';

requireAuth();
checkPasswordChange();

$pageTitle = _('Memory Status');

/**
 * Parse memory statistics into normalized structure.
 */
function parseMemStats(?array $data): array
{
    $stats = [];
    if (is_array($data)) {
        if (isset($data[0]) && is_array($data[0])) {
            foreach ($data as $item) {
                if (is_array($item) && count($item) >= 2) {
                    $stats[$item[0]] = $item[1];
                } elseif (is_array($item) && isset($item['name'])) {
                    $stats[$item['name']] = $item['value'] ?? null;
                }
            }
        } else {
            $stats = $data;
        }
    }

    $used  = 0;
    $free  = 0;
    $total = 0;
    foreach ($stats as $k => $v) {
        $key = (string) $k;
        if (stripos($key, 'used') !== false) {
            $used = (int) $v;
        }
        if (stripos($key, 'free') !== false || stripos($key, 'avail') !== false) {
            $free = (int) $v;
        }
        if (stripos($key, 'total') !== false || stripos($key, 'max') !== false || stripos($key, 'size') !== false) {
            $total = (int) $v;
        }
    }
    if ($total === 0 && ($used + $free) > 0) {
        $total = $used + $free;
    }
    $pct = $total > 0 ? round(($used / $total) * 100, 1) : 0;

    return compact('used', 'free', 'total', 'pct');
}

// SSE endpoint
if (!empty($_GET['sse']) && $_GET['sse'] === '1') {
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    header('X-Accel-Buffering: no');
    set_time_limit(0);
    ob_implicit_flush(true);

    $token = $_GET['token'] ?? '';
    if (!validateCsrfToken($token)) {
        echo "event: error\ndata: " . _('Invalid token') . "\n\n";
        exit;
    }

    while (true) {
        $pkgResult = miHttpCall('get_statistics', ['pkg:']);
        $shmResult = miHttpCall('get_statistics', ['shm:']);
        $data = [
            'pkg' => parseMemStats($pkgResult['data'] ?? []),
            'shm' => parseMemStats($shmResult['data'] ?? []),
        ];
        echo "data: " . json_encode($data) . "\n\n";
        if (connection_aborted()) {
            break;
        }
        sleep(5);
    }
    exit;
}

// AJAX endpoint
if (!empty($_GET['ajax']) && $_GET['ajax'] === '1') {
    header('Content-Type: application/json');
    $pkgResult = miHttpCall('get_statistics', ['pkg:']);
    $shmResult = miHttpCall('get_statistics', ['shm:']);
    echo json_encode([
        'pkg' => parseMemStats($pkgResult['data'] ?? []),
        'shm' => parseMemStats($shmResult['data'] ?? []),
    ]);
    exit;
}

// Page-load data fetch
$miData = ['success' => false, 'error' => null, 'data' => null];
$pkgData = ['used' => 0, 'free' => 0, 'total' => 0, 'pct' => 0];
$shmData = ['used' => 0, 'free' => 0, 'total' => 0, 'pct' => 0];

try {
    $pkgResult = miHttpCall('get_statistics', ['pkg:']);
    $shmResult = miHttpCall('get_statistics', ['shm:']);

    if ($pkgResult['success']) {
        $pkgData = parseMemStats($pkgResult['data']);
    }
    if ($shmResult['success']) {
        $shmData = parseMemStats($shmResult['data']);
    }

    $miData['success'] = $pkgResult['success'] || $shmResult['success'];
    if (!$miData['success']) {
        $miData['error'] = $pkgResult['error'] ?? $shmResult['error'] ?? _('Unknown');
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
            <button type="button" class="tsisip-btn tsisip-btn-secondary" onclick="TSiSIPMi.exportData('get_statistics', [], 'csv')"><?php echo _('Export CSV'); ?></button>
            <button type="button" class="tsisip-btn tsisip-btn-secondary" onclick="TSiSIPMi.exportData('get_statistics', [], 'json')"><?php echo _('Export JSON'); ?></button>
        </div>
    </div>

    <?php if (!$miData["success"]): ?>
        <?php echo miErrorBanner($miData["error"] ?? _("Unknown")); ?>
    <?php else: ?>
        <?php if ($pkgData['pct'] > 80 || $shmData['pct'] > 80): ?>
            <div class="tsisip-alert tsisip-alert--error" role="alert">
                <?php echo _('Memory usage is above 80%. Consider investigating.'); ?>
            </div>
        <?php endif; ?>

        <section class="tsisip-section">
            <h2 class="tsisip-section-title"><?php echo _('Package Memory (pkg)'); ?></h2>
            <div style="background:var(--tsisip-border-subtle);border-radius:8px;height:28px;overflow:hidden;">
                <div id="pkg-bar" style="width:<?php echo $pkgData['pct']; ?>%;background:<?php echo $pkgData['pct'] > 80 ? 'var(--tsisip-danger)' : ($pkgData['pct'] > 50 ? 'var(--tsisip-warning)' : 'var(--tsisip-success)'); ?>;height:100%;transition:width 0.5s ease;"></div>
            </div>
            <p style="margin-top:8px;color:var(--tsisip-text-secondary);">
                <?php echo sprintf(_('Used: %s / Total: %s (%s%%)'), number_format($pkgData['used']), number_format($pkgData['total']), $pkgData['pct']); ?>
            </p>
        </section>

        <section class="tsisip-section">
            <h2 class="tsisip-section-title"><?php echo _('Shared Memory (shm)'); ?></h2>
            <div style="background:var(--tsisip-border-subtle);border-radius:8px;height:28px;overflow:hidden;">
                <div id="shm-bar" style="width:<?php echo $shmData['pct']; ?>%;background:<?php echo $shmData['pct'] > 80 ? 'var(--tsisip-danger)' : ($shmData['pct'] > 50 ? 'var(--tsisip-warning)' : 'var(--tsisip-success)'); ?>;height:100%;transition:width 0.5s ease;"></div>
            </div>
            <p style="margin-top:8px;color:var(--tsisip-text-secondary);">
                <?php echo sprintf(_('Used: %s / Total: %s (%s%%)'), number_format($shmData['used']), number_format($shmData['total']), $shmData['pct']); ?>
            </p>
        </section>
    <?php endif; ?>
</div>

<script>
(function() {
    'use strict';
    const tokenMeta = document.querySelector('meta[name="csrf-token"]');
    const token = tokenMeta ? tokenMeta.content : '';
    if (window.EventSource && token) {
        const evtSource = new EventSource('memory-status.php?sse=1&token=' + encodeURIComponent(token));
        evtSource.onmessage = function(e) {
            try {
                const data = JSON.parse(e.data);
                updateBar('pkg-bar', data.pkg);
                updateBar('shm-bar', data.shm);
            } catch (err) {
                console.error('SSE parse error:', err);
            }
        };
        evtSource.onerror = function() {
            console.warn('SSE connection error');
        };
    }

    function updateBar(id, mem) {
        const bar = document.getElementById(id);
        if (!bar || !mem) return;
        bar.style.width = mem.pct + '%';
        bar.style.background = mem.pct > 80 ? 'var(--tsisip-danger)' : (mem.pct > 50 ? 'var(--tsisip-warning)' : 'var(--tsisip-success)');
        const section = bar.closest('.tsisip-section');
        if (section) {
            const text = section.querySelector('p');
            if (text) {
                text.textContent = text.textContent.replace(/[\d,]+\s*\/\s*[\d,]+\s*\([\d.]+\%\)/,
                    mem.used.toLocaleString() + ' / ' + mem.total.toLocaleString() + ' (' + mem.pct + '%)');
            }
        }
    }
})();
</script>

<?php require_once __DIR__ . '/common/footer.php'; ?>
