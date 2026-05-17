# Requirements Checklist: Advanced Container Health Checks and Auto-Healing

## Functional Requirements

- [x] FR-001: Multi-Layer Health Probes — Docker HEALTHCHECK and application-level probes defined for all containers.
- [x] FR-002: Auto-Restart Policies with Exponential Backoff — Compose restart_policy configured with on-failure and capped delay.
- [x] FR-003: Circuit Breaker for Dispatcher Targets — OpenSIPS dispatcher OPTIONS probing with inactive state and half-open retry.
- [x] FR-004: Graceful Degradation — Valid SIP responses (488/480) returned when RTPengine or PostgreSQL is unreachable.
- [x] FR-005: Health Status Dashboard Integration — Prometheus/Grafana scraping of OpenSIPS MI statistics.
- [x] FR-006: Alerting on Health State Transitions — Alerts fired on unhealthy, circuit-open, and restart-threshold events.

## Success Criteria

- [x] SC-001: Container restart time after fatal crash ≤ 15 seconds.
- [x] SC-002: Failed dispatcher target isolation time ≤ 30 seconds.
- [x] SC-003: Zero false-positive health failures during startup (10 repetitions).
- [x] SC-004: 100% valid SIP response under any single-component failure.
- [x] SC-005: Health dashboard scrape latency ≤ 5 seconds.
- [x] SC-006: Mean time to detect failure (MTTD) ≤ 10 seconds.

## Risks

- [x] R-001: Restart loops mitigated by max_attempts and backoff caps.
- [x] R-002: Probe latency mitigated by lightweight TCP/OPTIONS probes.
- [x] R-003: PostgreSQL false positives mitigated by 120s start_period.
- [x] R-004: Premature circuit breaker mitigated by 5-failure threshold and half-open state.
- [x] R-005: Dashboard SPOF mitigated by keeping probes Docker-native.

**Status: PASS**
