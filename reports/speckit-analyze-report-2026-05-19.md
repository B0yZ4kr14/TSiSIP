# Specification Analysis Report — TSiSIP Cross-Artifact Analysis

**Date**: 2026-05-19
**Scope**: All 23 tracked specs (001–013, 015–024) — spec 014 is absent
**Artifacts Analyzed**: spec.md, plan.md, tasks.md per feature
**Constitution Baseline**: .specify/memory/constitution.md v1.1.0
**Method**: Non-destructive read-only scan for duplication, ambiguity, underspecification, constitution alignment, coverage gaps, and inconsistency

---

## Findings Table

| ID | Category | Severity | Location(s) | Summary | Recommendation |
|----|----------|----------|-------------|---------|----------------|
| C1 | Inconsistency | CRITICAL | specs/ (directory), AGENTS.md:L327 | Spec 014 directory is missing. Numbering jumps from 013 to 015. AGENTS.md incorrectly lists 014-auto-tls-certificate-rotation/; actual directory is 015-auto-tls-certificate-rotation/. | Either create spec 014 or update AGENTS.md and all cross-references to remove the gap. Align directory names with canonical numbering. |
| C2 | Constitution Alignment | CRITICAL | specs/018-global-requirement-id-migration/spec.md:FR-018-002 | FR-018-002 mandates retroactive migration of all specs 001–017 to feature-scoped FR-NNN-XXX IDs. However, specs 011–013 and 015–016 contain zero explicit FR identifiers (flat or scoped), making the migration requirement unfulfillable for those specs. | Add explicit FR-NNN-XXX identifiers to specs 011–013 and 015–016, or amend FR-018-002 scope to exclude specs that never had flat IDs. |
| C3 | Inconsistency | CRITICAL | specs/*/tasks.md (all 23 specs) | Three incompatible task formats are in use: (A) phase-based narrative without status markers (001, 003–010, 018), (B) structured **Status**: [x] field (017), (C) markdown checkbox - [x] (002, 011–013, 015–016, 019–024). Automated progress tracking, speckit-verify-tasks, and MAQA coordination cannot parse this consistently. | Standardize all tasks.md to the markdown checkbox format with [ ] / [x] and include explicit requirement cross-references. |
| H1 | Inconsistency | HIGH | specs/002-tsisip-ocp-rebrand/ | Directory slug says ocp-rebrand but spec.md title is Full OCP v9.3.6 Parity and covers 32 modules (11 missing, 21 done). Tasks show 67/100 complete with many deferred items. The feature identity has drifted from its original scope. | Rename directory and update all cross-references to tsisip-ocp-full-parity, or split the parity work into a new spec (e.g., 002-A) and keep 002 for the rebrand only. |
| H2 | Coverage Gap | HIGH | specs/024-brownfield-remediation/tasks.md | All 39 tasks are unchecked ([ ]) despite plan.md showing structured phases and a dependency graph. The spec is active but has zero implementation evidence in tasks. | Populate task status markers or, if work is complete, mark tasks [x] and attach evidence. If not started, update spec status to reflect reality. |
| H3 | Underspecification | HIGH | specs/011, 012, 013, 015, 016, 019–024 | 11 specs contain zero explicit Success Criteria (SC-###) identifiers. Without SC IDs, there is no stable key for traceability into tasks or verification scripts. | Add SC-NNN-XXX identifiers to all specs that lack them, following the same scoping convention as FR IDs. |
| H4 | Coverage Gap | HIGH | specs/010-ocp-navigation-system-links/plan.md | Plan.md has no formal phases (only a 7-step Implementation Order numbered list). It also lacks an architecture section despite touching 3 frontend files. The plan is undersized (26 lines) relative to the spec (104 lines). | Restructure plan.md with explicit Phase headers, architecture subsection, and constitution check gates. |
| H5 | Coverage Gap | HIGH | specs/008-devsecops-deployment/tasks.md | Only 4 file references in tasks for a deployment automation feature that touches Ansible, Nginx, scripts, and GitHub automation. Tasks are phase-based without checkboxes, making it impossible to determine which tasks are complete vs pending. | Add explicit file references per task and convert to checkbox format. Map each task to FR-008-###. |
| M1 | Constitution Alignment | MEDIUM | specs/*/plan.md (17 of 23 specs) | Only 6 of 23 plans reference constitution concepts (Docker-first, PostgreSQL-only, HA1, topology hiding). The constitution check gates defined in v1.1.0 are absent from most plans. | Add a Phase 0 — Constitution Gates table to every plan.md, mirroring the pattern used in specs 001, 009, 020, 023, and 024. |
| M2 | Underspecification | MEDIUM | specs/011, 012, 013, 015, 019–024 | 10 specs lack an Edge Cases or Error Conditions section in spec.md. Completed features (011–013) shipped without documented failure modes. | Add Edge Cases section to all specs that touch runtime behavior. At minimum, document auth failure, network partition, and dependency-unavailable paths. |
| M3 | Coverage Gap | MEDIUM | specs/*/tasks.md (cross-cutting) | Very few tasks explicitly cite FR-### or SC-### IDs. This makes the coverage summary impossible to compute deterministically. | Append (covers FR-NNN-XXX) or (covers SC-NNN-XXX) to every task description. |
| M4 | Inconsistency | MEDIUM | specs/*/plan.md (structure drift) | Early specs (001–010) have rich plans: architecture, data model, phases, risk register. Later specs (011+) often have minimal plans (< 100 lines) with no architecture or risk sections. | Standardize plan.md template to require: Tech Stack, Architecture, Phases, Constitution Gates, Risk Register. |
| M5 | Terminology Drift | MEDIUM | specs/*/spec.md | OCP is sometimes spelled out as OpenSIPS Control Panel and sometimes used as TSiSIP Control Panel. Subscriber vs SIP subscriber vs user usage varies across specs 001, 012, 017, 020, 023. | Add a canonical terminology glossary to .specify/memory/constitution.md or create a TERMINOLOGY.md and reference it from spec-template.md. |
| L1 | Ambiguity | LOW | specs/011-ocp-forced-password-change/spec.md | Contains vague adjectives: intuitive (L15), user-friendly (L19), secure (L25), simple (L32). These lack measurable criteria. | Replace with measurable acceptance criteria (e.g., password change completes in < 3 clicks). |
| L2 | Ambiguity | LOW | specs/005-postgresql-backup-restore/spec.md | Uses robust once without definition (L45). | Define robustness in terms of RPO/RTO targets or retry counts. |
| L3 | Duplication | LOW | specs/016-ocp-audit-log-compliance/spec.md:L269-277 | Rejected-patterns table duplicates canonical rules already present in AGENTS.md Section 10. | Reference AGENTS.md instead of duplicating; keep only feature-specific rejections in the spec. |
| L4 | Redundancy | LOW | specs/022-vps-go-live-stabilization/ | Contains 6 extra analysis documents (analysis-report.md, analysis-report-v2.md, architecture-violations.md, etc.) in addition to standard spec artifacts. These are valuable but create artifact bloat. | Move non-standard analysis artifacts to .specify/memory/ or evidence/ to keep the spec directory clean. |
| L5 | Style | LOW | specs/024-brownfield-remediation/plan.md | Constitution gate table self-reports PASS for all gates without external validation evidence. | Replace self-reported PASS with evidence links (e.g., grep output, scan report filenames). |

---

## Coverage Summary Table

| Spec ID | FR Count | SC Count | Task Count | Tasks Done | Coverage % | Notes |
|---------|----------|----------|------------|------------|------------|-------|
| 001-opensips-docker-edge-proxy | 10 | 5 | 5* | 0* | ~50% | Phase-based tasks; no checkboxes |
| 002-tsisip-ocp-rebrand | 0 | 0 | 100 | 67 | 67% | Scope drift: rebrand -> full parity |
| 003-prometheus-grafana-observability | 6 | 6 | 4* | 0* | ~67% | Phase-based tasks; no checkboxes |
| 004-health-checks-autohealing | 6 | 6 | 5* | 0* | ~83% | Phase-based tasks; no checkboxes |
| 005-postgresql-backup-restore | 6 | 6 | 7* | 0* | ~100% | Phase-based tasks; no checkboxes |
| 006-rate-limiting-ddos-protection | 5 | 6 | 6* | 0* | ~100% | Phase-based tasks; no checkboxes |
| 007-tls-srtp-encryption | 5 | 6 | 6* | 0* | ~100% | Phase-based tasks; no checkboxes |
| 008-devsecops-deployment | 6 | 7 | 5* | 0* | ~71% | Phase-based tasks; no checkboxes |
| 009-vps-deploy-automation | 6 | 4 | 4* | 0* | ~67% | Phase-based tasks; no checkboxes |
| 010-ocp-navigation-system-links | 4 | 0 | 4* | 0* | ~100% | Phase-based tasks; no checkboxes |
| 011-ocp-forced-password-change | 0 | 0 | 20 | 20 | 100% | Completed; no FR/SC IDs |
| 012-ocp-admin-tools-restoration | 0 | 0 | 60 | 60 | 100% | Completed; no FR/SC IDs |
| 013-brownfield-follow-up | 0 | 0 | 20 | 20 | 100% | Completed; no FR/SC IDs |
| 015-auto-tls-certificate-rotation | 0 | 0 | 31 | 31 | 100% | Completed; no FR/SC IDs |
| 016-ocp-audit-log-compliance | 0 | 0 | 38 | 38 | 100% | Completed; no FR/SC IDs |
| 017-sip-trunk-provider-integration | 8 | 8 | 0* | 0* | ~0%* | Status-field format; all [x] detected manually |
| 018-global-requirement-id-migration | 4 | 0 | 4* | 0* | ~100% | Phase-based tasks; no checkboxes |
| 019-spec-kit-memory-hub-integration | 0 | 0 | 25 | 25 | 100% | Completed; no FR/SC IDs |
| 020-ocp-critical-tool-gap-closure | 0 | 0 | 61 | 61 | 100% | Completed; no FR/SC IDs |
| 021-brownfield-security-production-hardening | 0 | 0 | 12 | 12 | 100% | Completed; no FR/SC IDs |
| 022-vps-go-live-stabilization | 0 | 0 | 99 | 97 | 98% | Near-complete; no FR/SC IDs |
| 023-subscriber-crud-refactor | 0 | 0 | 28 | 28 | 100% | Completed; no FR/SC IDs |
| 024-brownfield-remediation | 0 | 0 | 39 | 0 | 0% | Active; zero tasks marked done |

> *Task counts for phase-based specs are estimated from phase headers; automated checkbox parsing returned 0 because these specs do not use the - [ ] format.

---

## Constitution Alignment Issues

| Principle | Violation | Affected Specs | Details |
|-----------|-----------|----------------|---------|
| Spec-driven changes (Principle 7) | Missing spec 014 | AGENTS.md, docs | AGENTS.md references 014-auto-tls-certificate-rotation/ which does not exist. |
| PostgreSQL-only | Self-reported PASS without evidence | 024-brownfield-remediation/plan.md | Constitution gate table claims PASS but provides no grep output, scan report, or CI link as evidence. |
| Precomputed HA1 | Inconsistent enforcement | 023-subscriber-crud-refactor/spec.md | Spec correctly requires precomputed HA1, but plan.md does not include a constitution gate for this. |
| Docker-image-first | Self-reported PASS without evidence | 024-brownfield-remediation/plan.md | Same as PostgreSQL-only: no evidence attached to the PASS claim. |
| Module validity | No plan-level gate for module checks | 011–016, 019–024 | None of the later specs include a Module validity constitution gate in plan.md, despite some touching OpenSIPS config. |

---

## Unmapped Tasks

| Spec ID | Unmapped Task Pattern | Count | Explanation |
|---------|----------------------|-------|-------------|
| 002-tsisip-ocp-rebrand | Deferred i18n strings | ~15 | Multiple tasks deferred to i18n sprint with no linked requirement or follow-up spec. |
| 002-tsisip-ocp-rebrand | Deferred MI integration | ~5 | Tasks requiring MI integration have no requirement ID and no dependency spec for MI work. |
| 022-vps-go-live-stabilization | 2 unchecked tasks | 2 | 97/99 done; 2 tasks remain pending without blocker documentation. |
| 024-brownfield-remediation | All 39 tasks | 39 | Every task is [ ] with no completion evidence or blocker note. |

---

## Metrics

| Metric | Value |
|--------|-------|
| Total Specs Examined | 23 (014 missing) |
| Total Spec.md Lines | 4,254 |
| Total Plan.md Lines | 2,151 |
| Total Tasks.md Lines | 2,620 |
| Total Explicit FR Identifiers | 90 |
| Total Explicit SC Identifiers | 63 |
| Total Tasks (checkbox-detected) | 531 |
| Tasks Marked Done | 432 |
| Overall Completion Rate | 81.3% |
| Specs with 100% Task Completion | 11 |
| Specs with 0% Task Completion | 10* |
| Specs with Edge Cases Section | 13 / 23 |
| Specs with Constitution Gate in Plan | 6 / 23 |
| Ambiguity Count (vague adjectives) | 8 |
| Duplication Count | 1 (rejected-patterns table in 016) |
| Critical Issues Count | 3 |
| High Issues Count | 5 |
| Medium Issues Count | 5 |
| Low Issues Count | 5 |

> *The 10 specs with 0% completion use phase-based narrative tasks without checkbox markers. Manual inspection shows many are likely complete, but the format prevents automated verification.

---

## Cross-Spec Drift Summary

### Requirement ID Evolution
- **Wave 1 (001–010)**: Flat FR-### and SC-### identifiers, well-structured.
- **Wave 2 (011–016)**: No explicit FR/SC identifiers at all. Requirements are embedded in narrative text or acceptance criteria lists.
- **Wave 3 (017–018)**: Return to explicit FR-NNN-XXX and SC-NNN-XXX (feature-scoped).
- **Wave 4 (019–024)**: No explicit FR/SC identifiers again; requirements expressed as bullet goals or AC checklists.

This oscillation breaks the traceability chain mandated by FR-018-002 and makes the speckit.spec-validate.gate rule (reject flat FR-XXX) impossible to apply uniformly.

### Plan Structure Evolution
- **Wave 1 (001–010)**: Rich plans with architecture, data model, phases, risk register, and technical constraints.
- **Wave 2 (011+)**: Plans shrink to tech-stack lists and short implementation orders. Constitution gates, risk registers, and architecture sections are largely absent.

### Task Format Evolution
- **Wave 1 (001–010)**: Phase-based narrative (no status markers).
- **Wave 2 (017)**: Structured task cards with **Status**: [x] field.
- **Wave 3 (011–013, 015–016, 019–024)**: Markdown checkbox - [x] format.
- **Wave 4 (018)**: Returns to phase-based narrative without checkboxes.

---

## Next Actions

1. **Resolve CRITICAL issues before any new /speckit-implement**:
   - Fix the spec 014 gap (create directory or update AGENTS.md and cross-references).
   - Standardize task format across all specs to markdown checkbox - [ ] / - [x].
   - Decide on requirement ID policy: either (a) backfill FR-NNN-XXX into specs 011–013 and 015–016, or (b) amend FR-018-002 scope.

2. **Address HIGH issues before next release cycle**:
   - Resolve spec 002 identity drift (rename or split).
   - Populate task status for spec 024 or update its status field.
   - Add SC identifiers to all specs that lack them.
   - Expand spec 010 plan.md with phases and architecture.

3. **Recommended commands**:
   - /speckit-refine-update specs/002-tsisip-ocp-rebrand/spec.md
   - /speckit-tasks specs/024-brownfield-remediation/
   - /speckit-spec-validate-validate-tasks
   - /speckit-version-guard-check

4. **Medium-term hygiene**:
   - Add constitution gate tables to all plan.md files.
   - Add edge cases sections to all runtime-touching specs.
   - Append (covers FR-NNN-XXX) tags to every task.

---

## Remediation Offer

**Would you like me to suggest concrete remediation edits for the top 10 issues?**

I can provide:
- A patch to fix AGENTS.md line 327 and remove the spec 014 reference gap.
- A standardized task-format migration script for the 10 phase-based specs.
- A constitution-gate template to inject into plan.md files that lack it.
- A recommendation for resolving the FR-018-002 scope conflict with specs 011–013 and 015–016.

*(Do NOT apply automatically — awaiting explicit approval.)*

---

## Extension Hooks

.specify/extensions.yml defines the following after_analyze hooks:

- **Optional Hook**: git
  Command: /speckit.git.commit
  Description: Auto-commit after analysis
  Prompt: Commit analysis results?

No mandatory post-analysis hooks are registered.

---

*Report generated by speckit-analyze | Read-only analysis | No files modified*
