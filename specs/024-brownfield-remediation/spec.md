# Feature Specification: 024 — Brownfield Remediation

## Overview

**Feature**: 024 — Brownfield Remediation  
**Short name**: brownfield-remediation  
**Created**: 2026-05-24  
**Status**: Specified

### Context

The post-Feature-022 brownfield scan surfaced 12 new findings (B1–B12) spanning supply-chain determinism, test script hygiene, deploy script robustness, and configuration completeness. This feature eliminates all HIGH and MEDIUM findings and addresses LOW findings where the fix is low-effort and high-value.

### Objective

1. Pin all Docker base images to SHA256 digests (B1).  
2. Parameterize hard-coded Docker network IPs in test scripts and deploy scripts (B2–B6).  
3. Bring env-example to full parity with docker-compose.vps.yml variables (B7).  
4. Add explanatory comments or replace polling loops for all sleep statements in deploy scripts (B8–B10).  
5. Verify OCP healthcheck behavior with userland-proxy=false (B11).  
6. Add HEALTHCHECK instructions to service Dockerfiles where missing (B12).

---

## Acceptance Criteria

- [ ] AC1: docker/admin-api/Dockerfile uses php image with SHA digest (B1 remediation)
- [ ] AC2: tests/integration/test_end_to_end_call.py has no hard-coded 172.x.x.x IPs (B2)
- [ ] AC3: tests/integration/test_sip_trunk_failover.py has no hard-coded 172.x.x.x IPs (B3)
- [ ] AC4: deploy scripts derive RTPENGINE_PRIVATE_IP and RTPENGINE_INTERNAL_IP dynamically via docker network inspect (B4–B6)
- [ ] AC5: env-example documents every variable referenced in docker-compose.vps.yml (B7)
- [ ] AC6: All sleep statements in deploy/scripts/*.sh have inline comments explaining the wait purpose (B8–B10)
- [ ] AC7: OCP healthcheck passes in docker-compose.vps.yml with userland-proxy=false on host, or compose healthcheck is updated (B11)
- [ ] AC8: Dockerfiles for admin-api, backup, anomaly-detector, ca-tool, and certbot-exporter contain HEALTHCHECK instructions (B12)
- [ ] AC9: docker compose config validates without errors after all changes
- [ ] AC10: Post-fix brownfield scan shows zero HIGH/MEDIUM findings

---

## Security Requirements

| ID | Requirement | Verification |
|---|---|---|
| R1 | No secrets committed in remediation changes | git diff excludes secrets dir |
| R2 | SHA pinning does not introduce new CVEs | Trivy scan on pinned image digest |
| R3 | Dynamic IP discovery does not leak host network topology | Scripts use docker network inspect on named networks only |

---

## Architecture Decisions

- AD-024-1: Dynamic IP discovery via docker network inspect is preferred over static defaults to survive Docker network recreation.
- AD-024-2: env-example will use placeholder values rather than real secrets.
- AD-024-3: Dockerfile HEALTHCHECK instructions will use lightweight commands with 30s interval and 3-retries.

---

## Out of Scope

- OpenSIPS memory tuning (separate feature)
- PostgreSQL work_mem reduction (separate feature)
- Unbounded audit query fix (separate OCP hardening feature)
- GitHub Deploy Workflow fix (separate CI/CD feature)
- Supply chain: pinning debian in main Dockerfile (already pinned)

---

## Cross-References

- Brownfield scan: reports/brownfield-scan-report.md
- Feature 021: Brownfield Security & Production Hardening
- Feature 022: VPS Go-Live Stabilization
- Architecture Constitution: .specify/memory/architecture_constitution.md
