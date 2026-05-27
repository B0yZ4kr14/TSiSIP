<?php
/**
 * TSiSIP Control Panel — Visual Topology View
 * SVG-based network topology visualization of the TSiSIP stack.
 */
require_once __DIR__ . '/common/config.php';
require_once __DIR__ . '/common/csrf.php';
require_once __DIR__ . '/common/mi-http.php';

requireAuth();
checkPasswordChange();
requireRole('devops');

logAuditEvent('CONFIG_VIEW', 'system', 'topology', true);

// --- Fetch component status ---
$components = [
    ['id' => 'internet',      'label' => _('Internet / SIP Clients'), 'type' => 'external', 'status' => 'unknown'],
    ['id' => 'opensips',      'label' => _('OpenSIPS 3.6 LTS'),       'type' => 'proxy',    'status' => 'unknown'],
    ['id' => 'rtpengine',     'label' => _('RTPengine'),              'type' => 'media',    'status' => 'unknown'],
    ['id' => 'asterisk',      'label' => _('Asterisk PBX'),           'type' => 'pbx',      'status' => 'unknown'],
    ['id' => 'postgresql',    'label' => _('PostgreSQL'),             'type' => 'db',       'status' => 'unknown'],
    ['id' => 'ocp',           'label' => _('OCP Web'),                'type' => 'web',      'status' => 'unknown'],
];

// Probe OpenSIPS via MI
$miHealth = miHttpCall('get_statistics', ['core:rcv_requests']);
if ($miHealth['success']) {
    foreach ($components as &$c) {
        if ($c['id'] === 'opensips') {
            $c['status'] = 'online';
        }
    }
    unset($c);
}

// Probe RTPengine via MI (if available)
$rtpHealth = miHttpCall('rtpengine_show');
if ($rtpHealth['success']) {
    foreach ($components as &$c) {
        if ($c['id'] === 'rtpengine') {
            $c['status'] = 'online';
        }
    }
    unset($c);
}

// Mark internal components as online (best effort in container env)
foreach ($components as &$c) {
    if (in_array($c['id'], ['asterisk', 'postgresql', 'ocp'], true)) {
        $c['status'] = 'online';
    }
}
unset($c);

$statusColors = [
    'online'   => '#22c55e',
    'offline'  => '#ef4444',
    'unknown'  => '#6b7280',
    'warning'  => '#f59e0b',
];

require_once __DIR__ . '/common/header.php';
?>
<div id="content" class="tsisip-dashboard">
    <h1><?php echo _('Network Topology'); ?></h1>

    <div class="tsisip-dashboard-section">
        <p class="tsisip-text-muted">
            <?php echo _('Visual representation of the TSiSIP SIP edge proxy topology.'); ?>
        </p>
    </div>

    <div class="tsisip-dashboard-section" style="background:var(--tsisip-surface-card);border:1px solid var(--tsisip-border-subtle);border-radius:8px;padding:24px;overflow:auto;">
        <svg viewBox="0 0 800 500" style="width:100%;max-width:800px;height:auto;display:block;margin:0 auto;" xmlns="http://www.w3.org/2000/svg">
            <!-- Background grid -->
            <defs>
                <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
                    <path d="M 40 0 L 0 0 0 40" fill="none" stroke="#e5e7eb" stroke-width="0.5"/>
                </pattern>
            </defs>
            <rect width="800" height="500" fill="url(#grid)" />

            <!-- Title -->
            <text x="400" y="30" text-anchor="middle" font-size="18" font-weight="bold" fill="#1f2937">TSiSIP Network Topology</text>

            <!-- Internet -->
            <rect x="340" y="60" width="120" height="50" rx="8" fill="#dbeafe" stroke="#3b82f6" stroke-width="2"/>
            <text x="400" y="90" text-anchor="middle" font-size="12" fill="#1e40af"><?php echo _('Internet / SIP Clients'); ?></text>

            <!-- OpenSIPS -->
            <rect x="340" y="160" width="120" height="50" rx="8" fill="#dcfce7" stroke="#22c55e" stroke-width="2"/>
            <text x="400" y="190" text-anchor="middle" font-size="12" fill="#166534">OpenSIPS 3.6 LTS</text>

            <!-- RTPengine -->
            <rect x="560" y="160" width="120" height="50" rx="8" fill="#fef3c7" stroke="#f59e0b" stroke-width="2"/>
            <text x="620" y="190" text-anchor="middle" font-size="12" fill="#92400e">RTPengine</text>

            <!-- PostgreSQL -->
            <rect x="120" y="280" width="120" height="50" rx="8" fill="#fce7f3" stroke="#ec4899" stroke-width="2"/>
            <text x="180" y="310" text-anchor="middle" font-size="12" fill="#9d174d">PostgreSQL</text>

            <!-- Asterisk -->
            <rect x="340" y="280" width="120" height="50" rx="8" fill="#e0e7ff" stroke="#6366f1" stroke-width="2"/>
            <text x="400" y="310" text-anchor="middle" font-size="12" fill="#3730a3">Asterisk PBX</text>

            <!-- OCP -->
            <rect x="560" y="280" width="120" height="50" rx="8" fill="#f3e8ff" stroke="#a855f7" stroke-width="2"/>
            <text x="620" y="310" text-anchor="middle" font-size="12" fill="#6b21a8">OCP Web</text>

            <!-- Connections -->
            <!-- Internet -> OpenSIPS -->
            <line x1="400" y1="110" x2="400" y2="160" stroke="#6b7280" stroke-width="2" marker-end="url(#arrow)"/>
            <text x="410" y="138" font-size="10" fill="#6b7280">5060/udp</text>

            <!-- Internet -> RTPengine -->
            <line x1="460" y1="85" x2="560" y2="160" stroke="#6b7280" stroke-width="2" stroke-dasharray="4"/>
            <text x="530" y="115" font-size="10" fill="#6b7280">10k-20k/udp</text>

            <!-- OpenSIPS -> PostgreSQL -->
            <line x1="340" y1="185" x2="240" y2="280" stroke="#6b7280" stroke-width="2"/>
            <text x="260" y="245" font-size="10" fill="#6b7280">5432/tcp</text>

            <!-- OpenSIPS -> Asterisk -->
            <line x1="400" y1="210" x2="400" y2="280" stroke="#6b7280" stroke-width="2"/>
            <text x="410" y="250" font-size="10" fill="#6b7280">sip_internal</text>

            <!-- OpenSIPS -> RTPengine -->
            <line x1="460" y1="185" x2="560" y2="185" stroke="#6b7280" stroke-width="2"/>
            <text x="490" y="178" font-size="10" fill="#6b7280">22222/udp</text>

            <!-- OCP -> PostgreSQL -->
            <line x1="560" y1="305" x2="240" y2="305" stroke="#6b7280" stroke-width="2" stroke-dasharray="4"/>
            <text x="380" y="298" font-size="10" fill="#6b7280">db_internal</text>

            <!-- Arrow marker -->
            <defs>
                <marker id="arrow" markerWidth="10" markerHeight="10" refX="9" refY="3" orient="auto" markerUnits="strokeWidth">
                    <path d="M0,0 L0,6 L9,3 z" fill="#6b7280"/>
                </marker>
            </defs>

            <!-- Legend -->
            <rect x="20" y="420" width="760" height="60" rx="6" fill="#f9fafb" stroke="#e5e7eb"/>
            <text x="40" y="445" font-size="11" font-weight="bold" fill="#374151"><?php echo _('Legend'); ?>:</text>
            <circle cx="110" cy="440" r="6" fill="#22c55e"/><text x="122" y="445" font-size="10" fill="#374151"><?php echo _('Online'); ?></text>
            <circle cx="200" cy="440" r="6" fill="#ef4444"/><text x="212" y="445" font-size="10" fill="#374151"><?php echo _('Offline'); ?></text>
            <circle cx="290" cy="440" r="6" fill="#6b7280"/><text x="302" y="445" font-size="10" fill="#374151"><?php echo _('Unknown'); ?></text>
            <line x1="400" y1="440" x2="430" y2="440" stroke="#6b7280" stroke-width="2"/><text x="438" y="445" font-size="10" fill="#374151"><?php echo _('SIP Signaling'); ?></text>
            <line x1="540" y1="440" x2="570" y2="440" stroke="#6b7280" stroke-width="2" stroke-dasharray="4"/><text x="578" y="445" font-size="10" fill="#374151"><?php echo _('Media / Other'); ?></text>
        </svg>
    </div>
</div>
<?php require_once __DIR__ . '/common/footer.php'; ?>
