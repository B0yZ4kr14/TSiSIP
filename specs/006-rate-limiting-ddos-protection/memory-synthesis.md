# Feature 006 Memory Synthesis: SIP-Layer Rate Limiting and DDoS Protection

## Current Scope
SIP edge protection via pike IP throttling, htable auth limits, dispatcher load balancing, ban lists, and anomaly detection. Status: 50% complete.

## Relevant Decisions
- pike for IP throttling (UDP silent drop, TCP 429).
- htable for auth limits and bans (shared memory, TTL).
- Python sidecar for statistical anomaly detection (z-score, not ML).
- Dispatcher redistributes at 80% backend load.

## Active Architecture Constraints
- OpenSIPS 3.6 LTS only public SIP endpoint.
- sanity forbidden.
- Network-layer DDoS out of scope.

## Accepted Deviations
- 50% complete; auth limits, ban lists, global throttle pending.

## Relevant Security Constraints
- Identical 403 for ban and invalid credentials (timing attack prevention).
- Ban TTL 300s default.
- htable LRU eviction limits.

## Related Historical Lessons
- (ip, from_user) tuple prevents NATed enterprise false positives.
- TCP connection limits prevent Slowloris.
- Baseline segmentation reduces Monday-morning false positives.

## Conflict Warnings
- Feature 003 consumes anomaly alerts and rate-limiting metrics.

## Retrieval Notes
- Keywords: pike, htable, rate limiting, DDoS, ban list, anomaly detection.
- Related: 001, 003, 004.
