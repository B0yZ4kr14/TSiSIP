# Requirements Checklist: SIP-Layer Rate Limiting and DDoS Protection

## Functional Requirements

- [x] FR-001: Per-Source IP Request Throttling — OpenSIPS pike module configured with 100/10s and 200/60s thresholds.
- [x] FR-002: Subscriber Authentication Rate Limits — htable counters track 10 failures per 60s per subscriber with 300s ban.
- [x] FR-003: Dispatcher Failover Under Load — Load-based routing redistributes traffic at 80% capacity threshold.
- [x] FR-004: Ban Lists with Automatic Expiration — htable entries with TTL and MI management commands.
- [x] FR-005: Traffic Anomaly Detection — Event sidecar aggregates pike, auth, and dispatcher events with statistical baseline.

## Success Criteria

- [x] SC-001: Legitimate call success rate during flood ≥ 99%.
- [x] SC-002: Time to block a single abusive IP ≤ 1 second.
- [x] SC-003: False-positive block rate for NATed enterprise ≤ 0.1%.
- [x] SC-004: Dispatcher redistribution latency under load ≤ 2 seconds.
- [x] SC-005: Ban list TTL accuracy 100%.
- [x] SC-006: Anomaly detection alert latency ≤ 60 seconds.

## Risks

- [x] R-001: Aggressive throttling of NATed users mitigated by tuple tracking and trunk whitelisting.
- [x] R-002: htable memory exhaustion mitigated by size limits and LRU eviction.
- [x] R-003: Timing attack leakage mitigated by uniform 403 responses.
- [x] R-004: Call state inconsistency mitigated by stateless backend design.
- [x] R-005: Anomaly false positives mitigated by segmented 24-hour baseline.

**Status: PASS**
