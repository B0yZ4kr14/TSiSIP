# Implementation Plan: Auto-Healing SIP Infrastructure

## Feature

**Feature Number**: 036  
**Feature Name**: Auto-Healing SIP Infrastructure  
**Branch**: `036-auto-healing-sip`

## Architecture

### Components

1. **Health Monitor** (`web/cli/auto-healer.php`)
   - Runs as a cron job every 60 seconds (or daemon loop).
   - Polls OpenSIPS MI `ds_list` for runtime dispatcher state.
   - Sends SIP OPTIONS probes to each destination.
   - Writes results to `dispatcher_health_log` table.

2. **Decision Engine** (embedded in `auto-healer.php`)
   - Evaluates consecutive failures against thresholds.
   - Queries `dispatcher_change_log` for recent changes.
   - Decides: AUTO_ROLLBACK, AUTO_FAILOVER, AUTO_PROBE, or NO_ACTION.
   - Respects circuit breaker state.

3. **Action Executor** (embedded in `auto-healer.php`)
   - Executes rollback by replaying `old_snapshot`.
   - Calls MI `ds_set_state` for runtime state changes.
   - Updates PostgreSQL `dispatcher` table.
   - Logs all actions to `dispatcher_change_log`.

4. **OCP Widget** (`web/dashboard.php` + `web/api/v1/autoheal-events.php`)
   - SSE endpoint for real-time auto-healing events.
   - Dashboard widget showing last 10 events.

5. **Prometheus Metrics** (`web/api/v1/metrics-autoheal.php`)
   - Exposes `tsisip_autoheal_actions_total` and `tsisip_autoheal_circuit_breaker_state`.

### Data Model

New table: `dispatcher_health_log`
```sql
CREATE TABLE dispatcher_health_log (
    id SERIAL PRIMARY KEY,
    destination_id INTEGER REFERENCES dispatcher(id) ON DELETE CASCADE,
    setid INTEGER NOT NULL,
    destination VARCHAR(192) NOT NULL,
    reachable BOOLEAN,
    sip_code INTEGER,
    rtt_ms NUMERIC(8,2),
    failure_count INTEGER DEFAULT 0,
    checked_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);
CREATE INDEX idx_dispatcher_health_log_dest ON dispatcher_health_log(destination_id, checked_at DESC);
```

### Configuration

All thresholds stored in new table `autoheal_config` (or OCP config JSON):
- `probe_interval_sec`: 60
- `probe_timeout_sec`: 3
- `auto_rollback_window_min`: 15
- `auto_failover_threshold`: 5 failures in 10 minutes
- `circuit_breaker_failures`: 3
- `circuit_breaker_cooldown_min`: 30

## Implementation Phases

### Phase 1: Database Schema & Core Health Monitor
- Create `dispatcher_health_log` table.
- Create `auto-healer.php` CLI with MI polling and OPTIONS probes.
- Integration test for health monitoring.

### Phase 2: Decision Engine & Auto-Failover
- Implement consecutive failure tracking.
- Implement auto-failover (mark inactive via MI + DB).
- Circuit breaker logic.
- Integration tests for failover.

### Phase 3: Auto-Rollback
- Query `dispatcher_change_log` for recent changes.
- Implement rollback replay logic.
- Integration tests for rollback.

### Phase 4: Anomaly Correlation
- Query anomaly detector API.
- Map anomalies to dispatcher sets.
- Increase probe frequency on correlated anomalies.

### Phase 5: OCP Widget & Prometheus Metrics
- Create `autoheal-events.php` SSE endpoint.
- Add widget to `dashboard.php`.
- Create `metrics-autoheal.php` Prometheus endpoint.
- Add Alertmanager rule.

### Phase 6: Integration & E2E Validation
- Full stack test with simulated failure.
- Verify audit trail in `dispatcher_change_log`.
- Performance test: ensure <1s MI latency.

## Validation Gates

- [ ] All new tables have indexes.
- [ ] `auto-healer.php` runs without errors for 24h in dev.
- [ ] Integration tests pass (target: 100% new code covered).
- [ ] Prometheus metrics are scrapable.
- [ ] OCP widget updates in real-time via SSE.
- [ ] Circuit breaker prevents flapping in stress test.
