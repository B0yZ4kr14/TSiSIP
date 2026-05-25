# Blueprint — Advanced Container Health Checks and Auto-Healing

## Overview

Implement multi-layer health probes, intelligent restart policies, circuit breaker patterns for dispatcher targets, graceful degradation, and health status observability to maximize service uptime and minimize manual intervention in the TSiSIP Docker infrastructure.

## Requirements

- **FR-004-001**: Multi-Layer Health Probes — Docker-native `HEALTHCHECK` for OpenSIPS, PostgreSQL, RTPengine (Asterisk deferred). Layers: TCP socket, HTTP/JSON MI, SIP OPTIONS.
- **FR-004-002**: Auto-Restart Policies with Exponential Backoff — `on-failure` with delay starting at 5s, max 60s, `max_attempts: 10`; critical services use `unless-stopped`.
- **FR-004-003**: Circuit Breaker for Dispatcher Targets — `ds_ping_method=OPTIONS`, `ds_ping_interval=10`, `ds_probing_mode=1`, `ds_probing_threshold=5`; half-open retry every 60s.
- **FR-004-004**: Graceful Degradation — RTPengine unreachable: `488 Not Acceptable Here`; PostgreSQL unreachable: `480 Temporarily Unavailable`.
- **FR-004-005**: Health Status Dashboard Integration — Prometheus metrics for health state; Grafana panels.
- **FR-004-006**: Alerting on Health State Transitions — healthy→unhealthy, circuit open, restart threshold exceeded; webhook channels.

## Architecture

- **Container Platform**: Docker Engine with Docker Compose V2; native Docker `HEALTHCHECK` instructions.
- **Health Check Layers**:
  - L1 (TCP): All services via `nc -z` or `wget` (5s interval).
  - L2 (HTTP/JSON): OpenSIPS, RTPengine via MI interface (10s interval).
  - L3 (SIP OPTIONS): OpenSIPS dispatcher probing (10s interval).
- **Circuit Breaker**: OpenSIPS `dispatcher` module with probing; state machine: active → probing → inactive → half-open → active.
- **Graceful Degradation**: OpenSIPS route logic; 488 for RTPengine failure, 480 for PostgreSQL failure.

## Implementation Plan

### Phase 1 — Health Check Infrastructure
- Docker `HEALTHCHECK` for all containers.
- Start period and interval staggering.
- Custom health check scripts where needed.

### Phase 2 — Restart Policies & Auto-Healing
- Docker Compose restart policies with exponential backoff.
- Critical vs. supporting service policies.

### Phase 3 — Circuit Breaker Implementation
- OpenSIPS dispatcher probing configuration.
- Failure threshold tuning.
- Half-open state management.

### Phase 4 — Graceful Degradation
- OpenSIPS route modifications for failure scenarios.
- SIP response code mapping.

### Phase 5 — Observability Integration
- Prometheus metrics for health state.
- Grafana panels for health visualization.
- Alert rules for state transitions.

## Tasks

**Phase 1 — Health Check Infrastructure**
- T1.1: Create OpenSIPS health check script
- T1.2: Create PostgreSQL health check script
- T1.3: Create RTPengine health check script
- T1.4: Create Asterisk health check script (DEFERRED)
- T1.5: Add `HEALTHCHECK` to OpenSIPS, PostgreSQL, RTPengine Dockerfiles

**Phase 2 — Restart Policies & Auto-Healing**
- T2.1: Configure Docker Compose restart policies
- T2.2: Implement exponential backoff verification

**Phase 3 — Circuit Breaker Implementation**
- T3.1: Configure OpenSIPS dispatcher probing
- T3.2: Implement half-open retry logic
- T3.3: Create circuit breaker integration test

**Phase 4 — Graceful Degradation**
- T4.1: Add RTPengine failure handling to OpenSIPS routes
- T4.2: Add PostgreSQL failure handling to OpenSIPS routes
- T4.3: Create graceful degradation integration test

**Phase 5 — Observability Integration**
- T5.1: Add Prometheus metrics for health state
- T5.2: Create Grafana health dashboard
- T5.3: Document health check runbook

## Validation

- `docker ps` shows `healthy` for OpenSIPS, PostgreSQL, RTPengine within 60s of startup.
- Simulated `kill -9` on OpenSIPS triggers restart within 15s.
- Packet capture shows no new INVITEs routed to failed target; `ds_list` reports `state=inactive`.
- 100% of failure scenarios result in valid SIP response codes; no crashes.
- Dashboard displays real-time health for all core services with <5s latency.

## Risks & Dependencies

| Risk | Mitigation |
|---|---|
| Aggressive restart loops exhaust host resources | Cap `max_attempts`, use exponential backoff |
| Health probes add latency under high load | Use lightweight TCP/OPTIONS probes |
| False positives from slow-starting PostgreSQL | Increase `start_period` to 120s for PostgreSQL |
| Circuit breaker opens prematurely during transient blips | Require 5 consecutive failures; half-open cooling period |

**Dependencies**: OpenSIPS 3.6 LTS; Docker Compose ≥2.20; Prometheus/Grafana; RTPengine.
