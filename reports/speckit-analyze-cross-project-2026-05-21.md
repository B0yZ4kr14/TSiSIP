# Cross-Project Specification Analysis Report — 2026-05-21

> Non-destructive consistency and quality analysis across all 17 feature specs, plans, and tasks.
> Authority: `.specify/memory/constitution.md` v1.1.0

## Executive Summary

| Metric | Value |
|---|---|
| Total features | 17 |
| Specs with status | 7/17 |
| Specs with plan.md | 17/17 (100%) |
| Specs with tasks.md | 7/17 (41%) |
| Critical issues | 1 |
| High issues | 2 |
| Medium issues | 4 |
| Low issues | 2 |

---

## Findings

### A1 — CRITICAL — Feature Number Collision

| Field | Value |
|---|---|
| **ID** | A1 |
| **Category** | Inconsistency |
| **Severity** | CRITICAL |
| **Location** | `specs/` directory |
| **Finding** | Three distinct features share the number `014`: `014-auto-tls-certificate-rotation`, `014-ocp-audit-log-compliance`, `014-sip-trunk-provider-integration`. |
| **Impact** | Breaks the `specs/{NNN-feature}/` naming convention. Causes ambiguity in cross-references, commit messages, and OMK goal tracking. Makes it impossible to uniquely identify "Feature 014" in conversation. |
| **Recommendation** | Renumber two of the three features to unique IDs (e.g., 014 → auto-tls, 015 → audit-log, 016 → sip-trunk). Update all internal references, runbook mentions, and OMK goals. |

---

### A2 — HIGH — Missing tasks.md for 10 Early Features

| Field | Value |
|---|---|
| **ID** | A2 |
| **Category** | Coverage Gap |
| **Severity** | HIGH |
| **Location** | `specs/001-010/` |
| **Finding** | Features 001–010 have `spec.md` and `plan.md` but zero `tasks.md` files. These specs were created before the Speckit SDD workflow was fully adopted. |
| **Impact** | No traceability from requirements to implementation tasks. Cannot run `/speckit-implement` on these features. Cannot verify completion via task checklist. Brownfield remediation (Feature 013) had to be tracked ad-hoc. |
| **Recommendation** | Retroactively generate `tasks.md` for features 001–010 by decomposing their existing `plan.md` waves into tasks. Priority: 001 (foundation), 008 (security governance), 009 (deploy automation — still Draft). |

---

### A3 — HIGH — Feature 009 Stuck in Draft Without Tasks

| Field | Value |
|---|---|
| **ID** | A3 |
| **Category** | Coverage Gap |
| **Severity** | HIGH |
| **Location** | `specs/009-vps-deploy-automation/` |
| **Finding** | Status is `Draft` in `spec.md`, `plan.md` exists, but `tasks.md` is empty (0 tasks). The deploy pipeline (`deploy/scripts/orchestrate-deploy.sh`) is already implemented and running in production. |
| **Impact** | The implementation outpaced the specification. The spec does not document the actual pipeline gates (0–5), rollback behavior, or CLI flags that are already committed and deployed. |
| **Recommendation** | Update `spec.md` status to `Implemented` or `Complete`. Generate `tasks.md` retroactively from the committed deploy script. Or, if the spec is intended for a v2 enhancement, create a new feature spec (e.g., 009-v2) and archive the current one. |

---

### A4 — MEDIUM — Requirement IDs Are Not Globally Unique

| Field | Value |
|---|---|
| **ID** | A4 |
| **Category** | Inconsistency |
| **Severity** | MEDIUM |
| **Location** | All `specs/*/spec.md` |
| **Finding** | `FR-001` appears in 11 different specs with 11 different meanings. `SC-002` appears in 11 specs. There is no project-wide requirement ID namespace. |
| **Impact** | Cross-feature references are ambiguous. A comment like "See FR-003" is meaningless without feature context. Compliance traceability (e.g., SOC 2 auditor asking for "FR-005 evidence") requires manual disambiguation. |
| **Recommendation** | Adopt a global ID scheme: `014-FR-003` or `FR-014-003`. Apply retroactively to active features (011–014) and going forward. Document the scheme in `constitution.md` Documentation Standards. |

---

### A5 — MEDIUM — 7 Features Lack Explicit Status

| Field | Value |
|---|---|
| **ID** | A5 |
| **Category** | Underspecification |
| **Severity** | MEDIUM |
| **Location** | `specs/004-007/`, `specs/012-014/` (excluding 014-audit) |
| **Finding** | Specs for 004, 005, 006, 007, 012, 013, 014-auto-tls, 014-sip-trunk have no `**Status**:` line. |
| **Impact** | Cannot determine at a glance whether these features are Draft, In Progress, Implemented, or Complete. The project status dashboard (`STATUS.md`) may be the only source of truth, creating a single point of failure. |
| **Recommendation** | Add a `**Status**:` line to every spec.md following the convention used in 001, 002, 008, 010, 014-audit. Possible states: `Draft`, `Specified`, `In Progress`, `Implemented`, `Complete`. |

---

### A6 — MEDIUM — Inconsistent Plan Wave Structure

| Field | Value |
|---|---|
| **ID** | A6 |
| **Category** | Inconsistency |
| **Severity** | MEDIUM |
| **Location** | `specs/*/plan.md` |
| **Finding** | Plan waves use inconsistent naming and granularity:
- 001: No waves (legacy format)
- 002: No waves (legacy format)
- 003–010: No waves (legacy format)
- 011: 10 waves (very granular)
- 012: 10 waves
- 013: 7 waves
- 014-auto-tls: 5 waves
- 014-audit: 6 waves
- 014-sip-trunk: 0 waves |
| **Impact** | Makes it hard to compare effort across features. A "wave" in 011 is not comparable to a "wave" in 014-audit. |
| **Recommendation** | Standardize wave sizing in the constitution or plan-template.md. Suggested: 4–6 waves per feature, each representing a deployable increment. |

---

### A7 — MEDIUM — Terminology Drift: "host-level"

| Field | Value |
|---|---|
| **ID** | A7 |
| **Category** | Ambiguity |
| **Severity** | MEDIUM |
| **Location** | `specs/003/spec.md:L59`, `specs/005/spec.md:L148`, `specs/006/spec.md:L134` |
| **Finding** | The term "host-level" is used in three different senses:
- 003: "host-level metrics" = node-exporter CPU/memory metrics (container runtime context)
- 005: "host-level backup" = encryption key backup outside container (disaster recovery)
- 006: "host-level SIP processes" = OS-native SIP daemons competing with Docker |
| **Impact** | Ambiguous whether "host-level" violates the Docker-first principle. In 006 it is explicitly rejected; in 003 and 005 it is accepted but not clearly justified. |
| **Recommendation** | Disambiguate in each spec: use "node-level metrics" (003), "off-container secret backup" (005), and keep "host-level processes" (006) with explicit constitution reference. |

---

### A8 — LOW — Feature 014-sip-trunk Has No Implementation Artifacts

| Field | Value |
|---|---|
| **ID** | A8 |
| **Category** | Coverage Gap |
| **Severity** | LOW |
| **Location** | `specs/014-sip-trunk-provider-integration/` |
| **Finding** | `spec.md` exists, `plan.md` exists, but `tasks.md` is empty (0 tasks). No implementation files referenced. |
| **Impact** | Feature is specified but not planned or tracked. Risk of scope creep or abandonment. |
| **Recommendation** | Either generate `tasks.md` and begin implementation, or move to `specs/_backlog/` if deprioritized. |

---

### A9 — LOW — Stray File in specs/

| Field | Value |
|---|---|
| **ID** | A9 |
| **Category** | Inconsistency |
| **Severity** | LOW |
| **Location** | `specs/orchestrated-014c-008sg-plan.md` |
| **Finding** | A loose file exists directly under `specs/`, not inside a numbered directory. Already flagged by speckit-doctor (D1). |
| **Impact** | Breaks directory conventions. |
| **Recommendation** | Move to `specs/014-sip-trunk-provider-integration/plan-orchestrated.md` or archive. |

---

## Coverage Summary

| Feature | Status | Has Plan | Has Tasks | Task Completion | Gap |
|---|---|---|---|---|---|
| 001-opensips-docker-edge-proxy | Completed | ✅ | ❌ | N/A | A2 |
| 002-tsisip-ocp-rebrand | Implemented | ✅ | ❌ | N/A | A2 |
| 003-prometheus-grafana-observability | Partial | ✅ | ❌ | N/A | A2 |
| 004-health-checks-autohealing | (none) | ✅ | ❌ | N/A | A2, A5 |
| 005-postgresql-backup-restore | (none) | ✅ | ❌ | N/A | A2, A5 |
| 006-rate-limiting-ddos-protection | (none) | ✅ | ❌ | N/A | A2, A5 |
| 007-tls-srtp-encryption | (none) | ✅ | ❌ | N/A | A2, A5 |
| 008-devsecops-deployment | Complete | ✅ | ❌ | N/A | A2 |
| 009-vps-deploy-automation | Draft | ✅ | ❌ | N/A | A2, A3 |
| 010-ocp-navigation-system-links | Implemented | ✅ | ❌ | N/A | A2 |
| 011-ocp-forced-password-change | (none) | ✅ | ✅ | 20/20 | A5 |
| 012-ocp-admin-tools-restoration | (none) | ✅ | ✅ | 60/60 | A5 |
| 013-brownfield-follow-up | (none) | ✅ | ✅ | 20/20 | A5 |
| 014-auto-tls-certificate-rotation | (none) | ✅ | ✅ | 31/31 | A1, A5 |
| 014-ocp-audit-log-compliance | Specified | ✅ | ✅ | 38/38 | A1 |
| 014-sip-trunk-provider-integration | (none) | ✅ | ❌ | N/A | A1, A5, A8 |

## Constitution Alignment

| Principle | Violations | Notes |
|---|---|---|
| Docker-first | 0 | All "bare-metal" references are in Rejected Patterns or justified contexts |
| PostgreSQL-only | 0 | `db_mysql` only appears in Rejected Patterns |
| OpenSIPS 3.6 LTS | 0 | Consistently referenced across all specs |
| Precomputed HA1 | 0 | Mentioned in 001 spec and AGENTS.md |
| Topology hiding | 0 | Referenced in 001 and 014-sip-trunk specs |

**Constitution alignment: PASS** — No CRITICAL constitution violations detected.

## Metrics

- Total Requirements (FRs across all specs): ~120+
- Total Tasks: 169 (38 + 31 + 20 + 60 + 20)
- Coverage % (specs with tasks): 41% (7/17)
- Ambiguity Count: 1 (A7 — "host-level")
- Duplication Count: 1 (A1 — feature number collision)
- Critical Issues Count: 1 (A1)

---

## Next Actions

1. **Resolve A1 (CRITICAL)**: Renumber colliding 014 features before any cross-feature references solidify.
2. **Resolve A2 (HIGH)**: Retroactively generate `tasks.md` for features 001–010. Start with 009 (Draft → Implemented).
3. **Resolve A3 (HIGH)**: Update 009 spec status and align with committed deploy pipeline.
4. **Adopt A4 (MEDIUM)**: Establish global requirement ID scheme in constitution.
5. **Resolve A5 (MEDIUM)**: Add `**Status**:` lines to all specs without them.

Would you like me to suggest concrete remediation edits for the top 3 issues (A1, A2, A3)?
