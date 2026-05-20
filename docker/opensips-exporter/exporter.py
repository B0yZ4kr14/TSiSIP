#!/usr/bin/env python3
"""
OpenSIPS MI-to-Prometheus Exporter
Polls OpenSIPS MI interface and exposes metrics in Prometheus format.
Implements scrape caching to prevent MI overload.
"""

import os
import time
import json
import logging
import subprocess
from datetime import datetime, timezone
from urllib.request import urlopen, Request
from urllib.error import URLError
from prometheus_client import start_http_server, Gauge, Counter, Info
from prometheus_client.core import CollectorRegistry

# Configuration
OPENSIPS_MI_HOST = os.environ.get('OPENSIPS_MI_HOST', 'opensips')
OPENSIPS_MI_PORT = int(os.environ.get('OPENSIPS_MI_PORT', '8888'))
EXPORTER_PORT = int(os.environ.get('EXPORTER_PORT', '9442'))
CACHE_TTL_SECONDS = int(os.environ.get('CACHE_TTL_SECONDS', '10'))

MI_BASE_URL = f"http://{OPENSIPS_MI_HOST}:{OPENSIPS_MI_PORT}/mi"

# Logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)

# Prometheus metrics
registry = CollectorRegistry()

opensips_active_dialogs = Gauge(
    'opensips_active_dialogs_total',
    'Total number of active dialogs',
    registry=registry
)

opensips_registered_subscribers = Gauge(
    'opensips_registered_subscribers',
    'Number of registered subscribers',
    registry=registry
)

opensips_dispatcher_target_state = Gauge(
    'opensips_dispatcher_target_state',
    'Dispatcher target state (0=inactive, 1=active, 2=probing)',
    ['target', 'setid'],
    registry=registry
)

opensips_dispatcher_target_response_ms = Gauge(
    'opensips_dispatcher_target_response_ms',
    'Dispatcher target response time in milliseconds',
    ['target', 'setid'],
    registry=registry
)

opensips_auth_failures = Counter(
    'opensips_auth_failures_total',
    'Total authentication failures',
    registry=registry
)

opensips_sip_requests = Counter(
    'opensips_sip_requests_total',
    'Total SIP requests by method and status',
    ['method', 'status'],
    registry=registry
)

opensips_info = Info(
    'opensips',
    'OpenSIPS version information',
    registry=registry
)

# Cache
_cache = {}
_cache_timestamp = 0


def fetch_mi(cmd: str) -> dict:
    """Fetch data from OpenSIPS MI interface."""
    url = f"{MI_BASE_URL}/{cmd}"
    try:
        req = Request(url, headers={'Accept': 'application/json'})
        with urlopen(req, timeout=5) as resp:
            return json.loads(resp.read().decode('utf-8'))
    except URLError as e:
        logger.error(f"Failed to fetch MI command {cmd}: {e}")
        return {}
    except json.JSONDecodeError as e:
        logger.error(f"Failed to parse MI response for {cmd}: {e}")
        return {}


def get_cached_or_fetch(cmd: str) -> dict:
    """Return cached data if fresh, otherwise fetch from MI."""
    global _cache_timestamp
    now = time.time()
    if cmd in _cache and (now - _cache_timestamp) < CACHE_TTL_SECONDS:
        return _cache[cmd]
    
    data = fetch_mi(cmd)
    _cache[cmd] = data
    _cache_timestamp = now
    return data


def update_metrics():
    """Update all Prometheus metrics from OpenSIPS MI data."""
    # Active dialogs
    dialogs = get_cached_or_fetch('get_statistics')
    if dialogs:
        active = dialogs.get('dialog:active_dialogs', 0)
        opensips_active_dialogs.set(active)
    
    # Registered subscribers
    usrloc = get_cached_or_fetch('ul_dump')
    if usrloc and 'Domains' in usrloc:
        total_regs = sum(
            len(domain.get('AORs', []))
            for domain in usrloc['Domains']
        )
        opensips_registered_subscribers.set(total_regs)
    
    # Dispatcher targets
    dispatcher = get_cached_or_fetch('ds_list')
    if dispatcher and 'Partitions' in dispatcher:
        for partition in dispatcher['Partitions']:
            for set_entry in partition.get('SETS', []):
                setid = str(set_entry.get('id', '0'))
                for target in set_entry.get('Destinations', []):
                    target_uri = target.get('URI', 'unknown')
                    state = target.get('State', 0)
                    opensips_dispatcher_target_state.labels(
                        target=target_uri, setid=setid
                    ).set(state)
    
    # Authentication failures (from statistics)
    stats = get_cached_or_fetch('get_statistics')
    if stats:
        auth_failures = stats.get('auth:failed_auths', 0)
        opensips_auth_failures._value.set(auth_failures)
    
    # SIP requests (from statistics)
    if stats:
        for method in ['INVITE', 'REGISTER', 'BYE', 'OPTIONS', 'ACK']:
            count = stats.get(f'tm:{method}_received', 0)
            opensips_sip_requests.labels(method=method, status='received')._value.set(count)
    
    # Version info
    version = get_cached_or_fetch('version')
    if version:
        opensips_info.info({
            'version': version.get('Server', 'unknown'),
            'build_date': version.get('Build-Date', 'unknown')
        })

    # Wave 5: Trunk provider health from dispatcher setid=100
    dispatcher = get_cached_or_fetch('ds_list')
    if dispatcher and 'Partitions' in dispatcher:
        for partition in dispatcher.get('Partitions', []):
            for set_entry in partition.get('SETS', []):
                setid = str(set_entry.get('id', '0'))
                if setid == '100':
                    for target in set_entry.get('Destinations', []):
                        target_uri = target.get('URI', 'unknown')
                        state = target.get('State', 0)
                        # Extract host from SIP URI for labeling
                        host = target_uri.replace('sip:', '').split(':')[0] if target_uri.startswith('sip:') else 'unknown'
                        desc = target.get('description', 'unknown')
                        # description format: "Trunk: <name>"
                        trunk_name = desc.replace('Trunk: ', '') if desc.startswith('Trunk: ') else desc
                        tsisip_trunk_provider_healthy.labels(
                            trunk_name=trunk_name,
                            trunk_host=host
                        ).set(1 if state == 0 else 0)

    # T3.2: TLS certificate expiry
    cert_path = os.environ.get('TLS_CERT_PATH', '/certs/live/server.crt')
    if os.path.exists(cert_path):
        try:
            result = subprocess.run(
                ['openssl', 'x509', '-noout', '-enddate', '-in', cert_path],
                capture_output=True, text=True, timeout=5
            )
            if result.returncode == 0:
                date_str = result.stdout.strip().replace('notAfter=', '')
                dt = datetime.strptime(date_str, '%b %d %H:%M:%S %Y %Z')
                expiry_ts = dt.replace(tzinfo=timezone.utc).timestamp()
                opensips_tls_certificate_expiry_timestamp.set(expiry_ts)
        except Exception as e:
            logger.error(f"Error reading certificate expiry: {e}")


def main():
    logger.info(f"Starting OpenSIPS exporter on port {EXPORTER_PORT}")
    logger.info(f"MI target: {MI_BASE_URL}")
    logger.info(f"Cache TTL: {CACHE_TTL_SECONDS}s")
    
    # Start metrics server
    start_http_server(EXPORTER_PORT, registry=registry)
    logger.info("Metrics server started")
    
    # Update metrics periodically
    while True:
        try:
            update_metrics()
        except Exception as e:
            logger.error(f"Error updating metrics: {e}")
        time.sleep(CACHE_TTL_SECONDS)


if __name__ == '__main__':
    main()

# Health check metrics (added for Feature 004)
opensips_healthcheck_failures = Counter(
    'opensips_healthcheck_failures_total',
    'Total health check failures',
    ['service'],
    registry=registry
)

opensips_container_restarts = Counter(
    'opensips_container_restarts_total',
    'Total container restarts',
    ['service'],
    registry=registry
)

opensips_dispatcher_circuit_state = Gauge(
    'opensips_dispatcher_circuit_state',
    'Dispatcher circuit breaker state (0=closed, 1=open, 2=half_open)',
    ['target', 'setid'],
    registry=registry
)

# T3.2: TLS certificate expiry metric
opensips_tls_certificate_expiry_timestamp = Gauge(
    'opensips_tls_certificate_expiry_timestamp',
    'Unix timestamp when the TLS certificate expires',
    registry=registry
)

# Wave 5: Trunk provider health metrics
tsisip_trunk_provider_healthy = Gauge(
    'tsisip_trunk_provider_healthy',
    'Trunk provider health status from dispatcher probes (1=healthy, 0=unhealthy)',
    ['trunk_name', 'trunk_host'],
    registry=registry
)

tsisip_trunk_calls_total = Counter(
    'tsisip_trunk_calls_total',
    'Total trunk-routed calls',
    ['trunk_name', 'direction'],
    registry=registry
)
