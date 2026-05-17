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

from flask import Flask, jsonify
from prometheus_client import Counter, Gauge, Histogram, generate_latest

from baseline import TrafficBaseline

logging.basicConfig(level=logging.INFO, format="%(asctime)s %(levelname)s %(message)s")
logger = logging.getLogger(__name__)

# Configuration
WINDOW_SECONDS = int(os.environ.get("ANOMALY_WINDOW_SECONDS", "60"))
Z_THRESHOLD = float(os.environ.get("ANOMALY_Z_THRESHOLD", "3.0"))
BASELINE_WINDOW_HOURS = int(os.environ.get("BASELINE_WINDOW_HOURS", "24"))
ALERT_COOLDOWN_SECONDS = int(os.environ.get("ALERT_COOLDOWN_SECONDS", "300"))

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

app = Flask(__name__)


class AnomalyDetector:
    """Detects traffic anomalies using statistical baseline."""

    def __init__(self):
        self.baseline = TrafficBaseline(window_hours=BASELINE_WINDOW_HOURS)
        self.event_buffer = defaultdict(int)
        self.ip_tracker = defaultdict(int)
        self.banned_ips = set()
        self.last_alert_time = None
        self.lock = threading.Lock()
        self.running = True

    def record_event(self, event_type: str, source_ip: str, sip_method: str = None):
        """Record an OpenSIPS event."""
        with self.lock:
            self.event_buffer[event_type] += 1
            self.ip_tracker[source_ip] += 1

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

                # Check threshold
                if z_score > Z_THRESHOLD:
                    if (self.last_alert_time is None or
                        (datetime.utcnow() - self.last_alert_time).seconds > ALERT_COOLDOWN_SECONDS):
                        result["alert"] = True
                        result["alert_type"] = "distributed_flood"
                        result["severity"] = "critical" if z_score > Z_THRESHOLD * 2 else "high"
                        self.last_alert_time = datetime.utcnow()
                        self._trigger_alert(result)

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
        """Trigger alert and optional global throttle."""
        ANOMALY_ALERTS.labels(
            alert_type=result["alert_type"],
            severity=result["severity"]
        ).inc()
        logger.warning(
            "ANOMALY ALERT: type=%s severity=%s z_score=%.2f rps=%.2f",
            result["alert_type"], result["severity"],
            result["z_score"], result["current_rps"]
        )

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
    })


if __name__ == "__main__":
    from flask import request  # noqa

    # Start analysis thread
    analysis_thread = threading.Thread(target=detector.run_analysis_loop, daemon=True)
    analysis_thread.start()

    logger.info("Anomaly detector starting on port 8080")
    app.run(host="0.0.0.0", port=8080, threaded=True)
