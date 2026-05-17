# Research: Advanced Container Health Checks and Auto-Healing

## Decision: Docker Native HEALTHCHECK vs External Monitoring

**Decision**: Use Docker native HEALTHCHECK for L1/L2, OpenSIPS module probing for L3.

**Rationale**:
- Docker HEALTHCHECK integrates with Compose restart policies automatically
- No additional infrastructure required
- OpenSIPS dispatcher probing provides application-level insight
- External monitoring (Prometheus) is supplementary, not required for core functionality

**Alternatives considered**:
- External monitoring agent (Consul health checks): adds complexity
- Kubernetes probes: requires K8s migration, out of scope
- Custom health daemon: maintenance burden

## Decision: Exponential Backoff Parameters

**Decision**: Start 5s, max 60s, multiplier 2x, max attempts 10.

**Rationale**:
- 5s start allows quick recovery from transient failures
- 60s max prevents aggressive restart loops
- 10 attempts before giving up aligns with operational SLOs
- Factor 2x is standard and predictable

**Alternatives considered**:
- Fixed 30s delay: slower recovery for transient issues
- Fibonacci backoff: more complex, marginal benefit
- Unlimited retries: risk of resource exhaustion

## Decision: Circuit Breaker Thresholds

**Decision**: 5 failures in 30s triggers inactive state; 60s half-open retry.

**Rationale**:
- 5 failures balances sensitivity vs. false positives
- 30s window captures sustained issues, not blips
- 60s half-open allows backend recovery without overwhelming it
- Aligns with SIP retransmission timers (typically 32s total)

**Alternatives considered**:
- 3 failures in 10s: too sensitive for SIP OPTIONS
- 10 failures in 60s: too slow for real-time voice
- No half-open state: requires manual intervention to recover

## Decision: Graceful Degradation Response Codes

**Decision**: 488 Not Acceptable Here (RTPengine), 480 Temporarily Unavailable (PostgreSQL).

**Rationale**:
- 488 is standard for media negotiation failure (RFC 3261)
- 480 is standard for temporary unavailability (RFC 3261)
- Both codes are well-understood by SIP clients
- Avoids 500-class codes that trigger client retry storms

**Alternatives considered**:
- 503 Service Unavailable: implies retry, not appropriate for media failure
- 486 Busy Here: misleading, not a capacity issue
- 404 Not Found: incorrect semantics

## Falsification Hypotheses

1. **Hypothesis**: Health probes add >5ms latency per SIP message.
   **Test**: Measure OpenSIPS throughput with/without health checks.
   **Mitigation**: If true, reduce probe frequency or use async probes.

2. **Hypothesis**: Circuit breaker opens too frequently during normal operation.
   **Test**: Monitor circuit state transitions over 7 days.
   **Mitigation**: If >10 false positives/day, increase threshold.

3. **Hypothesis**: Auto-restart causes cascade failures.
   **Test**: Simulate failure of 2+ containers simultaneously.
   **Mitigation**: If true, add dependency-aware restart ordering.
