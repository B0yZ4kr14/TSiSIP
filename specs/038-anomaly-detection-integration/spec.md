# Feature 038: Anomaly Detection Integration

## Overview

| Field | Value |
|-------|-------|
| **Feature** | Anomaly Detection Integration |
| **Short name** | anomaly-detection-integration |
| **Created** | 2026-05-19 |
| **Status** | In Progress |
| **Last Updated** | 2026-05-19 |
| **Context** | Feature 006 implemented rate limiting (pike, ratelimit, userblacklist) and event routes: E_PIKE_BLOCKED, E_AUTH_FAILURE, E_DISPATCHER_STATUS. The anomaly-detector container exists but is not yet receiving events from OpenSIPS. This feature wires the event routes to the detector and enables real-time anomaly detection with Alertmanager alerting. |
| **Objective** | Wire OpenSIPS event routes to the anomaly-detector sidecar, enable Z-score based anomaly detection, and route alerts through Alertmanager to configured channels (webhook, email, PagerDuty). |

## Goals

1. **OpenSIPS Event Forwarding**: Configure OpenSIPS `event_route`s to HTTP POST events to `anomaly-detector:8080/api/v1/event`.
2. **Event Ingestion**: Ensure anomaly-detector ingests events, updates traffic baselines, and calculates Z-scores.
3. **Alerting**: Fire alerts to Alertmanager when Z-score exceeds threshold for consecutive windows.
4. **Grafana Panel**: Add real-time anomaly score and event rate panels to the existing Grafana dashboard.
5. **Integration Tests**: Validate end-to-end flow from OpenSIPS event → detector → Alertmanager.

## Non-Goals

- Machine learning models (keep statistical Z-score).
- Automatic remediation (manual operator action only).
- Historical data warehouse.

## Success Criteria

| ID | Criterion | Verification |
|----|-----------|--------------|
| SC-1 | OpenSIPS sends events to anomaly-detector within 1s of occurrence | Integration test with simulated pike block |
| SC-2 | Anomaly detector calculates Z-score against 24h baseline | Unit test in detector container |
| SC-3 | Alerts fire when Z-score exceeds 3.0 for 2 consecutive windows | Integration test with load spike |
| SC-4 | Alertmanager receives and routes alerts | Verify webhook receiver gets payload |
| SC-5 | Grafana dashboard shows real-time anomaly score | Screenshot or curl /metrics |

## Architecture

```
OpenSIPS Event Routes
    | E_PIKE_BLOCKED, E_AUTH_FAILURE, E_DISPATCHER_STATUS
    | HTTP POST /api/v1/event
    v
anomaly-detector:8080
    |
    | Baseline comparison (24h window)
    | Z-score > threshold (3.0)
    v
Alertmanager:9093
    |
    | Webhook / Email / PagerDuty
    v
Operator
```

## Rejected Patterns

| Rejected | Canonical |
|----------|-----------|
| OpenSIPS `evi` TCP socket (complex, firewall issues) | HTTP POST via `http_client` module |
| Direct email from detector | Alertmanager as central router |
| Anomaly auto-ban (risk of false positives) | Alert only, manual remediation |
