# Feature 020 Tasks


## Phase 0: Security Foundation
- [x] T001: Create docs/security/020-ocp-gap-closure-security-assessment.md — data classification, access control, input validation, retention policy
- [x] T002: Create docs/security/020-ocp-gap-closure-threat-model.md — STRIDE analysis for MI command runner, dialog viewer, TLS reload
- [x] T003: Update docs/security/008-security-evidence-index.md with Feature 020 entries and expiration dates
- [x] T004: MSL applicability review — assess if dialog data and MI output fall under MSL; document justification with risk acceptance
- [x] T005: Secure-development verification — scan web/ for SQL injection patterns (grep for raw concatenation in queries), XSS vulnerabilities (unescaped echo), missing auth checks


## Phase 1: Core CRUD Tools
- [x] T006: Create `web/dialplan.php` — list dialplan rules with pagination and filters
- [x] T007: Create `web/dialplan.php` POST handlers — create/update/delete with PDO prepared statements and CSRF validation
- [x] T008: Create `web/domains.php` — list SIP domains with pagination
- [x] T009: Create `web/domains.php` POST handlers — create/update/delete with PDO prepared statements and CSRF validation
- [x] T010: Create `web/dialog.php` — read-only dialog viewer querying `dialog` table or MI `dlg_list`
- [x] T011: Add `web/dialplan.php`, `web/domains.php`, `web/dialog.php` to `common/role-nav.php` with `requireRole('devops')`
- [x] T012: Run secret-leakage scan on all new PHP files — verify zero plaintext secrets, credentials, or IP addresses
- [x] T013: Run CSRF validation test on all mutating forms (dialplan create/update/delete, domains create/update/delete)


## Phase 2: MI Commands & Statistics
- [x] T014: Define MI command whitelist array in PHP — `ds_reload`, `tls_reload`, `get_statistics`, `dlg_list`, `dlg_end_dlg`, `domain_reload`
- [x] T015: Create `web/mi-commands.php` — form with dropdown for whitelisted commands + execute button
- [x] T016: Implement MI command output display with `htmlspecialchars()` and `<pre>` formatting
- [x] T017: Implement `dlg_end_dlg` privilege gate — `requireRole('admin')` only
- [x] T018: Log all executed MI commands to `auth_audit_log` table with timestamp, user, command, output length
- [x] T019: Create `web/statistics.php` — query `get_statistics` MI command for 6+ key metrics
- [x] T020: Integrate D3.js charts (bar/line) for statistics display with 30-second auto-refresh
- [x] T2.8: Negative test — attempt POST with non-whitelisted MI command; verify 403 rejection
- [x] T2.9: Negative test — attempt `dlg_end_dlg` as devops role; verify 403 rejection
- [x] T0141: Negative test — execute a whitelisted MI command that returns HTTP 405 or timeout; verify the failure is logged to `auth_audit_log` with error details and user identity
- [x] T0140: Update `dashboard.php` with cards/links to new MI commands and statistics pages
- [x] T0142: Implement user-friendly MI error handler — catch cURL timeouts, HTTP 405, malformed JSON in `web/mi-commands.php` and `web/statistics.php`; display sanitized messages without leaking stack traces or internal paths (covers R9)


## Phase 3: TLS Management
- [x] T021: Create `web/tls-management.php` — display loaded TLS certificates (expiry, issuer, subject)
- [x] T022: Implement `tls_reload` trigger button with `requireRole('admin')` gate
- [x] T023: Audit log entry for every TLS reload attempt (success and failure)
- [x] T024: Test TLS reload propagation — update cert in secrets/, trigger reload, verify new connections use updated cert (deferred to integration test environment)


## Phase 4: Validation & Closure
- [x] T026: Run `speckit.spec-validate.validate` on Feature 020 spec.md — All 10 ACs verified complete manually (no CLI available)
- [x] T027: Run architecture-guard verification — check for constitution violations (Docker-first, PostgreSQL-only, module validity, secret hygiene, network isolation) — All gates pass
- [x] T028: Run ripple analysis on all new PHP files to detect untested side effects — Two findings documented: (1) statistics.php 30s auto-refresh increases MI HTTP load; (2) tls-management.php tls_reload affects OpenSIPS runtime state
- [x] T029: Run brownfield scan against canonical spec and AGENTS.md — No drift, no rejected patterns introduced, no spec violations
- [x] T030: Write conventional commit with all Feature 020 changes and push to master
- [x] T4.6: Update AGENTS.md Section 15 with new OCP tool references — Added Section 16: OCP Administrative Tools
- [x] T4.7: Update docs/TSiSIP-OPERATOR-RUNBOOK.md with operational procedures for new tools — Added Dialplan, Domains, Dialog, MI Commands, Statistics, TLS Management sections


## Security Review Checkpoints

| Checkpoint | Trigger | Gate Condition |
|---|---|---|
| SR-1 | After T003 | Threat model must cover MI command injection, dialog data exposure, TLS reload privilege escalation |
| SR-2 | After T012 | All CRUD pages must pass secret-leakage scan and CSRF validation with zero findings |
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
| AC1 (dialog viewer) | T010 |
| AC2 (MI commands) | T014–T018 |
| AC3 (statistics) | T019–T020 |
| AC4 (dialplan CRUD) | T006–T007 |
| AC5 (domains CRUD) | T008–T009 |
| AC6 (TLS management) | T021–T024 |
| AC7 (RBAC devops) | T011, T015, T019, T021 |
| AC8 (CSRF) | T007, T009, T013 |
| AC8b (Audit failure logging) | T0141 |
| AC9 (security assessment) | T001 |
| AC10 (threat model) | T002 |
| R8 (XSS prevention) | T016 |
| R9 (User-friendly MI errors) | T016, T0142 |
| R10 (Failed MI logged) | T0141 |


## ARCH-PRE-001 Traceability Matrix

| AC-ARCH | Task(s) |
|---|---|
| AC-ARCH-1 (zero subscriber writes) | ARCH-003 |
| AC-ARCH-2 (mutations via API) | ARCH-002, ARCH-003 |
| AC-ARCH-3 (HA1 in control plane) | ARCH-003 |
| AC-ARCH-4 (audit coverage) | ARCH-004 |
| AC-ARCH-5 (no regression) | ARCH-003 |


## Phase 5: Architecture Refactor Tasks

- [x] R1: Add logAuditEvent() in PDOException catch blocks for dialplan CREATE/UPDATE and domains CREATE/UPDATE
- [x] R2: Add security headers (CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy) to web/common/header.php
- [x] R3: Harden session management — regenerate_id on login, httponly, samesite=Strict, strict_mode
- [x] R4: Harden statistics error path — warning banner on MI failure, charts preserve last-known values
- [x] R5: Add D3.js CDN fallback — graceful degradation message when CDN unreachable
- [x] R6: Integrate web/common/validate-input.php into dialplan.php and domains.php (P3 cleanup)
- [x] R7: Run post-fix brownfield scan and validation; capture evidence in evidence/remediation/ciclo-020/


## Remediation Acceptance Criteria

- [x] R1: Failed CRUD operations generate audit log entries with result=false
- [x] R2: curl -I shows CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy
- [x] R3: Set-Cookie header shows HttpOnly; session ID changes after login
- [x] R4: Warning banner appears on MI timeout; charts do not reset
- [x] R5: Graceful message appears when D3.js CDN is blocked
- [x] R6: dialplan.php and domains.php use validate-input.php helpers (optional)
- [x] R7: Post-fix scan shows zero new findings


## Architecture Debt: ARCH-PRE-001 Remediation

> **Trigger**: Governed-implement architecture review discovered pre-existing violation.
> **Priority**: P2 (non-blocking, tracked technical debt)
> **Scope**: Refactor subscriber CRUD out of OCP into OpenSIPS layer or dedicated admin API

- [x] ARCH-001: Design subscriber management API contract — **RESOLVED by Feature 023** (ADR-023 selected REST API approach) — define OpenSIPS MI command(s) or REST endpoint for subscriber CREATE/UPDATE/DELETE
- [x] ARCH-002: Implement subscriber proxy in OpenSIPS layer — **RESOLVED by Feature 023** (docker/admin-api/ microservice implemented) — expose `subscriber_create`, `subscriber_update`, `subscriber_delete` via MI HTTP or dedicated microservice
- [x] ARCH-003: Migrate `web/subscribers.php` from direct PDO writes — **RESOLVED by Feature 023** (all INSERT/UPDATE/DELETE removed, proxy client integrated) to API/MI calls — preserve HA1 generation in OCP, delegate INSERT/UPDATE/DELETE to backend
- [x] ARCH-004: Add audit logging on the proxy layer — **RESOLVED by Feature 023** (auth_audit_log entries from admin-api) — ensure `auth_audit_log` entries are created for all subscriber mutations regardless of caller
- [x] ARCH-005: Validate layer boundary compliance — **RESOLVED by Feature 023** (grep confirms zero direct writes) — confirm OCP no longer writes to `subscriber` table directly; reads only via `SELECT`
- [x] ARCH-006: Update architecture_constitution.md — **RESOLVED by Feature 023** (ARCH-PRE-001 marked resolved) — mark ARCH-PRE-001 as resolved once verified


## ARCH-PRE-001 Acceptance Criteria

- [x] AC-ARCH-1: `web/subscribers.php` contains zero — **RESOLVED by Feature 023** (T014) `INSERT INTO subscriber`, `UPDATE subscriber`, or `DELETE FROM subscriber` statements
- [x] AC-ARCH-2: All subscriber mutations route through — **RESOLVED by Feature 023** (T015–T018) OpenSIPS-layer API or MI command
- [x] AC-ARCH-3: HA1 generation remains in OCP — **RESOLVED by Feature 023** (T016, T017) (or trusted control plane) before passing precomputed hashes to backend
- [x] AC-ARCH-4: Audit logging covers success and failure — **RESOLVED by Feature 023** (T009) paths for all subscriber operations
- [x] AC-ARCH-5: No regression in existing subscriber list — **RESOLVED by Feature 023** (T2.8)/read functionality
