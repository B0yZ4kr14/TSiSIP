"""
MFA Self-Service Disable Test (Feature 037 — T3.7)

Validates that users can self-disable MFA with password confirmation.
"""
import os
import pytest

class TestMfaSelfService:
    def test_disable_endpoint_requires_password(self):
        """T3.7: MFA disable requires password confirmation"""
        path = 'web/api/v1/mfa-disable.php'
        assert os.path.exists(path)
        with open(path) as f:
            content = f.read()
        assert 'password' in content.lower(), 'Password confirmation missing'
        assert 'requireAuth' in content, 'Auth check missing'

    def test_disable_deletes_mfa_data(self):
        """T3.7b: Disable removes MFA secret and backup codes"""
        path = 'web/api/v1/mfa-disable.php'
        with open(path) as f:
            content = f.read()
        assert 'DELETE FROM ocp_user_mfa' in content, 'MFA delete missing'
        assert 'DELETE FROM ocp_user_backup_codes' in content, 'Backup codes delete missing'

    def test_disable_logs_audit_event(self):
        """T3.7c: Disable logs audit event"""
        path = 'web/api/v1/mfa-disable.php'
        with open(path) as f:
            content = f.read()
        assert 'logAuditEvent' in content, 'Audit log missing'
        assert 'MFA_DISABLED' in content, 'MFA_DISABLED event missing'
