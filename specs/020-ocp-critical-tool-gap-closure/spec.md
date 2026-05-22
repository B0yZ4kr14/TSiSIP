# Feature 020: OCP Critical Tool Gap Closure

## Overview

The OCP Cross-Analysis (docs/OCP-CROSS-ANALYSIS.md) identified that TSiSIP's frontend covers only 16% of the official OpenSIPS Control Panel v9.3.6 tool set. Six critical gaps were identified as high-priority for production operations: Dialog (ongoing calls), MI Commands, Statistics Monitor, Dialplan, Domains, and TLS Management.

This feature closes those six critical gaps by implementing PHP-based administrative tools that integrate with the existing TSiSIP OCP frontend, PostgreSQL backend, and role-based access control.

## Security Governance Preset

### Memory-Safe Language Assessment

| Language | Memory-Safe | Justification |
|---|---|---|
| PHP 8.2 | No | Managed runtime with GC; buffer overflows possible in extensions. Mitigated via Docker image pinning and Trivy scanning. |
| SQL (PostgreSQL) | N/A | Declarative; managed by PostgreSQL 16. |
| JavaScript (D3.js) | N/A | Client-side only; no runtime security boundary. |

### Framework Relevance

| Framework | Relevance | Status |
|---|---|---|
| NIST SSDF | Relevant | Input validation, access control, secure session management |
| CWE Top 25 | Relevant | CWE-89 (SQL injection), CWE-79 (XSS), CWE-306 (missing auth) |
| OWASP ASVS | Relevant | V4 Level 2 — admin interfaces require CSRF, RBAC, audit logging |
| SBOM | Relevant | No new dependencies beyond existing OCP stack (PHP 8.2, PDO, D3.js) |
| VEX | Relevant | Vulnerability disclosure for any CVEs in PHP extensions |
| SLSA | Relevant | Provenance tracking for Docker image builds |

### Security Evidence Artefacts

- Create: docs/security/020-ocp-gap-closure-security-assessment.md
- Create: docs/security/020-ocp-gap-closure-threat-model.md
- Update: docs/security/008-security-evidence-index.md

## Motivation

Without these tools, operators must:
- SSH into containers to run MI commands for reloading configs or checking stats
- Use psql directly to inspect ongoing dialogs
- Have no visibility into OpenSIPS statistics without Prometheus/Grafana
- Manually edit opensips.cfg.tpl for dialplan changes instead of using database-driven rules
- Manage SIP domains via raw SQL instead of a web UI
- Restart containers for TLS certificate rotation instead of hot-reloading

## Functional Goals

1. **Dialog Viewer** — Read-only view of ongoing SIP dialogs with profile info
2. **MI Commands Runner** — Execute common MI commands via web UI with output display
3. **Statistics Monitor** — Display key OpenSIPS metrics with D3.js charts
4. **Dialplan Manager** — CRUD for dialplan rules stored in PostgreSQL
5. **Domains Manager** — CRUD for SIP domains used by OpenSIPS
6. **TLS Management UI** — View TLS certificate status and trigger reloads

## Non-Goals

- Full OCP v9.3.6 parity (28 remaining tools are out of scope)
- Real-time WebSocket updates (polling-based refresh is acceptable)
- Multi-box OpenSIPS management (single Docker Compose stack only)
- Call recording or media playback

## Security Requirements

| ID | Requirement |
|---|---|
| R1 | All admin tools require authentication via `requireRole('devops')` |
| R2 | All mutating operations (CREATE/UPDATE/DELETE) require CSRF token validation |
| R3 | All database queries use PDO prepared statements |
| R4 | MI command input is whitelisted — only pre-approved commands may be executed |
| R5 | Dialog viewer is read-only — no mutation of active calls permitted |
| R6 | TLS reload requires `requireRole('admin')` — higher privilege than devops |
| R7 | All actions are logged to `auth_audit_log` table |
| R8 | Output of MI commands is sanitized before HTML rendering (XSS prevention) |

## Acceptance Criteria

- [x] AC1: `web/dialog.php` lists active dialogs with call-id, from/to, duration, state
- [x] AC2: `web/mi-commands.php` executes whitelisted MI commands and displays output
- [x] AC3: `web/statistics.php` displays at least 6 key metrics with D3.js charts
- [x] AC4: `web/dialplan.php` provides full CRUD on `dialplan` table
- [x] AC5: `web/domains.php` provides full CRUD on `domain` table
- [x] AC6: `web/tls-management.php` shows certificate status and triggers `tls_reload` MI command
- [x] AC7: All pages enforce `requireRole('devops')` minimum
- [x] AC8: All mutating forms include `validateCsrfToken()`
- [x] AC9: Security assessment document exists and is approved
- [x] AC10: Threat model document exists and covers MI command injection risks

## Architecture Decisions

### AD-1: Database-Driven Dialplan
Dialplan rules are stored in PostgreSQL and loaded by OpenSIPS at startup or via MI reload. No hardcoded rules in opensips.cfg.tpl.

### AD-2: MI Command Whitelist
Only pre-approved MI commands may be executed via the web UI. The whitelist is defined in PHP code, not user-configurable, to prevent command injection.

### AD-3: Read-Only Dialog Viewer
Active dialogs are never mutated via the web UI. The dialog page is strictly read-only to prevent accidental call termination.

## References

- docs/OCP-CROSS-ANALYSIS.md
- docs/TSiSIP-CANONICAL-SPEC.md (Section 8: Routing Logic)
- web/subscribers.php (reference implementation pattern)
- web/dispatcher.php (reference implementation pattern)
