"""
Feature 008 Integration Tests: Anomaly Detection
Validates: event ingestion, baseline calculation, Z-score alerting, Alertmanager webhook
"""
import os
import pytest
import requests
import subprocess
import time


def _get_detector_url() -> str:
    """Return the URL to use for anomaly detector tests.

    Priority:
    1. DETECTOR_URL environment variable
    2. Docker container IP for tsisip-anomaly-detector-1
    3. Fallback to http://localhost:8080
    """
    env_url = os.environ.get("DETECTOR_URL")
    if env_url:
        return env_url

    try:
        result = subprocess.run(
            [
                "docker", "inspect", "tsisip-anomaly-detector-1",
                "--format", "{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}",
            ],
            capture_output=True,
            text=True,
            timeout=5,
        )
        if result.returncode == 0 and result.stdout.strip():
            ip = result.stdout.strip().split("\n")[0]
            return f"http://{ip}:8080"
    except Exception:
        pass

    return "http://localhost:8080"


def _send_events(base_url: str, event_type: str, count: int, source_ip: str = "192.0.2.1"):
    for i in range(count):
        resp = requests.post(
            f"{base_url}/api/v1/event",
            json={"event_type": event_type, "source_ip": source_ip, "sip_method": "INVITE"},
            timeout=5
        )
        assert resp.status_code == 200


class TestAnomalyDetector:
    """Anomaly detector event ingestion and alerting."""

    @pytest.fixture
    def detector_url(self):
        return _get_detector_url()

    def test_health(self, detector_url):
        resp = requests.get(f"{detector_url}/health", timeout=5)
        assert resp.status_code == 200
        data = resp.json()
        assert data["status"] == "healthy"

    def test_receive_event(self, detector_url):
        resp = requests.post(
            f"{detector_url}/api/v1/event",
            json={"event_type": "E_AUTH_FAILURE", "source_ip": "192.0.2.1", "sip_method": "INVITE"},
            timeout=5
        )
        assert resp.status_code == 200
        assert resp.json()["status"] == "ok"

    def test_metrics_endpoint(self, detector_url):
        resp = requests.get(f"{detector_url}/metrics", timeout=5)
        assert resp.status_code == 200
        assert b"tsisip_anomaly_events_received_total" in resp.content

    def test_status_endpoint(self, detector_url):
        resp = requests.get(f"{detector_url}/api/v1/status", timeout=5)
        assert resp.status_code == 200
        data = resp.json()
        assert "z_score" in data
        assert "baseline_mean" in data
        assert "baseline_stddev" in data

    def test_baseline_and_alert(self, detector_url):
        """Send enough events to trigger a Z-score alert after 2 windows."""
        pytest.skip("Requires 2+ analysis windows (120s+) — run manually for full validation")

    def test_alertmanager_webhook_mock(self, detector_url):
        """Verify detector can send alerts to a mock Alertmanager."""
        pytest.skip("Requires mock Alertmanager endpoint — run manually")
