#!/usr/bin/env python3
"""
Certbot Prometheus exporter for TSiSIP.

Exposes:
  - certbot_days_until_expiry{domain,source}
  - certbot_renewal_failure_total{source}
  - certbot_last_success_timestamp{source}
"""

import datetime
import os
import subprocess
import sys
import time

from prometheus_client import start_http_server, Gauge, Counter

# Configuration
CERT_PATH = os.environ.get("CERT_PATH", "/certs/live/server.crt")
STATE_DIR = os.environ.get("STATE_DIR", "/var/lib/certbot-exporter")
EXPORTER_PORT = int(os.environ.get("EXPORTER_PORT", "9101"))
SCRAPE_INTERVAL_SECONDS = int(os.environ.get("SCRAPE_INTERVAL_SECONDS", "60"))
CERT_SOURCE = os.environ.get("CERT_SOURCE", "certbot")
TLS_DOMAIN = os.environ.get("TLS_DOMAIN", "unknown")

FAILURES_FILE = os.path.join(STATE_DIR, "renewal_failures")
LAST_SUCCESS_FILE = os.path.join(STATE_DIR, "last_success")

# Prometheus metrics
days_until_expiry = Gauge(
    "certbot_days_until_expiry",
    "Number of days until the TLS certificate expires",
    ["domain", "source"],
)

renewal_failure_total = Counter(
    "certbot_renewal_failure_total",
    "Total number of certificate renewal failures",
    ["source"],
)

last_success_timestamp = Gauge(
    "certbot_last_success_timestamp",
    "Unix timestamp of the last successful certificate renewal",
    ["source"],
)


def _read_int_file(path: str, default: int = 0) -> int:
    try:
        with open(path, "r") as f:
            return int(f.read().strip())
    except (FileNotFoundError, ValueError):
        return default


def _parse_openssl_date(date_str: str) -> datetime.datetime:
    """Parse date from openssl output, e.g. 'Jan 15 00:00:00 2025 GMT'."""
    try:
        return datetime.datetime.strptime(date_str, "%b %d %H:%M:%S %Y %Z")
    except ValueError:
        # openssl may output single-digit day without leading zero padding
        return datetime.datetime.strptime(date_str, "%b  %d %H:%M:%S %Y %Z")


def get_cert_expiry(path: str) -> datetime.datetime:
    result = subprocess.run(
        ["openssl", "x509", "-noout", "-enddate", "-in", path],
        capture_output=True,
        text=True,
        check=True,
    )
    # notAfter=Jan 15 00:00:00 2025 GMT
    date_str = result.stdout.strip().split("=", 1)[1]
    return _parse_openssl_date(date_str)


def get_cert_domain(path: str) -> str:
    """Extract CN from certificate subject, fallback to TLS_DOMAIN env."""
    try:
        result = subprocess.run(
            ["openssl", "x509", "-noout", "-subject", "-in", path],
            capture_output=True,
            text=True,
            check=True,
        )
        # subject=CN = example.com
        for part in result.stdout.strip().replace("subject=", "").split(","):
            kv = part.strip().split(" = ", 1)
            if len(kv) == 2 and kv[0].strip().upper() == "CN":
                return kv[1].strip()
            kv = part.strip().split("=", 1)
            if len(kv) == 2 and kv[0].strip().upper() == "CN":
                return kv[1].strip()
    except subprocess.CalledProcessError:
        pass
    return TLS_DOMAIN


def update_metrics():
    global _last_failures
    try:
        expiry = get_cert_expiry(CERT_PATH)
        now = datetime.datetime.now(datetime.timezone.utc)
        if expiry.tzinfo is None:
            expiry = expiry.replace(tzinfo=datetime.timezone.utc)
        delta = expiry - now
        days = max(0.0, delta.total_seconds() / 86400.0)
        domain = get_cert_domain(CERT_PATH)
        days_until_expiry.labels(domain=domain, source=CERT_SOURCE).set(days)
    except subprocess.CalledProcessError as e:
        print(f"ERROR: Failed to read certificate expiry: {e}", file=sys.stderr)
    except Exception as e:
        print(f"ERROR: Unexpected error reading certificate: {e}", file=sys.stderr)

    failures = _read_int_file(FAILURES_FILE, default=0)
    # Track delta for true Counter semantics
    delta = failures - _last_failures
    if delta > 0:
        renewal_failure_total.labels(source=CERT_SOURCE).inc(delta)
        _last_failures = failures

    last_success = _read_int_file(LAST_SUCCESS_FILE, default=0)
    if last_success > 0:
        last_success_timestamp.labels(source=CERT_SOURCE).set(last_success)
    else:
        last_success_timestamp.labels(source=CERT_SOURCE).set(0)


_last_failures = 0


def main():
    global _last_failures
    os.makedirs(STATE_DIR, exist_ok=True)
    _last_failures = _read_int_file(FAILURES_FILE, default=0)
    start_http_server(EXPORTER_PORT)
    print(f"certbot-exporter listening on :{EXPORTER_PORT}")

    while True:
        update_metrics()
        time.sleep(SCRAPE_INTERVAL_SECONDS)


if __name__ == "__main__":
    main()
