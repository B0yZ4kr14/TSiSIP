# Tasks: TSiSIP Observability Platform with Prometheus and Grafana

## Phase 1 — Prometheus Infrastructure

### [X] T1.1: Create Prometheus Dockerfile and configuration template
**Description**: Create `docker/prometheus/Dockerfile` based on `prom/prometheus:v2.51`. Add `promtool` for config validation. Create `docker/prometheus/prometheus.yml.tpl` with scrape jobs for: OpenSIPS exporter (15s), RTPengine (30s), PostgreSQL via postgres_exporter (30s), host node_exporter (30s). Include retention flags: `--storage.tsdb.retention.time=30d` and `--storage.tsdb.retention.size=10GB`.
**Phase**: 1
**Depends on**: —
**Parallel**: No
**Acceptance**: `docker build -t tsisip/prometheus:test docker/prometheus/` succeeds; `promtool check config` on rendered template passes.

### [X] T1.2: Create Alertmanager configuration
**Description**: Create `docker/prometheus/alertmanager.yml.tpl` with: global SMTP/webhook settings, route with `group_by=['alertname','severity']`, receiver for critical alerts with webhook URL from env var. Include inhibition rules for alert suppression during maintenance.
**Phase**: 1
**Depends on**: T1.1
**Parallel**: No
**Acceptance**: `amtool check-config` passes; webhook URL is templated from environment.

### [X] T1.3: Define Prometheus alert rules
**Description**: Create `docker/prometheus/alert-rules.yml` with rules: `OpenSIPSDispatcherDegradation` (dispatcher target response >500ms for 2m), `OpenSIPSAuthSpike` (>10 auth failures/min for 2m), `RTPengineHighUtilization` (>80% port usage for 5m), `PostgreSQLSlowQueries` (>100ms avg for 5m), `PrometheusDiskFull` (>85% disk for 5m). Each rule must include `runbook_url` annotation.
**Phase**: 1
**Depends on**: T1.1
**Parallel**: [P] with T1.2
**Acceptance**: `promtool test rules` with test cases passes.

### [X] T1.4: Add Prometheus and Alertmanager to docker-compose.yml
**Description**: Add `prometheus` and `alertmanager` services to root `docker-compose.yml`. Attach to `db_internal` network only (no host-published ports). Mount config from `./docker/prometheus/`. Set health checks. Prometheus depends on alertmanager.
**Phase**: 1
**Depends on**: T1.2, T1.3
**Parallel**: No
**Acceptance**: `docker compose config` validates; services start and pass health checks.

## Phase 2 — OpenSIPS Metric Exporter

### [X] T2.1: Create OpenSIPS exporter sidecar
**Description**: Create `docker/opensips-exporter/Dockerfile` from `python:3.11-slim-bookworm`. Create `docker/opensips-exporter/exporter.py` that: polls OpenSIPS MI via `mi_json` on `sip_internal`, transforms output to Prometheus exposition format, exposes `/metrics` on port 9442. Metrics: `opensips_active_dialogs_total`, `opensips_registered_subscribers`, `opensips_dispatcher_target_state`, `opensips_auth_failures_total`, `opensips_sip_requests_total{method,status}`. Implement scrape caching (cache TTL = 10s) to prevent MI overload.
**Phase**: 2
**Depends on**: T1.4
**Parallel**: No
**Acceptance**: Exporter responds with valid Prometheus format; cache prevents duplicate MI queries within TTL.

### [X] T2.2: Add MI module to OpenSIPS configuration
**Description**: Add `loadmodule "mi_json.so"` and `modparam("mi_json", "mi_json_root", "/mi")` to `opensips/opensips.cfg.tpl`. Ensure MI socket listens on `sip_internal` interface only. Add `mi_json` to the module package list in the OpenSIPS Dockerfile if not already present.
**Phase**: 2
**Depends on**: T2.1
**Parallel**: No
**Acceptance**: `opensips -c` passes; MI responds on internal network.

### [X] T2.3: Add exporter service to docker-compose.yml
**Description**: Add `opensips-exporter` service to `docker-compose.yml`. Networks: `sip_internal`, `db_internal`. Expose port 9442 internally only. Depends on `opensips`. Add health check on `/metrics` endpoint.
**Phase**: 2
**Depends on**: T2.1, T2.2
**Parallel**: No
**Acceptance**: `docker compose up -d opensips-exporter` starts; health check passes.

## Phase 3 — Grafana Dashboards

### [X] T3.1: Create Grafana Dockerfile with provisioning
**Description**: Create `docker/grafana/Dockerfile` from `grafana/grafana:10.4`. Copy provisioning configs: `provisioning/datasources/prometheus.yml` (auto-configure Prometheus datasource), `provisioning/dashboards/dashboards.yml` (dashboard provider). Set env vars: `GF_SECURITY_ADMIN_PASSWORD_FILE`, `GF_USERS_DEFAULT_THEME`, `GF_SERVER_ROOT_URL`.
**Phase**: 3
**Depends on**: T1.4
**Parallel**: No
**Acceptance**: Image builds; Grafana starts with pre-configured datasource.

### [X] T3.2: Create dispatcher health dashboard
**Description**: Create `docker/grafana/provisioning/dashboards/tsisip/dispatcher-health.json`. Panels: target state (stat), response time (graph), active dialogs per target (graph), alert status (table). Variables: `target`, `time_range`. Refresh: 10s. Role visibility: NOC operator.
**Phase**: 3
**Depends on**: T3.1
**Parallel**: [P] with T3.3, T3.4
**Acceptance**: Dashboard JSON imports without errors; panels show data from Prometheus.

### [X] T3.3: Create capacity planning dashboard
**Description**: Create `docker/grafana/provisioning/dashboards/tsisip/capacity-planning.json`. Panels: concurrent calls week-over-week (graph), RTP port utilization % (gauge), subscriber growth (graph), PostgreSQL query latency (graph). Time range: last 30 days. Role visibility: Administrator.
**Phase**: 3
**Depends on**: T3.1
**Parallel**: [P] with T3.2, T3.4
**Acceptance**: Dashboard loads historical data; week-over-week comparison works.

### [X] T3.4: Create deployment validation dashboard
**Description**: Create `docker/grafana/provisioning/dashboards/tsisip/deployment-validation.json`. Panels: call success rate % (stat), registration rate (graph), dispatcher health summary (table), recent alerts (table). Time range: last 1 hour. Role visibility: DevOps engineer.
**Phase**: 3
**Depends on**: T3.1
**Parallel**: [P] with T3.2, T3.3
**Acceptance**: Dashboard updates in real-time after deployment.

### [X] T3.5: Implement i18n for dashboard labels
**Description**: Create `docker/grafana/provisioning/i18n/` with JSON files: `en.json`, `es.json`, `pt.json`. Each contains panel title translations. Modify dashboard JSONs to reference i18n keys via text panel variables or Grafana's built-in locale support.
**Phase**: 3
**Depends on**: T3.2, T3.3, T3.4
**Parallel**: No
**Acceptance**: Switching Grafana user locale changes panel titles for all dashboards.

## Phase 4 — Integration & Validation

### [X] T4.1: Add Grafana to docker-compose.yml
**Description**: Add `grafana` service to `docker-compose.yml`. Network: `db_internal` only. Environment from env file. Volume for persistent storage. Health check on `/api/health`. Depends on `prometheus`.
**Phase**: 4
**Depends on**: T3.5
**Parallel**: No
**Acceptance**: `docker compose up -d` starts all services; Grafana accessible internally.

### [X] T4.2: Create end-to-end validation test
**Description**: Create `tests/integration/test_observability.py` that: starts the stack, waits for Prometheus scrape, queries `/metrics` endpoint, verifies all metric families present, checks dashboard JSON via Grafana API, triggers a test alert and verifies Alertmanager receives it. Uses `pytest` and `requests`.
**Phase**: 4
**Depends on**: T4.1
**Parallel**: No
**Acceptance**: `pytest tests/integration/test_observability.py` passes.

### [X] T4.3: Document observability runbook
**Description**: Update `docs/TSiSIP-OPERATOR-RUNBOOK.md` with: Prometheus query examples, Grafana dashboard URLs, alert response procedures, metric cardinality troubleshooting, scrape failure diagnosis.
**Phase**: 4
**Depends on**: T4.2
**Parallel**: No
**Acceptance**: Runbook contains actionable procedures for all alerts defined in T1.3.
