#!/usr/bin/env python3
"""
Statistical baseline for traffic anomaly detection.
"""

import statistics
from collections import deque
from datetime import datetime


class TrafficBaseline:
    """Maintains a rolling statistical baseline of traffic rates."""

    def __init__(self, window_hours: int = 24, max_samples: int = 1440):
        self.window_hours = window_hours
        self.samples = deque(maxlen=max_samples)
        self.timestamps = deque(maxlen=max_samples)

    def add_sample(self, rps: float):
        """Add a traffic sample."""
        self.samples.append(rps)
        self.timestamps.append(datetime.utcnow())

        # Remove old samples outside window
        cutoff = datetime.utcnow() - __import__('datetime').timedelta(hours=self.window_hours)
        while self.timestamps and self.timestamps[0] < cutoff:
            self.samples.popleft()
            self.timestamps.popleft()

    def get_stats(self) -> tuple:
        """Return (mean, stddev) of current samples."""
        if len(self.samples) < 2:
            return (0.0, 0.0)
        return (
            statistics.mean(self.samples),
            statistics.stdev(self.samples) if len(self.samples) > 1 else 0.0
        )
