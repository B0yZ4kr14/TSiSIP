# Socratic Decision Log — Wave 3 (Spec 006)

> Decisions reached via structured questioning for each pending task in the Rate Limiting & DDoS Protection spec.

---

## T1.2: Handle NATed enterprise traffic

**Q**: How distinguish NATed legitimate traffic from spoofed floods?  
**A**: Legitimate enterprise PBXs use known public NAT IPs and authenticate with valid credentials. Spoofed floods typically lack valid credentials or use randomized From usernames.

**Q**: What mechanism can whitelist known enterprise NATs without weakening DDoS protection?  
**A**: Use the existing `permissions` module (`check_source_address(2)`) to match known enterprise NAT IPs against the `address` table with `grp=2`. These IPs bypass `pike` request-rate throttling but still traverse authentication and all other security layers.

**Conclusion**: Insert `check_source_address(2)` before `pike_check_req()`. Enterprise NAT IPs are maintained in the `address` table (grp=2) by operators.

---

## T1.3: Add TCP connection limits

**Q**: What is the attack vector?  
**A**: Slowloris-style partial SIP messages that hold TCP connections open indefinitely.

**Q**: Can OpenSIPS enforce per-source TCP connection limits natively?  
**A**: OpenSIPS 3.6 provides global `tcp_max_connections` but not per-source TCP connection counters. Per-source enforcement (100/IP) is delegated to host-level `iptables connlimit` when the container runs with `NET_ADMIN`.

**Conclusion**: Add global TCP core parameters (`tcp_max_connections=4096`, `tcp_connection_lifetime=300`, `tcp_connect_timeout=5`, `tcp_read_timeout=10`, `tcp_max_msg_time=10`) to prevent connection exhaustion. Document host-level per-source limit as operational procedure.

---

## T2.1: Configure htable for auth failures

**Q**: Why htable instead of userblacklist for transient auth failure counting?  
**A**: `userblacklist` is PostgreSQL-backed and designed for persistent bans. `htable` (or equivalent in-memory store) is O(1) lookup with auto-expire — ideal for a 60-second sliding window of auth failure counts.

**Q**: What key granularity?  
**A**: Per-subscriber (username) when available; per-source IP for unauthenticated probes.

**Q**: Does OpenSIPS 3.6 actually provide `htable`?  
**A**: No — `htable` module does not exist in OpenSIPS 3.6 source tree. `cachedb_local` provides the same in-memory key-value semantics with TTL.

**Conclusion**: Load `cachedb_local.so`, configure `cachedb_url=local:///`. Use `cache_add("local", "auth_failures_<key>", "1", "60")` to increment counters. Reset with `cache_remove` on successful auth.

---

## T2.2: Implement subscriber auth throttling

**Q**: Per-subscriber or per-IP throttling?  
**A**: Per-subscriber (username) for auth attempts; per-IP for connection-level limits. This prevents a botnet from collectively locking out a single subscriber.

**Q**: What is the minimum viable threshold?  
**A**: 3 failed attempts within 60 seconds balances brute-force deterrence against false positives from typo-prone users.

**Q**: What response code?  
**A**: `429 Too Many Requests` — semantically correct for rate limiting, distinct from `403` (permanent ban) and `401/407` (credential challenge).

**Conclusion**: After 3 failures, return `429`. The source IP is also added to `ban_list` for defense in depth. Counter resets on successful auth.

---

## T3.2: Add dispatcher load monitoring

**Q**: Active probing or passive metrics?  
**A**: Active OPTIONS probing is already enabled (`ds_ping_method=OPTIONS`, `ds_ping_interval=10`, `ds_probing_mode=1`). Passive Prometheus metrics are out of scope for the OpenSIPS config file and belong to the exporter.

**Q**: How does an operator query target health manually?  
**A**: Via MI commands exposed by the `dispatcher` module.

**Conclusion**: Verify `ds_probing_mode=1` is configured. Document MI commands (`ds_list`, `ds_set_state`) in config comments for operator runbook reference.

---

## T4.1: Create ban list htable

**Q**: In-memory htable or PostgreSQL for active bans?  
**A**: In-memory store for active bans (fast lookup, no DB round-trip); PostgreSQL for audit/history (`auth_audit_log`, `userblacklist`).

**Q**: What are the ban sources?  
**A**: `pike` overload (auto-ban via `E_PIKE_BLOCKED`), auth failure threshold (`auth_exceeded`), and manual admin blocks via MI.

**Conclusion**: Use `cachedb_local` with `cache_store("local", "ban_list_$si", "<reason>", "3600")`. Check with `cache_fetch("local", "ban_list_$si", "$avp(result)")` in the main request route for initial requests (in-dialog traffic is allowed to complete gracefully).

---

## T4.2: Add ban management MI commands

**Q**: CLI or MI for runtime management?  
**A**: MI commands are native to OpenSIPS and require no additional tooling. `htable` already exposes `htable_dump`, `htable_add`, and `htable_delete` via MI.

**Conclusion**: Document the existing htable MI commands in config comments rather than inventing custom MI commands. Batch operations can be scripted with `opensips-cli`.

---

## T4.3: Implement ban TTL accuracy

**Q**: Minimum viable ban TTL?  
**A**: 1 hour (3600s) is long enough to deter brute-force campaigns but short enough to avoid permanent lockout of dynamic IPs. Configurable via the TTL parameter in `cache_store`.

**Q**: How is TTL enforced?  
**A**: `cachedb_local` enforces TTL on each `cache_store`/`cache_add` call. Entries are automatically removed after the configured seconds.

**Conclusion**: Pass TTL="3600" to `cache_store` for ban entries. No manual cleanup required. TTL accuracy is inherent to the `cachedb_local` implementation.

---

## T5.3: Add global throttle on anomaly

**Q**: Statistical or rule-based anomaly detection?  
**A**: Rule-based for the foundation. The 3-sigma statistical baseline from the 24-hour rolling window is maintained by the Python sidecar but integration is deferred to avoid config complexity.

**Q**: How does the config react to an anomaly alert?  
**A**: The sidecar or operator sets `cache_store("local", "anomaly_state_global_throttle", "1", "<ttl>")` via MI or script. OpenSIPS switches from the normal `global` ratelimit pipe (500 rps) to the `global_alert` pipe (250 rps).

**Conclusion**: Add conditional logic around two `rl_check` pipes. Normal: `rl_check("global", 500, "TAILDROP")`. Alert: `rl_check("global_alert", 250, "TAILDROP")`. The `cachedb_local` key `anomaly_state_global_throttle` acts as the toggle.
