# Feature 034: OpenSIPS Real-Time Metrics Dashboard & Alerting

## Objective

Deliver a real-time SIP traffic metrics dashboard within the TSiSIP OCP that surfaces live call data, dispatcher health, trunk status, and anomaly alerts without requiring operators to access Grafana or Prometheus directly.

## Background

The TSiSIP platform already collects comprehensive metrics via:
- OpenSIPS MI HTTP interface (dialog count, memory, USRLoc, dispatcher state)
- Prometheus + Grafana (infrastructure metrics)
- Anomaly detector (statistical anomaly detection on SIP traffic)
- PostgreSQL CDR and audit tables

However, operators must context-switch between OCP, Grafana, and logs to assess system health. This feature consolidates the most critical operational metrics into the OCP dashboard with actionable alerting.

## Scope

### In Scope
- Real-time metric cards on OCP dashboard (dialogs, RTP sessions, memory, CPS, trunk health)
- Server-Sent Events (SSE) endpoint for live metric streaming
- Trunk provider health status widget (up/down, CPS, latency)
- Dispatcher set health widget (active/dead destinations)
- Anomaly alert banner (integration with anomaly_detector API)
- Prometheus alert list widget (firing alerts via Alertmanager API)
- Historical mini-charts (last 24h) for key metrics using existing D3.js stack

### Out of Scope
- Grafana replacement (Grafana remains the canonical observability platform)
- Log tailing / live log viewer
- SIP packet capture or pcap analysis
- Multi-region metrics federation

## Acceptance Criteria

| ID | Criterion | Verification |
|---|---|---|
| T34.1 | Dashboard loads with live metric cards within 2s of page load | Visual + network tab timing |
| T34.2 | SSE endpoint streams metric updates every 5s without page refresh | Browser devtools EventSource |
| T34.3 | Trunk health widget shows accurate up/down status per provider | Cross-reference with DB table + MI dispatcher state |
| T34.4 | Anomaly banner appears within 30s of detector triggering alert | Inject synthetic anomaly event via API |
| T34.5 | Prometheus alert widget shows only firing alerts (not resolved) | Cross-reference with Alertmanager API |
| T34.6 | All widgets are role-aware (admin sees all, readonly sees read-only metrics) | Login with readonly role and verify |
| T34.7 | No OpenSIPS MI access exposed to browser | SSE endpoint proxies via OCP PHP backend |
| T34.8 | Graceful degradation: if SSE fails, dashboard shows last-known values with stale indicator | Kill SSE endpoint and verify UI state |

## Architecture

```
Browser <---> OCP Dashboard (PHP)
                  |
                  +-- SSE endpoint: /api/v1/metrics-stream.php
                  |       +-- OpenSIPS MI HTTP (via curl to 127.0.0.1:8888)
                  |       +-- PostgreSQL (trunk status, dispatcher metadata)
                  |       +-- Anomaly detector API (http://anomaly_detector:8080/api/v1/status)
                  |
                  +-- AJAX endpoint: /api/v1/alerts.php
                  |       +-- Alertmanager API (http://alertmanager:9093/api/v1/alerts)
                  |
                  +-- Historical endpoint: /api/v1/metrics-history.php
                          +-- Prometheus query_range API
```

## Security Notes

- MI HTTP access is restricted to loopback (C4 remediation). OCP container must be on sip_internal network to reach OpenSIPS MI.
- Anomaly detector API requires X-API-Key header (H2 remediation). OCP stores key via Docker env.
- All browser-facing endpoints validate session/auth.
- SSE endpoint rate-limits to 1 connection per user session.

## Dependencies

- OpenSIPS MI HTTP module (already loaded)
- Anomaly detector API key env var (created in H2 remediation)
- Existing D3.js v7 + tsisip-charts.js stack
- Prometheus query API (already available on prometheus:9090)
- Alertmanager API (already available on alertmanager:9093)

## Non-Goals

- Replace Prometheus/Grafana
- Real-time SIP packet analysis
- Call recording playback
- Billing/CDR monetization features
