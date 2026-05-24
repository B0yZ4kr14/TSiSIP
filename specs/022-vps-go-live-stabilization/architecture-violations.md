# Architecture Violation Detection — Feature 022: VPS Go-Live Stabilization

**Generated**: 2026-05-23
**Constitution Version**: 1.1.0 (governance), architecture_constitution.md (enforcement)
**Scope**: spec.md, plan.md, memory-synthesis.md

---

## Constitution Check Gates

| Gate | Validation | Status |
|---|---|---|
| Docker-first | No bare-metal or VM-first runtime paths proposed | PASS |
| PostgreSQL-only | No db_mysql, db_sqlite, or MySQL/MariaDB references | PASS |
| Module validity | Only OpenSIPS 3.6 LTS documented modules | PASS |
| Secret hygiene | No plaintext secrets in proposed changes | PASS |
| Network isolation | Asterisk and PostgreSQL have no host-published ports | PASS |

## P0 Blocking Violations

| ID | Rule | Status | Evidence |
|---|---|---|---|
| ARCH-P0-01 | No public endpoint may bypass OpenSIPS auth | PASS | AC3 enforces OPTIONS test; no trusted gateway exceptions proposed |
| ARCH-P0-02 | No container may expose PostgreSQL or Asterisk ports | PASS | AC6 explicitly audits port exposure |
| ARCH-P0-03 | subscriber table must not store plaintext passwords | PASS | No schema changes to subscriber table proposed |
| ARCH-P0-04 | OpenSIPS config must not reference db_mysql, db_sqlite, or sanity | PASS | No config changes proposed in plan |
| ARCH-P0-05 | RTPengine control socket must bind to sip_internal only | REVIEW | Plan does not explicitly verify RTPengine --listen-ng binding. T8 (RTPengine network/ports) should confirm this |

## Layer Boundary Review

| Layer | Proposed Changes | Boundary Violation |
|---|---|---|
| Edge (SIP) | None (stabilization only) | None |
| Media | RTPengine health verification (T8) | None |
| Database | DB schema alignment (T7) | None - must use ALTER TABLE only |
| PBX | None | None |
| Control Plane | OCP healthcheck (T4, T9) | None - read-only verification |
| Observability | None | None |

## Architecture Drift Assessment

| Drift Category | Finding | Severity | Action |
|---|---|---|---|
| New service | None proposed | N/A | N/A |
| New network | None proposed | N/A | N/A |
| Module addition | None proposed | N/A | N/A |
| Config template | No changes to opensips.cfg.tpl in plan | N/A | N/A |
| Dockerfile | No image changes in plan | N/A | N/A |

## Consistency Risks

| Risk | Description | Mitigation |
|---|---|---|
| R1 | Plan assumes docker-compose.vps.yml exists but does not reference its network assignments explicitly | T6 (runtime/compose stabilization) should verify network topology |
| R2 | Evidence in .sisyphus/evidence/ (AD-022-3) could accidentally capture container stdout with secrets if not filtered | R1 (grep check) mitigates; recommend adding .gitignore for evidence/ |
| R3 | Rollback runbook (T5) may reference docker compose down which removes containers but not volumes; data-preserving rollback requires explicit volume handling | R3 in spec requires volume backup step |

## Architecture Review Sign-off

- **Status**: PASS with 1 REVIEW item and 2 consistency risks (0 P0 violations)
- **Condition**: T8 must verify RTPengine --listen-ng binding to sip_internal
- **Recommendation**: No Constitution Update Proposal required
