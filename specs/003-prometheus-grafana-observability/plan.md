## Summary

This feature implements the specified capability for the TSiSIP SIP edge-proxy platform.

## Technical Context

- **OpenSIPS 3.6 LTS**: Core SIP proxy and signaling edge
- **PostgreSQL**: Database backend for configuration and state
- **Docker & Docker Compose**: Container orchestration and deployment
- **RTPengine**: Media relay for RTP/RTCP
- **Asterisk**: Backend PBX for voice applications

## Project Structure

Relevant directories and files for this feature are located under `specs/$spec/` and integrated into the main project tree.

# Implementation Plan: TSiSIP Observability Platform with Prometheus and Grafana

## Overview

This plan translates the feature specification into an executable implementation roadmap for real-time observability of TSiSIP infrastructure through Prometheus time-series collection and Grafana visualization.

---

## Architecture & Stack Choices

### Container Platform
- **Docker Engine** with Docker Compose V2
- All observability services run on internal networks (`db_internal`, `sip_internal`)

### Base Images
- **Prometheus**: `prom/prometheus:v2.51` from official Docker Hub
- **Grafana**: `grafana/grafana:10.4` from official Docker Hub
- **Alertmanager**: `prom/alertmanager:v0.27` from official Docker Hub
- **OpenSIPS Exporter**: Custom lightweight exporter sidecar built from `debian:bookworm-slim`

### Network Architecture
| Service | Network | Ports (internal) |
|---|---|---|
| prometheus | db_internal | 9090 |
| grafana | db_internal | 3000 |
| alertmanager | db_internal | 9093 |
| opensips-exporter | sip_internal, db_internal | 9442 |

---

## Implementation Phases

### Phase 1 — Prometheus Infrastructure
- Prometheus server container with TSDB configuration
- Scrape job definitions for OpenSIPS, RTPengine, PostgreSQL, host metrics
- Retention policies: 30 days high-resolution (15s); long-term downsampled retention planned

### Phase 2 — OpenSIPS Metric Exporter
- Lightweight exporter sidecar that queries OpenSIPS MI interface
- Converts `jsonrpc` or `mi_http` output to Prometheus exposition format
- Metrics: active dialogs, registered subscribers, dispatcher state, auth failures, SIP counters

### Phase 3 — Grafana Dashboards
- Pre-configured datasources (Prometheus)
- Dashboard JSON as code, version controlled
- i18n support for EN/ES/PT panel labels
- Role-aware visibility (NOC operator, administrator, DevOps engineer)

### Phase 4 — Alerting & Alertmanager
- Alert rules for dispatcher degradation, auth failures, disk usage
- Multi-condition rules with 2-minute sustained breach requirement
- Webhook notifications with runbook links
- Cardinality limit enforcement

### Phase 5 — Integration & Validation
- Docker Compose service definitions
- Health checks for all observability containers
- End-to-end validation: scrape → TSDB → dashboard → alert

---

## File Structure

```
docker/
  prometheus/
    Dockerfile
    prometheus.yml.tpl
    alert-rules.yml
  grafana/
    Dockerfile
    provisioning/
      datasources/
        prometheus.yml
      dashboards/
        tsisip/
          dispatcher-dashboard.json
          capacity-dashboard.json
          deployment-dashboard.json
      alerting/
        contact-points.yml
  opensips-exporter/
    Dockerfile
    exporter.py
opensips/
  metrics.cfg.tpl          # MI module configuration
```

---

## Validation Gates

| Gate | Check | Command |
|---|---|---|
| Build | All images build cleanly | `docker compose build` |
| Config | Prometheus validates config | `promtool check config` |
| Scrape | Metrics endpoint responds | `curl http://opensips-exporter:9442/metrics` |
| Dashboard | Grafana loads all dashboards | API check + visual validation |
| Alert | Alert rules evaluate | `promtool test rules` |
