# Feature 008 Proposal: Anomaly Detection Integration

## Context

Feature 006 implemented rate limiting (pike, ratelimit, userblacklist) and event routes:
- `E_PIKE_BLOCKED` — IP throttling events
- `E_AUTH_FAILURE` — authentication failures
- `E_DISPATCHER_STATUS` — backend health changes

The `anomaly-detector` container exists but is not yet integrated with OpenSIPS events.

## Goal

Wire the anomaly detector to OpenSIPS event routes and implement real-time anomaly detection:
1. OpenSIPS pushes events to anomaly-detector via HTTP/UDP
2. Anomaly-detector maintains time-window baselines per metric
3. Deviations beyond Z-score threshold trigger alerts
4. Alerts feed into Alertmanager for routing (email, webhook, PagerDuty)

## Architecture

```
OpenSIPS Event Routes
    | E_PIKE_BLOCKED, E_AUTH_FAILURE, E_DISPATCHER_STATUS
    v
anomaly-detector:8080 (HTTP POST /events)
    |
    | Baseline comparison (24h window)
    v
Alertmanager (if Z-score > threshold)
    |
    | Webhook / Email / PagerDuty
    v
Operator
```

## In Scope
- OpenSIPS `event_route` HTTP POST to anomaly-detector
- Anomaly-detector event ingestion endpoint
- Baseline calculation and Z-score detection
- Alertmanager webhook receiver
- Grafana panel for anomaly score
- Integration tests

## Out of Scope
- Machine learning models (keep it statistical)
- Automatic remediation (manual operator action)
- Historical data warehouse

## Success Criteria

| ID | Criterion |
|----|-----------|
| SC-1 | OpenSIPS sends events to anomaly-detector within 1s of occurrence |
| SC-2 | Anomaly detector calculates Z-score against 24h baseline |
| SC-3 | Alerts fire when Z-score exceeds 3.0 for 2 consecutive windows |
| SC-4 | Alertmanager receives and routes alerts to configured channels |
| SC-5 | Grafana dashboard shows real-time anomaly score and event rate |
