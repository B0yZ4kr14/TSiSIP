# Developer Report — TSiSIP

*Generated: 2026-05-25T02:27:41.398251Z*

---

## Capability Map
*Sources: [l1-capabilities.md](../discovery/l1-capabilities.md) · [l2-capabilities.md](../discovery/l2-capabilities.md)*

### BC-001: SIP Edge Proxy
- **Code**: opensips/opensips.cfg.tpl, docker/Dockerfile, docker/entrypoint.sh
- **L2s**:
  - BC-001-01 Transport (UDP/TCP/TLS/WS/WSS) — opensips/opensips.cfg.tpl:30-65
  - BC-001-02 Digest Auth — opensips/opensips.cfg.tpl:120-160
  - BC-001-03 Tenant Routing — opensips/opensips.cfg.tpl:180-220
  - BC-001-04 Topology Hiding — opensips/opensips.cfg.tpl:200-220
  - BC-001-05 Rate Limiting — opensips/opensips.cfg.tpl:100-115
  - BC-001-06 Media Relay Integration — opensips/opensips.cfg.tpl:240-260
  - BC-001-07 WebRTC — opensips/opensips.cfg.tpl:55-65
- **External deps**: PostgreSQL (libpq), RTPengine (control socket), TLS certs

### BC-002: Media Relay
- **Code**: docker/rtpengine/Dockerfile
- **L2s**:
  - BC-002-01 RTP Relay — docker/rtpengine/Dockerfile
- **External deps**: OpenSIPS control, kernel modules (optional)

### BC-003: PBX Backend
- **Code**: docker/asterisk/Dockerfile
- **L2s**:
  - BC-003-01 Voice Application Server — docker/asterisk/Dockerfile
- **External deps**: OpenSIPS (sip_internal), PostgreSQL (CDR)

### BC-004: Tenant & Subscriber Management
- **Code**: db/init/01-*.sql, db/init/02-*.sql, db/init/04-ocp-*.sql
- **L2s**:
  - BC-004-01 Subscriber Provisioning — db/init/01-stock-opensips-schema.sql
  - BC-004-02 Tenant Provisioning — db/init/02-tsisip-extensions.sql
  - BC-004-03 Audit Logging — db/init/04-ocp-audit-schema.sql
- **External deps**: N/A (data layer)

### BC-005: SIP Trunk Management
- **Code**: db/init/04-trunk-*.sql, db/init/05-*.sql
- **L2s**:
  - BC-005-01 Trunk Provisioning — db/init/04-trunk-schema.sql
  - BC-005-02 Rate Limiting — db/init/04-trunk-schema.sql
  - BC-005-03 Health Probes — db/init/04-trunk-schema.sql
  - BC-005-04 IP Whitelisting — db/init/04-trunk-schema.sql
- **External deps**: N/A (data layer)

### BC-006: Anomaly Detection
- **Code**: docker/anomaly-detector/
- **L2s**:
  - BC-006-01 Traffic Anomaly Detection — docker/anomaly-detector/
- **External deps**: Prometheus metrics, OpenSIPS MI

---

## Ownership Assignments

| Squad | Capabilities |
|---|---|
| Platform / SRE | BC-001, BC-002, BC-006 |
| Voice Engineering | BC-003 |
| Operations / DevOps | BC-004, BC-005 |

---

## Health Dashboard

| Capability | Cohesion | Coupling | LOC | Status |
|---|---|---|---|---|
| BC-001 | HIGH | MEDIUM | ~400 | OK |
| BC-002 | HIGH | LOW | ~100 | OK |
| BC-003 | HIGH | LOW | ~100 | OK |
| BC-004 | HIGH | MEDIUM | ~400 | OK |
| BC-005 | HIGH | MEDIUM | ~300 | OK |
| BC-006 | HIGH | LOW | ~200 | OK |

---

## Refactor Targets

None flagged — all capabilities have HIGH cohesion and CLEAR boundaries.

---

## Orphan Code

| Path | LOC | Recommended Action |
|---|---|---|
| docs/ | ~5000 | Attach to BC-004/BC-005 as operational docs |
| reports/ | ~2000 | Keep as audit artifacts; no runtime action |
| specs/ | ~3000 | Attach to corresponding capabilities |
| commands/ | ~500 | Squad command definitions; keep as-is |
| plans/ | ~2000 | Implementation plans; archive after completion |

---

## Coverage Breakdown

| Capability | Significant Files | Tested Files | Proxy Coverage | Churn |
|---|---|---|---|---|
| BC-001 | ~15 | 0 | 0% | medium |
| BC-002 | ~3 | 0 | 0% | low |
| BC-003 | ~3 | 0 | 0% | low |
| BC-004 | ~8 | 0 | 0% | medium |
| BC-005 | ~6 | 0 | 0% | high |
| BC-006 | ~4 | 0 | 0% | low |

**Note**: Proxy coverage is 0% because tests validate Docker containers and configuration syntax, not individual source files. Integration tests exist but don't map to capability files by naming convention.

---

## Security Findings for Developers
*Pending `/assess` — no vulnerability severity scoring available.*

---

## QA Findings for Developers

- **No testability blockers**: 0 findings in testability scan.
- **Coverage gaps**: All capabilities lack file-level test mapping.
- **No flaky tests**: Not collected.

---

## Sprint Recommendations

### 1. Add OpenSIPS config syntax validation to CI
- **Scope**: S
- **Acceptance criteria**:
  - `docker run --rm opensips -c -f /etc/opensips/opensips.cfg` passes in CI
  - Fails the build on config syntax errors
- **Files**: `.github/workflows/ci.yml`, `opensips/opensips.cfg.tpl`
- **Evidence**: [qa-context.json](../qa/qa-context.json) — BC-001 automation partial

### 2. Create schema migration test harness
- **Scope**: M
- **Acceptance criteria**:
  - Test applies all `db/init/*.sql` files to a fresh PostgreSQL container
  - Verifies table creation and foreign key integrity
  - Runs in CI on every PR touching db/
- **Files**: `db/init/*.sql`, tests/integration/
- **Evidence**: [qa-context.json](../qa/qa-context.json) — BC-004 strategy gap

### 3. Add SIP OPTIONS health probe regression test
- **Scope**: S
- **Acceptance criteria**:
  - Sends SIP OPTIONS to running OpenSIPS container
  - Asserts 200 OK response with Server header
  - Runs in CI integration test stage
- **Files**: `tests/integration/`, `scripts/sip-auth-probe.py`
- **Evidence**: [test-inventory.json](../qa/test-inventory.json)

### 4. Parameterize hardcoded IPs in test files
- **Scope**: S
- **Acceptance criteria**:
  - All `172.22.0.1` references removed from test files
  - Use environment variable or fixture for target host
  - Already partially completed; verify completeness
- **Files**: `tests/integration/test_sip_trunk_*.py`
- **Evidence**: [ss1-static.json](../security/signals/ss1-static.json)

### 5. Add Docker Compose healthcheck validation
- **Scope**: S
- **Acceptance criteria**:
  - Verify all services in `docker-compose.yml` have HEALTHCHECK
  - Assert no services are `unhealthy` after 60s startup
  - Runs in CI
- **Files**: `docker-compose.yml`, tests/integration/
- **Evidence**: [ss3-config.json](../security/signals/ss3-config.json)

### 6. Document rate limiting behavior with integration test
- **Scope**: M
- **Acceptance criteria**:
  - Test sends >threshold requests from same IP
  - Asserts 429 Too Many Requests or SIP equivalent
  - Verifies ban_list htable population
- **Files**: `tests/integration/test_rate_limiting.py`, `opensips/opensips.cfg.tpl`
- **Evidence**: [qa-context.json](../qa/qa-context.json) — BC-005 strategy gap

### 7. Add TLS certificate expiry monitoring test
- **Scope**: S
- **Acceptance criteria**:
  - Test verifies certbot healthcheck script returns 0
  - Asserts certificate validity >24h
  - Mock expired cert scenario
- **Files**: `docker/certbot/healthcheck.sh`, tests/integration/
- **Evidence**: [ss3-config.json](../security/signals/ss3-config.json)
