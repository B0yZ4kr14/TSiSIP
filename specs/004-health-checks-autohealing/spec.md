# Feature Specification: Advanced Container Health Checks and Auto-Healing

## Overview

| Field | Value |
|-------|-------|
| **Feature** | Advanced Container Health Checks and Auto-Healing |
| **Short name** | health-checks-autohealing |
| **Created** | 2026-05-16 |
| **Status** | Completed |
| **Last Updated** | 2026-05-19 |
| **Context** | TSiSIP is a Docker-first SIP infrastructure using OpenSIPS 3.6 LTS, PostgreSQL, RTPengine, and Asterisk. Container orchestration reliability is critical for production deployments. |
| **Objective** | Implement multi-layer health probes, intelligent restart policies, circuit breaker patterns for dispatcher targets, graceful degradation, and health status observability to maximize service uptime and minimize manual intervention. |

## User Scenarios & Testing

### Scenario 1: TCP Health Probe Failure Triggers Container Restart
**Given** the OpenSIPS container is running in the Docker Compose stack,
**When** the TCP probe on port 5060/tcp fails three consecutive times,
**Then** Docker marks the container as unhealthy and triggers an auto-restart with exponential backoff.

### Scenario 2: Circuit Breaker Isolates Failing Asterisk Backend
**Given** a dispatcher target (Asterisk backend) is registered in OpenSIPS,
**When** the target fails the application-level OPTIONS probe 5 times within 30 seconds,
**Then** OpenSIPS marks the target as inactive (circuit open), stops routing traffic to it, and emits a monitoring event.

### Scenario 3: Graceful Degradation Under Partial Stack Failure
**Given** the RTPengine container becomes unhealthy,
**When** OpenSIPS detects the failure via the health status dashboard integration,
**Then** OpenSIPS returns 488 Not Acceptable for new INVITEs requiring media relay instead of crashing or routing to a dead relay.

### Edge Case 1: Health Probe Storm During Stack Startup
During cold start, all containers probe simultaneously. The system must stagger probes via `start_period` and `start_interval` to avoid false negatives.

### Edge Case 2: Flapping Backend Rapidly Opens/Closes Circuit
A backend with intermittent network loss triggers repeated circuit transitions. The circuit breaker must use a half-open state with a cooling-off period (e.g., 60s) before re-admitting traffic.

### Edge Case 3: Health Dashboard Becomes Unreachable
If the health status dashboard (e.g., Prometheus/Grafana) is down, health probes and auto-healing must continue independently without central dependency.

## Functional Requirements

### FR-004-001: Multi-Layer Health Probes
- Each container (OpenSIPS, PostgreSQL, RTPengine) must expose Docker-native `HEALTHCHECK` instructions. Asterisk health checks are deferred to a future Asterisk containerization feature (Feature 010).
- Layers: (a) TCP socket probe, (b) HTTP/JSON management API probe (OpenSIPS MI), (c) application-level SIP OPTIONS probe via `dispatcher` module.
- **Acceptance Criteria**: `docker ps` shows `healthy` for OpenSIPS, PostgreSQL, and RTPengine within 60 seconds of startup; `unhealthy` is reported within 10 seconds of failure. Asterisk health checks are out of scope for this feature.

### FR-004-002: Auto-Restart Policies with Exponential Backoff
- Docker Compose `restart_policy` must use `on-failure` with `delay` starting at 5s, max 60s, and `max_attempts: 10`.
- Critical path containers (OpenSIPS, PostgreSQL) use `restart: unless-stopped`; supporting services use `restart: on-failure`.
- **Acceptance Criteria**: Simulated `kill -9` on OpenSIPS process results in container restart within 15 seconds; backoff increases monotonically up to the cap.

### FR-004-003: Circuit Breaker for Dispatcher Targets
- OpenSIPS `dispatcher` module must use `ds_ping_method=OPTIONS` and `ds_ping_interval=10` with `ds_probing_mode=1`.
- After dispatcher probing threshold (5 consecutive failures) exceeds the configured limit (`ds_probing_threshold=5`), the target is set to inactive (`ds_set_state`).
- A half-open retry is attempted every 60 seconds.
- **Acceptance Criteria**: Packet capture shows no new INVITEs routed to the failed target; `opensips-cli -x mi ds_list` reports `state=inactive`.

### FR-004-004: Graceful Degradation
- If RTPengine is unreachable, OpenSIPS must skip `rtpengine_offer()` / `rtpengine_answer()` and reply `488 Not Acceptable Here` for calls requiring relay.
- If PostgreSQL is unreachable, OpenSIPS must return `480 Temporarily Unavailable` for registration-dependent requests.
- **Acceptance Criteria**: 100% of failure scenarios result in a valid SIP response code; no process crashes or segfaults.

### FR-004-005: Health Status Dashboard Integration
- OpenSIPS MI `get_statistics` output is scraped by a Prometheus exporter (or `statsd`) and visualized in Grafana.
- Key metrics: `dispatched_targets_active`, `healthcheck_failures_total`, `container_restarts_total`.
- **Acceptance Criteria**: Dashboard displays real-time health for all 4 core services with <5s latency.

### FR-004-006: Alerting on Health State Transitions
- Alerts are fired on transitions: healthy -> unhealthy, circuit open, container restart threshold exceeded.
- Alert channels: webhook to external incident system.
- **Acceptance Criteria**: All 3 transition types trigger an alert within 10 seconds.

## Success Criteria

| ID | Criterion | Target | Measurement Method |
|----|-----------|--------|-------------------|
| SC-001 | Container restart time after fatal crash | ≤ 15 seconds | `kill -9` simulation + `time` |
| SC-002 | Failed dispatcher target isolation time | ≤ 30 seconds | Packet capture + `ds_list` MI |
| SC-003 | False-positive health failures during startup | 0 | 10 cold-start repetitions |
| SC-004 | Valid SIP response under any single-component failure | 100% | Load test with component failure injection |
| SC-005 | Health dashboard scrape latency | ≤ 5 seconds | Prometheus query evaluation |
| SC-006 | Mean time to detect failure (MTTD) | ≤ 10 seconds | Synthetic blackhole test |

## Key Entities

| Entity | Description | Attributes |
|--------|-------------|------------|
| HealthProbe | A Docker or application-level probe configuration | target, method, interval, timeout, retries |
| DispatcherTarget | A backend SIP server managed by OpenSIPS dispatcher | uri, state, priority, probe_count, last_seen |
| CircuitBreaker | State machine guarding traffic to a failing target | target_id, state (closed/open/half-open), failure_count, last_failure |

## Scope

### In Scope
- Docker `HEALTHCHECK` definitions in all service Dockerfiles.
- Docker Compose `restart_policy` and `healthcheck` overrides.
- OpenSIPS dispatcher probing and target state management.
- Graceful SIP response paths for PostgreSQL and RTPengine failures.
- Prometheus/Grafana dashboard wiring for health metrics.
- Alerting rules for state transitions.

### Out of Scope
- Kubernetes-native health probes (e.g., liveness/readiness probes).
- Host-level systemd service restarts.
- Automatic horizontal scaling of OpenSIPS or Asterisk.
- Deep application-level business logic health checks inside Asterisk dialplan.

## Dependencies

| Dependency | Description | Impact if Missing |
|------------|-------------|-------------------|
| OpenSIPS 3.6 LTS | Dispatcher module, MI interface, statistics | Cannot implement circuit breaker or probes |
| Docker Compose ≥ 2.20 | `restart_policy` with `delay`, `start_interval` | Backoff and startup grace unavailable |
| Prometheus / Grafana | Metrics aggregation and visualization | Dashboard and alerting unavailable |
| RTPengine | Media relay health verification | Graceful degradation tests incomplete |

## Assumptions

- The Docker runtime supports `HEALTHCHECK` with custom `start_period` (true for Docker Engine ≥ 20.10).
- OpenSIPS MI socket is exposed on a Unix domain socket or TCP port for external scraping.
- Network partitions between containers are rare; most failures are process crashes or resource exhaustion.
- The operator has permissions to restart containers and inspect logs.

## Risks

| ID | Risk | Likelihood | Impact | Mitigation |
|----|------|------------|--------|------------|
| R-001 | Aggressive restart loops exhaust host resources (CPU, disk I/O) | Medium | High | Cap `max_attempts`, use exponential backoff, set `restart_window` |
| R-002 | Health probes add latency to OpenSIPS under high load | Medium | Medium | Use lightweight TCP/OPTIONS probes; run MI on separate thread |
| R-003 | False positives from slow-starting PostgreSQL cause unnecessary restarts | High | Medium | Increase `start_period` to 120s for PostgreSQL container |
| R-004 | Circuit breaker opens prematurely during transient network blips | Medium | Medium | Require 5 consecutive failures; use half-open cooling period |
| R-005 | Health dashboard becomes a single point of observability failure | Low | Low | Keep probes Docker-native; dashboard is read-only overlay |

## Notes

> **Constitution Reference**: See `.specify/memory/constitution.md` §2 — PostgreSQL is the sole persistence layer; `db_postgres` is the only OpenSIPS DB module.
> **Constitution Reference**: See `.specify/memory/constitution.md` §3 — The `sanity` module is forbidden in OpenSIPS 3.6 LTS.
- Store any probe credentials (e.g., MI authentication) as Docker secrets, never in the image layer.
- All service names and network names must use lowercase snake_case (e.g., `sip_edge`, `sip_internal`, `db_internal`).

## Requirements

### Functional Requirements

#### FR-004-001: Core Capability
**Description**: The system shall provide the primary capability described in this feature specification.
**Acceptance Criteria**:
- The capability is available when the feature is enabled.
- The capability integrates with existing TSiSIP components (OpenSIPS, PostgreSQL, OCP) without regression.

#### FR-004-002: Configuration & Persistence
**Description**: All configuration changes shall be persisted to PostgreSQL and reflected in runtime behavior without requiring a full stack restart.
**Acceptance Criteria**:
- Configuration changes survive container restarts.
- Invalid configuration is rejected at the validation gate.

#### FR-004-003: Observability & Audit
**Description**: The feature shall emit metrics or audit events compatible with the TSiSIP Prometheus/Grafana and OCP audit logging pipelines.
**Acceptance Criteria**:
- Metrics or audit events are visible in the appropriate dashboard or log.
- Failure conditions are logged with sufficient context for debugging.

