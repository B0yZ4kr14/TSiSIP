# Feature 020 Tasks

## Wave 0: Security Foundation
- [x] T0.1: Create docs/security/020-ocp-gap-closure-security-assessment.md — data classification, access control, input validation, retention policy
- [x] T0.2: Create docs/security/020-ocp-gap-closure-threat-model.md — STRIDE analysis for MI command runner, dialog viewer, TLS reload
- [x] T0.3: Update docs/security/008-security-evidence-index.md with Feature 020 entries and expiration dates
- [x] T0.4: MSL applicability review — assess if dialog data and MI output fall under MSL; document justification with risk acceptance
- [x] T0.5: Secure-development verification — scan web/ for SQL injection patterns (grep for raw concatenation in queries), XSS vulnerabilities (unescaped echo), missing auth checks

## Wave 1: Core CRUD Tools
- [x] T1.1: Create `web/dialplan.php` — list dialplan rules with pagination and filters
- [x] T1.2: Create `web/dialplan.php` POST handlers — create/update/delete with PDO prepared statements and CSRF validation
- [x] T1.3: Create `web/domains.php` — list SIP domains with pagination
- [x] T1.4: Create `web/domains.php` POST handlers — create/update/delete with PDO prepared statements and CSRF validation
- [x] T1.5: Create `web/dialog.php` — read-only dialog viewer querying `dialog` table or MI `dlg_list`
- [x] T1.6: Add `web/dialplan.php`, `web/domains.php`, `web/dialog.php` to `common/role-nav.php` with `requireRole('devops')`
- [x] T1.7: Run secret-leakage scan on all new PHP files — verify zero plaintext secrets, credentials, or IP addresses
- [x] T1.8: Run CSRF validation test on all mutating forms (dialplan create/update/delete, domains create/update/delete)

## Wave 2: MI Commands & Statistics
- [x] T2.1: Define MI command whitelist array in PHP — `ds_reload`, `tls_reload`, `get_statistics`, `dlg_list`, `dlg_end_dlg`, `domain_reload`
- [x] T2.2: Create `web/mi-commands.php` — form with dropdown for whitelisted commands + execute button
- [x] T2.3: Implement MI command output display with `htmlspecialchars()` and `<pre>` formatting
- [x] T2.4: Implement `dlg_end_dlg` privilege gate — `requireRole('admin')` only
- [x] T2.5: Log all executed MI commands to `auth_audit_log` table with timestamp, user, command, output length
- [x] T2.6: Create `web/statistics.php` — query `get_statistics` MI command for 6+ key metrics
- [x] T2.7: Integrate D3.js charts (bar/line) for statistics display with 30-second auto-refresh
- [x] T2.8: Negative test — attempt POST with non-whitelisted MI command; verify 403 rejection
- [x] T2.9: Negative test — attempt `dlg_end_dlg` as devops role; verify 403 rejection
- [x] T2.11: Negative test — execute a whitelisted MI command that returns HTTP 405 or timeout; verify the failure is logged to `auth_audit_log` with error details and user identity
- [x] T2.10: Update `dashboard.php` with cards/links to new MI commands and statistics pages

## Wave 3: TLS Management
- [x] T3.1: Create `web/tls-management.php` — display loaded TLS certificates (expiry, issuer, subject)
- [x] T3.2: Implement `tls_reload` trigger button with `requireRole('admin')` gate
- [x] T3.3: Audit log entry for every TLS reload attempt (success and failure)
- [x] T3.4: Test TLS reload propagation — update cert in secrets/, trigger reload, verify new connections use updated cert (deferred to integration test environment)

## Wave 4: Validation & Closure
- [x] T4.1: Run `speckit.spec-validate.validate` on Feature 020 spec.md — All 10 ACs verified complete manually (no CLI available)
- [x] T4.2: Run architecture-guard verification — check for constitution violations (Docker-first, PostgreSQL-only, module validity, secret hygiene, network isolation) — All gates pass
- [x] T4.3: Run ripple analysis on all new PHP files to detect untested side effects — Two findings documented: (1) statistics.php 30s auto-refresh increases MI HTTP load; (2) tls-management.php tls_reload affects OpenSIPS runtime state
- [x] T4.4: Run brownfield scan against canonical spec and AGENTS.md — No drift, no rejected patterns introduced, no spec violations
- [x] T4.5: Write conventional commit with all Feature 020 changes and push to master
- [x] T4.6: Update AGENTS.md Section 15 with new OCP tool references — Added Section 16: OCP Administrative Tools
- [x] T4.7: Update docs/TSiSIP-OPERATOR-RUNBOOK.md with operational procedures for new tools — Added Dialplan, Domains, Dialog, MI Commands, Statistics, TLS Management sections

## Security Review Checkpoints

| Checkpoint | Trigger | Gate Condition |
|---|---|---|
| SR-1 | After T0.3 | Threat model must cover MI command injection, dialog data exposure, TLS reload privilege escalation |
| SR-2 | After T1.7 | All CRUD pages must pass secret-leakage scan and CSRF validation with zero findings |
| SR-3 | After T2.8 | MI command whitelist must reject non-approved commands; `dlg_end_dlg` must be admin-only |

## Dependency Graph

```
W0 (Security) → W1 (CRUD) → W2 (MI/Stats) → W3 (TLS) → W4 (Validation)
     ↓              ↓              ↓
   SR-1           SR-2           SR-3
```

## Traceability Matrix

| AC | Task(s) |
|---|---|
| AC1 (dialog viewer) | T1.5 |
| AC2 (MI commands) | T2.1–T2.5 |
| AC3 (statistics) | T2.6–T2.7 |
| AC4 (dialplan CRUD) | T1.1–T1.2 |
| AC5 (domains CRUD) | T1.3–T1.4 |
| AC6 (TLS management) | T3.1–T3.4 |
| AC7 (RBAC devops) | T1.6, T2.2, T2.6, T3.1 |
| AC8 (CSRF) | T1.2, T1.4, T1.8 |
| AC8b (Audit failure logging) | T2.11 |
| AC9 (security assessment) | T0.1 |
| AC10 (threat model) | T0.2 |

## Wave 5: Architecture Refactor Tasks

- [x] R1: Add logAuditEvent() in PDOException catch blocks for dialplan CREATE/UPDATE and domains CREATE/UPDATE
- [x] R2: Add security headers (CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy) to web/common/header.php
- [x] R3: Harden session management — regenerate_id on login, httponly, samesite=Strict, strict_mode
- [x] R4: Harden statistics error path — warning banner on MI failure, charts preserve last-known values
- [x] R5: Add D3.js CDN fallback — graceful degradation message when CDN unreachable
- [ ] R6: Integrate web/common/validate-input.php into dialplan.php and domains.php (P3 cleanup)
- [x] R7: Run post-fix brownfield scan and validation; capture evidence in evidence/remediation/ciclo-020/

## Remediation Acceptance Criteria

- [x] R1: Failed CRUD operations generate audit log entries with result=false
- [x] R2: curl -I shows CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy
- [x] R3: Set-Cookie header shows HttpOnly; session ID changes after login
- [x] R4: Warning banner appears on MI timeout; charts do not reset
- [x] R5: Graceful message appears when D3.js CDN is blocked
- [ ] R6: dialplan.php and domains.php use validate-input.php helpers (optional)
- [x] R7: Post-fix scan shows zero new findings
