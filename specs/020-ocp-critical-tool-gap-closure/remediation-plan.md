# Remediation Plan — Feature 020 Architecture-Verify Findings

**Date**: 2026-05-21
**Source**: Architecture Verification Report V1–V3
**Preset**: Security Governance

---

## Findings Summary

| ID | Category | Severity | Finding |
|:---|:---|:---|:---|
| V1 | Data Isolation | MEDIUM | dialplan, domain, dialog tables lack tenant_id |
| V2 | Performance | LOW | statistics.php auto-refreshes every 30s without caching |
| V3 | UX | LOW | tls-management.php triggers tls_reload without secondary confirmation |

---

## MSL Applicability

| Finding | MSL Status | Justification |
|---|---|---|
| V1 | MSL-Relevant | Multi-tenant data isolation affects subscriber data boundaries |
| V2 | Non-MSL | Performance optimization, no memory-safety or data-privacy impact |
| V3 | Non-MSL | UX hardening, no security boundary change |

---

## Secure-Development Verification

- V1 requires schema migration with IF NOT EXISTS idempotency
- V2 requires no new dependencies (client-side debounce only)
- V3 requires no new dependencies (JavaScript confirm() or modal)

---

## Remediation Tasks

### Wave 1: Tenant Scoping (V1)

**T1.1**: Evaluate OpenSIPS module compatibility for tenant-scoped dialplan/domain/dialog
- **File**: docs/architecture/020-tenant-scoping-evaluation.md
- **Decision**: Whether to (a) add tenant_id columns and update module queries, (b) create PostgreSQL row-level security policies, or (c) document as accepted global scope

**T1.2**: If scoped — update 04-ocp-tools-schema.sql with tenant_id UUID columns and indexes
- **Files**: db/init/04-ocp-tools-schema.sql
- **Constraint**: Must remain idempotent (IF NOT EXISTS, ON CONFLICT)

**T1.3**: If scoped — update PHP CRUD pages to filter by tenant_id
- **Files**: web/dialplan.php, web/domains.php, web/dialog.php
- **Constraint**: PDO prepared statements only; derive tenant_id from authenticated session

**Security Checkpoint SR-V1**: After T1.3, verify no cross-tenant leakage via grep for missing tenant_id in WHERE clauses

### Wave 2: Statistics Caching (V2)

**T2.1**: Add server-side metric cache (5-second TTL) in statistics.php
- **File**: web/statistics.php
- **Approach**: File-based cache in /tmp/tsisip-stats-cache.json with timestamp check

**T2.2**: Add client-side debounce (pause auto-refresh when tab hidden)
- **File**: web/statistics.php
- **Approach**: document.visibilityState check before fetch()

### Wave 3: TLS Confirmation (V3)

**T3.1**: Add confirmation modal before tls_reload
- **File**: web/tls-management.php
- **Approach**: JavaScript confirm() or inline modal requiring typed confirmation (e.g., "RELOAD")

---

## Dependency & Supply-Chain Evidence

- No new dependencies introduced
- No Docker image changes
- No new runtime credentials or env vars

---

## Security Review Checkpoints

| Checkpoint | Trigger | Gate Condition |
|---|---|---|
| SR-V1 | After T1.3 | All CRUD queries include tenant_id filter; zero raw SQL |
| SR-V2 | After T2.1 | Cache file is written to /tmp with www-data ownership; no credentials cached |
| SR-V3 | After T3.1 | Reload cannot trigger via accidental single click; CSRF token still required |

---

## Traceability

| Finding | Task(s) | Acceptance Criteria |
|---|---|---|
| V1 | T1.1–T1.3 | Tenant isolation enforced or explicitly documented as global-by-design |
| V2 | T2.1–T2.2 | MI HTTP polling reduced by >=50% under normal dashboard usage |
| V3 | T3.1 | Accidental reload requires explicit user confirmation |
