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

- [ ] AC1: All core `vps-lite` services (postgres, rtpengine, opensips, ocp, asterisk-pbx-1/2, backup) report `healthy` status for >=10 minutes with healthcheck interval=10s, timeout=5s, retries=3. Services without healthchecks (certbot, certbot-exporter) must not be in a restart loop exceeding 5 restarts in 60 seconds (measured via `docker compose ps`).
- [ ] AC2: TDD RED→GREEN→REFACTOR cycle is evidenced for critical paths: each wave must produce (a) RED test output showing expected failure, (b) GREEN test output showing pass after fix, (c) git diff showing refactor without behavior change.
- [ ] AC3: SIP OPTIONS returns `200 OK` from the VPS edge with `Server: OpenSIPS` header, matching Via branch, within 2 seconds (measured by sipsak).
- [ ] AC4: OCP responds with HTTP 200 on `http://127.0.0.1:8084/login.php` within 5 seconds (localhost loopback), returning HTML body containing "TSiSIP". Production HTTPS endpoint `https://tsiapp.io/TSiSIP` verified once TLS certificates are active (requires DNS A record configuration). Verification: `curl -fsSL http://127.0.0.1:8084/login.php | grep -q 'TSiSIP'` (loopback); `curl -fsSL https://tsiapp.io/TSiSIP | grep -q 'TSiSIP'` (production, post-DNS).
- [ ] AC5: Rollback runbook is executable without ambiguity: second operator can execute runbook without asking clarifying questions, completing within 15 minutes.
- [ ] AC6: Port exposure audit confirms zero public Asterisk/PostgreSQL ports and container hardening (cap_drop/cap_add) verified via `docker compose -f docker-compose.vps.yml config` and `nmap` host scan
- [ ] AC7: Evidence bundle exists in `.sisyphus/evidence/` — minimum 14 task evidence files (task-1 through task-14) plus security governance evidence in `docs/security/evidence/022-vps-go-live/`
- [ ] AC8: Plan compliance audit (F1-F4) passes:
  - F1 (Compliance): All ACs and Rs verified with evidence artifacts
  - F2 (Quality): Zero CRITICAL/HIGH findings from architecture/security review
  - F3 (QA): All smoke tests pass (SIP OPTIONS 200 OK, OCP HTTP 200, INVITE 407)
  - F4 (Scope): No out-of-scope items introduced; all in-scope items delivered

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
- Load testing beyond 100 concurrent REGISTER requests
- PostgreSQL HA/replica setup (single-instance accepted for vps-lite MVP)
- DNS provider configuration (external dependency) — NOTE: required for AC4 HTTPS verification but managed outside project scope

---

## Cross-References

- Stabilization plan: `.sisyphus/plans/vps-24h-tdd-stabilization.md`
- Feature 021: Brownfield Security & Production Hardening
- Feature 008: DevSecOps Deployment (baseline compose)

---

## Post-Implementation Quality Gates

- [ ] AC9: MemoryLint remediation — Container resource limits align with shared memory and production load requirements: OpenSIPS mem_limit=512m (matches `-m 512` shared memory flag), RTPengine mem_limit=512m, OCP/backup memswap_limit ≤1.5x mem_limit. Verified via `docker inspect --format='{{.HostConfig.Memory}}'` on each container.
- [ ] AC10: Critique review — Post-implementation critique findings are addressed (C2-C7: INVITE auth test, load test, security audit, rollback rehearsal, monitoring, memory alerting)
