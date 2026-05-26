#!/usr/bin/env python3
"""
TSiSIP Runbook Integration Test
Validates the scale-asterisk runbook adds a backend and produces evidence.

Test IDs:
- RUN-001: scale-asterisk.sh adds a new dispatcher destination
- RUN-002: scale-asterisk.sh produces a valid JSON evidence artifact
"""

import json
import os
import pytest
import subprocess
from pathlib import Path


def run_runbook(runbook: str, args: list, timeout: int = 60) -> tuple:
    result = subprocess.run(
        ["bash", f"scripts/runbook/{runbook}"] + args,
        capture_output=True, text=True, timeout=timeout,
    )
    return result.stdout, result.stderr, result.returncode


class TestRunbookScale:

    def _clean_test_destination(self, test_ip: str):
        """Remove any pre-existing test destination to keep tests idempotent."""
        subprocess.run(
            ["docker", "compose", "-f", "docker-compose.vps.yml", "exec", "-T", "postgres",
             "psql", "-U", "opensips", "-d", "opensips", "-c",
             f"DELETE FROM dispatcher WHERE setid=1 AND destination = 'sip:{test_ip}:5060';"],
            capture_output=True, text=True, timeout=15,
        )

    def test_run_001_scale_adds_destination(self):
        """RUN-001: scale-asterisk.sh inserts a new dispatcher destination."""
        test_ip = "192.0.2.99"
        self._clean_test_destination(test_ip)
        stdout, stderr, rc = run_runbook("scale-asterisk.sh", [test_ip, "1", "runbook-test-pbx"])
        print(f"RUN-001 stdout: {stdout}")
        print(f"RUN-001 stderr: {stderr}")
        assert rc == 0, f"scale-asterisk.sh failed: {stderr}"

        # Verify the destination exists in the database
        result = subprocess.run(
            ["docker", "compose", "-f", "docker-compose.vps.yml", "exec", "-T", "postgres",
             "psql", "-U", "opensips", "-d", "opensips", "-t", "-c",
             f"SELECT COUNT(*) FROM dispatcher WHERE setid=1 AND destination = 'sip:{test_ip}:5060';"],
            capture_output=True, text=True, timeout=15,
        )
        count = int(result.stdout.strip())
        assert count >= 1, f"Expected destination to be inserted, found {count}"

    def test_run_002_scale_produces_evidence(self):
        """RUN-002: scale-asterisk.sh produces a valid JSON evidence artifact."""
        evidence_dir = Path("evidence/runbook")
        assert evidence_dir.exists(), "Evidence directory should exist"

        # Find the most recent evidence file for scale-asterisk
        files = sorted(evidence_dir.glob("*_scale-*/evidence.json"), key=lambda p: p.stat().st_mtime, reverse=True)
        assert files, "No evidence artifact found for scale-asterisk runbook"

        latest = files[0]
        data = json.loads(latest.read_text())
        assert data.get("runbook") == "scale-asterisk"
        assert "new_ip" in data
        assert "steps" in data
        assert data.get("result") == "success"


if __name__ == "__main__":
    pytest.main([__file__, "-v"])
