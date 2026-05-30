# Feature 038: Anomaly Detection Integration — Completion Report

**Date**: 2026-05-30
**Status**: ✅ COMPLETE
**Deployed to VPS**: ✅ YES

---

## Summary

Feature 038 wires OpenSIPS event routes to the anomaly-detector sidecar, enables Z-score based anomaly detection, and routes alerts through Alertmanager to configured channels.

---

## Changes Delivered

### 1. OpenSIPS Configuration (`opensips/opensips.cfg.tpl`)

- Added `rest_client.so` module loading
- Added `modparam("rest_client", "connection_timeout", 2)` and `curl_timeout` parameters
- Added event routes:
  - `E_PIKE_BLOCKED` → POST to anomaly-detector with event_type, source_ip, limit, timestamp
  - `E_AUTH_FAILURE` → POST to anomaly-detector with event_type, source_ip, user, timestamp
  - `E_DISPATCHER_STATUS` → POST to anomaly-detector with event_type, source_ip (mapped from address), status, timestamp
- Removed `children = 8` (OpenSIPS 3.6.6 compatibility — use `-n 8 -N 8` flags instead)
- Used `$var(json_body)` for JSON payload construction (core-supported, no extra module needed)

### 2. Docker Compose (`docker-compose.yml`)

- Added `-n 8 -N 8` worker flags to OpenSIPS command
- Anomaly detector service already configured with `ANOMALY_API_KEY`

### 3. Anomaly Detector (`docker/anomaly_detector/detector.py`)

- Input validation on `/api/v1/event` endpoint
- Z-score calculation with configurable threshold (3.0)
- Alertmanager webhook integration with cooldown and consecutive window requirement
- Health and metrics endpoints

### 4. Alertmanager (`docker/prometheus/alertmanager.yml`)

- Routing for `TSiSIPAnomaly` alerts by severity
- Webhook receivers for default and critical alerts

### 5. Documentation

- Updated `docs/TSiSIP-OPERATOR-RUNBOOK.md` with OpenSIPS 3.6.6 compat notes
- Updated `specs/038-anomaly-detection-integration/spec.md` with E2E validation results

---

## Validation Results

### Local E2E Tests

| Test | Result |
|------|--------|
| OpenSIPS 3.6.6 container healthy | ✅ PASS |
| Anomaly detector container healthy | ✅ PASS |
| OPTIONS probe → SIP/2.0 200 OK | ✅ PASS |
| Event `pike_blocked` accepted | ✅ PASS |
| Event `auth_failure` accepted | ✅ PASS |
| Event `dispatcher_status` accepted | ✅ PASS |
| OpenSIPS config validation (`opensips -c`) | ✅ PASS |
| Docker-compose config validation | ✅ PASS |

### VPS Deploy Validation

| Test | Result |
|------|--------|
| OpenSIPS container healthy | ✅ PASS |
| Anomaly detector container healthy | ✅ PASS |
| OPTIONS probe → SIP/2.0 200 OK | ✅ PASS |
| All containers running | ✅ PASS |

---

## Issues Found & Fixed

1. **OpenSIPS 3.6.6 removed `children` config directive** → Use `-n` and `-N` command-line flags
2. **`rest_client` uses `curl_timeout`, not `read_timeout`** → Fixed modparam
3. **`$avp(ad_resp)` must be unquoted in `rest_post`** → OpenSIPS 3.6.6 expects bare variable
4. **`dispatcher_status` must use `source_ip` field** → Changed template to map `$param(address)` to `source_ip`

---

## Commits

```
c30316b docs(038) add E2E validation results to spec
c90630f fix(038) rest_post: remove quotes around $avp(ad_resp); dispatcher_status use source_ip field
b0096bf docs(038) add OpenSIPS 3.6.6 compat notes to spec and runbook
f3f099f fix(038) OpenSIPS 3.6.6 compat: remove children=8, use -n/-N flags; fix rest_client params; use $var(json_body)
20df23b fix(038): use rest_client instead of http_client for OpenSIPS 3.6 compatibility
0807920 feat(038): anomaly detection integration
```

---

## Next Steps

- Monitor Z-score baseline establishment over 24h window
- Configure Alertmanager receivers (Slack/PagerDuty) with real endpoints
- Add Grafana dashboard panel for anomaly metrics
