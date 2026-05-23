# Feature 006 Memory: SIP-Layer Rate Limiting and DDoS Protection

## Current Scope
Protect the TSiSIP SIP edge from abuse via per-source IP throttling (pike), per-subscriber auth rate limits (htable), dispatcher load-based failover, dynamic ban lists with TTL, and traffic anomaly detection. Status: In Progress (50%).

## Relevant Decisions
- **pike for IP throttling**: Monitors request rate per source IP; silent drop for UDP, 429 for TCP/TLS.
- **htable + cachedb_local for auth limits and ban lists**: Shared-memory counters with TTL avoid PostgreSQL round-trips under attack.
- **Python sidecar for anomaly detection**: Statistical z-score analysis (not ML) over 24-hour rolling baseline; triggers global throttle at >3 sigma.
- **Dispatcher for load redistribution**: ds_select_dst with capacity thresholds redistributes traffic when backends exceed 80% load.

## Active Architecture Constraints
- OpenSIPS 3.6 LTS is the only public SIP endpoint.
- sanity module is forbidden; use maxfwd, uri, t_check_trans for minimal validation.
- All htable names use lowercase snake_case (e.g., ip_throttle, auth_failures, ban_list).
- Network-layer DDoS mitigation (SYN flood) is out of scope (host firewall responsibility).

## Accepted Deviations
- 50% complete as of 2026-05-19: pike and anomaly detection done; auth limits, ban lists, and global throttle pending.

## Relevant Security Constraints
- Return identical 403 for both ban and invalid credentials to prevent timing attacks leaking valid subscriber existence.
- Ban TTL default is 300s — short enough to avoid permanent lockout, long enough to deter brute force.
- htable size limits with LRU eviction prevent memory exhaustion under large-scale distributed attacks.

## Related Historical Lessons
- NATed enterprise customers sharing one public IP require (source_ip, from_user) tuple tracking or authenticated identity whitelisting to avoid false positives.
- TCP connection limits and read timeouts prevent Slowloris-style partial packet exhaustion.
- Weekday/hour segmentation in the baseline reduces false positives during scheduled events (e.g., Monday morning surges).

## Conflict Warnings
- Feature 003 observability stack consumes anomaly detection alerts and rate-limiting metrics.

## Retrieval Notes
- Search terms: pike, htable, rate limiting, DDoS, ban list, anomaly detection, cachedb_local, dispatcher load balancing.
- Related features: 001 (OpenSIPS foundation), 003 (observability/metrics), 004 (dispatcher probing).
