#!/usr/bin/env python3
"""
TSiSIP Anomaly Detection Sidecar
Consumes OpenSIPS events and detects distributed attacks.
"""

import json
import logging
import os
import threading
import time
from collections import defaultdict
from datetime import datetime, timedelta

import requests
from flask import Flask, jsonify, request
from prometheus_client import Counter, Gauge, Histogram, generate_latest

from baseline import TrafficBaseline

logging.basicConfig(level=logging.INFO, format="%(asctime)s %(levelname)s %(message)s")
logger = logging.getLogger(__name__)

# Configuration
WINDOW_SECONDS = int(os.environ.get("ANOMALY_WINDOW_SECONDS", "60"))
Z_THRESHOLD = float(os.environ.get("ANOMALY_Z_THRESHOLD", "3.0"))
BASELINE_WINDOW_HOURS = int(os.environ.get("BASELINE_WINDOW_HOURS", "24"))
ALERT_COOLDOWN_SECONDS = int(os.environ.get("ALERT_COOLDOWN_SECONDS", "300"))
ALERTMANAGER_URL = os.environ.get("ALERTMANAGER_URL", "http://alertmanager:9093/api/v1/alerts")
ENABLE_ALERTMANAGER = os.environ.get("ENABLE_ALERTMANAGER", "true").lower() == "true"

# Prometheus metrics
ANOMALY_ALERTS = Counter(
    "tsisip_anomaly_alerts_total",
    "Total anomaly alerts triggered",
    ["alert_type", "severity"]
)
CURRENT_RPS = Gauge("tsisip_current_rps", "Current requests per second")
BASELINE_MEAN = Gauge("tsisip_baseline_mean_rps", "Baseline mean RPS")
BASELINE_STDDEV = Gauge("tsisip_baseline_stddev_rps", "Baseline stddev RPS")
Z_SCORE = Gauge("tsisip_anomaly_z_score", "Current Z-score")
BANNED_IPS = Gauge("tsisip_anomaly_banned_ips", "IPs banned by anomaly detector")
EVENTS_RECEIVED = Counter(
    "tsisip_anomaly_events_received_total",
    "Total events received from OpenSIPS",
    ["event_type"]
)

app = Flask(__name__)


class AnomalyDetector:
    """Detects traffic anomalies using statistical baseline."""

    def __init__(self):
        self.baseline = TrafficBaseline(window_hours=BASELINE_WINDOW_HOURS)
        self.event_buffer = defaultdict(int)
        self.ip_tracker = defaultdict(int)
        self.banned_ips = set()
        self.last_alert_time = None
        self.consecutive_alerts = 0
        self.lock = threading.Lock()
        self.running = True

    def record_event(self, event_type: str, source_ip: str, sip_method: str = None):
        """Record an OpenSIPS event."""
        with self.lock:
            self.event_buffer[event_type] += 1
            self.ip_tracker[source_ip] += 1
        EVENTS_RECEIVED.labels(event_type=event_type).inc()

    def analyze_window(self) -> dict:
        """Analyze current window for anomalies."""
        with self.lock:
            total_events = sum(self.event_buffer.values())
            current_rps = total_events / WINDOW_SECONDS
            unique_ips = len(self.ip_tracker)

            # Update baseline
            self.baseline.add_sample(current_rps)
            mean, stddev = self.baseline.get_stats()

            result = {
                "timestamp": datetime.utcnow().isoformat(),
                "current_rps": current_rps,
                "unique_ips": unique_ips,
                "baseline_mean": mean,
                "baseline_stddev": stddev,
                "z_score": 0.0,
                "alert": False,
                "alert_type": None,
                "severity": None,
            }

            if stddev > 0:
                z_score = (current_rps - mean) / stddev
                result["z_score"] = z_score
                Z_SCORE.set(z_score)

                # Check threshold with consecutive window requirement
                if z_score > Z_THRESHOLD:
                    self.consecutive_alerts += 1
                    if self.consecutive_alerts >= 2:
                        if (self.last_alert_time is None or
                            (datetime.utcnow() - self.last_alert_time).seconds > ALERT_COOLDOWN_SECONDS):
                            result["alert"] = True
                            result["alert_type"] = "distributed_flood"
                            result["severity"] = "critical" if z_score > Z_THRESHOLD * 2 else "high"
                            self.last_alert_time = datetime.utcnow()
                            self._trigger_alert(result)
                else:
                    self.consecutive_alerts = 0

            # Update metrics
            CURRENT_RPS.set(current_rps)
            BASELINE_MEAN.set(mean)
            BASELINE_STDDEV.set(stddev)
            BANNED_IPS.set(len(self.banned_ips))

            # Clear window
            self.event_buffer.clear()
            self.ip_tracker.clear()

            return result

    def _trigger_alert(self, result: dict):
        """Trigger alert via Prometheus and optional Alertmanager."""
        ANOMALY_ALERTS.labels(
            alert_type=result["alert_type"],
            severity=result["severity"]
        ).inc()
        logger.warning(
            "ANOMALY ALERT: type=%s severity=%s z_score=%.2f rps=%.2f",
            result["alert_type"], result["severity"],
            result["z_score"], result["current_rps"]
        )
        if ENABLE_ALERTMANAGER:
            self._send_alertmanager(result)

    def _send_alertmanager(self, result: dict):
        """Send alert to Alertmanager webhook."""
        try:
            alert = {
                "labels": {
                    "alertname": "TSiSIPAnomaly",
                    "severity": result["severity"],
                    "type": result["alert_type"],
                },
                "annotations": {
                    "summary": f"Anomaly detected: {result['alert_type']}",
                    "description": (
                        f"Z-score: {result['z_score']:.2f}, "
                        f"RPS: {result['current_rps']:.2f}, "
                        f"Baseline: {result['baseline_mean']:.2f} ± {result['baseline_stddev']:.2f}"
                    ),
                },
                "startsAt": result["timestamp"],
            }
            resp = requests.post(ALERTMANAGER_URL, json=[alert], timeout=5)
            if resp.status_code not in (200, 201, 202):
                logger.error("Alertmanager rejected alert: %s %s", resp.status_code, resp.text)
        except Exception as e:
            logger.error("Failed to send alert to Alertmanager: %s", e)

    def run_analysis_loop(self):
        """Run periodic analysis."""
        while self.running:
            time.sleep(WINDOW_SECONDS)
            if self.running:
                self.analyze_window()


detector = AnomalyDetector()


@app.route("/health")
def health():
    return jsonify({"status": "healthy", "timestamp": datetime.utcnow().isoformat()})


@app.route("/metrics")
def metrics():
    return generate_latest()


@app.route("/api/v1/event", methods=["POST"])
def receive_event():
    """Receive OpenSIPS event."""
    data = request.get_json() or {}
    detector.record_event(
        event_type=data.get("event_type", "unknown"),
        source_ip=data.get("source_ip", "0.0.0.0"),
        sip_method=data.get("sip_method")
    )
    return jsonify({"status": "ok"})


@app.route("/api/v1/status")
def status():
    """Get current detector status."""
    mean, stddev = detector.baseline.get_stats()
    return jsonify({
        "current_rps": CURRENT_RPS._value.get(),
        "baseline_mean": mean,
        "baseline_stddev": stddev,
        "z_score": Z_SCORE._value.get(),
        "banned_ips": len(detector.banned_ips),
        "last_alert": detector.last_alert_time.isoformat() if detector.last_alert_time else None,
        "consecutive_alerts": detector.consecutive_alerts,
    })


if __name__ == "__main__":
    # Start analysis thread
    analysis_thread = threading.Thread(target=detector.run_analysis_loop, daemon=True)
    analysis_thread.start()

    logger.info("Anomaly detector starting on port 8080")
    app.run(host="0.0.0.0", port=8080, threaded=True)
