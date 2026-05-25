# SDET Report — TSiSIP

*Generated: 2026-05-25T02:27:41.398251Z*

---

## Test Strategy Snapshot
*Sources: [context.json](../context.json) · [qa-context.json](../qa/qa-context.json)*

- **Target pyramid**: Classic (70% unit / 30% integration / 10% e2e)
- **Current pyramid**: Heavily integration-focused (0% unit proxy, 100% integration, 0% e2e proxy)
- **Gap**: No unit tests for Python scripts; no e2e call flow tests; proxy coverage is LOW confidence.

---

## Capability Test Coverage Map
*Sources: [qa-context.json](../qa/qa-context.json) · [test-inventory.json](../qa/test-inventory.json)*

| Capability | Unit % | Integration % | E2E % | Source | Automation |
|---|---|---|---|---|---|
| BC-001 SIP Edge Proxy | not-collected | not-collected | not-collected | proxy LOW | partial |
| BC-002 Media Relay | not-collected | not-collected | not-collected | proxy LOW | none |
| BC-003 PBX Backend | not-collected | not-collected | not-collected | proxy LOW | none |
| BC-004 Tenant & Subscriber Mgmt | not-collected | not-collected | not-collected | proxy LOW | partial |
| BC-005 SIP Trunk Management | not-collected | not-collected | not-collected | proxy LOW | partial |
| BC-006 Anomaly Detection | not-collected | not-collected | not-collected | proxy LOW | none |

*Reason for not-collected: tests validate Docker containers and configuration, not source modules. No coverage tool is configured.*

---

## Automation Status Matrix
*Sources: [qa-context.json](../qa/qa-context.json) · [ci-map.json](../qa/environments/ci-map.json)*

| Capability | Regression | Smoke | Contract | Performance |
|---|---|---|---|---|
| BC-001 | partial | partial | absent | absent |
| BC-002 | none | none | absent | absent |
| BC-003 | none | none | absent | absent |
| BC-004 | partial | partial | absent | absent |
| BC-005 | partial | partial | absent | absent |
| BC-006 | none | none | absent | absent |

---

## Testability Hotspots

**No testability blockers found.** All 0 findings are clean.

---

## Defect & Flakiness Profile
*Sources: [flaky-tests.json](../qa/environments/flaky-tests.json)*

- **Open defects**: not-collected (no defect export provided)
- **Flaky tests**: not-collected (no flaky test history provided)
- **Change velocity**: BC-005 HIGH (active Feature 006), others MEDIUM/LOW

---

## Environment Readiness
*Sources: [environment-map.json](../qa/environments/environment-map.json)*

| Environment | Declared | CI Configured | Parity Issues |
|---|---|---|---|
| dev | yes | yes | N/A |
| staging | yes | no | Not tested in CI |
| prod | yes | no | Not tested in CI |

**Drift**: staging and prod are declared but absent from CI. No automated deployment gates.

---

## CI Quality Gates
*Sources: [ci-map.json](../qa/environments/ci-map.json)*

| Stage | Test Levels | Coverage Threshold | Merge-Blocking |
|---|---|---|---|
| validate | lint, typecheck | none | yes |
| test-integration | integration | none | yes |
| security-scan | trivy fs scan | none | yes |
| build-opensips | Docker build | none | no |
| build-ocp | Docker build | none | no |
| sbom | SBOM generation | none | no |
| provenance | SLSA attestation | none | no |

**Gaps**: No unit test stage, no coverage threshold enforcement, no e2e stage, no performance stage.

---

## QA Risk Ranking
*Pending `/assess` — no QA composite scoring available.*

---

## Unified Risk View for QA
*Pending `/assess` — no unified composite available.*

---

## Test Strategy Recommendations per Capability

### BC-001 SIP Edge Proxy
- **Add**: Config syntax validation test (fast, runs in CI)
- **Add**: SIP OPTIONS probe regression test
- **Add**: WebRTC WS/WSS handshake test
- **Retain**: Integration tests for compose validation

### BC-002 Media Relay
- **Add**: RTP port range accessibility test
- **Add**: SDP rewriting validation (mock INVITE/200 OK)
- **New tool**: RTP fuzzer or media quality probe

### BC-003 PBX Backend
- **Add**: SIP trunk interop test (Asterisk ↔ OpenSIPS)
- **Add**: Voice call end-to-end test (challenging but critical)

### BC-004 Tenant & Subscriber Management
- **Add**: Schema migration test harness
- **Add**: HA1 hash algorithm regression test (SHA-256, SHA-512/256)
- **Retain**: Integration tests for DB init

### BC-005 SIP Trunk Management
- **Add**: Rate limiting load test (sipsak or custom)
- **Add**: Circuit breaker chaos test (stop Asterisk, assert failover)
- **Add**: Trunk health probe failure simulation

### BC-006 Anomaly Detection
- **Add**: Anomaly detection accuracy benchmark
- **Add**: False-positive rate measurement
- **New tool**: Synthetic SIP traffic generator

---

## Sprint-Ready QA Backlog

### 1. Add schema migration test harness (est. 3d)
- **Files**: `db/init/*.sql`, tests/integration/
- **AC**: Applies all SQL files to fresh Postgres; verifies tables + FKs
- **Evidence**: [qa-context.json](../qa/qa-context.json) — BC-004 gap

### 2. Add SIP OPTIONS regression test (est. 2d)
- **Files**: tests/integration/, `scripts/sip-auth-probe.py`
- **AC**: Sends OPTIONS; asserts 200 OK; checks Server header
- **Evidence**: [test-inventory.json](../qa/test-inventory.json)

### 3. Add OpenSIPS config syntax validation to CI (est. 1d)
- **Files**: `.github/workflows/ci.yml`, `opensips/opensips.cfg.tpl`
- **AC**: `opensips -c -f` passes in CI; fails build on syntax error
- **Evidence**: [ci-map.json](../qa/environments/ci-map.json)

### 4. Add rate limiting load test (est. 5d)
- **Files**: tests/integration/test_rate_limiting.py, `opensips/opensips.cfg.tpl`
- **AC**: Sends >threshold requests; asserts throttling; checks ban_list
- **Evidence**: [qa-context.json](../qa/qa-context.json) — BC-005 gap

### 5. Add Docker Compose healthcheck validation (est. 2d)
- **Files**: `docker-compose.yml`, tests/integration/
- **AC**: All services healthy after 60s; no missing HEALTHCHECK
- **Evidence**: [ss3-config.json](../security/signals/ss3-config.json)

### 6. Add WebRTC WS handshake test (est. 3d)
- **Files**: tests/integration/test_webrtc_support.py, `opensips/opensips.cfg.tpl`
- **AC**: Opens WS to 8080; sends valid SIP over WS; asserts response
- **Evidence**: [qa-context.json](../qa/qa-context.json) — BC-001 gap

### 7. Add TLS certificate expiry monitoring test (est. 2d)
- **Files**: `docker/certbot/healthcheck.sh`, tests/integration/
- **AC**: Healthcheck returns 0; cert valid >24h; mock expiry scenario
- **Evidence**: [ss3-config.json](../security/signals/ss3-config.json)

### 8. Add anomaly detection accuracy benchmark (est. 5d)
- **Files**: docker/anomaly-detector/, tests/integration/
- **AC**: Synthetic attack traffic; asserts detection rate >80%; FP rate <5%
- **Evidence**: [qa-context.json](../qa/qa-context.json) — BC-006 gap

### 9. Add trunk circuit breaker chaos test (est. 4d)
- **Files**: tests/integration/test_sip_trunk_failover.py, `docker-compose.yml`
- **AC**: Stop Asterisk; assert dispatcher marks inactive; recovery detected
- **Evidence**: [qa-context.json](../qa/qa-context.json) — BC-005 gap

### 10. Add staging environment to CI pipeline (est. 3d)
- **Files**: `.github/workflows/ci.yml`, deploy/ansible/
- **AC**: Deploy to staging VPS on merge; run smoke tests; report health
- **Evidence**: [environment-map.json](../qa/environments/environment-map.json)

---

## Not-Collected Summary

| Capability | Signal | Reason | How to Unblock |
|---|---|---|---|
| All | Coverage report | No coverage tool configured | Add pytest-cov + upload to codecov |
| All | Unit coverage | No unit tests for scripts/config | Write pytest unit tests for Python scripts |
| All | E2E coverage | No end-to-end call flow tests | Add SIPp or sipsak e2e scenarios |
| All | Defect tracker export | No Jira/linear/etc. export | Export defects CSV and register in context.json |
| All | Flaky test history | No CI flaky test tracking | Enable pytest-rerunfailures + track in CI |
| BC-002 | Performance tests | No RTP load testing | Add RTP traffic generator + metrics validation |
| BC-003 | Interop tests | No Asterisk interop validation | Add SIP trunk registration test |
| BC-006 | Accuracy benchmarks | No synthetic traffic dataset | Generate labeled attack/normal SIP traffic |
