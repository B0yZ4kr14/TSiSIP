# Blueprint — TSiSIP Observability Platform with Prometheus and Grafana

## Overview

Deliver a real-time observability platform that exposes TSiSIP infrastructure metrics through Prometheus time-series collection and Grafana visualization. NOC operators detect anomalies before outages; administrators have historical data for capacity planning and audit trails.

## Requirements

- **FR-003-001**: OpenSIPS Metric Exposure — Prometheus-compatible `/metrics` endpoint with active dialogs, registered subscribers, dispatcher state, auth failures, SIP message counters.
- **FR-003-002**: Prometheus Time-Series Collection — scrape OpenSIPS, RTPengine, PostgreSQL, and host-level metrics; 30 days high-resolution retention.
- **FR-003-003**: Grafana Dashboard Suite — pre-configured dashboards for SIP signaling overview, dispatcher health, RTPengine sessions, PostgreSQL performance, system resources.
- **FR-003-004**: Alerting Rules and Notifications — Prometheus Alertmanager evaluates rules for dispatcher degradation, auth spikes, RTP port exhaustion, disk space; webhook notifications.
- **FR-003-005**: Health Check Integration — metric-based validation for Docker health checks.
- **FR-003-006**: Metric Cardinality Control — enforce limits; no single metric family exceeds 10,000 time series.

## Architecture

- **Container Platform**: Docker Engine with Docker Compose V2.
- **Base Images**: `prom/prometheus:v2.51`, `grafana/grafana:10.4`, `prom/alertmanager:v0.27`.
- **OpenSIPS Exporter**: Custom lightweight sidecar built from `debian:bookworm-slim`; polls MI interface, transforms to Prometheus exposition format.
- **Network Architecture**: All observability services attach to `db_internal` only; no host-published ports.
  - Prometheus: 9090
  - Grafana: 3000
  - Alertmanager: 9093
  - OpenSIPS Exporter: 9442

## Implementation Plan

### Phase 1 — Prometheus Infrastructure
- Prometheus server container with TSDB configuration.
- Scrape jobs for OpenSIPS, RTPengine, PostgreSQL, host metrics.
- Retention: 30 days high-resolution (15s); long-term downsampled planned.

### Phase 2 — OpenSIPS Metric Exporter
- Lightweight exporter sidecar querying OpenSIPS MI interface.
- Converts `jsonrpc` or `mi_http` output to Prometheus exposition format.
- Metrics: active dialogs, registered subscribers, dispatcher state, auth failures, SIP counters.

### Phase 3 — Grafana Dashboards
- Pre-configured datasources (Prometheus).
- Dashboard JSON as code, version controlled.
- i18n support for EN/ES/PT panel labels.
- Role-aware visibility.

### Phase 4 — Alerting & Alertmanager
- Alert rules for dispatcher degradation, auth failures, disk usage.
- Multi-condition rules with 2-minute sustained breach.
- Webhook notifications with runbook links.
- Cardinality limit enforcement.

### Phase 5 — Integration & Validation
- Docker Compose service definitions.
- Health checks for all observability containers.
- End-to-end validation: scrape → TSDB → dashboard → alert.

## Tasks

**Phase 1 — Prometheus Infrastructure**
- T1.1: Create Prometheus Dockerfile and configuration template
- T1.2: Create Alertmanager configuration
- T1.3: Define Prometheus alert rules
- T1.4: Add Prometheus and Alertmanager to `docker-compose.yml`

**Phase 2 — OpenSIPS Metric Exporter**
- T2.1: Create OpenSIPS exporter sidecar
- T2.2: Add MI module to OpenSIPS configuration
- T2.3: Add exporter service to `docker-compose.yml`

**Phase 3 — Grafana Dashboards**
- T3.1: Create Grafana Dockerfile with provisioning
- T3.2: Create dispatcher health dashboard
- T3.3: Create capacity planning dashboard
- T3.4: Create deployment validation dashboard
- T3.5: Implement i18n for dashboard labels

**Phase 4 — Integration & Validation**
- T4.1: Add Grafana to `docker-compose.yml`
- T4.2: Create end-to-end validation test
- T4.3: Document observability runbook

## Validation

- `docker compose build` succeeds for all images.
- `promtool check config` passes.
- Exporter `/metrics` responds with valid Prometheus format.
- Grafana loads all dashboards; panels show data from Prometheus.
- Alert rules evaluate within 60 seconds of condition breach.
- Dashboards load in under 3 seconds.

## Risks & Dependencies

| Risk | Mitigation |
|---|---|
| OpenSIPS MI overload from frequent scrapes | Scrape caching; limit frequency to 15s |
| Prometheus disk exhaustion from cardinality explosion | Enforce cardinality limits; set retention policies |
| Grafana dashboard drift from OCP theme updates | Document dashboard JSON as code; version control |
| Alert fatigue from noisy thresholds | Use multi-condition rules; require 2-minute sustained breach |
| Metric endpoint exposed to unauthorized access | Bind to `db_internal` only; no host-published ports |

**Dependencies**: Feature 001 (OpenSIPS MI interface); Feature 002 (role-aware UI framework); OpenSIPS 3.6 `jsonrpc`/`mi_http`; Docker Compose V2.
