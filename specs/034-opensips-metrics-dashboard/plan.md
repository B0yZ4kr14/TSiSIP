# Feature 034 Implementation Plan

## Phase 1: Backend Infrastructure (Days 1-2)

### 1.1 SSE Metrics Stream Endpoint
- Create `web/api/v1/metrics-stream.php`
- Implement EventSource header and loop
- Proxy OpenSIPS MI commands: `get_statistics all`, `ds_list`, `dlg_list_count`
- Query PostgreSQL for trunk provider health status
- Fetch anomaly detector status via internal HTTP with auth header
- Emit JSON payload every 5 seconds
- Rate limit: 1 connection per session

### 1.2 Alerts AJAX Endpoint
- Create `web/api/v1/alerts.php`
- Query Alertmanager `/api/v1/alerts` for firing alerts
- Filter by severity (critical, warning)
- Return JSON array with alert labels, annotations, severity

### 1.3 Historical Metrics Endpoint
- Create `web/api/v1/metrics-history.php`
- Proxy Prometheus `query_range` for:
  - `opensips_dialogs_active`
  - `opensips_usrloc_contacts`
  - `rtpengine_sessions`
  - `rate(opensips_received_replies_total[1m])`
- Return time-series JSON for D3.js consumption

## Phase 2: Frontend Dashboard Widgets (Days 3-4)

### 2.1 Live Metric Cards
- Enhance existing dashboard metric cards with SSE data binding
- Add auto-refresh indicator (pulse dot when data is fresh)
- Add stale indicator (greyed out + warning icon when SSE disconnects)
- Cards: Dialogs, RTP Sessions, Pkg Mem, Shm Mem, Processes, Blocked IPs, TCP Conns, Blacklists

### 2.2 Trunk Health Widget
- New dashboard section: "Trunk Providers"
- Display table with: Provider Name, Host, Status (up/down), CPS, Latency
- Status derived from PostgreSQL `sip_trunk_providers` + dispatcher probe state
- Color-coded: green (up), red (down), yellow (degraded)

### 2.3 Dispatcher Health Widget
- New dashboard section: "Dispatcher Sets"
- Display grid of destination groups
- Each destination: IP:Port, Weight, State (active/inactive/probing), Last Probe
- Data from MI `ds_list` command

### 2.4 Anomaly Alert Banner
- Sticky banner at top of dashboard
- Appears when anomaly_detector reports z_score > threshold
- Shows: alert type, severity, current RPS, baseline mean
- Auto-dismiss after cooldown period
- Link to detailed anomaly status page

### 2.5 Prometheus Alerts Widget
- Sidebar or dashboard section: "Active Alerts"
- List firing alerts from Alertmanager
- Columns: Severity, Alert Name, Summary, Started At
- Auto-refresh every 30s

### 2.6 Historical Mini-Charts
- Sparkline charts below each metric card
- 24h trend using D3.js line chart
- Hover tooltip showing exact value at timepoint

## Phase 3: Integration & Security (Day 5)

### 3.1 OCP Integration
- Add new widgets to `dashboard.php`
- Ensure role-based visibility (admin/devops sees all, readonly sees metrics only)
- Add navigation link to "System Health" page with full-screen metrics view

### 3.2 Security Hardening
- Verify MI HTTP is only accessible via loopback (C4)
- Verify anomaly detector auth header is injected via Docker env (H2)
- Add CSRF token validation to AJAX endpoints
- Add `Content-Security-Policy` headers for SSE connections

### 3.3 Error Handling & Graceful Degradation
- SSE reconnection with exponential backoff (max 30s)
- Display cached values with "stale data" warning when SSE is down
- Fallback to polling AJAX every 10s if EventSource is unsupported

## Phase 4: Testing & Validation (Day 6)

### 4.1 Unit Tests
- PHP endpoint tests with mock MI responses
- Frontend D3.js chart rendering tests
- Role-based visibility tests

### 4.2 Integration Tests
- End-to-end SSE streaming test
- Anomaly banner trigger test
- Alertmanager widget accuracy test
- Graceful degradation test (kill SSE endpoint)

### 4.3 Performance Tests
- 50 concurrent SSE connections
- Dashboard load time < 2s under load
- Memory usage of OCP container during streaming

## Deliverables

| Artifact | Path |
|---|---|
| SSE endpoint | `web/api/v1/metrics-stream.php` |
| Alerts endpoint | `web/api/v1/alerts.php` |
| History endpoint | `web/api/v1/metrics-history.php` |
| Dashboard widgets | `web/dashboard.php` (enhanced) |
| System Health page | `web/system-health.php` (new) |
| D3.js chart module | `web/tsisip/js/tsisip-metrics-charts.js` |
| CSS enhancements | `web/tsisip/css/tsisip-theme.css` |
| Integration tests | `tests/integration/test-metrics-dashboard*.sh` |
| Operator runbook update | `docs/TSiSIP-OPERATOR-RUNBOOK.md` |
