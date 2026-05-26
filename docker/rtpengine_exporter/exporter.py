#!/usr/bin/env python3
"""
TSiSIP RTPengine Prometheus Exporter
Communicates directly via UDP NG protocol (no rtpengine-ctl dependency).
"""

import json
import os
import socket
import sys
import time
from http.server import BaseHTTPRequestHandler, HTTPServer

# Configuration
RTPENGINE_IP = os.environ.get("RTPENGINE_IP", "127.0.0.1")
RTPENGINE_PORT = int(os.environ.get("RTPENGINE_PORT", "22222"))
EXPORTER_PORT = int(os.environ.get("EXPORTER_PORT", "8080"))
CACHE_TTL = int(os.environ.get("CACHE_TTL", "5"))

# Metric name prefix
PREFIX = "rtpengine"

# Cache
_cached_metrics = ""
_last_fetch = 0.0


def ng_request(cmd, params=None):
    """Send an NG protocol command to rtpengine via UDP and return parsed JSON."""
    cookie = f"exporter-{int(time.time() * 1000)}"
    payload = {"command": cmd}
    if params:
        payload.update(params)
    # NG wire format: "d <cookie>: <json>"
    msg = f"d {cookie}: {json.dumps(payload)}".encode("utf-8")

    sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
    sock.settimeout(5)
    try:
        sock.sendto(msg, (RTPENGINE_IP, RTPENGINE_PORT))
        data, _ = sock.recvfrom(65535)
        text = data.decode("utf-8", errors="replace")
        # Parse response: "d <cookie>: <json>"
        if text.startswith("d "):
            colon_idx = text.find(": ")
            if colon_idx != -1:
                json_text = text[colon_idx + 2 :]
                return json.loads(json_text)
        return None
    except Exception:
        return None
    finally:
        sock.close()


def fetch_stats():
    """Fetch stats via NG protocol."""
    resp = ng_request("statistics", {"interval": "cumulative"})
    if resp is None:
        return None
    # The response may be wrapped in a result dict
    if isinstance(resp, dict):
        if "result" in resp:
            return resp["result"]
        return resp
    return None


def to_snake(name):
    """Convert camelCase to snake_case for Prometheus metric names."""
    out = []
    for i, ch in enumerate(name):
        if ch.isupper() and i > 0:
            out.append("_")
        out.append(ch.lower())
    return "".join(out).replace(" ", "_").replace("-", "_")


def flatten_stats(data, prefix=""):
    """Recursively flatten JSON stats into (name, value) pairs."""
    metrics = []
    if isinstance(data, dict):
        for key, val in data.items():
            new_prefix = f"{prefix}_{to_snake(key)}" if prefix else to_snake(key)
            metrics.extend(flatten_stats(val, new_prefix))
    elif isinstance(data, list):
        pass
    elif isinstance(data, (int, float)):
        metrics.append((prefix, data))
    return metrics


def generate_metrics():
    """Generate Prometheus exposition format."""
    data = fetch_stats()
    lines = [
        f"# HELP {PREFIX}_up Whether rtpengine stats could be fetched (1=ok, 0=fail)",
        f"# TYPE {PREFIX}_up gauge",
    ]
    if data is None:
        lines.append(f"{PREFIX}_up 0")
        return "\n".join(lines) + "\n"

    lines.append(f"{PREFIX}_up 1")
    lines.append("")

    for name, value in flatten_stats(data):
        metric_name = f"{PREFIX}_{name}"
        lines.append(f"# HELP {metric_name} RTPengine statistic {name}")
        lines.append(f"# TYPE {metric_name} gauge")
        lines.append(f"{metric_name} {value}")
        lines.append("")

    return "\n".join(lines)


class Handler(BaseHTTPRequestHandler):
    def do_GET(self):
        global _cached_metrics, _last_fetch
        if self.path == "/metrics":
            now = time.time()
            if now - _last_fetch > CACHE_TTL:
                _cached_metrics = generate_metrics()
                _last_fetch = now
            body = _cached_metrics.encode("utf-8")
            self.send_response(200)
            self.send_header("Content-Type", "text/plain; charset=utf-8")
            self.send_header("Content-Length", str(len(body)))
            self.end_headers()
            self.wfile.write(body)
        elif self.path == "/":
            self.send_response(200)
            self.send_header("Content-Type", "text/plain")
            self.end_headers()
            self.wfile.write(b"RTPengine Prometheus Exporter\n")
        else:
            self.send_response(404)
            self.end_headers()

    def log_message(self, fmt, *args):
        pass


def main():
    server = HTTPServer(("0.0.0.0", EXPORTER_PORT), Handler)
    print(f"RTPengine exporter listening on :{EXPORTER_PORT}", file=sys.stderr)
    server.serve_forever()


if __name__ == "__main__":
    main()
