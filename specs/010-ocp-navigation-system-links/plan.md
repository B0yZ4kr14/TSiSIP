## Summary

This feature implements the OCP Navigation System Links for the TSiSIP SIP edge-proxy platform.

## Technical Context

- **PHP 8.2**: Existing OCP runtime
- **PostgreSQL**: Database backend for configuration and state
- **Docker & Docker Compose**: Container orchestration and deployment

## Project Structure

Relevant directories and files for this feature are located under `specs/010-ocp-navigation-system-links/` and integrated into the main project tree.

# Plan: OCP Navigation System Links

## Summary

This plan implements the feature described in the companion spec.md. It covers infrastructure changes, application code, validation gates, and deployment steps required to deliver the capability in a Docker-first, PostgreSQL-only TSiSIP environment.

## Technical Context

**Language/Version**: Bash, Docker, Docker Compose, Python 3 (for tests), PHP 8.2 (for OCP features), OpenSIPS 3.6 LTS config
**Primary Dependencies**: OpenSIPS 3.6 LTS, PostgreSQL 16, Docker Engine + Compose V2
**Testing**: pytest integration tests, shell-based health probes, PHP syntax validation
**Target Platform**: Docker containers (local dev + VPS production)
**Project Type**: Infrastructure / DevSecOps / SIP edge proxy

## Project Structure

```
specs/010-ocp-navigation-system-links/
├── spec.md              # Feature specification
├── plan.md              # This implementation plan
├── tasks.md             # Actionable task breakdown
└── checklists/          # Quality checklists (if present)
```



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
