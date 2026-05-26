#!/usr/bin/env python3
"""
TSiSIP LGPD Compliance Integration Test
Validates CDR anonymization and subscriber data export.

Test IDs:
- LGPD-001: purge-cdr.php --dry-run shows eligible CDRs without altering data
- LGPD-002: purge-cdr.php anonymizes CDRs and retains duration/cost aggregates
- LGPD-003: export-audit-lgpd.php produces a JSON file with all audit events
"""

import json
import os
import pytest
import subprocess
import tempfile
from pathlib import Path


def docker_compose_exec(service: str, cmd: list, timeout: int = 30) -> tuple:
    result = subprocess.run(
        ["docker", "compose", "-f", "docker-compose.vps.yml", "exec", "-T", service] + cmd,
        capture_output=True, text=True, timeout=timeout,
    )
    return result.stdout, result.stderr, result.returncode


def psql(sql: str, timeout: int = 15) -> tuple:
    return docker_compose_exec("postgres", [
        "psql", "-U", "opensips", "-d", "opensips", "-t", "-c", sql
    ], timeout)


class TestLGPDCompliance:

    def test_lgpd_001_purge_cdr_dry_run(self):
        psql("""
            INSERT INTO cdr (call_id, call_start, from_user, from_domain, to_user, to_domain, source_ip, sip_method, call_status)
            VALUES ('lgpd-test-001', NOW() - INTERVAL '400 days', 'test-from@example.com', 'example.com', 'test-to@example.com', 'example.com', '192.0.2.1'::INET, 'INVITE', 'completed')
            ON CONFLICT DO NOTHING;
        """)
        stdout, stderr, rc = docker_compose_exec("ocp", [
            "php", "/var/www/html/cli/purge-cdr.php", "--dry-run"
        ])
        print(f"LGPD-001 stdout: {stdout}")
        print(f"LGPD-001 stderr: {stderr}")
        assert rc == 0, f"purge-cdr.php --dry-run failed: {stderr}"
        assert "eligible" in stdout.lower() or "DRY-RUN" in stdout, f"Expected dry-run output, got: {stdout}"

        out, err, rc2 = psql("SELECT from_user FROM cdr WHERE call_id = 'lgpd-test-001';")
        assert rc2 == 0
        assert "test-from@example.com" in out, "CDR should not be anonymized after dry-run"

    def test_lgpd_002_purge_cdr_anonymizes(self):
        psql("""
            INSERT INTO cdr (call_id, call_start, duration, from_user, from_domain, to_user, to_domain, source_ip, sip_method, call_status)
            VALUES ('lgpd-test-002', NOW() - INTERVAL '400 days', 120, 'real-from@example.com', 'example.com', 'real-to@example.com', 'example.com', '192.0.2.2'::INET, 'INVITE', 'completed')
            ON CONFLICT DO NOTHING;
        """)
        stdout, stderr, rc = docker_compose_exec("ocp", [
            "php", "/var/www/html/cli/purge-cdr.php"
        ])
        print(f"LGPD-002 stdout: {stdout}")
        print(f"LGPD-002 stderr: {stderr}")
        assert rc == 0, f"purge-cdr.php failed: {stderr}"

        out, err, rc2 = psql("SELECT from_user, to_user, source_ip, duration FROM cdr WHERE call_id = 'lgpd-test-002';")
        assert rc2 == 0
        assert "real-from@example.com" not in out, "from_user should be anonymized"
        assert "real-to@example.com" not in out, "to_user should be anonymized"
        assert "120" in out, "duration should be preserved"

        psql("DELETE FROM cdr WHERE call_id LIKE 'lgpd-test-%';")

    def test_lgpd_003_export_audit(self):
        psql("""
            INSERT INTO ocp_audit_log (username, action, resource_type, resource_id, success, details, ip_address, hash)
            VALUES ('lgpd-test-sub@example.com', 'TEST_ACTION', 'subscriber', 'lgpd-test-sub@example.com', true, '{\"subscriber\": \"lgpd-test-sub@example.com\"}'::jsonb, '192.0.2.3'::INET, 'deadbeef')
            ON CONFLICT DO NOTHING;
        """)
        stdout, stderr, rc = docker_compose_exec("ocp", [
            "php", "/var/www/html/cli/export-audit-lgpd.php",
            "--subscriber=lgpd-test-sub@example.com"
        ])
        print(f"LGPD-003 stdout: {stdout}")
        print(f"LGPD-003 stderr: {stderr}")
        assert rc == 0, f"export-audit-lgpd.php failed: {stderr}"

        # stdout is the JSON export
        data = json.loads(stdout)
        assert data.get('export_type') == 'lgpd_right_of_access'
        assert data.get('subscriber') == 'lgpd-test-sub@example.com'
        assert any(e.get('action') == 'TEST_ACTION' for e in data.get('ocp_audit_events', [])), "Expected TEST_ACTION in audit events"

        psql("DELETE FROM ocp_audit_log WHERE resource_id = 'lgpd-test-sub@example.com';")


if __name__ == "__main__":
    pytest.main([__file__, "-v"])
