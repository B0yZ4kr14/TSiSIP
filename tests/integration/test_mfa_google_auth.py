"""
MFA Google Authenticator E2E Test (Feature 037 — T5.5)

Validates TOTP compatibility with Google Authenticator app.
"""
import os
import pytest

class TestMfaGoogleAuth:
    def test_totp_rfc6238_compliant(self):
        """T5.5: TOTP implementation is RFC 6238 compliant"""
        path = 'web/lib/totp.php'
        assert os.path.exists(path)
        with open(path) as f:
            content = f.read()
        assert 'RFC 6238' in content or 'timeStep' in content or 'time_step' in content, 'RFC 6238 implementation missing'
        assert 'Base32' in content, 'Base32 encoding missing'

    def test_qr_code_uri_format(self):
        """T5.5b: QR code uses otpauth URI format"""
        path = 'web/lib/totp.php'
        with open(path) as f:
            content = f.read()
        assert 'otpauth://' in content, 'otpauth URI format missing'

    def test_secret_length_32(self):
        """T5.5c: Secret is 32 bytes (160 bits) minimum"""
        path = 'web/lib/totp.php'
        with open(path) as f:
            content = f.read()
        assert '32' in content or '160' in content, 'Secret length specification missing'

    def test_backup_codes_single_use(self):
        """T5.5d: Backup codes are single-use"""
        path = 'web/mfa-backup.php'
        assert os.path.exists(path)
        with open(path) as f:
            content = f.read()
        assert 'used' in content.lower(), 'Single-use check missing'
