# Feature 010 Memory Synthesis: OCP Navigation System Links

## Current Scope
Role-gated dashboard and sidebar linking to system management pages (Dispatcher, RTPengine) and wiki documentation.

## Relevant Decisions
- Pure frontend PHP/CSS change; no new backend.
- Admin/DevOps see System Management + Wiki; others see Wiki only.
- Login redirect stays dashboard.php.

## Active Architecture Constraints
- Existing OCP runtime (PHP 8.2, Apache, PostgreSQL).
- No auth logic or schema changes.
- Uses Feature 002 theme CSS classes.

## Accepted Deviations
None.

## Relevant Security Constraints
- Role-based access via existing session role.
- No new endpoints.

## Related Historical Lessons
- Simple conditionals + CSS close gaps without architecture changes.
- Independent active-state detection needed for system vs. wiki pages.

## Conflict Warnings
- Depends on Feature 002 theme.
- Feature 016 audit link must follow same pattern.

## Retrieval Notes
- Keywords: OCP navigation, dashboard, role-nav, system management, sidebar.
- Related: 002, 011, 016.
