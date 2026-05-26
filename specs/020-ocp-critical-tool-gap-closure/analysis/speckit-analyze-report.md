# Feature 020: OCP Critical Tool Gap Closure — Specification Analysis Report

**Analysis Date**: 2026-05-19T15:12:43-03:00
**Feature**: 020-ocp-critical-tool-gap-closure
**Status**: Completed
**Analyst**: speckit-analyze (read-only)

---

## Pre-Execution Notes

The `.specify/scripts/bash/check-prerequisites.sh` script returned `ok: false` with `missing: [""]`. This is a known tooling bug where an empty string is incorrectly pushed into the missing array despite all required files (`spec.md`, `plan.md`, `tasks.md`) being present. Proceeding with analysis since all artifacts are verified available.

---

## Findings

| ID | Category | Severity | Location(s) | Summary | Recommendation |
|----|----------|----------|-------------|---------|----------------|
| C1 | Constitution Alignment | CRITICAL | spec.md:FR-018-002 | FR-018-002 mandates retroactive migration of all specs 001–017 to feature-scoped FR-NNN-XXX IDs. Specs 011–013 and 015–016 contain zero explicit FR identifiers, making migration unfulfillable. | Add explicit FR-NNN-XXX identifiers to specs 011–013 and 015–016, or amend FR-018-002 scope. |
| H1 | Underspecification | HIGH | spec.md:AC3 | AC3 (statistics) requires 6 metrics with D3.js charts, but does not specify fallback behavior when MI HTTP is unreachable beyond "error state." | Define explicit fallback: freeze charts, show warning banner, defer next poll by 30s. |
| H2 | Inconsistency | HIGH | plan.md:W4.5 vs tasks.md:T4.6 | Plan says "Update AGENTS.md Section 16" but tasks.md says "Added Section 15." | Fix plan.md to reference the correct section number (15). |
| M1 | Coverage Gap | MEDIUM | spec.md:R9/R10 | R9 (MI error handling) and R10 (failed MI logging) are security requirements with no explicit AC mapped in the acceptance criteria table. | Add AC11 covering R9/R10 or merge into existing ACs with explicit traceability. |
| M2 | Terminology Drift | MEDIUM | tasks.md:AC8b | Traceability matrix references `AC8b (Audit failure logging)` but spec.md defines only AC1–AC10. | Remove AC8b row or rename to R10 to match spec taxonomy. |
| M3 | Duplication | MEDIUM | plan.md:W2.3 vs tasks.md:T2.8/T2.9/T2.11 | W2.3 negative test is split into T2.8 (whitelist rejection), T2.9 (admin gate), T2.11 (MI failure logging). The plan only mentions W2.3 once. | Clarify in plan.md that W2.3 covers multiple negative test dimensions, or split into W2.3a, W2.3b, W2.3c. |
| L1 | Ambiguity | LOW | spec.md:AD-2 | "MI Command Whitelist" says whitelist is "defined in PHP code, not user-configurable" but does not specify where exactly (file path, array name). | Add reference: `web/mi-commands.php:$whitelist` or equivalent. |
| L2 | Redundancy | LOW | tasks.md:T4.1 | T4.1 says "All 10 ACs verified complete manually (no CLI available)." This is a meta-task, not an implementation task. | Move to plan.md validation section; remove from tasks.md or mark as process gate. |
| L3 | Style | LOW | spec.md:Overview | "covers only 16% of the official OpenSIPS Control Panel v9.3.6 tool set" — percentage is precise but lacks citation/source. | Add footnote referencing OCP-CROSS-ANALYSIS.md line or table. |

---

## Coverage Summary Table

| Requirement Key | Has Task? | Task IDs | Notes |
|-----------------|-----------|----------|-------|
| AC1 (dialog viewer) | ✅ | T1.5 | Covered |
| AC2 (MI commands) | ✅ | T2.1–T2.5 | Covered |
| AC3 (statistics) | ✅ | T2.6–T2.7 | Covered; H1 notes underspecification on fallback |
| AC4 (dialplan CRUD) | ✅ | T1.1–T1.2 | Covered |
| AC5 (domains CRUD) | ✅ | T1.3–T1.4 | Covered |
| AC6 (TLS management) | ✅ | T3.1–T3.4 | Covered |
| AC7 (RBAC devops) | ✅ | T1.6, T2.2, T2.6, T3.1 | Covered |
| AC8 (CSRF) | ✅ | T1.2, T1.4, T1.8 | Covered |
| AC9 (security assessment) | ✅ | T0.1 | Covered |
| AC10 (threat model) | ✅ | T0.2 | Covered |
| R1 (requireRole devops) | ✅ | T1.6, T2.2, T2.6, T3.1 | Covered |
| R2 (CSRF token validation) | ✅ | T1.2, T1.4, T1.8 | Covered |
| R3 (PDO prepared statements) | ✅ | T1.2, T1.4 | Covered |
| R4 (MI whitelist) | ✅ | T2.1, T2.8 | Covered |
| R5 (read-only dialog) | ✅ | T1.5 | Covered |
| R6 (TLS admin role) | ✅ | T3.2, T2.4 | Covered |
| R7 (audit logging) | ✅ | T2.5, T3.3 | Covered |
| R8 (XSS prevention) | ✅ | T2.3 | Covered |
| R9 (MI error handling) | ⚠️ | T2.3, T2.12 | Partial — no dedicated AC; M1 |
| R10 (failed MI logged) | ⚠️ | T2.11 | Partial — no dedicated AC; M1 |

---

## Constitution Alignment Issues

| Principle | Status | Evidence |
|---|---|---|
| Docker-first | ✅ PASS | No bare-metal paths; reuses existing OCP container |
| PostgreSQL-only | ✅ PASS | All CRUD targets PostgreSQL tables |
| Module validity | ✅ PASS | No sanity/db_mysql; whitelisted MI commands are 3.6 docs |
| Secret hygiene | ✅ PASS | W0.5 scan + T1.7 secret-leakage scan passed |
| Network isolation | ✅ PASS | No new networks/ports introduced |
| Precomputed HA1 | ✅ PASS | Feature 020 does not modify auth layer |
| Topology hiding | ✅ PASS | No changes to topology_hiding() |
| Explicit RTP management | ✅ PASS | No RTP changes in this feature |
| Spec-driven changes | ✅ PASS | spec.md + plan.md + tasks.md all present |

**No constitution violations found in Feature 020 scope.**

The CRITICAL finding C1 is a **cross-project constitution alignment issue** (FR-018-002 scope vs specs 011–016) and is **not a Feature 020 defect**. It is surfaced here because the analysis loads the constitution for validation.

---

## Unmapped Tasks

| Task ID | Description | Mapping Issue |
|---------|-------------|---------------|
| T4.1 | spec-validate on Feature 020 spec | Meta-validation task; not mapped to any AC (L2) |
| T4.5 | Conventional commit + push | Release task; not mapped to AC |
| T4.6 | Update AGENTS.md Section 15 | Documentation task; not mapped to AC |
| T4.7 | Update OPERATOR-RUNBOOK | Documentation task; not mapped to AC |
| R1–R7 | Wave 5 refactor tasks | Post-implementation hardening; not mapped to ACs (by design) |

All unmapped tasks are either process/meta tasks or post-implementation hardening. No orphaned implementation work.

---

## Metrics

| Metric | Value |
|---|---|
| Total Requirements (ACs + Rs) | 20 |
| Total Tasks | 49 (incl. Wave 5) |
| Coverage % (requirements with ≥1 task) | 100% (18/18 explicit); R9/R10 have partial coverage |
| Ambiguity Count | 1 (L1) |
| Duplication Count | 1 (M3 — plan vs tasks split) |
| Critical Issues Count | 1 (C1 — cross-project, not Feature 020 specific) |
| HIGH Issues Count | 2 (H1, H2) |
| MEDIUM Issues Count | 3 (M1, M2, M3) |
| LOW Issues Count | 3 (L1, L2, L3) |

---

## Next Actions

1. **Fix H2**: Update plan.md W4.5 to reference "AGENTS.md Section 15" (not 16) — 1-line fix.
2. **Fix M2**: Remove or rename `AC8b` row in tasks.md traceability matrix to `R10` — 1-line fix.
3. **Address M1**: Add explicit AC11 for MI error handling + failure logging, or expand AC2/AC3 to cover R9/R10.
4. **Address H1**: Add explicit fallback specification to AC3 statistics (freeze charts, warning banner, 30s deferral).
5. **C1**: Escalate to project-level — Feature 018 scope may need amendment, or specs 011–016 need backfill.

**Verdict**: Feature 020 may proceed to closure. All CRITICAL/HIGH issues are either cross-project (C1) or minor documentation fixes (H1, H2). No implementation blockers.

---

## Extension Hooks

No `.specify/extensions.yml` found. Skipping hook checks.

---

*Report generated by speckit-analyze*
