# Data Model: Health Checks and Auto-Healing

## Entity: HealthProbe
- **probe_id**: UUID
- **service_name**: enum (opensips, postgres, rtpengine, asterisk)
- **layer**: enum (L1_TCP, L2_HTTP, L3_SIP)
- **check_command**: string
- **interval_seconds**: integer
- **timeout_seconds**: integer
- **retries**: integer
- **start_period_seconds**: integer
- **created_at**: timestamp

## Entity: HealthState
- **state_id**: UUID
- **container_id**: string (Docker container ID)
- **service_name**: enum
- **status**: enum (starting, healthy, unhealthy, dead)
- **last_check_at**: timestamp
- **consecutive_failures**: integer
- **failure_reason**: string (nullable)

## Entity: CircuitState
- **circuit_id**: UUID
- **target_uri**: string (dispatcher target)
- **set_id**: integer
- **state**: enum (closed, open, half_open)
- **failure_count**: integer
- **last_failure_at**: timestamp
- **last_success_at**: timestamp
- **half_open_attempts**: integer

## Entity: RestartEvent
- **event_id**: UUID
- **container_id**: string
- **service_name**: enum
- **restart_reason**: enum (healthcheck_failure, manual, oom, crash)
- **restart_count**: integer
- **backoff_delay_seconds**: integer
- **restarted_at**: timestamp

## Entity: DegradationEvent
- **event_id**: UUID
- **failed_component**: enum (rtpengine, postgres, opensips)
- **sip_method**: string
- **response_code**: integer
- **response_reason**: string
- **occurred_at**: timestamp

## Relationships
- HealthProbe (1) -> (*) HealthState (one probe generates many states)
- CircuitState (*) -> (1) HealthState (circuit reflects health)
- RestartEvent (*) -> (1) HealthState (restart triggered by state change)
- DegradationEvent (*) -> (1) HealthState (degradation follows failure)
