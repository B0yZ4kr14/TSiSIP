# Blueprint — SIP-Layer Rate Limiting and DDoS Protection

## Overview

Protect the TSiSIP stack from SIP-layer abuse by implementing per-source IP throttling, subscriber auth rate limits, dispatcher failover under load, dynamic ban lists with expiration, and traffic anomaly detection.

## Requirements

- **FR-006-001**: Per-Source IP Request Throttling — OpenSIPS `pike` module; 100 requests/10s per IP, 200/60s; silent drop for UDP, `429` for TCP/TLS.
- **FR-006-002**: Subscriber Authentication Rate Limits — `htable` counters for auth failures; 10 failures/60s per subscriber; ban duration 300s; `403 Forbidden` after threshold.
- **FR-006-003**: Dispatcher Failover Under Load — `dispatcher` with load-based weights; redistribution when target exceeds 80% capacity.
- **FR-006-004**: Ban Lists with Automatic Expiration — `htable` stores banned IPs/URIs with TTL; sources: `pike`, auth, manual; MI management.
- **FR-006-005**: Traffic Anomaly Detection — Python sidecar consuming OpenSIPS events; z-score >3 sigma triggers alert and optional global throttle.

## Architecture

- **Container Platform**: Docker Engine with Docker Compose V2.
- **Rate Limiting Stack**:
  - IP Throttling: `pike` module.
  - Auth Limits: `htable` + `auth_db`.
  - Load Balancing: `dispatcher` with capacity-based routing.
  - Ban Lists: `htable` with TTL.
  - Anomaly Detection: Python sidecar listening to OpenSIPS events via `event_route`.
- **Network**: `pike` monitors all inbound SIP on `sip_edge`; `htable` operates in shared memory.

## Implementation Plan

### Phase 1 — IP Throttling (pike)
- Configure `pike` module with per-IP thresholds.
- Add `pike_check_req()` to main request route.
- Handle NATed enterprise (tuple-based tracking).

### Phase 2 — Auth Rate Limits
- Configure `htable` counters for auth failures.
- Add failure tracking to auth routes.
- Implement `403` response on threshold breach.

### Phase 3 — Dispatcher Load Balancing
- Configure `dispatcher` with load-based weights.
- Add capacity thresholds (80% high-water).
- Implement redistribution logic.

### Phase 4 — Ban Lists
- Create `htable` for banned IPs and URIs.
- Add ban management MI commands.
- Implement TTL-based expiration.

### Phase 5 — Anomaly Detection
- Python sidecar consuming OpenSIPS events.
- Statistical baseline calculation.
- Global throttle trigger.

### Phase 6 — Integration & Testing
- End-to-end DDoS simulation.
- False-positive measurement.
- Performance validation.

## Tasks

**Phase 1 — IP Throttling (pike)**
- T1.1: Configure `pike` module
- T1.2: Handle NATed enterprise traffic
- T1.3: Add TCP connection limits

**Phase 2 — Auth Rate Limits**
- T2.1: Configure `htable` for auth failures
- T2.2: Implement subscriber auth throttling

**Phase 3 — Dispatcher Load Balancing**
- T3.1: Configure load-based dispatcher routing
- T3.2: Add dispatcher load monitoring

**Phase 4 — Ban Lists**
- T4.1: Create ban list `htable`
- T4.2: Add ban management MI commands
- T4.3: Implement ban TTL accuracy

**Phase 5 — Anomaly Detection**
- T5.1: Create anomaly detector sidecar
- T5.2: Add event routes for anomaly detection
- T5.3: Add global throttle on anomaly

**Phase 6 — Integration & Testing**
- T6.1: Create DDoS simulation test
- T6.2: Add rate limiting Grafana dashboard
- T6.3: Document rate limiting runbook

## Validation

- Single IP flood at 200 rps shows ≥95% dropped after threshold; no impact on other IPs.
- 15 wrong passwords result in `403` on attempts 11-15; success after 300s.
- Load test with 2 backends shows redistribution at 80% capacity; zero dropped calls.
- Banned entry disappears at TTL expiry; MI reflects current state.
- Synthetic distributed flood (1000 IPs at 50 rps each) triggers anomaly alert within 60s.

## Risks & Dependencies

| Risk | Mitigation |
|---|---|
| Overly aggressive throttling blocks legitimate NATed users | Use `(ip, from_user)` tuple; whitelist known trunk IPs |
| `htable` memory exhaustion under large-scale attack | Set size limits with LRU eviction; monitor memory |
| Auth rate limiting leaks valid subscriber existence | Return identical `403` for ban and invalid credentials |
| Dispatcher redistribution causes call state inconsistency | Ensure transaction state is local to OpenSIPS |
| Anomaly detection false positives during scheduled events | Use 24-hour rolling baseline with weekday/hour segmentation |

**Dependencies**: OpenSIPS 3.6 LTS (`pike`, `htable`, `dispatcher`, `auth_db`); PostgreSQL; Docker Compose.
