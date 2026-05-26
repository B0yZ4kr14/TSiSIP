# Feature Specification: TSiSIP Observability Platform with Prometheus and Grafana

## Overview

**Feature**: TSiSIP Observability Platform with Prometheus and Grafana
**Short name**: prometheus-grafana-observability
**Created**: 2026-05-17
**Status**: Completed
**Last Updated**: 2026-05-19

### Context

TSiSIP currently operates as a black box from an operational perspective. Administrators and NOC operators have no visibility into OpenSIPS call throughput, dispatcher target health, RTPengine session utilization, or PostgreSQL query performance until a failure occurs. This reactive posture increases MTTR (Mean Time To Recovery) and prevents capacity planning.

### Objective

Deliver a real-time observability platform that exposes TSiSIP infrastructure metrics through Prometheus time-series collection and Grafana visualization. NOC operators must detect anomalies before they become outages, and administrators must have historical data for capacity planning and audit trails.

---

## User Scenarios & Testing

### Primary Flows

#### Scenario 1: NOC operator detects dispatcher target degradation
- **Given** the observability platform is collecting metrics from OpenSIPS
- **When** a dispatcher target's response time exceeds the threshold for 2 consecutive minutes
- **Then** the NOC operator sees a red alert on the Grafana dispatcher dashboard and receives a notification

#### Scenario 2: Administrator reviews weekly capacity trends
- **Given** Prometheus has been collecting metrics for at least 7 days
- **When** the administrator opens the Grafana capacity planning dashboard
- **Then** they see week-over-week trends for concurrent calls, RTP port utilization, and subscriber growth

#### Scenario 3: DevOps engineer validates a new deployment
- **Given** a new OpenSIPS configuration was deployed 10 minutes ago
- **When** the DevOps engineer checks the Grafana deployment validation dashboard
- **Then** they confirm that call success rate, registration rate, and dispatcher health are within normal parameters

### Edge Cases & Error Conditions

- Edge case 1: Prometheus scrape fails because OpenSIPS MI interface is temporarily overloaded; metrics must not be lost for more than 1 minute
- Edge case 2: Grafana is unavailable during an incident; operators must still access raw Prometheus metrics via CLI
- Edge case 3: A metric cardinality explosion (e.g., per-subscriber metrics) could overwhelm Prometheus; cardinality limits must be enforced

---

## Functional Requirements

### FR-003-001: OpenSIPS Metric Exposure
**Description**: OpenSIPS must expose critical metrics via a Prometheus-compatible scraping endpoint. Metrics must include: active dialogs, registered subscribers, dispatcher target state, authentication failures, and SIP message counters.
**Acceptance Criteria**:
- Metrics endpoint responds to HTTP GET at `/metrics` with `Content-Type: text/plain; version=0.0.4`
- All listed metric families are present and update within 15 seconds of state change
- Metric names follow Prometheus naming conventions (`opensips_active_dialogs_total`)

### FR-003-002: Prometheus Time-Series Collection
**Description**: Prometheus must scrape OpenSIPS, RTPengine, PostgreSQL, and host-level metrics at configurable intervals. Data retention supports 30 days of high-resolution (15s) data. Long-term retention (1 year downsampled) is planned for future implementation.
**Acceptance Criteria**:
- Prometheus configuration file defines scrape jobs for all TSiSIP services
- Scrapes succeed with HTTP 200 and valid metric format
- Storage usage does not exceed 10GB for 30 days of collected data

### FR-003-003: Grafana Dashboard Suite
**Description**: Grafana must provide pre-configured dashboards for: SIP signaling overview, dispatcher health, RTPengine sessions, PostgreSQL performance, and system resource utilization.
**Acceptance Criteria**:
- Each dashboard loads in under 3 seconds
- Dashboards are role-aware (admin sees all panels; readonly sees non-actionable views)
- All panels have human-readable titles and descriptions in EN/ES/PT

### FR-003-004: Alerting Rules and Notifications
**Description**: Prometheus Alertmanager must evaluate rules and send notifications for critical conditions: dispatcher target down, authentication spike, RTP port exhaustion, and disk space critical.
**Acceptance Criteria**:
- Alert rules trigger within 60 seconds of condition breach
- Notifications are delivered via webhook (configurable endpoint)
- Alerts include context: metric value, threshold, affected service, and runbook link

### FR-003-005: Health Check Integration
**Description**: The existing Docker health checks must be enhanced to include metric-based validation. Container orchestration must use these health checks for auto-restart decisions.
**Acceptance Criteria**:
- OpenSIPS health check fails if `/metrics` endpoint is unreachable for 30 seconds
- Container is marked unhealthy after 3 consecutive failed health checks
- Prometheus records health check state as a metric (`container_health_status`)

### FR-003-006: Metric Cardinality Control
**Description**: The system must prevent cardinality explosion by enforcing limits on label combinations. Per-subscriber or per-call metrics must be aggregated or sampled.
**Acceptance Criteria**:
- No single metric family exceeds 10,000 unique time series
- Cardinality overflow triggers a warning alert, not a system failure
- Excessive-cardinality metrics are automatically dropped with audit logging

---

## Success Criteria

| ID | Criterion | Measurement | Target |
|---|---|---|---|
| SC-001 | NOC operator detects anomalies before outage | Time from threshold breach to alert visibility | <= 60 seconds |
| SC-002 | Administrator completes weekly capacity review | Time to load and interpret capacity dashboard | <= 5 minutes |
| SC-003 | Zero data loss during normal operations | Metric gaps in Prometheus time series | 0 gaps > 2 minutes |
| SC-004 | Platform supports peak load monitoring | Prometheus scrape duration at 1000 concurrent calls | <= 500ms |
| SC-005 | All critical alerts are actionable | Alerts without documented runbook link | 0 |
| SC-006 | Multi-language dashboard accessibility | Grafana dashboard panels with i18n labels | 100% coverage EN/ES/PT |

---

## Key Entities

### Entity: Metric Family
- **Attributes**: name, type (counter/gauge/histogram), description, labels, cardinality_limit
- **Relationships**: belongs to one Service; has many TimeSeries

### Entity: Time Series
- **Attributes**: metric_family, label_set, timestamp, value
- **Relationships**: belongs to MetricFamily; stored in Prometheus TSDB

### Entity: Alert Rule
- **Attributes**: name, expression, duration, severity, notification_channel, runbook_url
- **Relationships**: evaluates against MetricFamily; triggers Notification

### Entity: Dashboard
- **Attributes**: title, role_visibility, refresh_interval, panel_configuration_json
- **Relationships**: queries Prometheus datasource; rendered by Grafana

---

## Scope

### In Scope
- Prometheus server installation and configuration
- OpenSIPS MI-to-Prometheus metric adapter
- Grafana server with pre-configured dashboards
- Alertmanager with critical alert rules
- Enhanced Docker health checks
- Metric cardinality controls
- i18n for dashboard labels (EN/ES/PT)

### Out of Scope
- Log aggregation (Loki/ELK) — logs remain in Docker stdout/stderr
- Distributed tracing (Jaeger/Zipkin) — SIP tracing is out of scope
- Synthetic monitoring / uptime probes — covered by existing health checks
- Billing or CDR analytics — business intelligence feature

---

## Dependencies

- Feature 001 (OpenSIPS Docker Edge Proxy) must expose MI interface for metric scraping
- Feature 002 (OCP Rebranding) provides the role-aware UI framework for Grafana dashboards
- OpenSIPS 3.6 LTS must support `jsonrpc` or `mi_http` module for metric extraction
- Docker Compose V2 for service orchestration

---

## Assumptions

- Prometheus and Grafana are delivered as Docker containers on the existing `db_internal` network
- OpenSIPS MI interface is accessible internally; no authentication is required for `/metrics`
- Metric storage growth is linear with call volume; 10GB is sufficient for 30 days at projected load
- Alert notifications are delivered via generic webhook; email/SMS integration is operator-configured

---

## Risks

| Risk | Impact | Likelihood | Mitigation |
|---|---|---|---|
| OpenSIPS MI overload from frequent scrapes | High | Medium | Implement scrape caching; limit scrape frequency to 15s |
| Prometheus disk exhaustion from cardinality explosion | High | Low | Enforce cardinality limits; set retention policies |
| Grafana dashboard drift from OCP theme updates | Medium | Low | Document dashboard JSON as code; version control |
| Alert fatigue from noisy thresholds | Medium | High | Use multi-condition rules; require 2-minute sustained breach |
| Metric endpoint exposed to unauthorized access | High | Low | Bind to `db_internal` only; no host-published ports |

---

## Notes

- The `mi_http` module in OpenSIPS 3.6 provides JSON output that can be transformed to Prometheus exposition format via a lightweight exporter sidecar.
- Grafana dashboards should reuse the TSiSIP color palette from `theme.json` (Feature 002) for visual consistency.
> **Cross-feature dependency**: Dashboard color palette consistency depends on Feature 002 (TSiSIP OCP Rebrand) `theme.json`. Ensure Feature 002 SC-002 (100% branding coverage) is completed before implementing dashboard theming.
- Consider `prometheus-node-exporter` for host-level metrics (CPU, memory, disk) on the OpenSIPS and RTPengine containers.
- Falsification hypothesis: If Prometheus scrape adds >5ms latency per SIP message, pivot to asynchronous metric buffering.

## Requirements

### Functional Requirements

#### FR-003-001: Core Capability
**Description**: The system shall provide the primary capability described in this feature specification.
**Acceptance Criteria**:
- The capability is available when the feature is enabled.
- The capability integrates with existing TSiSIP components (OpenSIPS, PostgreSQL, OCP) without regression.

#### FR-003-002: Configuration & Persistence
**Description**: All configuration changes shall be persisted to PostgreSQL and reflected in runtime behavior without requiring a full stack restart.
**Acceptance Criteria**:
- Configuration changes survive container restarts.
- Invalid configuration is rejected at the validation gate.

#### FR-003-003: Observability & Audit
**Description**: The feature shall emit metrics or audit events compatible with the TSiSIP Prometheus/Grafana and OCP audit logging pipelines.
**Acceptance Criteria**:
- Metrics or audit events are visible in the appropriate dashboard or log.
- Failure conditions are logged with sufficient context for debugging.

