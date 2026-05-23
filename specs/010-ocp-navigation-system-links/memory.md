# Feature 010 Memory: OCP Navigation System Links

## Current Scope
Restructure the TSiSIP Control Panel dashboard and sidebar so authenticated users — especially Admin and DevOps — can access system management pages (Dispatcher, RTPengine) and wiki documentation from the landing page, with role-gated visibility.

## Relevant Decisions
- **Pure frontend change**: No new backend services; conditional PHP rendering within existing OCP auth framework.
- **Role-gated sections**: Admin/DevOps see "System Management" + "Documentation & Wiki"; non-privileged roles see only wiki.
- **Keep login redirect to dashboard.php**: Rejected redirecting to wiki.php.
- **Separate sections visually**: System and Wiki headings distinct in sidebar; primary vs. secondary button styles.

## Active Architecture Constraints
- PHP 8.2 + Apache 2.4 + PostgreSQL (existing OCP runtime).
- No modification to PHP auth logic or database schema.
- Uses existing TSiSIP theme CSS classes (tsisip-btn-primary, tsisip-btn-secondary, tsisip-nav-heading, tsisip-status-dot).

## Accepted Deviations
- None.

## Relevant Security Constraints
- Role-based access control enforced via existing $_SESSION['ocp_user_role'].
- No new endpoints or API surfaces introduced.

## Related Historical Lessons
- Simple PHP conditionals + CSS additions can close navigation gaps without architectural changes.
- Active-state highlighting requires independent $currentPage detection for system pages vs. wiki pages.

## Conflict Warnings
- Depends on Feature 002 (TSiSIP theme CSS classes must exist).
- Feature 016 (Audit Log) adds another system management link that must follow the same role-gating pattern.

## Retrieval Notes
- Search terms: OCP navigation, dashboard, role-nav, system management, wiki links, sidebar.
- Related features: 002 (theme), 011 (forced password change), 016 (audit log).
