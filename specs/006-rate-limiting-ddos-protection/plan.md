# Implementation Plan: SIP-Layer Rate Limiting and DDoS Protection

## Overview

This plan translates the feature specification into an executable implementation roadmap for protecting TSiSIP from SIP-layer abuse through per-source IP throttling, subscriber auth rate limits, dispatcher failover, dynamic ban lists, and traffic anomaly detection.

---

## Architecture & Stack Choices

### Container Platform
- **Docker Engine** with Docker Compose V2
- OpenSIPS modules: `pike`, `htable`, `dispatcher`, `auth_db`
- Anomaly detection sidecar (Python + Redis)

### Rate Limiting Stack
| Component | Module/Tool | Purpose |
|---|---|---|
| IP Throttling | `pike` | Per-source IP request rate limiting |
| Auth Limits | `htable` + `auth_db` | Per-subscriber auth failure tracking |
| Load Balancing | `dispatcher` | Capacity-based routing |
| Ban Lists | `htable` | Dynamic IP/URI blocking with TTL |
| Anomaly Detection | Python sidecar | Statistical traffic analysis |

### Network Architecture
- `pike` monitors all inbound SIP on `sip_edge`
- `htable` operates in shared memory
- Anomaly sidecar listens to OpenSIPS events via `event_route`

---

## Implementation Phases

### Phase 1 — IP Throttling (pike)
- Configure `pike` module with per-IP thresholds
- Add `pike_check_req()` to main request route
- Handle NATed enterprise (tuple-based tracking)

### Phase 2 — Auth Rate Limits
- Configure `htable` counters for auth failures
- Add failure tracking to auth routes
- Implement 403 response on threshold breach

### Phase 3 — Dispatcher Load Balancing
- Configure `dispatcher` with load-based weights
- Add capacity thresholds (80% high-water)
- Implement redistribution logic

### Phase 4 — Ban Lists
- Create `htable` for banned IPs and URIs
- Add ban management MI commands
- Implement TTL-based expiration

### Phase 5 — Anomaly Detection
- Python sidecar consuming OpenSIPS events
- Statistical baseline calculation
- Global throttle trigger

### Phase 6 — Integration & Testing
- End-to-end DDoS simulation
- False-positive measurement
- Performance validation

---

## File Structure

```
opensips/
  rate-limiting.cfg.tpl    # pike, htable, dispatcher config
  ban-list.cfg.tpl         # Ban list management routes
  anomaly-events.cfg       # Event route definitions
docker/
  anomaly-detector/
    Dockerfile
    detector.py            # Anomaly detection engine
    baseline.py            # Statistical baseline
```

---

## Validation Gates

| Gate | Check | Command |
|---|---|---|
| Config | OpenSIPS config valid | `opensips -c` |
| Throttle | Single IP blocked | `sipp` flood test |
| Auth | Subscriber throttled | Wrong password test |
| Load | Dispatcher redistributes | Capacity test |
| Ban | TTL expiration works | `opensipsctl fifo htable_dump` |
| Anomaly | Distributed attack detected | Synthetic botnet test |
