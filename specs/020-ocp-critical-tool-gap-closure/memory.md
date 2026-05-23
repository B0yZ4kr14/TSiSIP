# Feature Memory — 020: OCP Critical Tool Gap Closure

> Maintained by memory-md extension.
> Created: 2026-05-23
> Last updated: 2026-05-23

---

## Current Scope

Six PHP-based administrative tools for the TSiSIP OCP (Operator Control Panel):

1. Dialog Viewer (web/dialog.php) — Read-only view of active SIP dialogs
2. MI Commands (web/mi-commands.php) — Whitelisted OpenSIPS Management Interface proxy
3. Statistics Monitor (web/statistics.php) — Real-time metrics with D3.js charts
4. Dialplan Manager (web/dialplan.php) — CRUD for routing rules
5. Domains Manager (web/domains.php) — CRUD for tenant domains
6. TLS Management (web/tls-management.php) — Certificate status and reload

All tools enforce RBAC (devops minimum, admin for TLS), CSRF protection, PDO prepared statements, and audit logging.

## Relevant Decisions

- AD-020-1: MI Command Whitelist in PHP — hardcoded array prevents command injection
- AD-020-2: Statistics backpressure — 30s auto-refresh, 5s timeout, freeze-on-error
- AD-020-3: Database-driven dialplan — rules loaded from PostgreSQL
- AD-020-4: Read-only dialog viewer — direct PDO queries, no mutation endpoints

## Active Architecture Constraints

| Constraint | Evidence | Status |
|---|---|---|
| Docker-first | OCP container only | Pass |
| PostgreSQL-only | All PDO queries | Pass |
| Module validity | No invalid modules | Pass |
| Secret hygiene | No secrets in source | Pass |
| Network isolation | Internal network only | Pass |

## Accepted Deviations

- D3.js loaded from CDN (statistics.php) — air-gapped deployments need local copy
- web/common/validate-input.php exists but is unused by dialplan/domains (P3 cleanup)

## Relevant Security Constraints

- RBAC: requireRole('devops') minimum, admin for TLS reload
- CSRF: validateCsrfToken() on all mutating forms
- PDO: prepared statements everywhere
- Audit: logAuditEvent() on all success and failure paths
- Output: htmlspecialchars() on all user-facing output

## Related Historical Lessons

- Audit logging asymmetry was a recurring pattern — now enforced via remediation
- Security headers should be centralized in common/header.php for all OCP pages
- Session hardening must be applied at config.php level, not per-page

## Conflict Warnings

- None at this time.

## Retrieval Notes

- Search terms: OCP, dialog, MI, statistics, dialplan, domains, TLS, admin tools
- Related features: 002 (OCP rebrand), 016 (audit log compliance)
