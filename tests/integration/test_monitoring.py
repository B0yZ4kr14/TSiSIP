#!/usr/bin/env python3
"""
Integration tests for monitoring stack (Grafana dashboards).
"""

import os
import json
import pytest

DASHBOARD_DIR = "docker/grafana/provisioning/dashboards/tsisip"


class TestMonitoring:
    """Test Grafana dashboards and monitoring configuration."""

    def test_dashboard_files_exist(self):
        """All dashboard JSON files exist."""
        expected = [
            "dispatcher-health.json",
            "capacity-planning.json",
            "deployment-validation.json",
            "health-status.json",
        ]
        for dash in expected:
            path = os.path.join(DASHBOARD_DIR, dash)
            assert os.path.exists(path), f"Dashboard {dash} not found"

    def test_dashboards_have_title(self):
        """All dashboards have non-empty titles."""
        for filename in os.listdir(DASHBOARD_DIR):
            if not filename.endswith(".json"):
                continue
            path = os.path.join(DASHBOARD_DIR, filename)
            with open(path) as f:
                data = json.load(f)
            # Support both wrapped (API format) and unwrapped (provisioning format)
            dashboard = data.get("dashboard", data)
            title = dashboard.get("title", "")
            assert title, f"Dashboard {filename} missing title"
            assert "TSiSIP" in title, f"Dashboard {filename} title missing 'TSiSIP'"

    def test_dashboards_have_panels(self):
        """All dashboards have at least one panel."""
        for filename in os.listdir(DASHBOARD_DIR):
            if not filename.endswith(".json"):
                continue
            path = os.path.join(DASHBOARD_DIR, filename)
            with open(path) as f:
                data = json.load(f)
            # Support both wrapped (API format) and unwrapped (provisioning format)
            dashboard = data.get("dashboard", data)
            panels = dashboard.get("panels", [])
            assert len(panels) > 0, f"Dashboard {filename} has no panels"

    def test_datasource_config_exists(self):
        """Prometheus datasource provisioning exists."""
        path = "docker/grafana/provisioning/datasources/prometheus.yml"
        assert os.path.exists(path), "Prometheus datasource config missing"

    def test_alert_rules_exist(self):
        """Prometheus alert rules exist."""
        path = "docker/prometheus/alert-rules.yml"
        assert os.path.exists(path), "Alert rules missing"
        with open(path) as f:
            data = yaml_safe_load(f) if "yaml" in globals() else f.read()
        assert "groups" in str(data), "Alert rules missing groups"


if __name__ == "__main__":
    pytest.main([__file__, "-v"])
