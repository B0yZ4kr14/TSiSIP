# Blueprint: OCP Navigation System Links

**Branch**: `master` | **Date**: 2026-05-19
**Mode**: doc-only
**Total Tasks**: 8 | **Files**: 0 new, 3 modified, 0 deleted

## Key Decisions

- Role-gated system links (admin/devops only) prevent UI clutter for non-technical users → T1.1
- Sidebar sections visually separated by headings (System vs Wiki) → T2.1
- Status indicators added for operational visibility → T1.2
- No backend changes; pure frontend PHP/CSS → All tasks

## Implementation Order

```
T1.1 (dashboard system section)
  ├── T1.2 (status indicators)
  ├── T2.1 (sidebar system section) [P] T2.2 (active-state)
  └── T3.1 (nav heading CSS) [P] T3.2 (status dot CSS)
      └── T4.1 (PHP syntax validation)
          ├── T4.2 (local Docker test)
          └── T4.3 (VPS deploy)
              └── T4.4 (docs update)
```

## Phase 1 — Dashboard Restructure

### T1.1: Add System Management section

**Type**: Modified file (`web/dashboard.php`)

Replace single `$quickLinks` array with two arrays:

```php
$systemLinks = [];
if ($userRole === 'admin' || $userRole === 'devops') {
    $systemLinks = [
        ['url' => 'dispatcher.php', 'label' => _('Dispatcher Targets')],
        ['url' => 'rtpengine.php',  'label' => _('RTPengine Sessions')],
    ];
}

$wikiLinks = [];
if (isset($roleNav[$userRole])) {
    foreach ($roleNav[$userRole] as $page) { ... }
}
```

Render in separate `<div class="tsisip-dashboard-section">` blocks.

### T1.2: Add System Status indicators

**Type**: Modified file (`web/dashboard.php`)

Add status list:
```html
<ul class="tsisip-status-list">
    <li><span class="tsisip-status-dot tsisip-status-dot--ok"></span> OpenSIPS SIP Proxy</li>
    ...
</ul>
```

## Phase 2 — Sidebar Restructure

### T2.1: Add System section

**Type**: Modified file (`web/common/role-nav.php`)

Add conditional `$systemPages` array and render before wiki links:
```php
$systemPages = [];
if ($userRole === 'admin' || $userRole === 'devops') {
    $systemPages = [
        'dispatcher' => _('Dispatcher Targets'),
        'rtpengine'  => _('RTPengine Sessions'),
    ];
}
```

Render with `<li class="tsisip-nav-heading">` separator.

### T2.2: Fix active-state

**Type**: Modified file (`web/common/role-nav.php`)

Use `$currentPage === $sysPage` for system pages, independent of `$currentWikiPage`.

## Phase 3 — Styling

### T3.1 + T3.2: CSS additions

**Type**: Modified file (`web/tsisip/css/tsisip-theme.css`)

Append:
```css
.tsisip-nav-heading { ... }
.tsisip-status-dot { ... }
.tsisip-status-dot--ok { background-color: var(--tsisip-accent-success); }
```

## Phase 4 — Validation

### T4.1-T4.4: Standard validation pipeline

- `php -l` on modified files
- `docker build -t tsisip/ocp:test`
- Local curl validation
- GHCR push + VPS deploy
- Docs update

## Pre-completed Tasks

None.

## Checklist

- [x] T1.1: Dashboard System Management section
- [x] T1.2: System Status indicators
- [x] T2.1: Sidebar System section
- [x] T2.2: Active-state highlighting
- [x] T3.1: Nav heading CSS
- [x] T3.2: Status dot CSS
- [x] T4.1: PHP syntax validation
- [x] T4.2: Local Docker validation
- [x] T4.3: VPS deploy and verification
- [x] T4.4: Canonical docs updated

## Validation

```bash
# Local
docker compose up -d --build ocp
docker compose exec ocp sh -c 'curl ... | grep "Dispatcher Targets"'

# VPS (via Tailscale or public IP)
ssh tsia-root-tail "docker compose ... exec ocp sh -c 'curl ... | grep ...'"
```
