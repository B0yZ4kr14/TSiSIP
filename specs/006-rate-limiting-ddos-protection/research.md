# Research: SIP-Layer Rate Limiting and DDoS Protection

## Decision: pike vs Custom Rate Limiter

**Decision**: Use OpenSIPS native `pike` module.

**Rationale**:
- `pike` is optimized for SIP traffic patterns
- Integrated with OpenSIPS shared memory
- No additional infrastructure needed
- Well-documented in OpenSIPS 3.6 LTS

**Alternatives considered**:
- Custom C module: maintenance burden
- External iptables: breaks container encapsulation
- nftables: host-level, not portable

## Decision: htable vs Redis for Ban Lists

**Decision**: Use `htable` (shared memory hash table) for ban lists.

**Rationale**:
- Zero latency (same process)
- No network dependency
- TTL support via `autoexpire`
- Survives process restart with `db_mode=1`

**Alternatives considered**:
- Redis: adds dependency, network latency
- PostgreSQL: too slow for per-packet checks
- Memcached: no TTL, less reliable

## Decision: Anomaly Detection Approach

**Decision**: Statistical z-score over 24-hour rolling baseline.

**Rationale**:
- Simple to implement and understand
- No ML training required
- 3-sigma threshold is standard in monitoring
- Works with small data sets

**Alternatives considered**:
- ML-based (isolation forest): overkill, needs training data
- Simple threshold: high false positive rate
- Rate of change: misses distributed attacks

## Decision: NAT Handling Strategy

**Decision**: Use `(source_ip, from_user)` tuple for authenticated traffic.

**Rationale**:
- Distinguishes multiple users behind same NAT
- Preserves security for unauthenticated traffic
- Whitelist for known enterprise IPs
- Backwards compatible with existing auth flow

**Alternatives considered**:
- Header-based identity: spoofable
- Always per-IP: blocks legitimate NATed users
- Trust all authenticated: allows credential sharing abuse

## Decision: Global Throttle Strategy

**Decision**: Reduce accepted request rate to 50% of baseline during anomaly.

**Rationale**:
- Preserves some service capacity
- Reduces impact on legitimate users
- Simple to implement with `htable` flag
- Reversible when anomaly clears

**Alternatives considered**:
- Complete shutdown: too aggressive
- Challenge-response (CAPTCHA): not applicable to SIP
- Rate limit all sources equally: punishes legitimate users

## Falsification Hypotheses

1. **Hypothesis**: pike blocks legitimate retry traffic.
   **Test**: Measure block rate during normal SIP retransmissions.
   **Mitigation**: If >1%, increase `reqs_density_per_unit`.

2. **Hypothesis**: Anomaly detection false-positives on marketing campaigns.
   **Test**: Monitor alerts during known high-volume events.
   **Mitigation**: If >5 false positives/week, add exclusion windows.

3. **Hypothesis**: Ban list grows unbounded.
   **Test**: Monitor htable memory usage over 30 days.
   **Mitigation**: If >100MB, reduce TTL or add LRU eviction.
