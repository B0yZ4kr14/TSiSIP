# Tasks: Auto-Healing SIP Infrastructure (Feature 036)

## Phase 1: Database Schema & Core Health Monitor

- [x] T1.1: Create `dispatcher_health_log` table migration
- [x] T1.2: Create `autoheal_config` table migration  
- [x] T1.3: Create `web/cli/auto-healer.php` CLI entrypoint
- [x] T1.4: Implement MI `ds_list` polling in auto-healer
- [x] T1.5: Implement SIP OPTIONS probe loop with timeout
- [x] T1.6: Write probe results to `dispatcher_health_log`
- [x] T1.7: Add cron entry or systemd timer for auto-healer
- [x] T1.8: Integration test: health monitor records probes

## Phase 2: Decision Engine & Auto-Failover

- [x] T2.1: Implement consecutive failure counter logic
- [x] T2.2: Implement threshold evaluation against `autoheal_config`
- [x] T2.3: Implement auto-failover: MI `ds_set_state` to inactive
- [x] T2.4: Implement auto-failover: update PostgreSQL `dispatcher.state`
- [x] T2.5: Implement circuit breaker (open after N failures, cooldown M min)
- [x] T2.6: Circuit breaker metric `tsisip_autoheal_circuit_breaker_state`
- [x] T2.7: Integration test: auto-failover on probe failure
- [x] T2.8: Integration test: circuit breaker prevents flapping

## Phase 3: Auto-Rollback

- [x] T3.1: Query `dispatcher_change_log` for recent changes by destination
- [x] T3.2: Validate rollback window (default 15min) and `old_snapshot`
- [x] T3.3: Implement rollback replay logic programmatically
- [x] T3.4: Log `AUTO_ROLLBACK` action to `dispatcher_change_log`
- [x] T3.5: Graceful degradation when MI HTTP is unreachable
- [x] T3.6: Integration test: auto-rollback on destination degradation
- [ ] T3.7: Integration test: no rollback when multiple destinations fail

## Phase 4: Anomaly Correlation

- [x] T4.1: Query anomaly detector `/api/v1/status`
- [x] T4.2: Map anomaly patterns to dispatcher sets/destinations
- [x] T4.3: Implement increased probe frequency (state=2) on correlation
- [x] T4.4: Correlation confidence threshold (default 0.7)
- [x] T4.5: Integration test: anomaly correlation increases probing

## Phase 5: OCP Widget & Prometheus Metrics

- [x] T5.1: Create `web/api/v1/autoheal-events.php` SSE endpoint
- [x] T5.2: Create `web/api/v1/metrics-autoheal.php` Prometheus endpoint
- [x] T5.3: Add auto-healing widget to `web/dashboard.php`
- [x] T5.4: Add Alertmanager rule for failed auto-heal actions
- [x] T5.5: Integration test: SSE endpoint emits events
- [x] T5.6: Integration test: Prometheus metrics are scrapable

## Phase 6: Integration & E2E Validation

- [ ] T6.1: E2E test: simulate destination failure, verify auto-failover
- [x] T6.2: E2E test: simulate bad dispatcher change, verify auto-rollback
- [x] T6.3: E2E test: circuit breaker stress test
- [ ] T6.4: Performance test: auto-healer MI latency < 1s per cycle
- [ ] T6.5: Security review: verify no new privileges required
- [ ] T6.6: Update operator runbook with auto-healing procedures
- [ ] T6.7: Final commit, push, and merge to main
