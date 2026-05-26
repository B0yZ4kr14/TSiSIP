#!/usr/bin/env python3
# @req FR-003
"""
Integration tests for TSiSIP Observability Platform
Validates: Prometheus scrape, Grafana dashboards, Alertmanager alerts
"""

import os
import time
import json
import pytest
import requests

# Service endpoints (internal Docker network)
PROMETHEUS_URL = os.environ.get('PROMETHEUS_URL', 'http://prometheus:9090')
GRAFANA_URL = os.environ.get('GRAFANA_URL', 'http://grafana:3000')
ALERTMANAGER_URL = os.environ.get('ALERTMANAGER_URL', 'http://alertmanager:9093')
EXPORTER_URL = os.environ.get('EXPORTER_URL', 'http://opensips-exporter:9442')

# Grafana auth
GRAFANA_USER = os.environ.get('GRAFANA_USER', 'admin')
GRAFANA_PASS = os.environ.get('GRAFANA_PASS', '')


def _grafana_auth():
    """Return auth tuple if password is configured."""
    if GRAFANA_PASS:
        return (GRAFANA_USER, GRAFANA_PASS)
    # Try to read from secrets file if available (host-side test execution)
    secret_path = os.environ.get('GRAFANA_PASS_FILE', 'secrets/grafana_admin_password')
    if os.path.isfile(secret_path):
        with open(secret_path, 'r') as f:
            return (GRAFANA_USER, f.read().strip())
    return None

# Retry configuration
MAX_RETRIES = 30
RETRY_DELAY = 2


def _services_reachable():
    """Quick probe to see if any observability service is reachable."""
    import socket
    from urllib.parse import urlparse
    for url in (PROMETHEUS_URL, GRAFANA_URL, ALERTMANAGER_URL):
        parsed = urlparse(url)
        hostname = parsed.hostname or ""
        # Fast-path: if the hostname is a bare Docker service name on the host
        # (no dots, not numeric), DNS resolution will be slow when it fails.
        # We try a quick numeric check to avoid waiting for DNS timeout.
        if "." not in hostname and not hostname.startswith("http"):
            try:
                socket.getaddrinfo(hostname, None, socket.AF_INET, socket.SOCK_STREAM, 0, socket.AI_NUMERICHOST)
            except socket.gaierror:
                # Not numeric — may or may not resolve. Try HTTP anyway,
                # but with a very short timeout so DNS failures don't hang.
                try:
                    resp = requests.get(f"{url}/-/healthy", timeout=3)
                    if resp.status_code == 200:
                        return True
                except Exception:
                    pass
                continue
        try:
            resp = requests.get(f"{url}/-/healthy", timeout=2)
            if resp.status_code == 200:
                return True
        except Exception:
            pass
    return False


# Skip entire module if observability services are not on the same network
if not _services_reachable():
    pytest.skip(
        "Observability services not reachable from this network. "
        "Set PROMETHEUS_URL/GRAFANA_URL/ALERTMANAGER_URL/EXPORTER_URL env vars to override.",
        allow_module_level=True,
    )


def wait_for_service(url: str, path: str = '/-/healthy', timeout: int = 5) -> bool:
    """Wait for a service to become healthy."""
    for _ in range(MAX_RETRIES):
        try:
            resp = requests.get(f"{url}{path}", timeout=timeout)
            if resp.status_code == 200:
                return True
        except requests.RequestException:
            pass
        time.sleep(RETRY_DELAY)
    return False


class TestPrometheus:
    """Test Prometheus server and scraping."""

    def test_prometheus_health(self):
        """Prometheus health endpoint responds."""
        assert wait_for_service(PROMETHEUS_URL), "Prometheus did not become healthy"

    def test_prometheus_config_valid(self):
        """Prometheus configuration is valid."""
        resp = requests.get(f"{PROMETHEUS_URL}/api/v1/status/config")
        assert resp.status_code == 200
        data = resp.json()
        assert data['status'] == 'success'

    def test_scrape_targets(self):
        """All scrape targets are up."""
        resp = requests.get(f"{PROMETHEUS_URL}/api/v1/targets")
        assert resp.status_code == 200
        data = resp.json()
        assert data['status'] == 'success'
        
        active_targets = data['data']['activeTargets']
        jobs = {t['labels']['job'] for t in active_targets}
        
        expected_jobs = {'opensips', 'postgres', 'node', 'prometheus'}
        assert expected_jobs.issubset(jobs), f"Missing jobs: {expected_jobs - jobs}"

    def test_opensips_metrics_present(self):
        """OpenSIPS metrics are available in Prometheus."""
        metrics = [
            'opensips_active_dialogs_total',
            'opensips_registered_subscribers',
            'opensips_dispatcher_target_state',
            'opensips_auth_failures_total',
            'opensips_sip_requests_total',
        ]
        for metric in metrics:
            resp = requests.get(
                f"{PROMETHEUS_URL}/api/v1/query",
                params={'query': metric}
            )
            assert resp.status_code == 200
            data = resp.json()
            assert data['status'] == 'success', f"Metric {metric} not found"


class TestGrafana:
    """Test Grafana server and dashboards."""

    def test_grafana_health(self):
        """Grafana health endpoint responds."""
        assert wait_for_service(GRAFANA_URL, '/api/health'), "Grafana did not become healthy"

    def test_datasource_configured(self):
        """Prometheus datasource is pre-configured."""
        auth = _grafana_auth()
        resp = requests.get(f"{GRAFANA_URL}/api/datasources", auth=auth)
        assert resp.status_code == 200
        datasources = resp.json()
        names = [ds['name'] for ds in datasources]
        assert 'Prometheus' in names

    def test_dashboards_imported(self):
        """All TSiSIP dashboards are available."""
        expected_dashboards = [
            'TSiSIP - Dispatcher Health',
            'TSiSIP - Capacity Planning',
            'TSiSIP - Deployment Validation',
            'TSiSIP - Anomaly Detection',
            'TSiSIP - Health Status',
            'TSiSIP Rate Limiting & DDoS Protection',
            'TSiSIP \u2014 SIP Trunk Providers',
        ]
        auth = _grafana_auth()
        resp = requests.get(f"{GRAFANA_URL}/api/search", auth=auth)
        assert resp.status_code == 200
        dashboards = resp.json()
        titles = [d['title'] for d in dashboards]
        
        for expected in expected_dashboards:
            assert expected in titles, f"Dashboard '{expected}' not found"


class TestAlertmanager:
    """Test Alertmanager configuration."""

    def test_alertmanager_health(self):
        """Alertmanager health endpoint responds."""
        assert wait_for_service(ALERTMANAGER_URL), "Alertmanager did not become healthy"

    def test_alert_rules_loaded(self):
        """Alert rules are loaded in Prometheus."""
        resp = requests.get(f"{PROMETHEUS_URL}/api/v1/rules")
        assert resp.status_code == 200
        data = resp.json()
        assert data['status'] == 'success'
        
        groups = data['data']['groups']
        group_names = {g['name'] for g in groups}
        expected_groups = {'tsisip-opensips', 'tsisip-rtpengine', 'tsisip-postgres', 'tsisip-infrastructure'}
        assert expected_groups.issubset(group_names)


class TestExporter:
    """Test OpenSIPS metric exporter."""

    def test_exporter_responds(self):
        """Exporter /metrics endpoint responds with valid Prometheus format."""
        assert wait_for_service(EXPORTER_URL, '/metrics'), "Exporter did not become healthy"

    def test_exporter_metrics_format(self):
        """Exporter returns valid Prometheus exposition format."""
        resp = requests.get(f"{EXPORTER_URL}/metrics")
        assert resp.status_code == 200
        content = resp.text
        
        # Check for required metric families
        assert 'opensips_active_dialogs_total' in content
        assert 'opensips_registered_subscribers' in content
        assert 'opensips_dispatcher_target_state' in content
        
        # Check content type
        assert 'text/plain' in resp.headers.get('Content-Type', '')


if __name__ == '__main__':
    pytest.main([__file__, '-v'])
