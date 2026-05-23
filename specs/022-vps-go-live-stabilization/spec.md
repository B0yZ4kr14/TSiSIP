# Feature Specification: 022 — VPS Go-Live Stabilization

## Overview

**Feature**: 022 — VPS Go-Live Stabilization
**Short name**: vps-stabilization
**Created**: 2026-05-23
**Status**: In Progress

### Context

Post-Feature 021 hardening, the TSiSIP platform requires a coordinated 24-hour stabilization window to achieve production readiness on the VPS (`tsiapp.io`). This feature executes the TDD-first go-live plan from `.sisyphus/plans/vps-24h-tdd-stabilization.md`.

### Objective

Deliver a verifiable, production-hardened `vps-lite` stack (postgres, rtpengine, opensips, ocp, backup) with TDD smoke/integration tests and operational rollback runbook.

---

## Acceptance Criteria

- [ ] AC1: All `vps-lite` services report `healthy` or stable `Up` status
- [ ] AC2: TDD RED→GREEN→REFACTOR cycle is evidenced for critical paths
- [ ] AC3: SIP OPTIONS returns `200 OK` from the VPS edge
- [ ] AC4: OCP accessible at `https://tsiapp.io/TSiSIP`
- [ ] AC5: Rollback runbook is executable without ambiguity
- [ ] AC6: Port exposure audit confirms zero public Asterisk/PostgreSQL ports
- [ ] AC7: Evidence bundle exists in `.sisyphus/evidence/`
- [ ] AC8: Plan compliance audit (F1-F4) passes

---

## Security Requirements

| ID | Requirement | Verification |
|---|---|---|
| R1 | No secrets in committed evidence files | `grep -r "password\|secret\|token" .sisyphus/evidence/` returns only placeholder markers |
| R2 | Asterisk/PostgreSQL ports remain unpublished | `docker compose -f docker-compose.vps.yml config` shows no host port mapping for asterisk/postgres |
| R3 | Rollback preserves data integrity | Runbook includes volume backup step before destructive changes |

---

## Architecture Decisions

- **AD-022-1**: Use `docker-compose.vps.yml` as the canonical production runtime for VPS.
- **AD-022-2**: TDD tests use bash + `docker compose` + `sipsak` + `curl` + Python UDP probe (existing toolchain).
- **AD-022-3**: Evidence is stored in `.sisyphus/evidence/` (not `reports/`) to separate operational evidence from static scans.

---

## Out of Scope

- NAT advanced/transcoding (out of 24h window)
- New components outside vps-lite baseline
- Architecture changes without ADR

---

## Cross-References

- Stabilization plan: `.sisyphus/plans/vps-24h-tdd-stabilization.md`
- Feature 021: Brownfield Security & Production Hardening
- Feature 008: DevSecOps Deployment (baseline compose)
