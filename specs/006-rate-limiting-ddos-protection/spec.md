# Feature Specification: SIP-Layer Rate Limiting and DDoS Protection

## Overview

| Field | Value |
|-------|-------|
| **Feature** | SIP-Layer Rate Limiting and DDoS Protection |
| **Short name** | rate-limiting-ddos-protection |
| **Created** | 2026-05-16 |
| **Status** | Draft |
| **Context** | OpenSIPS 3.6 LTS is the public SIP signaling endpoint on 5060/udp and 5060/tcp. It is exposed to the internet and vulnerable to SIP flooding, brute-force registration attempts, and toll fraud scan campaigns. |
| **Objective** | Protect the TSiSIP stack from SIP-layer abuse by implementing per-source IP throttling, subscriber auth rate limits, dispatcher failover under load, dynamic ban lists with expiration, and traffic anomaly detection. |

## User Scenarios & Testing

### Scenario 1: Per-Source IP Request Throttling Blocks a Flood
**Given** an attacker sends 200 INVITEs per second from a single IP,
**When** the rate exceeds the configured threshold of 100 requests per 10 seconds per IP,
**Then** OpenSIPS drops subsequent requests and returns `429 Too Many Requests` (or silently drops UDP packets) for the remainder of the window.

### Scenario 2: Brute-Force Registration Attempts Are Throttled per Subscriber
**Given** a malicious client attempts 50 REGISTERs with different passwords for the same subscriber URI,
**When** the auth failure count exceeds 10 per 60 seconds for that subscriber,
**Then** OpenSIPS rejects further REGISTER attempts with `403 Forbidden` and adds the source IP to a temporary ban list.

### Scenario 3: Dispatcher Failover Under Load Maintains Service
**Given** one Asterisk backend is handling 90% of its capacity,
**When** a traffic spike pushes the per-target load above the high-water mark,
**Then** OpenSIPS `dispatcher` module redistributes new calls to lower-load targets and emits a load-balancing event.

### Edge Case 1: NATed Enterprise Customer Shares One Public IP
A legitimate enterprise PBX behind NAT sends high call volume from one public IP. The throttling algorithm must use a tuple of `(source_ip, from_user)` or trust an authenticated identity rather than blanket IP blocking.

### Edge Case 2: Slowloris-Style SIP Attack with Partial Packets
An attacker opens many TCP connections and sends incomplete SIP messages. The TCP connection limit and read timeout must prevent connection exhaustion.

### Edge Case 3: Distributed Attack from a Botnet
Requests originate from thousands of IPs, each under the individual threshold. The anomaly detection engine must aggregate traffic patterns and trigger a global rate-limit or CAPTCHA challenge.

## Functional Requirements

### FR-001: Per-Source IP Request Throttling
- OpenSIPS `pike` module monitors request rate per source IP across all SIP methods.
- Default threshold: 100 requests per 10-second window per IP; 200 requests per 60-second window.
- Exceeding the threshold results in silent drop for UDP and `429` for TCP/TLS.
- **Acceptance Criteria**: `sipp` flood test at 200 rps from one IP shows ≥95% dropped after threshold; no impact on traffic from other IPs.

### FR-002: Subscriber Authentication Rate Limits
- OpenSIPS `auth_db` module tracks failed auth attempts per `auth_username` using `htable` counters.
- Threshold: 10 failures per 60 seconds per subscriber; ban duration: 300 seconds.
- After threshold, subsequent REGISTER/INVITE for that subscriber returns `403 Forbidden`.
- **Acceptance Criteria**: 15 consecutive wrong passwords result in `403` on attempts 11-15; legitimate auth succeeds after the 300s ban expires.

### FR-003: Dispatcher Failover Under Load
- OpenSIPS `dispatcher` module uses `ds_ping_method=OPTIONS` and load-based routing (`ds_select_dst` with priority/weight).
- If a target exceeds a configurable load threshold (e.g., 80% active calls), new traffic is routed to alternate targets.
- **Acceptance Criteria**: Load test with 2 backends shows traffic redistribution when one backend reaches 80% capacity; zero dropped calls during redistribution.

### FR-004: Ban Lists with Automatic Expiration
- OpenSIPS `htable` stores banned IPs and subscriber URIs with TTL.
- Ban sources: `pike` overload, auth failure threshold, manual admin block.
- A management MI command (`ban_list`, `ban_del`) allows operator inspection and early unban.
- **Acceptance Criteria**: Banned entry disappears from `htable` exactly at TTL expiry; MI query reflects current ban state.

### FR-005: Traffic Anomaly Detection
- A sidecar or OpenSIPS `event_route` aggregates `E_PIKE_BLOCKED`, `E_AUTH_FAILURE`, and `E_DISPATCHER_STATUS` events.
- If global request volume exceeds 3 standard deviations from a 24-hour rolling baseline, an alert is raised and a global throttle is optionally applied.
- **Acceptance Criteria**: Synthetic distributed flood (1000 IPs at 50 rps each) triggers anomaly alert within 60 seconds.

## Success Criteria

| ID | Criterion | Target | Measurement Method |
|----|-----------|--------|-------------------|
| SC-001 | Legitimate call success rate during flood | ≥ 99% | sipp load test with concurrent flood |
| SC-002 | Time to block a single abusive IP | ≤ 1 second | Packet capture + log timestamp analysis |
| SC-003 | False-positive block rate for NATed enterprise | ≤ 0.1% | 24-hour production-like simulation |
| SC-004 | Dispatcher redistribution latency under load | ≤ 2 seconds | Load test instrumentation |
| SC-005 | Ban list TTL accuracy | 100% | Unit test on htable expiry |
| SC-006 | Anomaly detection alert latency | ≤ 60 seconds | Distributed synthetic flood test |

## Key Entities

| Entity | Description | Attributes |
|--------|-------------|------------|
| RateLimitWindow | A sliding window tracking request counts per source | source_ip, method, window_start, request_count, limit |
| BanEntry | A temporarily blocked identifier | target, reason, created_at, expires_at, source_module |
| TrafficBaseline | Statistical baseline for anomaly detection | timestamp, mean_rps, stddev_rps, sample_window_hours |

## Scope

### In Scope
- OpenSIPS `pike` module configuration for IP-level throttling.
- `htable` counters for per-subscriber auth failure tracking.
- `dispatcher` load-based routing and failover.
- `htable` ban lists with TTL and MI management.
- Event aggregation sidecar for anomaly detection.
- SIP response codes for rejected requests (429, 403, 480).

### Out of Scope
- Network-layer DDoS mitigation (e.g., SYN flood protection at the host firewall).
- Web Application Firewall (WAF) for HTTP APIs.
- CAPTCHA or interactive challenge mechanisms.
- Machine-learning-based behavioral analysis (beyond statistical baseline).
- Integration with external threat-intelligence feeds.

## Dependencies

| Dependency | Description | Impact if Missing |
|------------|-------------|-------------------|
| OpenSIPS 3.6 LTS | `pike`, `htable`, `dispatcher`, `auth_db` modules | Cannot implement rate limiting or auth tracking |
| PostgreSQL | `subscriber` table for auth verification and rate-limit counters | Auth rate limiting is blind |
| Docker Compose | Container isolation for event sidecar | Anomaly detection sidecar cannot run |

## Assumptions

- Attackers will primarily use UDP/5060 for volume floods; TCP/TLS floods are less common but must still be handled.
- Legitimate subscribers use standard SIP phones or PBXs that retry on `429` with exponential backoff.
- The operator has access to OpenSIPS MI for manual ban/unban operations.
- Sufficient CPU and memory exist to maintain `htable` entries for tens of thousands of concurrent sources.

## Risks

| ID | Risk | Likelihood | Impact | Mitigation |
|----|------|------------|--------|------------|
| R-001 | Overly aggressive throttling blocks legitimate NATed users | High | High | Use `(ip, from_user)` tuple for granular tracking; whitelist known trunk IPs |
| R-002 | `htable` memory exhaustion under large-scale distributed attack | Medium | High | Set `htable` size limits with LRU eviction; monitor memory |
| R-003 | Auth rate limiting leaks valid subscriber existence (timing attacks) | Low | Medium | Return identical `403` for both ban and invalid credentials; do not differentiate |
| R-004 | Dispatcher redistribution causes call state inconsistency | Low | High | Ensure transaction state is local to OpenSIPS; backends are stateless for routing |
| R-005 | Anomaly detection false positives during scheduled events (e.g., Monday morning surge) | Medium | Medium | Use 24-hour rolling baseline with weekday/hour segmentation |

## Notes

- OpenSIPS is the only public SIP endpoint; no host-level SIP processes compete for ports.
- Use `db_postgres` for subscriber lookups; do not introduce MySQL.
- The `sanity` module is forbidden; use `maxfwd`, `uri`, and `t_check_trans` for minimal request validation instead.
- All `htable` names must use lowercase snake_case (e.g., `ip_throttle`, `auth_failures`, `ban_list`).
- Ban list TTLs must be short enough to avoid permanent lockout but long enough to deter brute force (default 300s).
