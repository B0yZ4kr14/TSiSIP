# Post-Implementation Review — Feature 020

**Date**: 2026-05-24
**Commits**: 18 Feature 020 commits on `main`
**Files touched**: 45 unique files
**New PHP files**: 6 (dialog.php, dialplan.php, domains.php, mi-commands.php, statistics.php, tls-management.php)

---

## 1. Ripple Scan Results

### Scope of Change

| Category | Count | Details |
|---|---|---|
| New PHP files | 6 | Admin tools for Dialog, Dialplan, Domains, MI, Statistics, TLS |
| Modified PHP files | 4 | dashboard.php, role-nav.php, header.php, config.php |
| New security docs | 3 | Security assessment, threat model, evidence index update |
| Modified docs | 2 | AGENTS.md (Section 16), Operator Runbook (6 sections) |
| Evidence artifacts | 1 | evidence/remediation/ciclo-020/ (R1–R7) |

### Ripple Findings

| ID | Finding | Severity | Impact |
|---|---|---|---|
| RP-001 | `web/common/header.php` security headers (R2) affect **all 22 pages** that include it | LOW | Positive: CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy applied globally |
| RP-002 | `web/common/config.php` session hardening (R3) affects **all authenticated sessions** | LOW | Positive: HttpOnly, SameSite=Strict, strict_mode for all users |
| RP-003 | `web/statistics.php` 30-second auto-refresh generates **continuous MI HTTP load** | MEDIUM | Monitoring recommended: MI endpoint receives 2 req/min per active statistics tab |
| RP-004 | `web/tls-management.php` `tls_reload` affects **OpenSIPS runtime state** | MEDIUM | Operational: New TLS connections use updated certs; existing connections unaffected |
| RP-005 | `web/dashboard.php` and `web/common/role-nav.php` now reference 6 new tools | LOW | Navigation surface expanded; no breaking changes to existing links |

### Untested Side Effects

1. **D3.js CDN failure in air-gapped environments**: R5 added graceful degradation, but no automated test validates CDN-blocked behavior.
2. **MI HTTP timeout cascading**: If the MI HTTP endpoint becomes unreachable, statistics.php and mi-commands.php both enter error states simultaneously. No circuit breaker exists.
3. **Audit log table growth**: All 6 new tools plus remediation logging increase `auth_audit_log` write volume. No retention or partitioning strategy is documented for high-volume audit scenarios.

---

## 2. Architecture Review Results

### P0 Rule Verification

| Rule | Status | Evidence |
|---|---|---|
| Docker-first | ✅ PASS | All tools run inside OCP container; no bare-metal paths |
| PostgreSQL-only | ✅ PASS | All DB queries use PDO to PostgreSQL; no db_mysql/db_sqlite |
| Module validity | ✅ PASS | No banned modules (sanity, rtpproxy) in new files |
| Secret hygiene | ✅ PASS | Zero plaintext secrets in new PHP files |
| Network isolation | ✅ PASS | No new host-published ports; PostgreSQL/Asterisk remain internal |
| HA1-only auth | ✅ PASS | Feature 020 does not modify auth layer |
| RTPengine internal bind | ✅ PASS | No changes to RTPengine configuration |

### Architecture Decisions Validated

| Decision | Validation | Status |
|---|---|---|
| AD-1: Database-Driven Dialplan | dialplan.php uses PDO prepared statements; no hardcoded rules | ✅ Validated |
| AD-2: MI Command Whitelist | Whitelist array rejects non-approved commands with 403 | ✅ Validated |
| AD-3: Read-Only Dialog Viewer | dialog.php has zero POST mutation handlers | ✅ Validated |
| AD-020-2: Statistics Backpressure | 30s refresh, 5s timeout, freeze-on-error implemented | ✅ Validated |

### Drift Assessment

| Check | Result |
|---|---|
| New dependencies introduced | None — reuses existing PHP 8.2, PDO, D3.js |
| New Docker services | None |
| New networks | None |
| Layer boundary violations (Feature 020) | Zero |
| Pre-existing violations | ARCH-PRE-001 (subscribers.php) — documented, tracked |

**Conclusion**: Feature 020 introduces zero architecture drift. All changes are confined to the Control Plane (OCP) layer and respect established boundaries.

---

## 3. Memory Capture — Durable Lessons

### Patterns Established

| Pattern | Description | Where Applied |
|---|---|---|
| **Centralized Security Headers** | Add CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy via `common/header.php` rather than per-page | All 22 OCP pages |
| **Config-Level Session Hardening** | Set `cookie_httponly`, `cookie_samesite=Strict`, `use_strict_mode=1` in `common/config.php` rather than per-page | All authenticated sessions |
| **MI Command Whitelist Array** | Hardcoded PHP array mapping command → required role, with explicit rejection for non-whitelisted commands | mi-commands.php |
| **Dual-Path Audit Logging** | Log both success (`result=true`) and failure (`result=false`) with reason metadata | dialplan.php, domains.php, mi-commands.php, tls-management.php |
| **Statistics Backpressure** | Fixed-interval polling with timeout, freeze-on-error, and no retry storm | statistics.php |
| **Graceful CDN Degradation** | Check `typeof d3 === 'undefined'` and display fallback message | statistics.php |

### Decisions to Preserve

| ID | Decision | Rationale |
|---|---|---|
| AD-020-1 | MI whitelist in PHP, not user-configurable | Prevents command injection via file upload or config tampering |
| AD-020-2 | 30s refresh + 5s timeout + freeze-on-error | Balances real-time visibility with MI HTTP load and user experience |
| AD-020-3 | Database-driven dialplan (not hardcoded) | Enables runtime rule changes without container restart |
| AD-020-4 | Read-only dialog viewer | Prevents accidental call termination by operators |

### Anti-Patterns Observed

| ID | Anti-Pattern | Mitigation |
|---|---|---|
| AP-001 | `web/subscribers.php` writes directly to `subscriber` table | ARCH-PRE-001 debt tasks scheduled for refactor |
| AP-002 | D3.js loaded from CDN (air-gapped risk) | R5 graceful degradation mitigates; local copy recommended for air-gapped |
| AP-003 | No circuit breaker on MI HTTP failures | Timeout exists (5s/10s) but no exponential backoff or circuit breaker |

### Repeatable Implementation Recipe

For future OCP admin tools:

1. **Create PHP file** in `web/` with:
   - `require_once __DIR__ . '/common/config.php'`
   - `require_once __DIR__ . '/common/csrf.php'`
   - `requireRole('devops')` (or `admin` for privileged ops)
2. **For CRUD**: Use PDO prepared statements; validate CSRF on POST; call `logAuditEvent()` on success and in `catch (PDOException)` blocks.
3. **For MI commands**: Use `$miWhitelist` pattern; check `array_key_exists($command, $miWhitelist)`; enforce role gating per command.
4. **For display**: Wrap all output in `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
5. **Add to navigation**: Update `web/common/role-nav.php` and `web/dashboard.php`.
6. **Run scans**: secret-leakage, CSRF validation, brownfield scan.
7. **Capture evidence**: Commit evidence to `evidence/remediation/ciclo-{NNN}/`.

---

## Sign-Off

- **Ripple Scan**: Complete — 5 findings documented (2 MEDIUM, 3 LOW)
- **Architecture Review**: Complete — Zero P0 violations, zero drift
- **Memory Capture**: Complete — 6 patterns, 4 decisions, 3 anti-patterns, 1 recipe preserved

**Feature 020 is cleared for production use.**
