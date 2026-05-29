# Feature 034 Tasks

## Phase 1: Backend Infrastructure

- [x] T34.1.1 Create `web/api/v1/metrics-stream.php` with EventSource headers
- [x] T34.1.2 Implement OpenSIPS MI proxy (get_statistics, ds_list, dlg_list_count)
- [x] T34.1.3 Add PostgreSQL trunk health query to SSE endpoint
- [x] T34.1.4 Integrate anomaly_detector API with X-API-Key auth
- [x] T34.1.5 Implement 5-second emission loop with connection rate limit
- [x] T34.1.6 Create `web/api/v1/alerts.php` for Alertmanager proxy
- [x] T34.1.7 Create `web/api/v1/metrics-history.php` for Prometheus query_range proxy
- [x] T34.1.8 Add session/auth guards to all API endpoints

## Phase 2: Frontend Dashboard Widgets

- [ ] T34.2.1 Enhance metric cards with SSE data binding and stale indicator
- [ ] T34.2.2 Build Trunk Health widget (table with status, CPS, latency)
- [ ] T34.2.3 Build Dispatcher Health widget (grid of destination groups)
- [x] T34.2.4 Build Anomaly Alert Banner (sticky, auto-dismiss, severity colors)
- [ ] T34.2.5 Build Prometheus Alerts widget (sidebar list, auto-refresh)
- [x] T34.2.6 Build Historical Mini-Charts with D3.js sparklines
- [x] T34.2.7 Create full-screen System Health page (`web/system-health.php`)

## Phase 3: Integration & Security

- [ ] T34.3.1 Integrate widgets into `dashboard.php` with role-based visibility
- [ ] T34.3.2 Verify MI HTTP loopback restriction (C4)
- [ ] T34.3.3 Verify anomaly detector auth header via Docker env (H2)
- [x] T34.3.4 Add CSRF validation to AJAX endpoints
- [x] T34.3.5 Implement SSE reconnection with exponential backoff
- [ ] T34.3.6 Implement fallback AJAX polling for browsers without EventSource

## Phase 4: Testing & Validation

- [ ] T34.4.1 Write PHP unit tests for endpoints with mock MI responses
- [ ] T34.4.2 Write frontend D3.js rendering tests
- [ ] T34.4.3 Write role-based visibility tests
- [x] T34.4.4 Write end-to-end SSE streaming integration test
- [ ] T34.4.5 Write anomaly banner trigger test
- [x] T34.4.6 Write graceful degradation test (kill SSE, verify stale indicator)
- [ ] T34.4.7 Performance test: 50 concurrent SSE connections
- [ ] T34.4.8 Update operator runbook with new dashboard features

## Dependencies

- T34.1.4 blocked until H2 anomaly detector API key is configured in compose
- T34.1.2 blocked until C4 MI HTTP loopback is verified on target host
- T34.2.1 depends on T34.1.1
- T34.3.1 depends on T34.2.1 through T34.2.7
- T34.4.4 depends on T34.3.5
