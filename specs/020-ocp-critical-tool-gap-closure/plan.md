# Feature 020 Implementation Plan

## Wave 0: Security Foundation

**Agents:** `security`, `doc-forensics`

**Security Review Checkpoint SR-1** (after W0.3): Threat model must cover MI command injection, dialog data exposure, and TLS reload privilege escalation.

- [x] W0.1: Create docs/security/020-ocp-gap-closure-security-assessment.md
  - Data classification for each tool (dialog data = internal, MI output = internal, dialplan = internal)
  - Secret/credential prohibition in MI command output
  - Access control model (devops vs admin roles)
  - Input validation strategy for MI command whitelist
- [x] W0.2: Create docs/security/020-ocp-gap-closure-threat-model.md
  - STRIDE analysis for MI command runner
  - Threat: Command injection via MI interface
  - Threat: Unauthorized dialog termination (mitigated by read-only design)
  - Threat: Privilege escalation via TLS reload
- [x] W0.3: Update docs/security/008-security-evidence-index.md with Feature 020 entries
- [x] W0.4: MSL Applicability Review
  - Dialog data contains SIP URIs and IP addresses — assess if PII
  - MI commands can expose runtime state — assess if sensitive
- [x] W0.5: Secure-development verification — scan existing web/ for SQL injection patterns

## Wave 1: Core CRUD Tools

**Agent:** `coder`

**Security Review Checkpoint SR-2** (after W1.4): All CRUD pages must pass secret-leakage scan and CSRF validation test.

- [x] W1.1: Create `web/dialplan.php` — CRUD on `dialplan` table
  - List rules with match_op, match_exp, match_flags, subst_exp, repl_exp, attrs
  - Create/update/delete with PDO prepared statements
  - CSRF protection via `csrf.php`
  - `requireRole('devops')` enforced
- [x] W1.2: Create `web/domains.php` — CRUD on `domain` table
  - List domains with id, domain, did, last_modified
  - Create/update/delete with PDO prepared statements
  - CSRF protection
  - `requireRole('devops')` enforced
- [x] W1.3: Create `web/dialog.php` — Read-only dialog viewer
  - Query `dialog` table (or MI `dlg_list` command)
  - Display: callid, from_uri, to_uri, state, start_time, duration
  - No mutation operations
  - `requireRole('devops')` enforced
- [x] W1.4: Automated scan — verify no secrets in PHP files, no inline credentials, no `echo` of raw DB output without `htmlspecialchars`

## Wave 2: MI Commands & Statistics

**Agent:** `coder`

**Security Review Checkpoint SR-3** (after W2.3): MI command whitelist must reject any non-approved command; statistics output must not leak internal IPs.

- [x] W2.1: Create `web/mi-commands.php` — MI command runner
  - Whitelist: `ds_reload`, `tls_reload`, `get_statistics`, `dlg_list`, `dlg_end_dlg` (admin only), `domain_reload`
  - Display command output in `<pre>` with `htmlspecialchars()`
  - Log all executed commands to `auth_audit_log`
  - `requireRole('devops')` enforced; `dlg_end_dlg` requires `requireRole('admin')`
- [x] W2.2: Create `web/statistics.php` — Statistics monitor
  - Query `get_statistics` MI command for: active_dialogs, registered_users, dispatcher_sets, transactions, replies, requests
  - D3.js bar/line charts for time-series display
  - Auto-refresh every 30 seconds
  - `requireRole('devops')` enforced
- [x] W2.3: Negative test — attempt to execute non-whitelisted MI command; verify rejection
- [x] W2.3b: Negative test — execute a whitelisted MI command that returns HTTP 405 or timeout; verify the failure is logged to `auth_audit_log` with error details and user identity
- [x] W2.4: Integrate new pages into `common/role-nav.php` navigation
- [x] W2.5: Update `dashboard.php` with links to new tools

## Wave 3: TLS Management

**Agent:** `coder`

- [x] W3.1: Create `web/tls-management.php` — TLS certificate status viewer
  - Display loaded TLS certificates (via MI or file system read)
  - Show expiry dates, issuer, subject
  - `requireRole('devops')` for viewing
- [x] W3.2: Implement `tls_reload` trigger
  - Button to execute `tls_reload` MI command
  - `requireRole('admin')` enforced
  - Audit log entry created
- [x] W3.3: Verify TLS reload propagates to OpenSIPS container
  - Test: reload after cert update, verify new connections use new cert

## Wave 4: Validation & Closure

**Agents:** `docs`, `release`

- [x] W4.1: Run `speckit.spec-validate.validate` on Feature 020 spec
- [x] W4.2: Run architecture-guard verification — ensure no constitution violations
- [x] W4.3: Run ripple analysis on all new PHP files
- [x] W4.4: Write conventional commit and push to master
- [x] W4.5: Update AGENTS.md with new OCP tool references

## Dependency Graph

```
W0 (Security) → W1 (CRUD) → W2 (MI/Stats) → W3 (TLS) → W4 (Validation)
     ↓              ↓              ↓
   SR-1           SR-2           SR-3
```

## MSL Applicability

| Aspect | Assessment |
|---|---|
| New tools expose OpenSIPS runtime state | **MSL-relevant** — MI commands and dialog data could reveal topology |
| Mitigation: RBAC + whitelist + audit logging | Justification for controlled access |
| **Action**: Document MSL controls in W0.4 | Required |

## Supply-Chain Notes

- No new Docker images or base images.
- No new npm packages (reuses existing D3.js).
- No new PHP extensions beyond existing PDO.

## Wave 5: Architecture Refactor Remediation (Post-Implementation)

**Agents:** `security`, `coder`, `reviewer`

**Constitution Gate**: Brownfield Hygiene — fixes require evidence in evidence/remediation/ciclo-020/

- [x] W5.1 (R1): CRUD failure audit logging — Add logAuditEvent() in PDOException catch blocks for dialplan.php and domains.php
- [x] W5.2 (R2): Security headers — Add CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy to common/header.php
- [x] W5.3 (R3): Session hardening — Add session_regenerate_id(true) on login, cookie_httponly, cookie_samesite=Strict, use_strict_mode=1
- [x] W5.4 (R4): Statistics error path — Add warning banner on MI timeout; charts preserve last-known values
- [x] W5.5 (R5): D3.js fallback — Display graceful degradation message when CDN is unreachable
- [x] W5.6 (R6): validate-input.php integration — Refactor dialplan.php and domains.php to use common helper (P3, optional)
- [x] W5.7 (R7): Post-fix brownfield scan — Validate no new drift, all findings resolved

**Dependency Graph:**
```
W5.1 / W5.2 / W5.3 (parallel) → W5.4 / W5.5 (parallel) → W5.7 (validation gate)
W5.6 (independent, P3)
```
