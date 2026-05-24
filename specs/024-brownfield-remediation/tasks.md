# Feature 024 Tasks — Brownfield Remediation

## Phase 1: Supply-Chain Determinism (B1)

- [ ] T1.1: Replace FROM line in docker/admin-api/Dockerfile with SHA-pinned php image
- [ ] T1.2: Add digest comment documenting fetch date and SHA verification command
- [ ] T1.3: Run docker build to verify image builds successfully
- [ ] T1.4: Run Trivy scan on pinned digest to verify no new HIGH/CRITICAL CVEs (R2)
- [ ] T1.5: Capture Trivy scan evidence in docs/security/evidence/024-trivy-scan.txt

## Phase 2: Test Script Hygiene (B2–B3)

- [ ] T2.1: Create get_test_ip() helper in tests/integration/test_end_to_end_call.py
- [ ] T2.2: Replace all hard-coded 172.x IPs in test_end_to_end_call.py with TEST_IP env var
- [ ] T2.3: Create get_test_ip() helper in tests/integration/test_sip_trunk_failover.py
- [ ] T2.4: Replace all hard-coded 172.x IPs in test_sip_trunk_failover.py with TEST_IP env var
- [ ] T2.5: Run pytest smoke test with TEST_IP=127.0.0.1 to verify parameterization

## Phase 3: Deploy Script Robustness (B4–B6, B8–B10)

- [ ] T3.1: Replace static RTPENGINE_PRIVATE_IP in deploy/scripts/test-vps-local.sh with docker network inspect
- [ ] T3.2: Replace static RTPENGINE_INTERNAL_IP in deploy/scripts/test-vps-local.sh with docker network inspect
- [ ] T3.3: Replace static RTPENGINE_PRIVATE_IP in deploy/scripts/vps-bootstrap.sh with docker network inspect
- [ ] T3.4: Replace static RTPENGINE_INTERNAL_IP in deploy/scripts/vps-bootstrap.sh with docker network inspect
- [ ] T3.5: Replace static RTPENGINE_PRIVATE_IP in deploy/scripts/vps-deploy.sh with docker network inspect
- [ ] T3.6: Replace static RTPENGINE_INTERNAL_IP in deploy/scripts/vps-deploy.sh with docker network inspect
- [ ] T3.7: Add error handling: exit 1 with descriptive message if docker network inspect fails (no silent default)
- [ ] T3.8: Add inline comments before every sleep in deploy/scripts/orchestrate-deploy.sh explaining wait purpose
- [ ] T3.9: Add inline comments before every sleep in deploy/scripts/safe-recovery.sh explaining wait purpose
- [ ] T3.10: Add inline comments before every sleep in deploy/scripts/vps-deploy.sh explaining wait purpose
- [ ] T3.11: Verify with grep that all sleep statements in deploy/scripts/*.sh have preceding comments

## Phase 4: Configuration Completeness (B7)

- [ ] T4.1: Extract all variable references from docker-compose.vps.yml using grep
- [ ] T4.2: Audit existing .env.example and identify missing variables
- [ ] T4.3: Add all missing variables to .env.example with placeholder values and descriptive comments
- [ ] T4.4: Validate docker compose config with placeholder env values

## Phase 5: Healthcheck Hardening (B11–B12)

- [ ] T5.1: Run docker compose exec ocp curl to verify current healthcheck behavior inside container namespace
- [ ] T5.2: If healthcheck fails with userland-proxy=false, update docker-compose.vps.yml healthcheck to use localhost or internal DNS
- [ ] T5.3: Add HEALTHCHECK to docker/admin-api/Dockerfile with 30s interval, 60s start_period, 3 retries
- [ ] T5.4: Add HEALTHCHECK to docker/backup/Dockerfile with file-based or command-based probe
- [ ] T5.5: Add HEALTHCHECK to docker/anomaly-detector/Dockerfile with lightweight probe
- [ ] T5.6: Add HEALTHCHECK to docker/ca-tool/Dockerfile with certificate validity check
- [ ] T5.7: Add HEALTHCHECK to docker/certbot-exporter/Dockerfile with metrics endpoint probe
- [ ] T5.8: Build each modified image and verify health status transitions to healthy within 60s

## Phase 6: Validation & Sign-Off

- [ ] T6.1: Run docker compose config to validate zero errors after all changes (AC9)
- [ ] T6.2: Run post-fix brownfield scan against changed files (AC10)
- [ ] T6.3: Verify zero HIGH/MEDIUM findings in scan results (AC10)
- [ ] T6.4: Run git diff to confirm no secrets in changes (R1)
- [ ] T6.5: Write conventional commit with all Feature 024 changes
- [ ] T6.6: Push commit to main and verify CI passes

---

## Security Review Checkpoints

| Checkpoint | Trigger | Gate Condition |
|---|---|---|
| SR-1 | After T1.4 | Trivy scan must show zero new HIGH/CRITICAL CVEs in pinned image digest |
| SR-2 | After T3.7 | Dynamic IP discovery scripts must not log or echo discovered IPs to stdout (prevent leak in CI logs) |
| SR-3 | After T6.3 | Post-fix brownfield scan must show zero HIGH/MEDIUM findings |

## Dependency Graph

```
Phase 1 (T1.1–T1.5) ──┐
Phase 2 (T2.1–T2.5) ──┤
Phase 3 (T3.1–T3.11) ─┤──> Phase 6 (T6.1–T6.6)
Phase 4 (T4.1–T4.4) ──┤
Phase 5 (T5.1–T5.8) ──┘
         │
    SR-1 / SR-2 / SR-3
```

Phases 1–5 can execute in parallel. Phase 6 (validation) requires all prior phases complete.

## Traceability Matrix

| AC | Task(s) |
|---|---|
| AC1 (SHA pin) | T1.1–T1.3 |
| AC2 (test IPs end-to-end) | T2.1–T2.2 |
| AC3 (test IPs failover) | T2.3–T2.4 |
| AC4 (dynamic IP discovery) | T3.1–T3.7 |
| AC5 (env-example complete) | T4.1–T4.4 |
| AC6 (sleep comments) | T3.8–T3.11 |
| AC7 (OCP healthcheck) | T5.1–T5.2 |
| AC8 (Dockerfile HEALTHCHECK) | T5.3–T5.8 |
| AC9 (compose config valid) | T6.1 |
| AC10 (zero HIGH/MEDIUM) | T6.2–T6.3 |
| R1 (no secrets) | T6.4 |
| R2 (Trivy scan) | T1.4 |
| R3 (no topology leak) | T3.7 |

## Security Evidence Artifacts

- docs/security/evidence/024-trivy-scan.txt — Trivy scan results for pinned image (T1.4)
- docs/security/evidence/024-brownfield-postfix.txt — Post-fix brownfield scan results (T6.2)
- docs/security/evidence/024-git-diff.txt — Git diff confirming no secrets (T6.4)
