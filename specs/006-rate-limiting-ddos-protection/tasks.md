# Tasks: SIP-Layer Rate Limiting and DDoS Protection

## Phase 1 — IP Throttling (pike)

### [ ] T1.1: Configure pike module
**Description**: Update `opensips/opensips.cfg.tpl` with `loadmodule "pike.so"`, `modparam("pike", "sampling_time_unit", 2)`, `modparam("pike", "reqs_density_per_unit", 50)`, `modparam("pike", "remove_latency", 10)`. Add `pike_check_req()` to main request route. Return `429` for TCP/TLS, silent drop for UDP.
**Phase**: 1
**Depends on**: —
**Parallel**: No
**Acceptance**: `opensips -c` passes; `sipp` at 200 rps shows >95% dropped.

### [ ] T1.2: Handle NATed enterprise traffic
**Description**: Add logic to use `(source_ip, from_user)` tuple for authenticated traffic instead of blanket IP blocking. Whitelist known enterprise IPs via `permissions` module or `htable`.
**Phase**: 1
**Depends on**: T1.1
**Parallel**: No
**Acceptance**: Enterprise PBX behind NAT with high volume is not blocked.

### [ ] T1.3: Add TCP connection limits
**Description**: Configure `tcp_max_connections` and `tcp_connection_lifetime` in OpenSIPS. Add connection count monitoring via `get_statistics`.
**Phase**: 1
**Depends on**: T1.1
**Parallel**: No
**Acceptance**: Slowloris test with partial packets does not exhaust connections.

## Phase 2 — Auth Rate Limits

### [ ] T2.1: Configure htable for auth failures
**Description**: Update `opensips/opensips.cfg.tpl` with `loadmodule "htable.so"`, `modparam("htable", "htable", "auth_failures=>size=1024;autoexpire=3600")`. Add counter increment on `auth_challenge` failure.
**Phase**: 2
**Depends on**: T1.3
**Parallel**: No
**Acceptance**: `opensipsctl fifo htable_dump auth_failures` shows counters.

### [ ] T2.2: Implement subscriber auth throttling
**Description**: Add route logic: if `auth_failures[$au]` > 10 in 60s, reply `403 Forbidden` and add source IP to ban list. Reset counter on successful auth.
**Phase**: 2
**Depends on**: T2.1
**Parallel**: No
**Acceptance**: 15 wrong passwords result in 403 on attempts 11-15; success after 300s.

## Phase 3 — Dispatcher Load Balancing

### [ ] T3.1: Configure load-based dispatcher routing
**Description**: Update `opensips/opensips.cfg.tpl` with `modparam("dispatcher", "ds_ping_method", "OPTIONS")`, `modparam("dispatcher", "ds_ping_interval", 10)`, load-based weights. Add `ds_select_dst` with capacity check.
**Phase**: 3
**Depends on**: T2.2
**Parallel**: No
**Acceptance**: Load test shows redistribution at 80% capacity.

### [ ] T3.2: Add dispatcher load monitoring
**Description**: Add Prometheus metrics: `opensips_dispatcher_target_load`, `opensips_dispatcher_redistributions_total`. Update exporter.
**Phase**: 3
**Depends on**: T3.1
**Parallel**: No
**Acceptance**: Metrics appear in Prometheus; Grafana shows load distribution.

## Phase 4 — Ban Lists

### [ ] T4.1: Create ban list htable
**Description**: Add `modparam("htable", "htable", "ban_list=>size=4096;autoexpire=86400")`. Create route to check `ban_list` on incoming requests. Sources: pike, auth, manual.
**Phase**: 4
**Depends on**: T3.2
**Parallel**: No
**Acceptance**: Banned IPs are dropped; MI shows current ban state.

### [ ] T4.2: Add ban management MI commands
**Description**: Create MI commands: `ban_add`, `ban_del`, `ban_list`. Document in runbook.
**Phase**: 4
**Depends on**: T4.1
**Parallel**: No
**Acceptance**: `opensipsctl fifo ban_list` returns JSON of banned entries.

### [ ] T4.3: Implement ban TTL accuracy
**Description**: Ensure `autoexpire` in `htable` removes entries exactly at TTL. Add validation test.
**Phase**: 4
**Depends on**: T4.2
**Parallel**: No
**Acceptance**: Unit test confirms TTL accuracy to within 1 second.

## Phase 5 — Anomaly Detection

### [ ] T5.1: Create anomaly detector sidecar
**Description**: Create `docker/anomaly-detector/Dockerfile` from `python:3.11-slim`. Create `detector.py` that: consumes OpenSIPS events via UDP socket, maintains 24-hour rolling baseline, calculates z-score, triggers alert if >3 sigma.
**Phase**: 5
**Depends on**: T4.3
**Parallel**: No
**Acceptance**: Synthetic distributed flood triggers alert within 60s.

### [ ] T5.2: Add event routes for anomaly detection
**Description**: Update `opensips/opensips.cfg.tpl` with `event_route[E_PIKE_BLOCKED]`, `event_route[E_AUTH_FAILURE]`, `event_route[E_DISPATCHER_STATUS]` that send events to detector sidecar.
**Phase**: 5
**Depends on**: T5.1
**Parallel**: No
**Acceptance**: Events are received by detector; baseline establishes within 1 hour.

### [ ] T5.3: Add global throttle on anomaly
**Description**: Add route to apply global rate limit (e.g., 50% of baseline) when anomaly alert fires. Use `htable` flag `global_throttle`.
**Phase**: 5
**Depends on**: T5.2
**Parallel**: No
**Acceptance**: Global throttle reduces accepted requests during attack.

## Phase 6 — Integration & Testing

### [ ] T6.1: Create DDoS simulation test
**Description**: Create `tests/integration/test_ddos_protection.py` that: runs single-IP flood, verifies throttling, runs distributed flood, verifies anomaly detection, runs NATed enterprise simulation, verifies no false positive.
**Phase**: 6
**Depends on**: T5.3
**Parallel**: No
**Acceptance**: All tests pass; legitimate call success rate >99%.

### [ ] T6.2: Add rate limiting Grafana dashboard
**Description**: Create `docker/grafana/provisioning/dashboards/tsisip/rate-limiting.json` with panels: blocked IPs (table), auth failures (graph), dispatcher load (gauge), anomaly score (graph), ban list size (stat).
**Phase**: 6
**Depends on**: T6.1
**Parallel**: No
**Acceptance**: Dashboard shows real-time rate limiting data.

### [ ] T6.3: Document rate limiting runbook
**Description**: Update `docs/TSiSIP-OPERATOR-RUNBOOK.md` with: pike tuning, auth threshold adjustment, ban list management, anomaly response procedures, false-positive troubleshooting.
**Phase**: 6
**Depends on**: T6.2
**Parallel**: No
**Acceptance**: Runbook contains actionable procedures for all rate limiting scenarios.
