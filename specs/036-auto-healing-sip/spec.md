# Feature Specification: Auto-Healing SIP Infrastructure

## Overview

**Feature**: Auto-Healing SIP Infrastructure  
**Short name**: auto-healing-sip  
**Feature Number**: 036  
**Created**: 2026-05-28  
**Status**: Draft

### Context

TSiSIP currently has three powerful but independent subsystems:
- **Feature 008** (Anomaly Detection): Detects traffic anomalies via Z-score analysis.
- **Feature 034** (Metrics Dashboard): Displays real-time OpenSIPS MI metrics, trunk health, and active alerts.
- **Feature 035** (Smart Dispatcher Management): Provides CRUD, changelog audit, and one-click rollback for dispatcher destinations.

Operators must manually correlate anomalies, check dispatcher health, and decide whether to rollback changes. This creates a gap between detection and remediation, increasing MTTR (Mean Time To Recovery) during incidents.

### Objective

Build an **Auto-Healing layer** that bridges anomaly detection, dispatcher health monitoring, and automatic remediation. The system shall:
1. Periodically poll dispatcher health via MI `ds_list` and SIP OPTIONS probes.
2. Correlate unhealthy destinations with anomaly detector events.
3. Automatically trigger rollback of recent dispatcher changes when a destination degrades within a configurable time window.
4. Automatically mark failed destinations as inactive (state=1) after probe failures exceed a threshold.
5. Expose an OCP dashboard widget showing auto-healing events and recommendations.
6. Log all auto-healing actions to `dispatcher_change_log` for audit compliance.

---

## User Scenarios & Testing

### Primary Flows

#### Scenario 1: Automatic Rollback on Destination Degradation
- **Given** a dispatcher destination was recently added/modified
- **And** the destination fails 3 consecutive OPTIONS probes within 5 minutes
- **When** the auto-healer evaluates health state
- **Then** the system finds the most recent changelog entry for that destination
- **And** if the entry is within the 15-minute rollback window, executes rollback automatically
- **And** logs the action as `AUTO_ROLLBACK` to `dispatcher_change_log`

#### Scenario 2: Automatic Failover on Probe Failure
- **Given** a dispatcher destination is active (state=0)
- **And** it fails 5 consecutive probes over 10 minutes
- **When** the auto-healer runs its health check cycle
- **Then** the destination is marked inactive (state=1)
- **And** OpenSIPS MI `ds_set_state` is called to update runtime state
- **And** an alert is sent to the OCP dashboard

#### Scenario 3: Anomaly-Correlated Remediation
- **Given** the anomaly detector reports a z-score > 3.0 for a specific source IP pattern
- **And** the pattern correlates with traffic to a specific dispatcher set
- **When** the auto-healer correlates the anomaly with dispatcher health
- **Then** it marks the affected destination as `probing` (state=2)
- **And** increases probe frequency for that destination

### Edge Cases & Error Conditions

- **Edge case 1**: Multiple recent changelog entries exist — auto-rollback must pick the most recent one with a valid `old_snapshot`.
- **Edge case 2**: MI HTTP is unreachable during auto-healing — degrade gracefully, queue the action, retry on next cycle.
- **Edge case 3**: All destinations in a set fail simultaneously — mark all as inactive, trigger critical alert, do NOT auto-rollback (ambiguous root cause).
- **Edge case 4**: Auto-healing action fails repeatedly — circuit breaker stops auto-healing for 30 minutes to prevent flapping.

---

## Functional Requirements

### FR-001: Health Monitoring Service
**Description**: A background PHP CLI service (`auto-healer.php`) runs every 60 seconds via cron or loop.
**Acceptance Criteria**:
- Polls MI `ds_list` for all dispatcher sets and destinations.
- Sends SIP OPTIONS probe to each destination.
- Records probe results (reachable, code, rtt_ms) in a new table `dispatcher_health_log`.
- Tracks consecutive failure count per destination.

### FR-002: Auto-Rollback Decision Engine
**Description**: When a destination fails health checks, the engine checks if a recent manual change may have caused it.
**Acceptance Criteria**:
- Queries `dispatcher_change_log` for the most recent `ADD`/`UPDATE` affecting the failed destination.
- Rollback window is configurable (default: 15 minutes).
- Validates that `old_snapshot` is non-null and parseable.
- Calls `dispatcher-rollback.php` logic programmatically (not via HTTP).
- Inserts a new `dispatcher_change_log` entry with action `AUTO_ROLLBACK`.

### FR-003: Auto-Failover State Management
**Description**: When consecutive probe failures exceed threshold, mark destination inactive via MI.
**Acceptance Criteria**:
- Threshold is configurable (default: 5 failures over 10 minutes).
- Calls MI `ds_set_state` with state=1 (inactive) for the destination.
- Updates PostgreSQL `dispatcher.state` to maintain consistency.
- Does NOT delete or remove the destination — only disables it.

### FR-004: Anomaly Correlation
**Description**: Correlate anomaly detector events with dispatcher sets.
**Acceptance Criteria**:
- Queries anomaly detector `/api/v1/status` for active anomalies.
- Maps anomaly source IP patterns to dispatcher destinations via `attrs` or setid.
- When correlation confidence > 0.7, triggers increased probing (state=2).

### FR-005: OCP Dashboard Widget
**Description**: Display auto-healing events in the OCP dashboard.
**Acceptance Criteria**:
- New widget on `dashboard.php`: "Auto-Healing Events".
- Shows last 10 events with timestamp, destination, action, result.
- Color-coded: green (success), yellow (probing), red (failed).
- Link to `dispatcher.php` for manual intervention.

### FR-006: Audit and Alerting
**Description**: All auto-healing actions are auditable and alertable.
**Acceptance Criteria**:
- Every action logged to `dispatcher_change_log` with `action = AUTO_ROLLBACK | AUTO_FAILOVER | AUTO_PROBE`.
- Prometheus metric `tsisip_autoheal_actions_total` incremented per action type.
- Alertmanager rule fires if `tsisip_autoheal_actions_total{result="failed"} > 3` in 10m.

### FR-007: Circuit Breaker
**Description**: Prevent flapping when auto-healing repeatedly fails.
**Acceptance Criteria**:
- Circuit breaker opens after 3 consecutive failed auto-healing actions.
- Remains open for 30 minutes.
- While open, no automatic actions are taken; only health monitoring continues.
- Metric `tsisip_autoheal_circuit_breaker_state` (0=closed, 1=open).

---

## Non-Functional Requirements

- **NFR-001**: Auto-healer must not add > 1 second of latency to OpenSIPS MI per cycle.
- **NFR-002**: Health probe timeout must be <= 3 seconds per destination.
- **NFR-003**: All auto-healing code must be covered by integration tests.
- **NFR-004**: OCP widget must refresh via SSE (reuse Feature 034 SSE stream).

---

## Security Considerations

- Auto-healing actions require no new privileges — reuse existing MI HTTP and PostgreSQL credentials.
- Circuit breaker prevents abuse if an attacker intentionally triggers failures.
- All auto-healing decisions must be reversible (rollback of rollback is possible).

---

## Rejected Patterns

| Rejected | Canonical |
|---|---|
| Delete failed destinations permanently | Only mark as inactive (state=1) |
| Auto-heal via direct SQL only | Must update both PostgreSQL AND OpenSIPS MI runtime state |
| Hard-coded thresholds | All thresholds configurable via OCP admin panel |
| Email/SMS alerts from auto-healer | Use existing Prometheus/Alertmanager stack |

---

## Acceptance Criteria Summary

| ID | Criterion | Priority |
|---|---|---|
| AC-001 | `auto-healer.php` polls `ds_list` and OPTIONS probes every 60s | Required |
| AC-002 | `dispatcher_health_log` table stores probe history | Required |
| AC-003 | Auto-rollback triggers within 15min window on probe failure | Required |
| AC-004 | Auto-failover marks destination inactive after 5 failures | Required |
| AC-005 | OCP dashboard widget shows last 10 auto-healing events | Required |
| AC-006 | Prometheus metrics `tsisip_autoheal_actions_total` emitted | Required |
| AC-007 | Circuit breaker prevents flapping after 3 failures | Required |
| AC-008 | Anomaly correlation increases probe frequency | Optional |
| AC-009 | Integration tests cover all auto-healing scenarios | Required |
