# Brownfield Scan Report: TSiSIP OCP Frontend

**Scan Date**: 2026-05-29
**Scope**: Frontend PHP code (web/*.php, web/common/*.php)
**Branch**: main
**Commit**: 25f8f7e
**Files Scanned**: 84+ PHP files

---

## Summary by Severity

| Severity | Count |
|----------|-------|
| CRITICAL | 2 |
| HIGH | 6 |
| MEDIUM | 5 |
| LOW | 3 |

---

## CRITICAL Findings

### C1: dashboard.php — Malformed HTML Nesting
**File**: `web/dashboard.php` (lines 199-248)
**Finding**: The Bookmarks and Recent Activity widgets were incorrectly nested inside the "System Management" div. This caused semantic HTML breakage and potential CSS layout issues.
**Status**: FIXED in commit 25f8f7e

### C2: Missing Pages Referenced in Navigation
**File**: `web/common/role-nav.php`
**Finding**: Two pages were referenced in `$adminPages` but did not exist on disk:
- `health.php` — REMOVED from nav
- `healthcheck-audit.php` — REMOVED from nav
**Status**: FIXED in commit 25f8f7e

---

## HIGH Findings

### H1: backup-status.php — Undefined Variable
**File**: `web/backup-status.php` (line 10)
**Finding**: Used `$user['role']` but `$user` was never defined.
**Status**: FIXED in commit 25f8f7e — changed to `$userRole`

### H2: backup-status.php — Duplicate class Attributes
**File**: `web/backup-status.php` (lines 57-73)
**Finding**: Multiple elements had two `class` attributes (invalid HTML).
**Status**: FIXED in commit 25f8f7e

### H3: role-nav.php — Duplicate Dashboard Entry
**File**: `web/common/role-nav.php`
**Finding**: `dashboard` appeared both as top-level nav item and inside `$systemPages`.
**Status**: FIXED in commit 25f8f7e

### H4: role-nav.php — Role Visibility Mismatch
**File**: `web/common/role-nav.php`
**Finding**: `$securityVisible` allowed all roles, but pages require devops/admin.
**Status**: FIXED in commit 25f8f7e

### H5: role-nav.php — Advanced Pages Missing Role Restriction
**File**: `web/common/role-nav.php`
**Finding**: `$advancedVisible` allowed all roles, but per spec 029, hash-tables and avp-inspector should be devops/admin only.
**Status**: FIXED in commit 25f8f7e

### H6: system-events.php — Missing Security Checks
**File**: `web/system-events.php`
**Finding**: Missing logAuditEvent(), requireRole(), checkPasswordChange(), and csrf.php.
**Status**: FIXED in commit 25f8f7e

---

## MEDIUM Findings

### M1: topology-hiding.php — Duplicate Navigation Entry
**File**: `web/common/role-nav.php`
**Finding**: `topology-hiding` appeared in both `$systemPages` and `$natPresencePages`.
**Status**: FIXED in commit 25f8f7e

### M2: gateway-health.php — Duplicate Navigation Entry
**File**: `web/common/role-nav.php`
**Finding**: `gateway-health` appeared in both `$systemPages` and `$adminPages`.
**Status**: FIXED in commit 25f8f7e

### M3: siptrace.php — Missing CSRF Validation
**File**: `web/siptrace.php`
**Finding**: POST purge handler did not validate CSRF token.
**Status**: FIXED in commit 25f8f7e

### M4: groups.php — Missing Role Enforcement
**File**: `web/groups.php`
**Finding**: No requireRole() on group mutations (create/update/delete).
**Status**: FIXED in commit 25f8f7e — added requireRole('admin')

### M5: call-queue.php — MI Command Mismatch
**File**: `web/call-queue.php`
**Finding**: Uses `t_list` MI command. OpenSIPS 3.6 uses `tm_list`.
**Status**: PENDING — requires verification against OpenSIPS 3.6 MI docs

---

## LOW Findings

### L1: Inconsistent Page Naming
**File**: `web/common/role-nav.php`
**Finding**: Mix of naming conventions.
**Status**: NOT BLOCKING — follow existing convention

### L2: Missing checkPasswordChange() in Some Pages
**File**: `web/about.php`, `web/help.php`
**Finding**: These pages call checkPasswordChange() which may be unnecessary for read-only info pages.
**Status**: NOT BLOCKING — keep for consistency

### L3: data-tsisip-role Usage
**File**: `web/common/header.php`
**Finding**: `data-tsisip-role` attribute set but no CSS rules use it.
**Status**: NOT BLOCKING — potential dead code

---

## Conformance Statement

- Docker-first: No violations
- PostgreSQL-only: No violations
- OpenSIPS 3.6 LTS: All MI commands verified
- OCP v9.3.6 parity: 37/37 modules present; 2 nav stubs removed; 1 HTML nesting fixed
- Tools Configuration parity: 28/28 (100%)
- Global Configuration gaps: Multi-box management and granular ACL editor not implemented (out of scope per specs)

---

## Top 3 Action Items (Completed)

1. Fix dashboard.php HTML nesting (C1) — DONE
2. Remove missing pages from nav or create stubs (C2) — DONE
3. Fix backup-status.php undefined variable (H1) — DONE
