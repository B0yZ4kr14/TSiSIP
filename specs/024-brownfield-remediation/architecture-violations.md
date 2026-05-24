# Architecture Violation Detection — Feature 024: Brownfield Remediation

**Date**: 2026-05-24  
**Focus**: general  
**Artifacts Reviewed**:
- specs/024-brownfield-remediation/spec.md
- specs/024-brownfield-remediation/plan.md
- specs/024-brownfield-remediation/memory-synthesis.md
- specs/024-brownfield-remediation/security-constraints.md
- .specify/memory/constitution.md
- .specify/memory/architecture_constitution.md
- .specify/memory/security_constitution.md

---

## Boundary Model

No new boundaries introduced. Changes are confined to:
- Control Plane (admin-api Dockerfile, OCP healthcheck)
- Test/Deploy scripts (infrastructure automation, not runtime)
- Configuration files (.env.example, docker-compose.vps.yml)

## Violation Categories

### A. Intent & Alignment
| Check | Finding | Status |
|---|---|---|
| Spec-plan divergence | Plan tasks map 1:1 to spec ACs (T1→AC1, T2→AC2, etc.) | PASS |
| Hallucinated abstractions | No new abstractions proposed | PASS |
| Spec-code mismatch | Not applicable (planning phase) | PASS |

### B. Boundaries & Layering
| Check | Finding | Status |
|---|---|---|
| Boundary erosion | No business logic changes | PASS |
| Isolation breach | No new cross-layer dependencies | PASS |
| Separation of concerns | Test/deploy changes remain in automation layer | PASS |

### C. Contracts & Consistency
| Check | Finding | Status |
|---|---|---|
| Missing contracts | No new inter-service contracts needed | PASS |
| Contract mismatch | No API or schema changes | PASS |
| Response drift | Not applicable | PASS |

### D. Coupling & Dependencies
| Check | Finding | Status |
|---|---|---|
| Tight coupling | No new module dependencies | PASS |
| Hidden coordination | No shared state introduced | PASS |

### E. Constitution & Security
| Check | Finding | Status |
|---|---|---|
| Constitution breach | None detected | PASS |
| Security-architecture conflict | SEC-024-01/02 are advisory, not blocking | PASS |

---

## Detailed Findings

### ARCH-P0-01 (PASS): Docker-first adherence
- All changes are within Dockerfiles or Docker Compose configuration.
- No bare-metal or VM-first paths proposed.

### ARCH-P0-02 (PASS): PostgreSQL-only adherence
- No database changes proposed.
- No db_mysql or db_sqlite references introduced.

### ARCH-P0-03 (PASS): Module validity
- No new OpenSIPS modules proposed.
- No sanity module references.

### ARCH-P0-04 (PASS): Network isolation
- No new published ports.
- OCP healthcheck verification (T7) confirms existing port binding remains correct.

### ARCH-P0-05 (PASS): SHA-pinned base images
- T1 fixes an existing violation (admin-api Dockerfile used unpinned image).
- This is a remediation, not a new violation.

### ARCH-P1-01 (REVIEW): HEALTHCHECK start_period
- T8 should specify `start_period: 60s` for services with slow initialization (backup, anomaly-detector).
- **Action**: Add start_period recommendation to T8 implementation notes.

### ARCH-P1-02 (REVIEW): Dynamic IP discovery error handling
- T4 proposes exiting on discovery failure. Ensure this does not break unattended automation (e.g., CI pipelines).
- **Action**: Document that CI must pre-create Docker networks before running deploy scripts.

---

## Pre-Existing Violations Impact

| ID | File | Original Violation | Status | Feature 024 Impact |
|---|---|---|---|---|
| ARCH-PRE-001 | web/subscribers.php | OCP writes to subscriber | Resolved by Feature 023 | None — no subscriber changes in 024 |

---

## Summary

| Severity | Count |
|---|---|
| P0 Blocking | 0 |
| P1 Review | 2 |
| P2 Advisory | 0 |

**Architecture Validation**: ✅ PASS  
**Conditions**: Address ARCH-P1-01 and ARCH-P1-02 during implementation  
**Constitution Compliance**: 100%
