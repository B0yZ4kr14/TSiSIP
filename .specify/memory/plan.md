# TSiSIP Accumulated Implementation Plan

> Main project memory plan. Content merged from individual feature plans under specs/.
> Version: 1.0.0 | Last Updated: 2026-05-24

---

## Feature 020: OCP Critical Tool Gap Closure

[Source: specs/020-ocp-critical-tool-gap-closure]

**Branch**: feature-020-ocp-critical-tool-gap-closure | **Date**: 2026-05-24 | **Spec**: specs/020-ocp-critical-tool-gap-closure/spec.md

### Summary

Implement six PHP-based administrative tools for the TSiSIP OCP frontend to close critical gaps in OpenSIPS runtime management: Dialog Viewer, MI Commands Runner, Statistics Monitor, Dialplan Manager, Domains Manager, and TLS Management UI.

### Technical Context

- Language/Version: PHP 8.2
- Primary Dependencies: PDO (PostgreSQL), D3.js (existing), Apache
- Storage: PostgreSQL 16
- Testing: Manual validation, secret-leakage scan, CSRF validation test
- Target Platform: Docker container (OCP v9 PHP/Apache image)

### Constitution Check

| Gate | Status |
|---|---|
| Docker-first | Pass - all tools run inside OCP container |
| PostgreSQL-only | Pass - all DB queries use PDO to PostgreSQL |
| Module validity | Pass - no invalid OpenSIPS modules |
| Secret hygiene | Pass - zero plaintext secrets in new files |
| Network isolation | Pass - no new host-published ports |

### Project Structure

```
web/
├── dialog.php              # Read-only dialog viewer
├── mi-commands.php         # Whitelisted MI command runner
├── statistics.php          # D3.js statistics monitor
├── dialplan.php            # CRUD for dialplan table
├── domains.php             # CRUD for domain table
├── tls-management.php      # TLS certificate status and reload
├── common/
│   ├── config.php          # Session hardening (updated)
│   ├── header.php          # Security headers (updated)
│   ├── role-nav.php        # Navigation for new tools (updated)
│   └── mi-http.php         # Reusable MI HTTP helper
├── tsisip/locale/          # EN/ES/PT i18n strings (updated)
```

### Dependencies

- No new Docker images or base images
- No new npm packages (reuses existing D3.js)
- No new PHP extensions beyond existing PDO

### Tasks Completed

61/61 tasks completed across 5 waves (W0 Security Foundation, W1 Core CRUD, W2 MI/Stats, W3 TLS, W4 Validation, W5 Architecture Refactor)

---

## Revision History

| Date | Feature | Change |
|---|---|---|
| 2026-05-24 | Feature 020 | Bootstrapped main plan from specs/020-ocp-critical-tool-gap-closure |
