# Feature 038 Tasks: Anomaly Detection Integration

## Phase 1: OpenSIPS Event Forwarding

### [ ] T001: Load http_client module in OpenSIPS config
**Description**: Add `loadmodule "http_client.so"` and `modparam("http_client", "connection_timeout", 2)` to `opensips/opensips.cfg.tpl`. Ensure module is compiled in Docker image.
**Files**: `opensips/opensips.cfg.tpl`
**Depends**: —

### [ ] T002: Implement event_route POST to anomaly-detector
**Description**: In each event_route (E_PIKE_BLOCKED, E_AUTH_FAILURE, E_DISPATCHER_STATUS), after existing logic, construct JSON body and POST via http_client_query to `http://anomaly-detector:8080/api/v1/event`. JSON: `{event_type:"pike_blocked",source_ip:"$param(src_ip)",timestamp:$Ts}`.
**Files**: `opensips/opensips.cfg.tpl`
**Depends**: T001

### [ ] T003: Validate OpenSIPS config syntax with http_client
**Description**: Build image and run `opensips -c`. Ensure no module load errors.
**Files**: `opensips/opensips.cfg.tpl`, `Dockerfile`
**Depends**: T002

## Phase 2: Detector Hardening

### [ ] T004: Harden /api/v1/event endpoint
**Description**: Add input validation: required fields `event_type`, `source_ip`, `timestamp`. Reject unknown event types. Return 400 for bad input, 202 for accepted.
**Files**: `docker/anomaly_detector/detector.py`
**Depends**: —

### [ ] T005: Add per-event-type metrics
**Description**: Add Counter `tsisip_events_received_total` labeled by `event_type`. Increment on each valid ingestion.
**Files**: `docker/anomaly_detector/detector.py`
**Depends**: T004

## Phase 3: Alertmanager & Grafana

### [ ] T006: Add Alertmanager routing rule for anomaly alerts
**Description**: In `docker/prometheus/alertmanager.yml` (or equivalent), add route matching `alertname=~"AnomalyDetected.*"`. Route to webhook receiver.
**Files**: `docker/prometheus/alertmanager.yml`
**Depends**: —

### [ ] T007: Create Grafana dashboard panels
**Description**: Add 3 panels to existing Grafana dashboard JSON: Z-score gauge, RPS vs baseline graph, alert rate graph.
**Files**: `docker/grafana/dashboards/tsisip.json`
**Depends**: —

## Phase 4: Integration & Validation

### [ ] T008: Run test_anomaly_detection.py
**Description**: Execute existing integration test. Fix any failures.
**Files**: `tests/integration/test_anomaly_detection.py`
**Depends**: T003, T004, T006

### [ ] T009: Run end-to-end anomaly spike test
**Description**: Use sipp or Python to generate 1000+ auth failures in 10s. Verify detector Z-score spikes and Alertmanager receives alert within 2 windows.
**Files**: `tests/integration/`
**Depends**: T008

### [ ] T010: Update operator runbook
**Description**: Add section to `docs/TSiSIP-OPERATOR-RUNBOOK.md` covering: interpreting Z-score, responding to anomaly alerts, manually tuning threshold.
**Files**: `docs/TSiSIP-OPERATOR-RUNBOOK.md`
**Depends**: T009
