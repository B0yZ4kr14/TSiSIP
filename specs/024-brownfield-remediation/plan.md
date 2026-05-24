# Implementation Plan: 024 — Brownfield Remediation

## Phase 0 — Pre-Flight (Constitution Gates)

| Gate | Check | Status |
|---|---|---|
| Docker-first | No bare-metal paths proposed | PASS |
| PostgreSQL-only | No db_mysql references | PASS |
| Module validity | No new OpenSIPS modules | PASS |
| Secret hygiene | No plaintext secrets in changes | PASS |
| Network isolation | No new published ports | PASS |

## Phase 1 — Supply-Chain Determinism (B1)

**T1: Pin admin-api Dockerfile base image**
- File: docker/admin-api/Dockerfile
- Action: Replace FROM php line with SHA-pinned variant
- Verification: docker build succeeds
- Deliverable: Updated Dockerfile with digest comment

## Phase 2 — Test Script Hygiene (B2–B3)

**T2: Parameterize test_end_to_end_call.py**
- File: tests/integration/test_end_to_end_call.py
- Action: Replace hard-coded 172.x IPs with TEST_IP env var
- Add helper that falls back to docker network inspect
- Verification: pytest smoke test passes

**T3: Parameterize test_sip_trunk_failover.py**
- File: tests/integration/test_sip_trunk_failover.py
- Action: Same pattern as T2
- Verification: pytest smoke test passes

## Phase 3 — Deploy Script Robustness (B4–B6, B8–B10)

**T4: Dynamic IP discovery in deploy scripts**
- Files: deploy/scripts/test-vps-local.sh, vps-bootstrap.sh, vps-deploy.sh
- Action: Replace static RTPENGINE_PRIVATE_IP and RTPENGINE_INTERNAL_IP with docker network inspect
- Fallback: Exit with error if discovery fails (no silent default)
- Verification: Run with set -x to confirm IP resolution

**T5: Document sleep statements**
- Files: deploy/scripts/orchestrate-deploy.sh, safe-recovery.sh, vps-deploy.sh
- Action: Add inline comments before each sleep explaining wait purpose
- Verification: grep sleep shows every sleep preceded by comment

## Phase 4 — Configuration Completeness (B7)

**T6: Complete env-example**
- File: .env.example
- Action: Audit docker-compose.vps.yml for all variable references; add each to env-example with placeholder and comment
- Verification: docker compose config validates with placeholders

## Phase 5 — Healthcheck Hardening (B11–B12)

**T7: Verify OCP healthcheck behavior**
- File: docker-compose.vps.yml
- Action: Confirm ocp healthcheck works inside container namespace
- If it fails: update healthcheck to use localhost or internal DNS
- Verification: docker compose exec ocp curl health endpoint

**T8: Add Dockerfile HEALTHCHECK instructions**
- Files: docker/admin-api/Dockerfile, docker/backup/Dockerfile, docker/anomaly-detector/Dockerfile, docker/ca-tool/Dockerfile, docker/certbot-exporter/Dockerfile
- Action: Add HEALTHCHECK appropriate to each service
- Verification: Build each image and inspect health status after 60s

## Phase 6 — Validation & Sign-Off

**T9: Compose config validation**
- Command: docker compose config
- Expected: Zero errors

**T10: Post-fix brownfield scan**
- Command: Re-run brownfield scan
- Expected: Zero HIGH/MEDIUM findings

**T11: Git commit**
- Scope: All files modified in T1–T10
- Message: feat(024): brownfield remediation

## Dependency Graph

T1–T8 can run in parallel groups:
- Group A: T1, T6, T8 (Dockerfile and config changes)
- Group B: T2, T3 (test changes)
- Group C: T4, T5 (deploy script changes)
- Group D: T7 (compose healthcheck verification)

All groups feed into T9, then T10, then T11.

## Risk Register

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| SHA-pinned image has CVE | Low | Medium | Run Trivy scan before commit |
| Dynamic IP discovery fails on fresh VPS | Medium | High | Add error handling; document manual override |
| HEALTHCHECK adds startup latency | Low | Low | Use generous start_period and interval |
| env-example still incomplete | Medium | Medium | Use grep to generate exhaustive variable list |
