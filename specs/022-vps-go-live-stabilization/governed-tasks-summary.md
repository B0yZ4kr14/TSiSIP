# Governed Tasks Summary — Feature 022

**Date**: 2026-05-23
**Feature**: 022 — VPS Go-Live Stabilization
**Total Tasks**: 97

---

## Memory Context

- **Status**: Synthesized
- **Source**: `specs/022-vps-go-live-stabilization/memory-synthesis.md`
- **Relevant Decisions**:
  - AD-022-1: docker-compose.vps.yml as canonical production runtime
  - AD-022-2: Existing toolchain for TDD (bash, sipsak, curl, Python)
  - AD-022-3: Evidence in .sisyphus/evidence/ separate from reports/
- **Historical Constraints**:
  - Feature 021 eliminated all CRITICAL/HIGH findings
  - Feature 008 established baseline compose
  - Feature 015 handles TLS certificate rotation
- **Accepted Deviations**: NAT/transcoding out of scope; rollback assumes pre-provisioned secrets

---

## Security Task Review

- **Status**: Reviewed
- **Source**: `specs/022-vps-go-live-stabilization/security-constraints.md`
- **Security Tasks Present**: 9 tasks reference security constraints
  - S1-S6: Security hardening (secrets, ports, TLS, topology hiding, auth)
  - G5-G9: Evidence production (SSL Labs, Trivy, port scan, auth contract, TLS)
  - G18-G21: Encryption & access control validation
- **Missing Security Tasks**: 1 gap identified
  - SEC-022-01 (MEDIUM): `cap_drop`/`cap_add` validation not explicitly tasked
  - **Resolution**: Add task to T10/T11 to verify container capabilities
- **Constraints Respected**:
  - R1 (no secrets in evidence) → S1, G1-G4
  - R2 (unpublished ports) → T10, S2, A5
  - R3 (data-preserving rollback) → T5.4
  - Trust boundaries → A2, S4
  - Header sanitization → S5, S6

---

## Architecture Task Review

- **Status**: Validated
- **Source**: `specs/022-vps-go-live-stabilization/architecture-violations.md`
- **Architecture Tasks Present**: 4 tasks reference architecture decisions
  - T6.1: docker-compose.vps.yml runtime stabilization
  - T8.1-T8.3: RTPengine network/ports verification
  - A1-A5: Architecture validation tasks
- **P0 Review Items**: 5 identified, all addressed in tasks
  - ARCH-P0-01 (no auth bypass) → T3, T9.2, S6
  - ARCH-P0-02 (no exposed private ports) → T10, A5
  - ARCH-P0-03 (HA1 only) → S6
  - ARCH-P0-04 (no db_mysql/sanity) → A1
  - ARCH-P0-05 (RTPengine control socket binding) → T8
- **Refactor Tasks**: None required
- **Migration Tasks**: None required
- **Architecture Risks**:
  - R1: Plan assumes docker-compose.vps.yml network topology without explicit reference → Mitigated by T6.1, A2
  - R2: Evidence directory could accidentally capture secrets → Mitigated by S1, R1
  - R3: Rollback runbook volume handling → Mitigated by T5.4

---

## Task Coverage Matrix

| Constitution Gate | Tasks | Status |
|---|---|---|
| Docker-first | T1-T14, T6.1, A1-A5 | ✅ Covered |
| PostgreSQL-only | T7.1-T7.3 | ✅ Covered |
| Module validity | A1 | ✅ Covered |
| Secret hygiene | S1-S2, R1, G1-G4 | ✅ Covered |
| Network isolation | T10, A2, A5, S4 | ✅ Covered |

| Security Area | Tasks | Status |
|---|---|---|
| Authentication | S6, G8, G21 | ✅ Covered |
| Trust boundaries | A2, S4 | ✅ Covered |
| Data isolation | G2, G14-G17 | ✅ Covered |
| Secrets management | S1, G13 | ✅ Covered |
| Header sanitization | S5 | ✅ Covered |
| Container hardening | S2, A4, **T10.4** | ✅ Covered |
| TLS | S3, G5, G9, G19 | ✅ Covered |
| Rate limiting | C3, C5 | ✅ Covered |
| Audit | G14-G16 | ✅ Covered |

---

## Recommended Next Step

1. **Address SEC-022-01 gap**: Add explicit task for `cap_drop`/`cap_add` validation (suggest T10.4 or T11.4)
2. **Continue to implementation**: Task list is 97/97 complete with 0 P0 violations
3. **No architecture refactors required**: All drift findings are advisory or already mitigated

---

## Durable Memory Preservation

**Proposed entries** (from this validation):
- **Entry**: Feature 022 task structure pattern — Security governance tasks (G1-G27) should be appended as a distinct phase after core implementation tasks
- **Entry**: cap_drop/cap_add validation should be an explicit architecture validation task in all Docker-first features
- **Entry**: DNS A record configuration should be a Wave 0 baseline task for any feature requiring TLS

**Status**: Proposed for capture to `.specify/memory/BUGS.md` and `DECISIONS.md`
