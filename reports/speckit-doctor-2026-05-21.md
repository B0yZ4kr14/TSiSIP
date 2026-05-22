# Speckit Doctor Report — 2026-05-21

> Project health check for Spec-Driven Development structure, artifact completeness, and Speckit hygiene.

## Scan Metadata

| Field | Value |
|---|---|
| **Specs found** | 17 directories + 1 stray file |
| **Specs with spec.md** | 17/17 (100%) |
| **Specs with plan.md** | 17/17 (100%) |
| **Specs with tasks.md** | 17/17 (100%) |
| **Specs with checklists/** | 9/17 |
| **Stray artifacts** | 1 |

---

## Findings

### D1 — LOW — Stray File in specs/ Directory

| Field | Value |
|---|---|
| **File** | `specs/orchestrated-014c-008sg-plan.md` |
| **Issue** | File exists directly under `specs/`, not inside a numbered feature directory. |
| **Impact** | Breaks the `specs/{NNN-feature-name}/` convention. Confuses automated tooling that expects directories. |
| **Recommendation** | Move to `specs/017-sip-trunk-provider-integration/plan.md` or archive to `specs/_archive/` if superseded. |

---

## Healthy Checks — PASS

| Check | Result |
|---|---|
| All specs have `spec.md` | PASS |
| All specs have `plan.md` | PASS |
| All specs have `tasks.md` | PASS |
| No orphaned spec directories | PASS |
| `.specify/` structure intact | PASS |
| Extensions configured | PASS |

---

**Next check**: After next feature spec creation or directory restructuring.
