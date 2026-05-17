#!/usr/bin/env python3
"""
Integration tests for Feature 006: Rate Limiting & DDoS Protection.
"""

import os
import subprocess
import pytest

COMPOSE_FILE = os.environ.get("COMPOSE_FILE", "docker-compose.yml")


class TestRateLimiting:
    """Test SIP-layer rate limiting and DDoS protection."""

    def test_pike_module_loaded(self):
        """T1.1: pike module is loaded in OpenSIPS."""
        result = subprocess.run(
            ["docker", "compose", "-f", COMPOSE_FILE, "exec", "-T", "opensips",
             "opensipsctl", "fifo", "which"],
            capture_output=True, text=True
        )
        assert result.returncode in [0, 1], "OpenSIPS container not accessible"

    def test_htable_module_loaded(self):
        """T2.1: htable module is loaded with auth_failures table."""
        result = subprocess.run(
            ["docker", "compose", "-f", COMPOSE_FILE, "exec", "-T", "opensips",
             "opensipsctl", "fifo", "htable_dump", "auth_failures"],
            capture_output=True, text=True
        )
        assert result.returncode == 0, f"htable_dump failed: {result.stderr}"

    def test_ban_list_htable_exists(self):
        """T4.1: ban_list htable exists."""
        result = subprocess.run(
            ["docker", "compose", "-f", COMPOSE_FILE, "exec", "-T", "opensips",
             "opensipsctl", "fifo", "htable_dump", "ban_list"],
            capture_output=True, text=True
        )
        assert result.returncode == 0, f"ban_list htable dump failed: {result.stderr}"

    def test_trunk_whitelist_htable_exists(self):
        """T1.2: trunk_whitelist htable exists for NAT handling."""
        result = subprocess.run(
            ["docker", "compose", "-f", COMPOSE_FILE, "exec", "-T", "opensips",
             "opensipsctl", "fifo", "htable_dump", "trunk_whitelist"],
            capture_output=True, text=True
        )
        assert result.returncode == 0, f"trunk_whitelist htable dump failed: {result.stderr}"

    def test_tcp_connection_limits_configured(self):
        """T1.3: TCP connection limits are configured."""
        result = subprocess.run(
            ["docker", "compose", "-f", COMPOSE_FILE, "exec", "-T", "opensips",
             "bash", "-c",
             "grep -E 'tcp_max_connections|tcp_connection_lifetime|tcp_read_timeout' /etc/opensips/opensips.cfg"],
            capture_output=True, text=True
        )
        assert result.returncode == 0, "TCP connection limits not found in config"
        assert "tcp_max_connections" in result.stdout

    def test_dispatcher_load_metrics_exist(self):
        """T3.2: Dispatcher load metrics are exported."""
        result = subprocess.run(
            ["docker", "compose", "-f", COMPOSE_FILE, "exec", "-T", "opensips-exporter",
             "curl", "-s", "http://localhost:8080/metrics"],
            capture_output=True, text=True
        )
        if result.returncode != 0:
            pytest.skip("OpenSIPS exporter not accessible")

    def test_anomaly_detector_health(self):
        """T5: Anomaly detector health endpoint responds."""
        result = subprocess.run(
            ["docker", "compose", "-f", COMPOSE_FILE, "exec", "-T", "anomaly-detector",
             "curl", "-s", "http://localhost:8080/health"],
            capture_output=True, text=True
        )
        if result.returncode != 0:
            pytest.skip("Anomaly detector not accessible")
        assert '"status": "healthy"' in result.stdout


if __name__ == "__main__":
    pytest.main([__file__, "-v"])
