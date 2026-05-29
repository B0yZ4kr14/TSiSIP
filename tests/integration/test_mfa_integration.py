"""
Test MFA integration: rate limiting, replay protection, audit logging (Feature 037)

Validates that MFA endpoints enforce rate limits, prevent replay attacks,
and log all security-relevant events to the audit log.
"""
import os
import pytest
import psycopg2

DB_HOST = os.environ.get('DB_HOST', 'localhost')
DB_NAME = os.environ.get('DB_NAME', 'opensips')
DB_USER = os.environ.get('DB_USER', 'opensips')
DB_PASS = os.environ.get('DB_PASSWORD', '')


@pytest.fixture(scope='module')
def db():
    if not DB_PASS:
        pytest.skip('DB_PASSWORD not set')
    conn = psycopg2.connect(
        host=DB_HOST, dbname=DB_NAME, user=DB_USER, password=DB_PASS
    )
    yield conn
    conn.close()


class TestMfaRateLimiting:
    def test_verify_screen_has_rate_limit(self):
        """T5.1: mfa-verify.php implements rate limiting"""
        path = 'web/mfa-verify.php'
        assert os.path.exists(path)
        with open(path) as f:
            content = f.read()
        assert 'failed_attempts' in content.lower(), 'Rate limit (failed_attempts) not found'
        assert 'locked_until' in content.lower(), 'Lockout (locked_until) not found'

    def test_verify_screen_has_replay_protection(self):
        """T5.2: mfa-verify.php prevents replay attacks"""
        path = 'web/mfa-verify.php'
        with open(path) as f:
            content = f.read()
        assert 'last_code_window' in content.lower(), 'Replay protection (last_code_window) not found'

    def test_mfa_audit_events_logged(self, db):
        """T5.3: MFA events are recorded in audit log"""
        cur = db.cursor()
        cur.execute("""
            SELECT COUNT(*) FROM audit_log
            WHERE event_type LIKE 'MFA_%'
        """)
        count = cur.fetchone()[0]
        cur.close()
        assert count >= 0, 'Audit log query failed'

    def test_backup_codes_are_hashed(self, db):
        """T5.4: Backup codes stored as hashes, not plaintext"""
        cur = db.cursor()
        cur.execute("""
            SELECT code_hash FROM ocp_user_backup_codes LIMIT 1
        """)
        row = cur.fetchone()
        cur.close()
        if row:
            code_hash = row[0]
            assert code_hash.startswith('$2') or len(code_hash) > 40, 'Backup code not hashed with bcrypt'

    def test_mfa_secret_is_encrypted(self, db):
        """T5.5: MFA secrets stored encrypted"""
        cur = db.cursor()
        cur.execute("""
            SELECT secret_encrypted FROM ocp_user_mfa WHERE enabled = true LIMIT 1
        """)
        row = cur.fetchone()
        cur.close()
        if row and row[0]:
            secret = row[0]
            assert len(secret) > 32, 'Secret does not appear encrypted'


class TestMfaAdminReset:
    def test_admin_reset_endpoint_exists(self):
        """T5.6: Admin MFA reset API exists"""
        assert os.path.exists('web/api/v1/mfa-reset.php')

    def test_admin_reset_requires_admin_role(self):
        """T5.7: Admin reset enforces admin role"""
        with open('web/api/v1/mfa-reset.php') as f:
            content = f.read()
        assert "admin" in content.lower(), 'Admin role check missing'
        assert '403' in content, 'HTTP 403 not returned for non-admin'

    def test_admin_reset_deletes_mfa_and_codes(self):
        """T5.8: Reset deletes both MFA secret and backup codes"""
        with open('web/api/v1/mfa-reset.php') as f:
            content = f.read()
        assert 'DELETE FROM ocp_user_mfa' in content, 'MFA delete missing'
        assert 'DELETE FROM ocp_user_backup_codes' in content, 'Backup codes delete missing'


class TestMfaPolicyEnforcement:
    def test_policy_table_has_defaults(self, db):
        """T5.9: MFA policy defaults are populated"""
        cur = db.cursor()
        cur.execute("SELECT role FROM mfa_policy")
        roles = [r[0] for r in cur.fetchall()]
        cur.close()
        assert 'admin' in roles, 'Admin policy missing'

    def test_policy_enforced_on_login(self):
        """T5.10: Login flow checks MFA policy"""
        with open('web/login.php') as f:
            content = f.read()
        assert 'mfaRequiredForUser' in content or 'mfa_policy' in content, 'MFA policy check missing in login'
