# Plan: Feature 038 — Anomaly Detection Integration

## Tech Stack

| Layer | Technology |
|-------|------------|
| SIP Proxy | OpenSIPS 3.6 LTS |
| Event Transport | http_client module (HTTP POST) |
| Detector | Python 3.12 + Flask + prometheus_client |
| Baseline | Rolling window statistics (mean/stddev) |
| Alerting | Prometheus Alertmanager |
| Dashboard | Grafana + Prometheus |

## File Structure

```
docker/anomaly_detector/
  detector.py          # Existing — add event ingestion robustness
  baseline.py          # Existing — verify 24h window logic
opensips/
  opensips.cfg.tpl     # Add http_client module + event_route POST calls
docker-compose.yml     # Ensure anomaly_detector service + env vars
web/
  (no changes — Grafana handles UI)
tests/integration/
  test_anomaly_detection.py  # Existing — validate end-to-end
docs/
  TSiSIP-OPERATOR-RUNBOOK.md # Add anomaly response procedures
```

## OpenSIPS Changes

1. Load `http_client.so` module.
2. Set `modparam("http_client", "connection_timeout", 2)`.
3. In `event_route[E_PIKE_BLOCKED]`: after cache_store, call `http_client_query("http://anomaly-detector:8080/api/v1/event", "$json_body", "$avp(resp)", "Content-Type: application/json")`.
4. In `event_route[E_AUTH_FAILURE]`: same pattern.
5. In `event_route[E_DISPATCHER_STATUS]`: same pattern.

## Detector Changes

1. Verify `/api/v1/event` handles missing fields gracefully.
2. Ensure baseline.py computes 24h rolling window correctly.
3. Add structured logging per event type.

## Alertmanager Changes

1. Add routing rule for `tsisip_anomaly_alerts_total`.
2. Configure webhook receiver for critical alerts.

## Grafana Changes

1. Add panel: `tsisip_anomaly_z_score` (gauge, threshold 3.0).
2. Add panel: `tsisip_current_rps` vs `tsisip_baseline_mean_rps`.
3. Add panel: `tsisip_anomaly_alerts_total` rate.
