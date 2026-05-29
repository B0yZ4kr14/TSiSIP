"""Unit tests for crypto library (Feature 037)"""
import os
import sys
import tempfile

sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..', 'integration'))

# Mock the auth_secret environment
os.environ['AUTH_SECRET'] = 'test-auth-secret-32bytes1234'

# We can't directly test PHP crypto, but we can test the Python TOTP helper
from totp_test import generateTotpCode, verifyTotpCode, base32Decode

def test_totp_generation():
    secret = 'JBSWY3DPEHPK3PXP'
    code = generateTotpCode(secret)
    assert len(code) == 6 and code.isdigit(), f"Expected 6-digit code, got {code}"
    print(f"✅ PASS: TOTP generation ({code})")

def test_totp_self_verification():
    import secrets
    secret = ''.join(secrets.choice('ABCDEFGHIJKLMNOPQRSTUVWXYZ234567') for _ in range(32))
    code = generateTotpCode(secret)
    assert verifyTotpCode(secret, code), "Self-verification failed"
    print("✅ PASS: TOTP self-verification")

def test_totp_wrong_code():
    secret = 'JBSWY3DPEHPK3PXP'
    assert not verifyTotpCode(secret, '000000'), "Wrong code should fail"
    print("✅ PASS: Wrong code rejection")

def test_totp_window():
    import time
    secret = 'JBSWY3DPEHPK3PXP'
    t = int(time.time())
    code = generateTotpCode(secret, 30, t)
    assert verifyTotpCode(secret, code, 1), "Window tolerance failed"
    print("✅ PASS: Window tolerance")

def test_base32_decode():
    decoded = base32Decode('JBSWY3DPEHPK3PXP')
    assert len(decoded) > 0, "Base32 decode failed"
    print("✅ PASS: Base32 decode")

if __name__ == '__main__':
    test_totp_generation()
    test_totp_self_verification()
    test_totp_wrong_code()
    test_totp_window()
    test_base32_decode()
    print("\n=== All crypto/TOTP tests passed ===")
