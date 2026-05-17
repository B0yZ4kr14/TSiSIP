# Data Model: SIP-Layer Rate Limiting and DDoS Protection

## Entity: RateLimitRule
- **rule_id**: UUID
- **rule_type**: enum (ip_throttle, auth_limit, global_throttle)
- **target**: string (IP, URI, or global)
- **threshold**: integer (requests per window)
- **window_seconds**: integer
- **action**: enum (drop, reject_429, reject_403, log)
- **created_at**: timestamp

## Entity: ThrottleEvent
- **event_id**: UUID
- **rule_id**: UUID (FK to RateLimitRule)
- **source_ip**: string
- **source_port**: integer
- **sip_method**: string
- **timestamp**: timestamp
- **action_taken**: enum (dropped, rejected, allowed)
- **reason**: string

## Entity: AuthFailureCounter
- **counter_id**: UUID
- **auth_username**: string
- **source_ip**: string
- **failure_count**: integer
- **window_start**: timestamp
- **last_failure**: timestamp
- **banned_until**: timestamp (nullable)

## Entity: BanEntry
- **ban_id**: UUID
- **target_type**: enum (ip, uri, subnet)
- **target_value**: string
- **source**: enum (pike, auth, manual, anomaly)
- **reason**: string
- **banned_at**: timestamp
- **expires_at**: timestamp
- **banned_by**: string (system or admin username)

## Entity: AnomalyAlert
- **alert_id**: UUID
- **alert_type**: enum (distributed_flood, auth_spike, load_anomaly)
- **severity**: enum (low, medium, high, critical)
- **baseline_value**: float
- **current_value**: float
- **z_score**: float
- **started_at**: timestamp
- **resolved_at**: timestamp (nullable)
- **affected_ips**: JSON array

## Entity: DispatcherLoad
- **load_id**: UUID
- **target_uri**: string
- **set_id**: integer
- **active_calls**: integer
- **capacity_limit**: integer
- **load_percentage**: float
- **measured_at**: timestamp

## Relationships
- RateLimitRule (1) -> (*) ThrottleEvent (rules generate events)
- AuthFailureCounter (1) -> (*) BanEntry (counters trigger bans)
- AnomalyAlert (*) -> (*) BanEntry (alerts may trigger bans)
- DispatcherLoad (*) -> (1) ThrottleEvent (load affects throttling)
