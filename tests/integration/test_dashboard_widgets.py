"""
Dashboard Widgets Tests (Feature 034)

Validates role-based visibility and widget data binding.
"""
import os
import pytest

class TestDashboardWidgets:
    def test_dispatcher_health_widget_exists(self):
        """T34.2.3: Dispatcher Health widget exists in dashboard"""
        path = 'web/dashboard.php'
        assert os.path.exists(path)
        with open(path) as f:
            content = f.read()
        assert 'dispatcher-health-widget' in content, 'Dispatcher Health widget missing'
        assert 'updateDispatcherHealth(' in content, 'updateDispatcherHealth function missing'

    def test_trunk_health_widget_exists(self):
        """T34.2.2: Trunk Health widget exists"""
        path = 'web/dashboard.php'
        with open(path) as f:
            content = f.read()
        assert 'trunk-health-widget' in content, 'Trunk Health widget missing'
        assert 'updateTrunkHealth(' in content, 'updateTrunkHealth function missing'

    def test_alerts_widget_exists(self):
        """T34.2.5: Prometheus Alerts widget exists"""
        path = 'web/dashboard.php'
        with open(path) as f:
            content = f.read()
        assert 'alerts-widget' in content, 'Alerts widget missing'
        assert 'loadAlerts(' in content, 'loadAlerts function missing'

    def test_role_based_visibility(self):
        """T34.3.1: Widgets hidden for readonly users"""
        path = 'web/dashboard.php'
        with open(path) as f:
            content = f.read()
        assert '$isAdmin || $isDevOps' in content or "userRole === 'admin'" in content, 'Role-based visibility check missing'

    def test_sse_data_binding(self):
        """T34.2.1: Metric cards have SSE data binding"""
        path = 'web/dashboard.php'
        with open(path) as f:
            content = f.read()
        assert 'data-sse-field' in content, 'SSE data binding missing'
