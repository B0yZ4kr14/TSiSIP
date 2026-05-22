# Plan: OCP Navigation System Links

## Tech Stack

- PHP 8.2 (existing OCP runtime)
- Apache 2.4 (existing OCP web server)
- CSS (existing TSiSIP theme)
- PostgreSQL (existing auth backend)

## Architecture

No new backend services. Pure frontend change within the existing OCP PHP application:

1. `dashboard.php` — Add conditional "System Management" section
2. `common/role-nav.php` — Add conditional "System" sidebar section
3. `tsisip/css/tsisip-theme.css` — Add styles for nav headings and status dots

## Implementation Order

1. Update `dashboard.php` with role-gated system links + status indicators
2. Update `role-nav.php` with system page sidebar entries + active-state logic
3. Append CSS rules for `.tsisip-nav-heading` and `.tsisip-status-dot`
4. Validate PHP syntax
5. Build and test locally
6. Deploy to VPS
7. Verify with curl (Admin sees system links, non-admin does not)
