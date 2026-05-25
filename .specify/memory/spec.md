# TSiSIP Accumulated Project Specification

> Main project memory specification. Content merged from individual feature specs under specs/.
> Version: 1.0.0 | Last Updated: 2026-05-24

---

## Feature 020: OCP Critical Tool Gap Closure

[Source: specs/020-ocp-critical-tool-gap-closure]

**Status**: Completed

### Overview

The OCP Cross-Analysis identified that TSiSIP frontend covered only 16% of the official OpenSIPS Control Panel v9.3.6 tool set. Six critical gaps were identified as high-priority for production operations: Dialog, MI Commands, Statistics Monitor, Dialplan, Domains, and TLS Management.

This feature closes those six critical gaps by implementing PHP-based administrative tools that integrate with the existing TSiSIP OCP frontend, PostgreSQL backend, and role-based access control.

### Functional Goals

1. Dialog Viewer - Read-only view of ongoing SIP dialogs with profile info
2. MI Commands Runner - Execute common MI commands via web UI with output display
3. Statistics Monitor - Display key OpenSIPS metrics with D3.js charts
4. Dialplan Manager - CRUD for dialplan rules stored in PostgreSQL
5. Domains Manager - CRUD for SIP domains used by OpenSIPS
6. TLS Management UI - View TLS certificate status and trigger reloads

### Security Requirements

| ID | Requirement |
|---|---|
| R1 | All admin tools require authentication via requireRole('devops') |
| R2 | All mutating operations require CSRF token validation |
| R3 | All database queries use PDO prepared statements |
| R4 | MI command input is whitelisted |
| R5 | Dialog viewer is read-only |
| R6 | TLS reload requires requireRole('admin') |
| R7 | All actions are logged to audit_log table |
| R8 | Output of MI commands is sanitized before HTML rendering |
| R9 | MI command errors are caught and displayed as user-friendly messages |
| R10 | Failed MI command attempts are logged with error code and user identity |

### Acceptance Criteria

- [x] AC1: web/dialog.php lists active dialogs
- [x] AC2: web/mi-commands.php executes whitelisted MI commands
- [x] AC3: web/statistics.php displays 6+ key metrics with D3.js charts
- [x] AC4: web/dialplan.php provides full CRUD on dialplan table
- [x] AC5: web/domains.php provides full CRUD on domain table
- [x] AC6: web/tls-management.php shows certificate status and triggers tls_reload
- [x] AC7: All pages enforce role-based access
- [x] AC8: All mutating forms include CSRF validation
- [x] AC9: Security assessment document exists and is approved
- [x] AC10: Threat model document exists and covers MI command injection risks

### Architecture Decisions

- AD-1: Database-Driven Dialplan - rules stored in PostgreSQL, no hardcoded rules
- AD-2: MI Command Whitelist - hardcoded PHP array prevents command injection
- AD-3: Read-Only Dialog Viewer - prevents accidental call termination

### Key Entities

- dialog - Active SIP dialogs
- dialplan - Routing rules
- domain - SIP domains
- audit_log - Audit trail for admin operations

---

## Revision History

| Date | Feature | Change |
|---|---|---|
| 2026-05-24 | Feature 020 | Bootstrapped main spec from specs/020-ocp-critical-tool-gap-closure |
