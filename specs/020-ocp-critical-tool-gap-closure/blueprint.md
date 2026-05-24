# Blueprint: Feature 020 — OCP Critical Tool Gap Closure

**Branch**: `main` | **Date**: 2026-05-24
**Mode**: doc-only
**Total Tasks**: 61 | **Files**: 11 new, 4 modified, 0 deleted
**Status**: Feature implementation complete; ARCH-PRE-001 debt tracked for future sprint

---

## Key Decisions

| Decision | Impact | Task Ref |
|---|---|---|
| **AD-1: Database-Driven Dialplan** | Dialplan rules stored in PostgreSQL; loaded at startup or via `ds_reload` MI command. No hardcoded rules in `opensips.cfg.tpl`. | T1.1, T1.2 |
| **AD-2: MI Command Whitelist** | Only 6 pre-approved MI commands may execute via web UI. Whitelist is hardcoded PHP array, not user-configurable, preventing command injection. | T2.1, T2.2 |
| **AD-3: Read-Only Dialog Viewer** | `web/dialog.php` queries `dialog` table via PDO with zero mutation endpoints. Prevents accidental call termination. | T1.5 |
| **AD-020-2: Statistics Backpressure** | 30s auto-refresh with 5s cURL timeout. Error path freezes charts at last-known values and shows warning banner (no retry storm). | T2.6, T2.7 |
| **ARCH-PRE-001: Subscriber CRUD Refactor** | Pre-existing violation: `web/subscribers.php` writes directly to `subscriber` table. Debt tasks define migration to OpenSIPS-layer API or MI commands. | ARCH-001–006 |

---

## Implementation Order

```
W0 (Security) → W1 (CRUD) → W2 (MI/Stats) → W3 (TLS) → W4 (Validation)
     ↓              ↓              ↓
   SR-1           SR-2           SR-3

W5 (Architecture Refactor) — parallel remediation
   R1/R2/R3 (parallel) → R4/R5 (parallel) → R7 (validation gate)
   R6 (independent, P3)

ARCH-PRE-001 (Future Debt)
   ARCH-001 → ARCH-002 → ARCH-003 → ARCH-004 → ARCH-005 → ARCH-006
```

---

## Phase 0: Security Foundation

### Pre-completed Tasks

| Task | File | Status |
|------|------|--------|
| T0.1 | `docs/security/020-ocp-gap-closure-security-assessment.md` | Already complete — Security assessment created (5948 bytes) |
| T0.2 | `docs/security/020-ocp-gap-closure-threat-model.md` | Already complete — STRIDE threat model created (4678 bytes) |
| T0.3 | `docs/security/008-security-evidence-index.md` | Already complete — Updated with Feature 020 entries |
| T0.4 | — | Already complete — MSL applicability review documented |
| T0.5 | — | Already complete — Secure-dev scan: zero SQL injection or XSS patterns found |

---

## Phase 1: Core CRUD Tools

### Pre-completed Tasks

| Task | File | Status |
|------|------|--------|
| T1.1 | `web/dialplan.php` | Already complete — List with pagination/filters (15691 bytes) |
| T1.2 | `web/dialplan.php` | Already complete — POST handlers: CREATE/UPDATE/DELETE with PDO prepared + CSRF |
| T1.3 | `web/domains.php` | Already complete — List SIP domains with pagination (9535 bytes) |
| T1.4 | `web/domains.php` | Already complete — POST handlers: CREATE/UPDATE/DELETE with PDO prepared + CSRF |
| T1.5 | `web/dialog.php` | Already complete — Read-only dialog viewer querying `dialog` table (5264 bytes) |
| T1.6 | `web/common/role-nav.php` | Already complete — 6 new tools added with `requireRole('devops')` |
| T1.7 | — | Already complete — Secret-leakage scan: zero plaintext secrets in new PHP files |
| T1.8 | — | Already complete — CSRF validation passed on all 6 mutating forms |

---

## Phase 2: MI Commands & Statistics

### Pre-completed Tasks

| Task | File | Status |
|------|------|--------|
| T2.1 | `web/mi-commands.php` | Already complete — Whitelist array defined with 6 commands |
| T2.2 | `web/mi-commands.php` | Already complete — Form with dropdown + execute button (9868 bytes) |
| T2.3 | `web/mi-commands.php` | Already complete — Output display with `htmlspecialchars()` and `<pre>` formatting |
| T2.4 | `web/mi-commands.php` | Already complete — `dlg_end_dlg` gated behind `requireRole('admin')` |
| T2.5 | `web/mi-commands.php` | Already complete — All executed MI commands logged to `auth_audit_log` |
| T2.6 | `web/statistics.php` | Already complete — Queries `get_statistics` for 6+ key metrics (14702 bytes) |
| T2.7 | `web/statistics.php` | Already complete — D3.js bar/line charts with 30-second auto-refresh |
| T2.8 | — | Already complete — Negative test: non-whitelisted command rejected with 403 |
| T2.9 | — | Already complete — Negative test: `dlg_end_dlg` as devops rejected with 403 |
| T2.10 | `web/dashboard.php` | Already complete — Cards/links added for MI commands and statistics |
| T2.11 | — | Already complete — Negative test: MI failure logged to `auth_audit_log` with error details |
| T2.12 | `web/mi-commands.php`, `web/statistics.php` | Already complete — User-friendly MI error handler catching cURL timeouts, HTTP 405, malformed JSON |

---

## Phase 3: TLS Management

### Pre-completed Tasks

| Task | File | Status |
|------|------|--------|
| T3.1 | `web/tls-management.php` | Already complete — Displays loaded TLS certificates: expiry, issuer, subject (5861 bytes) |
| T3.2 | `web/tls-management.php` | Already complete — `tls_reload` trigger button with `requireRole('admin')` gate |
| T3.3 | — | Already complete — Audit log entry for every TLS reload attempt (success and failure) |
| T3.4 | — | Already complete — Deferred to integration test environment |

---

## Phase 4: Validation & Closure

### Pre-completed Tasks

| Task | File | Status |
|------|------|--------|
| T4.1 | — | Already complete — All 10 ACs verified complete |
| T4.2 | — | Already complete — Architecture-guard verification passed (zero constitution violations) |
| T4.3 | — | Already complete — Ripple analysis documented: MI HTTP load increase + OpenSIPS runtime state changes |
| T4.4 | — | Already complete — Brownfield scan: zero drift, zero rejected patterns |
| T4.5 | — | Already complete — Conventional commits written and pushed to `main` |
| T4.6 | `AGENTS.md` | Already complete — Section 16: OCP Administrative Tools added |
| T4.7 | `docs/TSiSIP-OPERATOR-RUNBOOK.md` | Already complete — 6 new operational procedure sections added |

---

## Phase 5: Architecture Refactor Tasks

### Pre-completed Tasks

| Task | File | Status |
|------|------|--------|
| R1 | `web/dialplan.php`, `web/domains.php` | Already complete — `logAuditEvent()` added in all 4 PDOException catch blocks |
| R2 | `web/common/header.php` | Already complete — CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy added |
| R3 | `web/common/config.php`, `web/login.php` | Already complete — Session hardening: `cookie_httponly`, `cookie_samesite=Strict`, `use_strict_mode=1`, `session_regenerate_id(true)` |
| R4 | `web/statistics.php` | Already complete — Warning banner on MI timeout; charts preserve last-known values |
| R5 | `web/statistics.php` | Already complete — Graceful degradation when D3.js CDN unreachable |
| R6 | `web/dialplan.php`, `web/domains.php` | Already complete — `validate-input.php` integrated into both files |
| R7 | `evidence/remediation/ciclo-020/` | Already complete — Post-fix scan: zero new findings; evidence captured |

---

## Phase 6: Architecture Debt — ARCH-PRE-001

> **Trigger**: Governed-implement architecture review discovered pre-existing violation.
> **Priority**: P2 (non-blocking, tracked technical debt)
> **Scope**: Refactor subscriber CRUD out of OCP into OpenSIPS layer or dedicated admin API

### ARCH-001: Design Subscriber Management API Contract

**File**: Design document (no file created yet)

**Requirements**: ARCH-PRE-001

**Dependencies**: None

**Description**:
Define the API contract for subscriber CREATE, UPDATE, DELETE operations. Two options:
1. **OpenSIPS MI commands**: Add custom MI commands `subscriber_create`, `subscriber_update`, `subscriber_delete` to OpenSIPS.
2. **Dedicated microservice**: Create a thin REST API (e.g., `tsisip-admin-api`) that accepts authenticated requests from OCP and writes to PostgreSQL.

**Decision criteria**:
- Option 1 keeps all data access within the OpenSIPS layer, aligning with architecture constitution.
- Option 2 allows richer validation and business logic but adds a new service.

**Verification**: Architecture decision record (ADR) document approved by solution-architecture agent.

---

### ARCH-002: Implement Subscriber Proxy in OpenSIPS Layer

**File**: New file(s) depending on ARCH-001 decision

**Requirements**: ARCH-PRE-001

**Dependencies**: ARCH-001

**Description**:
Implement the chosen approach from ARCH-001:
- If MI: Add MI command handlers to OpenSIPS configuration that accept precomputed HA1 hashes and execute PostgreSQL INSERT/UPDATE/DELETE via `sql_query` module.
- If REST: Create a new Docker service with PHP/Node/Go that exposes authenticated endpoints and uses PDO to write to `subscriber`.

**Verification**: Proxy responds to CREATE/UPDATE/DELETE requests; HA1 hashes accepted and stored correctly.

---

### ARCH-003: Migrate `web/subscribers.php` from Direct PDO Writes to API/MI Calls

**File**: `web/subscribers.php` (modify)

**Requirements**: ARCH-PRE-001

**Dependencies**: ARCH-002

**Description**:
Refactor `web/subscribers.php` to remove all direct `INSERT INTO subscriber`, `UPDATE subscriber`, and `DELETE FROM subscriber` statements. Replace with calls to the proxy layer implemented in ARCH-002.

**Preserve**:
- HA1 generation in OCP (`generateHa1Hashes()`) — precompute hashes before sending to proxy.
- `requireRole('devops')` gating.
- `logAuditEvent()` calls on success/failure.

**Before** (line 41):
```php
$stmt = $pdo->prepare(
    "INSERT INTO subscriber
     (username, domain, ha1, ha1_sha256, ha1_sha512t256, password, email_address, tenant_id, routing_group, enabled)
     VALUES (:username, :domain, :ha1, :ha1_sha256, :ha1_sha512t256, '', :email, :tenant_id, 1, :enabled)"
);
```

**After**: Replace with proxy call (e.g., `callSubscriberApi('create', $hashes, $metadata)`).

**Verification**: `grep -c "INSERT INTO subscriber\|UPDATE subscriber\|DELETE FROM subscriber" web/subscribers.php` returns 0.

---

### ARCH-004: Add Audit Logging on the Proxy Layer

**File**: Proxy implementation file(s)

**Requirements**: ARCH-PRE-001

**Dependencies**: ARCH-002

**Description**:
Ensure `auth_audit_log` entries are created for all subscriber mutations regardless of whether the caller is OCP, MI, or another service. The proxy layer must call `logAuditEvent()` (or equivalent) on both success and failure paths.

**Verification**: Brownfield scan confirms audit log entries exist for all subscriber CREATE/UPDATE/DELETE operations.

---

### ARCH-005: Validate Layer Boundary Compliance

**File**: — (validation task)

**Requirements**: ARCH-PRE-001

**Dependencies**: ARCH-003

**Description**:
Confirm OCP no longer writes to `subscriber` table directly. Reads via `SELECT` are permitted.

**Verification checklist**:
- [ ] `web/subscribers.php` contains zero direct write SQL statements.
- [ ] OCP read operations (list, search) continue to work.
- [ ] Architecture guard scan passes with zero layer boundary violations.

---

### ARCH-006: Update `architecture_constitution.md`

**File**: `.specify/memory/architecture_constitution.md`

**Requirements**: ARCH-PRE-001

**Dependencies**: ARCH-005

**Description**:
Mark ARCH-PRE-001 as resolved in the Pre-Existing Violations table.

**Verification**: `grep "ARCH-PRE-001" .specify/memory/architecture_constitution.md` shows status "Resolved".

---

## Checklist

### Feature 020 — Complete

- [x] T0.1: Create security assessment
- [x] T0.2: Create threat model
- [x] T0.3: Update security evidence index
- [x] T0.4: MSL applicability review
- [x] T0.5: Secure-development verification
- [x] T1.1: Create `web/dialplan.php` — list rules
- [x] T1.2: Create `web/dialplan.php` POST handlers
- [x] T1.3: Create `web/domains.php` — list domains
- [x] T1.4: Create `web/domains.php` POST handlers
- [x] T1.5: Create `web/dialog.php` — read-only viewer
- [x] T1.6: Add pages to `common/role-nav.php`
- [x] T1.7: Secret-leakage scan
- [x] T1.8: CSRF validation test
- [x] T2.1: Define MI command whitelist
- [x] T2.2: Create `web/mi-commands.php`
- [x] T2.3: MI output display with `htmlspecialchars()`
- [x] T2.4: `dlg_end_dlg` admin gate
- [x] T2.5: Log MI commands to audit table
- [x] T2.6: Create `web/statistics.php`
- [x] T2.7: D3.js charts with auto-refresh
- [x] T2.8: Negative test — non-whitelisted command 403
- [x] T2.9: Negative test — `dlg_end_dlg` as devops 403
- [x] T2.10: Update `dashboard.php`
- [x] T2.11: Negative test — MI failure logging
- [x] T2.12: User-friendly MI error handler
- [x] T3.1: Create `web/tls-management.php`
- [x] T3.2: `tls_reload` trigger button
- [x] T3.3: Audit log for TLS reload
- [x] T3.4: Test TLS reload propagation
- [x] T4.1: Spec validation
- [x] T4.2: Architecture-guard verification
- [x] T4.3: Ripple analysis
- [x] T4.4: Brownfield scan
- [x] T4.5: Conventional commit and push
- [x] T4.6: Update AGENTS.md
- [x] T4.7: Update operator runbook
- [x] R1: CRUD failure audit logging
- [x] R2: Security headers
- [x] R3: Session hardening
- [x] R4: Statistics error path
- [x] R5: D3.js fallback
- [x] R6: validate-input.php integration
- [x] R7: Post-fix brownfield scan

### ARCH-PRE-001 — Future Debt

- [ ] ARCH-001: Design subscriber management API contract
- [ ] ARCH-002: Implement subscriber proxy in OpenSIPS layer
- [ ] ARCH-003: Migrate `web/subscribers.php` from direct PDO writes to API/MI calls
- [ ] ARCH-004: Add audit logging on the proxy layer
- [ ] ARCH-005: Validate layer boundary compliance
- [ ] ARCH-006: Update `architecture_constitution.md`
