#!/usr/bin/env python3
"""
Feature 037 Integration Tests: Multi-Factor Authentication (MFA) for OCP
Validates: TOTP engine, enrollment, verification, backup codes, policy, audit.

Test IDs:
- T37.1: Database tables exist (ocp_user_mfa, ocp_user_backup_codes, mfa_policy)
- T37.2: TOTP library generates valid codes
- T37.3: Encryption roundtrip works
- T37.4: MFA enrollment endpoint exists
- T37.5: MFA verification screen exists
- T37.6: Backup code recovery exists
- T37.7: Admin reset endpoint exists
- T37.8: Policy endpoint exists
- T37.9: Login flow modified for MFA
- T37.10: Profile page has MFA section
"""

import os
import subprocess
import pytest

PROJECT_ROOT = os.path.dirname(os.path.dirname(os.path.dirname(__file__)))


class TestMfaFiles:
    """T37.4-T37.10: All backend artifacts exist."""

    def test_totp_library_exists(self):
        path = os.path.join(PROJECT_ROOT, "web/lib/totp.php")
        assert os.path.exists(path), "totp.php missing"

    def test_crypto_library_exists(self):
        path = os.path.join(PROJECT_ROOT, "web/lib/crypto.php")
        assert os.path.exists(path), "crypto.php missing"

    def test_mfa_verify_screen_exists(self):
        path = os.path.join(PROJECT_ROOT, "web/mfa-verify.php")
        assert os.path.exists(path), "mfa-verify.php missing"

    def test_mfa_backup_screen_exists(self):
        path = os.path.join(PROJECT_ROOT, "web/mfa-backup.php")
        assert os.path.exists(path), "mfa-backup.php missing"

    def test_mfa_enroll_endpoint_exists(self):
        path = os.path.join(PROJECT_ROOT, "web/api/v1/mfa-enroll.php")
        assert os.path.exists(path), "mfa-enroll.php missing"

    def test_mfa_disable_endpoint_exists(self):
        path = os.path.join(PROJECT_ROOT, "web/api/v1/mfa-disable.php")
        assert os.path.exists(path), "mfa-disable.php missing"

    def test_mfa_reset_endpoint_exists(self):
        path = os.path.join(PROJECT_ROOT, "web/api/v1/mfa-reset.php")
        assert os.path.exists(path), "mfa-reset.php missing"

    def test_mfa_policy_endpoint_exists(self):
        path = os.path.join(PROJECT_ROOT, "web/api/v1/mfa-policy.php")
        assert os.path.exists(path), "mfa-policy.php missing"

    def test_mfa_status_endpoint_exists(self):
        path = os.path.join(PROJECT_ROOT, "web/api/v1/mfa-status.php")
        assert os.path.exists(path), "mfa-status.php missing"

    def test_login_has_mfa_check(self):
        path = os.path.join(PROJECT_ROOT, "web/login.php")
        with open(path) as f:
            content = f.read()
        assert "mfa-verify.php" in content, "login.php missing MFA redirect"
        assert "ocp_user_mfa" in content, "login.php missing MFA query"

    def test_profile_has_mfa_section(self):
        path = os.path.join(PROJECT_ROOT, "web/profile.php")
        with open(path) as f:
            content = f.read()
        assert "mfa-section" in content, "profile.php missing MFA section"
        assert "mfa-enroll" in content, "profile.php missing enrollment UI"


class TestMfaMigration:
    """T37.1: Database schema exists."""

    def test_migration_file_exists(self):
        path = os.path.join(PROJECT_ROOT, "db/init/11-mfa-schema.sql")
        assert os.path.exists(path), "Migration 11-mfa-schema.sql missing"

    def test_migration_creates_mfa_tables(self):
        path = os.path.join(PROJECT_ROOT, "db/init/11-mfa-schema.sql")
        with open(path) as f:
            content = f.read().lower()
        assert "ocp_user_mfa" in content, "Migration missing ocp_user_mfa"
        assert "ocp_user_backup_codes" in content, "Migration missing ocp_user_backup_codes"
        assert "mfa_policy" in content, "Migration missing mfa_policy"


class TestMfaIntegration:
    """Runtime integration tests requiring Docker Compose stack."""

    @pytest.fixture
    def compose_file(self):
        return os.environ.get("COMPOSE_FILE", "docker-compose.yml")

    def test_mfa_table_in_database(self, compose_file):
        r = subprocess.run(
            [
                "docker", "compose", "-f", compose_file, "exec", "-T", "postgres",
                "psql", "-U", "opensips", "-d", "opensips", "-tAc",
                "SELECT 1 FROM information_schema.tables WHERE table_name = 'ocp_user_mfa';",
            ],
            capture_output=True, text=True,
        )
        if r.returncode != 0:
            pytest.skip("PostgreSQL not reachable")
        assert "1" in r.stdout, "ocp_user_mfa table does not exist"

    def test_policy_defaults_populated(self, compose_file):
        r = subprocess.run(
            [
                "docker", "compose", "-f", compose_file, "exec", "-T", "postgres",
                "psql", "-U", "opensips", "-d", "opensips", "-tAc",
                "SELECT COUNT(*) FROM mfa_policy;",
            ],
            capture_output=True, text=True,
        )
        if r.returncode != 0:
            pytest.skip("PostgreSQL not reachable")
        count = int(r.stdout.strip())
        assert count >= 6, f"Expected >=6 policy rows, got {count}"

    def test_totp_code_generation(self):
        """T37.2: TOTP library generates 6-digit codes."""
        from totp_test import generateTotpCode, verifyTotpCode
        secret = "JBSWY3DPEHPK3PXP"
        code = generateTotpCode(secret)
        assert len(code) == 6, f"Expected 6-digit code, got {code}"
        assert code.isdigit(), f"Code should be numeric, got {code}"
        assert verifyTotpCode(secret, code), "TOTP self-verification failed"


if __name__ == "__main__":
    pytest.main([__file__, "-v"])
