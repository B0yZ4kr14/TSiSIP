#!/usr/bin/env python3
"""
Feature 034 Integration Tests: Real-Time Metrics Dashboard & Alerting
Test IDs: T34.4.1 - T34.4.8
"""

import json
import os
import re

import pytest

PROJECT_ROOT = os.path.dirname(os.path.dirname(os.path.dirname(__file__)))


class TestMetricsDashboard:
    """Verify Feature 034 backend endpoints, frontend widgets, and configuration."""

    def test_alerts_endpoint_file_exists(self):
        """T34.1.6: alerts.php endpoint exists."""
        path = os.path.join(PROJECT_ROOT, "web", "api", "v1", "alerts.php")
        assert os.path.exists(path), "alerts.php endpoint missing"

    def test_metrics_history_endpoint_file_exists(self):
        """T34.1.7: metrics-history.php endpoint exists."""
        path = os.path.join(PROJECT_ROOT, "web", "api", "v1", "metrics-history.php")
        assert os.path.exists(path), "metrics-history.php endpoint missing"

    def test_metrics_stream_endpoint_file_exists(self):
        """T34.1.1: metrics-stream.php endpoint exists."""
        path = os.path.join(PROJECT_ROOT, "web", "api", "v1", "metrics-stream.php")
        assert os.path.exists(path), "metrics-stream.php endpoint missing"

    def test_alerts_endpoint_proxies_alertmanager(self):
        """T34.1.6: alerts.php calls Alertmanager API."""
        path = os.path.join(PROJECT_ROOT, "web", "api", "v1", "alerts.php")
        with open(path) as f:
            content = f.read()
        assert "alertmanager:9093/api/v1/alerts" in content, "alerts.php missing Alertmanager URL"
        assert "firing" in content, "alerts.php not filtering to firing alerts"

    def test_metrics_history_validates_metric(self):
        """T34.1.7: metrics-history.php validates allowed metrics."""
        path = os.path.join(PROJECT_ROOT, "web", "api", "v1", "metrics-history.php")
        with open(path) as f:
            content = f.read()
        assert "allowedMetrics" in content, "metrics-history.php missing metric whitelist"
        assert "opensips_dialogs_active" in content, "missing opensips_dialogs_active metric"
        assert "rtpengine_sessions" in content, "missing rtpengine_sessions metric"

    def test_sse_stream_requires_auth(self):
        """T34.4.4: metrics-stream.php validates session auth."""
        path = os.path.join(PROJECT_ROOT, "web", "api", "v1", "metrics-stream.php")
        with open(path) as f:
            content = f.read()
        assert "requireAuth()" in content, "metrics-stream.php missing auth guard"

    def test_sse_stream_rate_limited(self):
        """T34.1.5: metrics-stream.php rate-limits to 1 connection per session."""
        path = os.path.join(PROJECT_ROOT, "web", "api", "v1", "metrics-stream.php")
        with open(path) as f:
            content = f.read()
        assert "429" in content, "metrics-stream.php missing rate limit (429)"
        assert "sseConnections" in content, "metrics-stream.php missing connection tracking"

    def test_sse_stream_has_max_runtime(self):
        """T34.3.5: metrics-stream.php has max runtime guard."""
        path = os.path.join(PROJECT_ROOT, "web", "api", "v1", "metrics-stream.php")
        with open(path) as f:
            content = f.read()
        assert "maxRuntime" in content, "metrics-stream.php missing max runtime guard"

    def test_dashboard_has_anomaly_banner(self):
        """T34.2.4: dashboard.php contains anomaly alert banner."""
        path = os.path.join(PROJECT_ROOT, "web", "dashboard.php")
        with open(path) as f:
            content = f.read()
        assert "anomaly-banner" in content, "dashboard.php missing anomaly banner"
        assert "updateAnomalyBanner" in content, "dashboard.php missing anomaly JS handler"

    def test_dashboard_has_trunk_health_widget(self):
        """T34.2.2: dashboard.php contains trunk health widget."""
        path = os.path.join(PROJECT_ROOT, "web", "dashboard.php")
        with open(path) as f:
            content = f.read()
        assert "trunk-health-widget" in content, "dashboard.php missing trunk health widget"
        assert "trunk-health-tbody" in content, "dashboard.php missing trunk health table body"
        assert "updateTrunkHealth" in content, "dashboard.php missing trunk health JS handler"

    def test_dashboard_has_alerts_widget(self):
        """T34.2.5: dashboard.php contains active alerts widget."""
        path = os.path.join(PROJECT_ROOT, "web", "dashboard.php")
        with open(path) as f:
            content = f.read()
        assert "alerts-widget" in content, "dashboard.php missing alerts widget"
        assert "loadAlerts" in content, "dashboard.php missing alerts JS handler"
        assert "api/v1/alerts.php" in content, "dashboard.php not calling alerts endpoint"

    def test_system_health_page_exists(self):
        """T34.2.7: system-health.php full-screen page exists."""
        path = os.path.join(PROJECT_ROOT, "web", "system-health.php")
        assert os.path.exists(path), "system-health.php missing"

    def test_system_health_has_dispatcher_widget(self):
        """T34.2.3: system-health.php shows dispatcher sets."""
        path = os.path.join(PROJECT_ROOT, "web", "system-health.php")
        with open(path) as f:
            content = f.read()
        assert "dispatcher-widget" in content, "system-health.php missing dispatcher widget"
        assert "updateDispatcher" in content, "system-health.php missing dispatcher JS handler"

    def test_role_based_visibility_admin_devops(self):
        """T34.3.1: dashboard.php shows trunk/alerts only to admin/devops."""
        path = os.path.join(PROJECT_ROOT, "web", "dashboard.php")
        with open(path) as f:
            content = f.read()
        assert "$isAdmin || $isDevOps" in content, "dashboard.php missing role guard for trunk/alerts"

    def test_ocp_has_anomaly_api_key_env(self):
        """T34.3.2: OCP service has ANOMALY_API_KEY environment variable."""
        path = os.path.join(PROJECT_ROOT, "docker-compose.yml")
        with open(path) as f:
            content = f.read()
        assert "ANOMALY_API_KEY" in content, "docker-compose.yml missing ANOMALY_API_KEY for OCP"

    def test_ocp_has_opensips_mi_url_env(self):
        """T34.3.2: OCP service has OPENSIPS_MI_URL environment variable."""
        path = os.path.join(PROJECT_ROOT, "docker-compose.yml")
        with open(path) as f:
            content = f.read()
        assert "OPENSIPS_MI_URL" in content, "docker-compose.yml missing OPENSIPS_MI_URL for OCP"

    def test_sse_client_reconnects(self):
        """T34.3.5: SSE client has exponential backoff reconnection."""
        path = os.path.join(PROJECT_ROOT, "web", "tsisip", "js", "sse-client.js")
        with open(path) as f:
            content = f.read()
        assert "scheduleReconnect" in content, "sse-client.js missing reconnection logic"
        assert "reconnectDelay" in content, "sse-client.js missing reconnect delay"

    def test_sse_stream_extended_with_anomaly(self):
        """T34.1.4: sse-stream.php extended with anomaly detector data."""
        path = os.path.join(PROJECT_ROOT, "web", "common", "sse-stream.php")
        with open(path) as f:
            content = f.read()
        assert "anomalyUrl" in content, "sse-stream.php missing anomaly detector integration"
        assert "X-API-Key" in content, "sse-stream.php missing anomaly API key header"

    def test_sse_stream_extended_with_trunks(self):
        """T34.1.3: sse-stream.php extended with trunk provider data."""
        path = os.path.join(PROJECT_ROOT, "web", "common", "sse-stream.php")
        with open(path) as f:
            content = f.read()
        assert "sip_trunk_providers" in content, "sse-stream.php missing trunk provider query"
        assert "trunks" in content, "sse-stream.php missing trunks data key"

    def test_css_has_alert_list_style(self):
        """T34.2.5: CSS has alert list styling."""
        path = os.path.join(PROJECT_ROOT, "web", "tsisip", "css", "tsisip-theme.css")
        with open(path) as f:
            content = f.read()
        assert ".tsisip-alert-list" in content, "tsisip-theme.css missing alert list style"


if __name__ == "__main__":
    pytest.main([__file__, "-v"])
