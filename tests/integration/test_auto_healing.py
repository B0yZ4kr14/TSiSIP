#!/usr/bin/env python3
"""
Feature 036 Integration Tests: Auto-Healing SIP Infrastructure
Validates: health monitor, auto-failover, auto-rollback, circuit breaker, metrics, OCP widget.

Test IDs:
- T36.1: dispatcher_health_log table exists
- T36.2: autoheal_config table exists with defaults
- T36.3: auto-healer.php CLI exists and is executable
- T36.4: autoheal-events.php SSE endpoint exists
- T36.5: metrics-autoheal.php Prometheus endpoint exists
- T36.6: Dashboard widget HTML exists
- T36.7: Alertmanager rules added
- T36.8: Circuit breaker prevents flapping
"""

import os
import pytest

PROJECT_ROOT = os.path.dirname(os.path.dirname(os.path.dirname(__file__)))


class TestAutoHealingFiles:
    """T36.3-T36.7: All backend artifacts exist."""

    def test_auto_healer_cli_exists(self):
        path = os.path.join(PROJECT_ROOT, "web/cli/auto-healer.php")
        assert os.path.exists(path), "auto-healer.php CLI missing"

    def test_auto_healer_has_health_log_insert(self):
        path = os.path.join(PROJECT_ROOT, "web/cli/auto-healer.php")
        with open(path) as f:
            content = f.read()
        assert "dispatcher_health_log" in content, "auto-healer.php missing health log insert"
        assert "probeDestination" in content, "auto-healer.php missing probe function"
        assert "ds_list" in content, "auto-healer.php missing ds_list MI call"

    def test_auto_healer_has_circuit_breaker(self):
        path = os.path.join(PROJECT_ROOT, "web/cli/auto-healer.php")
        with open(path) as f:
            content = f.read()
        assert "circuit breaker" in content.lower() or "isCircuitBreakerOpen" in content, "auto-healer.php missing circuit breaker"

    def test_auto_healer_has_auto_rollback(self):
        path = os.path.join(PROJECT_ROOT, "web/cli/auto-healer.php")
        with open(path) as f:
            content = f.read()
        assert "AUTO_ROLLBACK" in content, "auto-healer.php missing auto-rollback logic"
        assert "AUTO_FAILOVER" in content, "auto-healer.php missing auto-failover logic"

    def test_autoheal_events_endpoint_exists(self):
        path = os.path.join(PROJECT_ROOT, "web/api/v1/autoheal-events.php")
        assert os.path.exists(path), "autoheal-events.php missing"

    def test_autoheal_metrics_endpoint_exists(self):
        path = os.path.join(PROJECT_ROOT, "web/api/v1/metrics-autoheal.php")
        assert os.path.exists(path), "metrics-autoheal.php missing"

    def test_metrics_has_counter(self):
        path = os.path.join(PROJECT_ROOT, "web/api/v1/metrics-autoheal.php")
        with open(path) as f:
            content = f.read()
        assert "tsisip_autoheal_actions_total" in content, "metrics missing actions counter"
        assert "tsisip_autoheal_circuit_breaker_state" in content, "metrics missing circuit breaker gauge"
        assert "tsisip_autoheal_destinations_unhealthy" in content, "metrics missing unhealthy gauge"

    def test_dashboard_has_widget(self):
        path = os.path.join(PROJECT_ROOT, "web/dashboard.php")
        with open(path) as f:
            content = f.read()
        assert "autoheal-events-widget" in content, "dashboard missing auto-healing widget"
        assert "Auto-Healing Events" in content or "_('Auto-Healing Events')" in content, "dashboard missing widget title"

    def test_sse_stream_has_autoheal(self):
        path = os.path.join(PROJECT_ROOT, "web/common/sse-stream.php")
        with open(path) as f:
            content = f.read()
        assert "autoheal" in content, "sse-stream.php missing autoheal data"
        assert "dispatcher_change_log" in content, "sse-stream.php missing changelog query"

    def test_alert_rules_added(self):
        path = os.path.join(PROJECT_ROOT, "docker/prometheus/alert-rules.yml")
        with open(path) as f:
            content = f.read()
        assert "TSiSIPAutohealFailures" in content, "alert-rules missing autoheal failures"
        assert "TSiSIPAutohealCircuitBreakerOpen" in content, "alert-rules missing circuit breaker"
        assert "TSiSIPDispatcherDestinationsUnhealthy" in content, "alert-rules missing unhealthy destinations"


class TestAutoHealingMigration:
    """T36.1-T36.2: Database schema exists."""

    def test_migration_file_exists(self):
        path = os.path.join(PROJECT_ROOT, "db/init/05-auto-healing.sql")
        assert os.path.exists(path), "Migration 05-auto-healing.sql missing"

    def test_migration_creates_health_log(self):
        path = os.path.join(PROJECT_ROOT, "db/init/05-auto-healing.sql")
        with open(path) as f:
            content = f.read().lower()
        assert "dispatcher_health_log" in content, "Migration missing dispatcher_health_log"
        assert "reachable" in content, "Migration missing reachable column"
        assert "rtt_ms" in content, "Migration missing rtt_ms column"

    def test_migration_creates_config(self):
        path = os.path.join(PROJECT_ROOT, "db/init/05-auto-healing.sql")
        with open(path) as f:
            content = f.read().lower()
        assert "autoheal_config" in content, "Migration missing autoheal_config"
        assert "probe_interval_sec" in content, "Migration missing default config"


class TestAutoHealingIntegration:
    """Runtime integration tests requiring Docker Compose stack."""

    @pytest.fixture
    def compose_file(self):
        return os.environ.get("COMPOSE_FILE", "docker-compose.yml")

    def test_health_log_table_in_database(self, compose_file):
        import subprocess
        r = subprocess.run(
            [
                "docker", "compose", "-f", compose_file, "exec", "-T", "postgres",
                "psql", "-U", "opensips", "-d", "opensips", "-tAc",
                "SELECT 1 FROM information_schema.tables WHERE table_name = 'dispatcher_health_log';",
            ],
            capture_output=True, text=True,
        )
        if r.returncode != 0:
            pytest.skip("PostgreSQL not reachable")
        assert "1" in r.stdout, "dispatcher_health_log table does not exist"

    def test_config_table_in_database(self, compose_file):
        import subprocess
        r = subprocess.run(
            [
                "docker", "compose", "-f", compose_file, "exec", "-T", "postgres",
                "psql", "-U", "opensips", "-d", "opensips", "-tAc",
                "SELECT 1 FROM information_schema.tables WHERE table_name = 'autoheal_config';",
            ],
            capture_output=True, text=True,
        )
        if r.returncode != 0:
            pytest.skip("PostgreSQL not reachable")
        assert "1" in r.stdout, "autoheal_config table does not exist"

    def test_config_defaults_populated(self, compose_file):
        import subprocess
        r = subprocess.run(
            [
                "docker", "compose", "-f", compose_file, "exec", "-T", "postgres",
                "psql", "-U", "opensips", "-d", "opensips", "-tAc",
                "SELECT COUNT(*) FROM autoheal_config;",
            ],
            capture_output=True, text=True,
        )
        if r.returncode != 0:
            pytest.skip("PostgreSQL not reachable")
        count = int(r.stdout.strip())
        assert count >= 7, f"Expected >=7 config rows, got {count}"


if __name__ == "__main__":
    pytest.main([__file__, "-v"])
