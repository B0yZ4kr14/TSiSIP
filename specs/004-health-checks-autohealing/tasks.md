# Tasks: Advanced Container Health Checks and Auto-Healing

## Phase 1 — Health Check Infrastructure

### [X] T1.1: Create OpenSIPS health check script
**Description**: Create `docker/healthcheck/opensips-health.sh` that performs L1 (TCP 5060), L2 (MI `/get_statistics`), and L3 (SIP OPTIONS via `nc` or container-based Python script) checks. Exit 0 if all pass, 1 if any fail. Use `timeout` to prevent hanging.
**Phase**: 1
**Depends on**: —
**Parallel**: No
**Acceptance**: Script exits 0 when OpenSIPS is healthy, 1 when simulated failure injected.

### [X] T1.2: Create PostgreSQL health check script
**Description**: Create `docker/healthcheck/postgres-health.sh` that runs `pg_isready -U opensips -d opensips` and verifies replication lag if applicable.
**Phase**: 1
**Depends on**: —
**Parallel**: [P] with T1.1
**Acceptance**: Script exits 0 when PostgreSQL is healthy.

### [X] T1.3: Create RTPengine health check script
**Description**: Create `docker/healthcheck/rtpengine-health.sh` that checks UDP port 22222 (control) and verifies active sessions via `rtpengine-ctl` if available.
**Phase**: 1
**Depends on**: —
**Parallel**: [P] with T1.1, T1.2
**Acceptance**: Script exits 0 when RTPengine is healthy.

### [X] T1.4: Create Asterisk health check script (DEFERRED)
**Description**: Create `docker/healthcheck/asterisk-health.sh` that checks Asterisk Manager Interface (AMI) or SIP socket on 5060.
**Phase**: 1
**Depends on**: —
**Parallel**: [P] with T1.1, T1.2, T1.3
**Acceptance**: Script exits 0 when Asterisk is healthy.
**Note**: **DEFERRED**. Asterisk container is not part of the current docker-compose baseline (Feature 004 scope is OpenSIPS+RTPengine+PostgreSQL health checks). Skip T1.4 until Asterisk container is introduced in a separate feature.

### [X] T1.5: Add HEALTHCHECK to OpenSIPS, PostgreSQL, RTPengine Dockerfiles
**Description**: Update Dockerfiles for OpenSIPS, PostgreSQL, RTPengine to include `HEALTHCHECK` instruction with appropriate intervals and retries. Stagger start periods (10s, 15s, 20s) to avoid probe storms.
**Phase**: 1
**Depends on**: T1.1, T1.2, T1.3
**Parallel**: No
**Acceptance**: `docker ps` shows `(healthy)` for OpenSIPS, PostgreSQL, RTPengine within 60s of startup.
**Note**: Asterisk HEALTHCHECK deferred with T1.4.

## Phase 2 — Restart Policies & Auto-Healing

### [X] T2.1: Configure Docker Compose restart policies
**Description**: Update `docker-compose.yml` with restart policies: OpenSIPS/PostgreSQL use `unless-stopped`, others use `on-failure` with `delay: 5s`, `max_attempts: 10`, `window: 60s`.
**Phase**: 2
**Depends on**: T1.5
**Parallel**: No
**Acceptance**: `docker compose config` validates; simulated `kill -9` on OpenSIPS triggers restart within 15s.

### [X] T2.2: Implement exponential backoff verification
**Description**: Create `tests/integration/test_restart_policy.py` that verifies restart delay increases monotonically up to 60s cap.
**Phase**: 2
**Depends on**: T2.1
**Parallel**: No
**Acceptance**: Test passes; delay sequence is [5, 10, 20, 40, 60, 60...].

## Phase 3 — Circuit Breaker Implementation

### [X] T3.1: Configure OpenSIPS dispatcher probing
**Description**: Update `opensips/opensips.cfg.tpl` with: `modparam("dispatcher", "ds_ping_method", "OPTIONS")`, `modparam("dispatcher", "ds_ping_interval", 10)`, `modparam("dispatcher", "ds_probing_mode", 1)`, `modparam("dispatcher", "ds_ping_reply_codes", "5xx,timeout")`. Add `ds_set_state` on failure threshold.
**Phase**: 3
**Depends on**: T2.2
**Parallel**: No
**Acceptance**: `opensips-cli -x mi ds_list` shows probing states; failed targets become inactive.

### [X] T3.2: Implement half-open retry logic
**Description**: Add route logic that attempts `ds_next_dst` every 60s for inactive targets. Use `branch_route` to track half-open attempts.
**Phase**: 3
**Depends on**: T3.1
**Parallel**: No
**Acceptance**: Packet capture shows OPTIONS probes to inactive targets every 60s.

### [X] T3.3: Create circuit breaker integration test
**Description**: Create `tests/integration/test_circuit_breaker.py` that: starts stack, kills Asterisk backend, verifies no INVITEs routed to failed target, verifies half-open probes, restarts backend, verifies circuit closes.
**Phase**: 3
**Depends on**: T3.2
**Parallel**: No
**Acceptance**: Test passes end-to-end.

## Phase 4 — Graceful Degradation

### [X] T4.1: Add RTPengine failure handling to OpenSIPS routes
**Description**: Update `opensips/opensips.cfg.tpl` INVITE route to check RTPengine availability before `rtpengine_offer()`. If unavailable, reply `488 Not Acceptable Here` with appropriate Reason header.
**Phase**: 4
**Depends on**: T3.3
**Parallel**: No
**Acceptance**: Container-based SIP test (e.g., `opensips-cli -x mi` query or Python script via `socket`) shows 100% `488 Not Acceptable Here` responses when RTPengine UDP port 22222 is unreachable. If no SIP test tool is available, verify via OpenSIPS logs that `rtpengine_offer()` failure triggers `t_reply("488", "Not Acceptable Here")`.

### [X] T4.2: Add PostgreSQL failure handling to OpenSIPS routes
**Description**: Update auth and registration routes to check database connectivity. If unavailable, reply `480 Temporarily Unavailable`.
**Phase**: 4
**Depends on**: T3.3
**Parallel**: [P] with T4.1
**Acceptance**: Container-based test or `opensips-cli -x mi` query shows 100% `480 Temporarily Unavailable` responses when PostgreSQL is unreachable. If no SIP test tool is available, verify via OpenSIPS logs that DB failure triggers `t_reply("480", "Temporarily Unavailable")`.

### [X] T4.3: Create graceful degradation integration test
**Description**: Create `tests/integration/test_graceful_degradation.py` that injects failures into RTPengine and PostgreSQL and verifies correct SIP response codes.
**Phase**: 4
**Depends on**: T4.1, T4.2
**Parallel**: No
**Acceptance**: Test passes; all failure scenarios return valid SIP codes.

## Phase 5 — Observability Integration

### [X] T5.1: Add Prometheus metrics for health state
**Description**: Update OpenSIPS exporter to expose: `opensips_healthcheck_failures_total`, `opensips_container_restarts_total`, `opensips_dispatcher_circuit_state`. Update Prometheus alert rules.
**Phase**: 5
**Depends on**: T4.3
**Parallel**: No
**Acceptance**: Metrics appear in Prometheus; alert rules evaluate.

### [X] T5.2: Create Grafana health dashboard
**Description**: Create `docker/grafana/provisioning/dashboards/tsisip/health-status.json` with panels: container health (table), circuit state (stat), restart count (graph), degradation events (annotations).
**Phase**: 5
**Depends on**: T5.1
**Parallel**: No
**Acceptance**: Dashboard loads real-time health data.

### [X] T5.3: Document health check runbook
**Description**: Update `docs/TSiSIP-OPERATOR-RUNBOOK.md` with: health check procedures, restart policy explanations, circuit breaker troubleshooting, graceful degradation verification.
**Phase**: 5
**Depends on**: T5.2
**Parallel**: No
**Acceptance**: Runbook contains actionable procedures for all health scenarios.
