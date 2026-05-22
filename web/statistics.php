<?php
/**
 * TSiSIP Control Panel — OpenSIPS Statistics Monitor
 * Real-time metrics via OpenSIPS MI HTTP interface with D3.js visualization.
 */
require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';

requireAuth();
checkPasswordChange();
requireRole('devops');

logAuditEvent('CONFIG_VIEW', 'system', 'statistics', true);

$miUrl = 'http://opensips:8888/mi';

/**
 * Fetch all statistics from the OpenSIPS MI HTTP endpoint.
 */
function fetchOpenSIPSStats(string $miUrl): ?array
{
    $payload = json_encode([
        'jsonrpc' => '2.0',
        'method'  => 'get_statistics',
        'params'  => ['all'],
        'id'      => 1,
    ]);

    $opts = [
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\n",
            'content' => $payload,
            'timeout' => 5,
        ],
    ];

    $ctx  = stream_context_create($opts);
    $resp = @file_get_contents($miUrl, false, $ctx);

    if ($resp === false) {
        error_log('TSiSIP statistics: OpenSIPS MI unreachable at ' . $miUrl);
        return null;
    }

    $data = json_decode($resp, true);
    if (json_last_error() !== JSON_ERROR_NONE || empty($data['result'])) {
        error_log('TSiSIP statistics: Invalid JSON from OpenSIPS MI');
        return null;
    }

    $result = $data['result'];

    // Normalize possible array-of-pairs response into associative array
    $stats = [];
    if (is_array($result)) {
        if (isset($result[0]) && is_array($result[0])) {
            foreach ($result as $item) {
                if (is_array($item) && count($item) >= 2) {
                    $stats[$item[0]] = $item[1];
                } elseif (is_array($item) && isset($item['name'])) {
                    $stats[$item['name']] = $item['value'] ?? null;
                }
            }
        } else {
            $stats = $result;
        }
    }

    return $stats;
}

/**
 * Extract a numeric metric from the statistics array.
 */
function extractMetric(array $stats, string $key): ?int
{
    if (isset($stats[$key]) && is_numeric($stats[$key])) {
        return (int) $stats[$key];
    }
    return null;
}

/**
 * Extract a metric with fallback keys.
 */
function extractMetricFallback(array $stats, array $keys): ?int
{
    foreach ($keys as $key) {
        $val = extractMetric($stats, $key);
        if ($val !== null) {
            return $val;
        }
    }
    return null;
}

// -- AJAX endpoint --------------------------------------------------
if (!empty($_GET['ajax']) && $_GET['ajax'] === '1') {
    header('Content-Type: application/json');

    $stats = fetchOpenSIPSStats($miUrl);
    if ($stats === null) {
        http_response_code(503);
        echo json_encode(['error' => _('OpenSIPS MI endpoint is unreachable.')]);
        exit;
    }

    $metrics = [
        'active_dialogs'   => extractMetric($stats, 'dialog:active_dialogs'),
        'registered_users' => extractMetric($stats, 'usrloc:registered_users'),
        'transactions'     => extractMetric($stats, 'tm:processed_requests'),
        'replies'          => extractMetricFallback($stats, ['sl:2xx_replies', 'tm:received_replies']),
        'requests'         => extractMetric($stats, 'core:rcv_requests'),
        'dispatcher_sets'  => extractMetric($stats, 'dispatcher:sets'),
    ];

    echo json_encode($metrics);
    exit;
}

// -- Page-load data fetch -------------------------------------------
$stats = fetchOpenSIPSStats($miUrl);

$metrics = [
    'active_dialogs'   => null,
    'registered_users' => null,
    'transactions'     => null,
    'replies'          => null,
    'requests'         => null,
    'dispatcher_sets'  => null,
];

if ($stats !== null) {
    $metrics['active_dialogs']   = extractMetric($stats, 'dialog:active_dialogs');
    $metrics['registered_users'] = extractMetric($stats, 'usrloc:registered_users');
    $metrics['transactions']     = extractMetric($stats, 'tm:processed_requests');
    $metrics['replies']          = extractMetricFallback($stats, ['sl:2xx_replies', 'tm:received_replies']);
    $metrics['requests']         = extractMetric($stats, 'core:rcv_requests');
    $metrics['dispatcher_sets']  = extractMetric($stats, 'dispatcher:sets');
}

$metricLabels = [
    'active_dialogs'   => _('Active Dialogs'),
    'registered_users' => _('Registered Users'),
    'transactions'     => _('Processed Transactions'),
    'replies'          => _('2xx Replies'),
    'requests'         => _('Received Requests'),
    'dispatcher_sets'  => _('Dispatcher Sets'),
];

require_once __DIR__ . '/common/header.php';
?>

<!-- D3.js v7 (global) for inline chart rendering -->
<script src="https://cdn.jsdelivr.net/npm/d3@7/dist/d3.min.js"></script>

<div id="content" class="tsisip-dashboard">
    <h1><?php echo _('OpenSIPS Statistics Monitor'); ?></h1>

    <div class="tsisip-dashboard-section">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
            <h2><?php echo _('Real-time Metrics'); ?></h2>
            <span id="last-updated" class="tsisip-badge tsisip-badge-info">
                <?php echo _('Loading…'); ?>
            </span>
        </div>

        <div id="metrics-cards"
             style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:16px;margin-top:16px;">
            <?php foreach ($metrics as $key => $value): ?>
                <div class="tsisip-metric-card"
                     data-metric="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>"
                     style="background:var(--tsisip-surface-card);border:1px solid var(--tsisip-border-subtle);border-radius:8px;padding:16px;text-align:center;">
                    <div class="tsisip-metric-label"
                         style="font-size:var(--tsisip-text-sm);color:var(--tsisip-text-secondary);margin-bottom:8px;">
                        <?php echo htmlspecialchars($metricLabels[$key], ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                    <div class="tsisip-metric-value"
                         style="font-size:1.75rem;font-weight:700;color:var(--tsisip-primary-blue);">
                        <?php echo $value !== null ? htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') : '—'; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="tsisip-dashboard-section">
        <h2><?php echo _('Metrics Overview'); ?></h2>
        <div id="statistics-chart" style="width:100%;height:320px;"></div>
    </div>
</div>

<script>
(function () {
    'use strict';

    const metricLabels = {
        active_dialogs:   <?php echo json_encode(_('Active Dialogs')); ?>,
        registered_users: <?php echo json_encode(_('Registered Users')); ?>,
        transactions:     <?php echo json_encode(_('Processed Transactions')); ?>,
        replies:          <?php echo json_encode(_('2xx Replies')); ?>,
        requests:         <?php echo json_encode(_('Received Requests')); ?>,
        dispatcher_sets:  <?php echo json_encode(_('Dispatcher Sets')); ?>,
    };

    const palette = {
        primary:       getComputedStyle(document.documentElement).getPropertyValue('--tsisip-primary-blue').trim()       || '#1A3A5C',
        text:          getComputedStyle(document.documentElement).getPropertyValue('--tsisip-text-primary').trim()       || '#0A1628',
        textSecondary: getComputedStyle(document.documentElement).getPropertyValue('--tsisip-text-secondary').trim()    || '#6B7B8D',
        surface:       getComputedStyle(document.documentElement).getPropertyValue('--tsisip-surface-card').trim()      || '#FFFFFF',
    };

    function updateCards(data) {
        Object.keys(data).forEach(function (key) {
            const card = document.querySelector('.tsisip-metric-card[data-metric="' + key + '"]');
            if (!card) return;
            const valEl = card.querySelector('.tsisip-metric-value');
            const value = data[key];
            valEl.textContent = (value !== null && value !== undefined) ? value : '—';
        });
    }

    function updateChart(data) {
        const container = document.getElementById('statistics-chart');
        if (!container || typeof d3 === 'undefined') return;

        const chartData = Object.keys(data).map(function (key) {
            return {
                label: metricLabels[key] || key,
                value: (data[key] !== null && data[key] !== undefined) ? +data[key] : 0,
            };
        }).filter(function (d) { return d.value >= 0; });

        container.innerHTML = '';

        const width  = container.clientWidth || 600;
        const height = 320;
        const margin = { top: 20, right: 20, bottom: 60, left: 60 };
        const innerW = width  - margin.left - margin.right;
        const innerH = height - margin.top  - margin.bottom;

        const svg = d3.select(container)
            .append('svg')
            .attr('viewBox', '0 0 ' + width + ' ' + height)
            .attr('preserveAspectRatio', 'xMidYMid meet')
            .style('width', '100%')
            .style('height', 'auto')
            .style('overflow', 'visible');

        const g = svg.append('g')
            .attr('transform', 'translate(' + margin.left + ',' + margin.top + ')');

        const x = d3.scaleBand()
            .domain(chartData.map(function (d) { return d.label; }))
            .range([0, innerW])
            .padding(0.3);

        const maxVal = d3.max(chartData, function (d) { return d.value; }) || 1;
        const y = d3.scaleLinear()
            .domain([0, maxVal * 1.1])
            .nice()
            .range([innerH, 0]);

        g.selectAll('.bar')
            .data(chartData)
            .enter()
            .append('rect')
            .attr('class', 'bar')
            .attr('x', function (d) { return x(d.label); })
            .attr('y', function (d) { return y(d.value); })
            .attr('width', x.bandwidth())
            .attr('height', function (d) { return innerH - y(d.value); })
            .attr('fill', palette.primary)
            .attr('rx', 4);

        g.append('g')
            .attr('transform', 'translate(0,' + innerH + ')')
            .call(d3.axisBottom(x))
            .selectAll('text')
            .style('fill', palette.textSecondary)
            .style('font-size', '12px')
            .attr('transform', 'rotate(-25)')
            .style('text-anchor', 'end');

        g.append('g')
            .call(d3.axisLeft(y).ticks(5))
            .selectAll('text')
            .style('fill', palette.textSecondary)
            .style('font-size', '12px');

        g.selectAll('.domain, .tick line')
            .style('stroke', palette.textSecondary)
            .style('opacity', 0.3);

        // Tooltip
        const tooltip = d3.select(container)
            .append('div')
            .style('position', 'absolute')
            .style('visibility', 'hidden')
            .style('background', palette.surface)
            .style('color', palette.text)
            .style('padding', '8px 12px')
            .style('border-radius', '6px')
            .style('border', '1px solid ' + palette.textSecondary)
            .style('font-size', '12px')
            .style('pointer-events', 'none')
            .style('box-shadow', '0 4px 12px rgba(0,0,0,0.1)');

        g.selectAll('.bar')
            .on('mouseover', function (event, d) {
                tooltip.style('visibility', 'visible')
                    .html('<strong>' + d.label + '</strong><br>' + d.value);
            })
            .on('mousemove', function (event) {
                tooltip
                    .style('top', (event.pageY - 10) + 'px')
                    .style('left', (event.pageX + 10) + 'px');
            })
            .on('mouseout', function () {
                tooltip.style('visibility', 'hidden');
            });
    }

    async function refreshStats() {
        try {
            const resp = await fetch('statistics.php?ajax=1');
            if (!resp.ok) throw new Error('HTTP ' + resp.status);
            const data = await resp.json();
            if (data.error) throw new Error(data.error);

            updateCards(data);
            updateChart(data);

            const now = new Date();
            document.getElementById('last-updated').textContent =
                <?php echo json_encode(_('Updated: ')); ?> + now.toLocaleTimeString();
        } catch (e) {
            console.error('TSiSIP statistics refresh failed:', e);
            document.getElementById('last-updated').textContent =
                <?php echo json_encode(_('Update failed')); ?>;
        }
    }

    // Initial render with server-side data
    const initialData = {
        active_dialogs:   <?php echo json_encode($metrics['active_dialogs']); ?>,
        registered_users: <?php echo json_encode($metrics['registered_users']); ?>,
        transactions:     <?php echo json_encode($metrics['transactions']); ?>,
        replies:          <?php echo json_encode($metrics['replies']); ?>,
        requests:         <?php echo json_encode($metrics['requests']); ?>,
        dispatcher_sets:  <?php echo json_encode($metrics['dispatcher_sets']); ?>,
    };

    updateChart(initialData);

    <?php if ($stats !== null): ?>
        document.getElementById('last-updated').textContent =
            <?php echo json_encode(_('Updated: ')); ?> + new Date().toLocaleTimeString();
    <?php else: ?>
        document.getElementById('last-updated').textContent =
            <?php echo json_encode(_('OpenSIPS MI unreachable')); ?>;
    <?php endif; ?>

    // Auto-refresh every 30 seconds
    setInterval(refreshStats, 30000);
})();
</script>

<?php require_once __DIR__ . '/common/footer.php'; ?>
