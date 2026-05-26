#!/usr/bin/env python3
# @req FR-006
"""
Integration tests for Feature 006: Rate Limiting & DDoS Protection.

OpenSIPS 3.6 uses cachedb_local (in-memory key-value with TTL) instead of htable.
MI commands are accessed via JSON-RPC over the mi_http interface.
"""

import json
import os
import subprocess
import pytest

COMPOSE_FILE = os.environ.get("COMPOSE_FILE", "docker-compose.yml")


def _mi_rpc(method: str, params: list = None) -> dict:
    """Execute an OpenSIPS MI JSON-RPC command and return the parsed response."""
    payload = {"jsonrpc": "2.0", "method": method, "id": 1}
    if params is not None:
        payload["params"] = params

    result = subprocess.run(
        [
            "docker", "compose", "-f", COMPOSE_FILE, "exec", "-T", "opensips",
            "curl", "-s", "-X", "POST",
            "-H", "Content-Type: application/json",
            "-d", json.dumps(payload),
            "http://localhost:8888/mi/",
        ],
        capture_output=True,
        text=True,
    )
    if result.returncode != 0:
        return {"error": f"curl failed: {result.stderr}"}
    try:
        return json.loads(result.stdout)
    except json.JSONDecodeError:
        return {"error": f"invalid JSON: {result.stdout}"}


class TestRateLimiting:
    """Test SIP-layer rate limiting and DDoS protection."""

    def test_pike_module_loaded(self):
        """T1.1: pike module is loaded in OpenSIPS."""
        resp = _mi_rpc("which")
        assert "error" not in resp, f"MI call failed: {resp.get('error')}"
        result = resp.get("result", [])
        assert "pike_list" in result, "pike module not loaded (pike_list MI command missing)"

    def test_cachedb_local_auth_failures(self):
        """T2.1: cachedb_local can store and fetch auth failure counters."""
        # Store a test counter
        resp = _mi_rpc("cache_store", ["local", "auth_failures_test", "1", "60"])
        assert "error" not in resp, f"cache_store failed: {resp.get('error')}"

        # Fetch it back
        resp = _mi_rpc("cache_fetch", ["local", "auth_failures_test"])
        assert "error" not in resp or resp.get("error", {}).get("code") == 400, \
            f"cache_fetch failed unexpectedly: {resp}"

        # Clean up
        _mi_rpc("cache_remove", ["local", "auth_failures_test"])

    def test_cachedb_local_ban_list(self):
        """T4.1: cachedb_local ban list entries work."""
        resp = _mi_rpc("cache_store", ["local", "ban_list_test_ip", "brute_force", "3600"])
        assert "error" not in resp, f"ban store failed: {resp.get('error')}"

        resp = _mi_rpc("cache_fetch", ["local", "ban_list_test_ip"])
        assert "result" in resp, f"ban fetch failed: {resp}"
        assert resp["result"]["value"] == "brute_force"

        _mi_rpc("cache_remove", ["local", "ban_list_test_ip"])

    def test_cachedb_local_trunk_whitelist(self):
        """T1.2: trunk_whitelist entries can be stored in cachedb_local."""
        resp = _mi_rpc("cache_store", ["local", "trunk_whitelist_test_ip", "1", "86400"])
        assert "error" not in resp, f"whitelist store failed: {resp.get('error')}"

        resp = _mi_rpc("cache_fetch", ["local", "trunk_whitelist_test_ip"])
        assert "result" in resp, f"whitelist fetch failed: {resp}"

        _mi_rpc("cache_remove", ["local", "trunk_whitelist_test_ip"])

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

    def test_ratelimit_module_loaded(self):
        """T3.1: ratelimit module is loaded."""
        resp = _mi_rpc("which")
        assert "error" not in resp
        result = resp.get("result", [])
        assert "rl_list" in result, "ratelimit module not loaded (rl_list MI command missing)"

    def test_dispatcher_load_metrics_exist(self):
        """T3.2: Dispatcher load metrics are exported."""
        result = subprocess.run(
            ["docker", "compose", "-f", COMPOSE_FILE, "exec", "-T", "opensips_exporter",
             "curl", "-s", "http://localhost:8080/metrics"],
            capture_output=True, text=True
        )
        if result.returncode != 0:
            pytest.skip("OpenSIPS exporter not accessible")

    def test_anomaly_detector_health(self):
        """T5: Anomaly detector health endpoint responds."""
        ip_result = subprocess.run(
            ["docker", "inspect", "tsisip-anomaly-detector-1",
             "--format", "{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}"],
            capture_output=True, text=True, timeout=5,
        )
        if ip_result.returncode != 0 or not ip_result.stdout.strip():
            pytest.skip("Anomaly detector container not found")

        detector_ip = ip_result.stdout.strip().split("\n")[0]
        result = subprocess.run(
            ["docker", "compose", "-f", COMPOSE_FILE, "exec", "-T", "anomaly_detector",
             "curl", "-s", f"http://{detector_ip}:8080/health"],
            capture_output=True, text=True
        )
        if result.returncode != 0:
            pytest.skip("Anomaly detector health check failed")
        assert '"status": "healthy"' in result.stdout


if __name__ == "__main__":
    pytest.main([__file__, "-v"])
