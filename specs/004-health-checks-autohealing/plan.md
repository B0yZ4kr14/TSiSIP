## Summary

This feature implements the specified capability for the TSiSIP SIP edge-proxy platform.

## Technical Context

- **OpenSIPS 3.6 LTS**: Core SIP proxy and signaling edge
- **PostgreSQL**: Database backend for configuration and state
- **Docker & Docker Compose**: Container orchestration and deployment
- **RTPengine**: Media relay for RTP/RTCP
- **Asterisk**: Backend PBX for voice applications

## Project Structure

Relevant directories and files for this feature are located under `specs/$spec/` and integrated into the main project tree.

# Implementation Plan: Advanced Container Health Checks and Auto-Healing

## Overview

This plan translates the feature specification into an executable implementation roadmap for multi-layer health probes, intelligent restart policies, circuit breaker patterns, and graceful degradation in the TSiSIP Docker infrastructure.

---

## Architecture & Stack Choices

### Container Platform
- **Docker Engine** with Docker Compose V2
- Native Docker HEALTHCHECK instructions for all services
- Docker restart policies with exponential backoff

### Health Check Layers
| Layer | Service | Method | Interval |
|---|---|---|---|
| L1 (TCP) | All | `nc -z` or `wget` | 5s |
| L2 (HTTP/JSON) | OpenSIPS, RTPengine | MI interface | 10s |
| L3 (SIP OPTIONS) | OpenSIPS dispatcher | `ds_ping_method=OPTIONS` | 10s |

### Circuit Breaker
- OpenSIPS `dispatcher` module with probing
- State machine: active -> probing -> inactive -> half-open -> active
- Half-open retry interval: 60s

### Graceful Degradation
- OpenSIPS route logic for RTPengine/PostgreSQL failure
- SIP response codes: 488 (RTPengine down), 480 (PostgreSQL down)

---

## Implementation Phases

### Phase 1 — Health Check Infrastructure
- Docker HEALTHCHECK for all containers
- Start period and interval staggering
- Custom health check scripts where needed

### Phase 2 — Restart Policies & Auto-Healing
- Docker Compose restart policies
- Exponential backoff configuration
- Critical vs. supporting service policies

### Phase 3 — Circuit Breaker Implementation
- OpenSIPS dispatcher probing configuration
- Failure threshold tuning
- Half-open state management

### Phase 4 — Graceful Degradation
- OpenSIPS route modifications for failure scenarios
- SIP response code mapping
- Dependency failure handling

### Phase 5 — Observability Integration
- Prometheus metrics for health state
- Grafana panels for health visualization
- Alert rules for state transitions

---

## File Structure

```
docker/
  healthcheck/
    opensips-health.sh
    postgres-health.sh
    rtpengine-health.sh
    asterisk-health.sh
opensips/
  health-routes.cfg.tpl    # Health check route logic
  dispatcher-probing.cfg   # Circuit breaker configuration
prometheus/
  health-metrics-rules.yml # Alert rules for health transitions
```

---

## Validation Gates

| Gate | Check | Command |
|---|---|---|
| Build | All images build with HEALTHCHECK | `docker compose build` |
| Health | All containers report healthy | `docker ps` |
| Restart | Simulated failure triggers restart | `kill -9` test |
| Circuit | Failed target isolated | `opensips-cli -x mi ds_list` |
| Degradation | Valid SIP responses under failure | container-based SIP load test (document external tooling requirement if not containerized) |
