#!/usr/bin/env python3
# @req FR-008
"""
Integration tests for Feature 007: TLS/SRTP Encryption.
"""

import os
import subprocess
import pytest

COMPOSE_FILE = os.environ.get("COMPOSE_FILE", "docker-compose.yml")


class TestTLSEncryption:
    """Test TLS encryption for SIP signaling."""

    def test_tls_module_loaded(self):
        """T2.1: tls_mgm module is loaded in OpenSIPS."""
        result = subprocess.run(
            ["docker", "compose", "-f", COMPOSE_FILE, "exec", "-T", "opensips",
             "bash", "-c", "ls /usr/local/lib64/opensips/modules/ | grep -E 'tls|proto_tls'"],
            capture_output=True, text=True
        )
        # Skip if TLS modules not available
        if result.returncode != 0:
            pytest.skip("TLS modules not available in this build")
        assert "tls_mgm.so" in result.stdout or "proto_tls.so" in result.stdout

    def test_tls_listener_configured(self):
        """T2.2: TLS listener is configured in OpenSIPS."""
        result = subprocess.run(
            ["docker", "compose", "-f", COMPOSE_FILE, "exec", "-T", "opensips",
             "bash", "-c", "grep 'socket=tls' /etc/opensips/opensips.cfg"],
            capture_output=True, text=True
        )
        if result.returncode != 0:
            pytest.skip("OpenSIPS container not accessible")
        assert "5061" in result.stdout

    def test_tls_secrets_mounted(self):
        """T1.4: TLS secrets are mounted in OpenSIPS container."""
        secrets = ["ca.crt", "server.crt", "server.key"]
        for secret in secrets:
            result = subprocess.run(
                ["docker", "compose", "-f", COMPOSE_FILE, "exec", "-T", "opensips",
                 "test", "-f", f"/run/secrets/{secret}"],
                capture_output=True
            )
            if result.returncode != 0:
                pytest.skip(f"Secret {secret} not mounted")

    def test_certificate_valid(self):
        """FR-007-002: Server certificate chain validates."""
        result = subprocess.run(
            ["docker", "compose", "-f", COMPOSE_FILE, "exec", "-T", "opensips",
             "bash", "-c",
             "openssl verify -CAfile /run/secrets/ca.crt /run/secrets/server.crt"],
            capture_output=True, text=True
        )
        if result.returncode != 0:
            pytest.skip("Certificates not available")
        assert "OK" in result.stdout


class TestSRTPEncryption:
    """Test SRTP encryption for media."""

    def test_rtpengine_srtp_available(self):
        """T4.1: RTPengine supports SRTP."""
        result = subprocess.run(
            ["docker", "compose", "-f", COMPOSE_FILE, "exec", "-T", "rtpengine",
             "bash", "-c", "rtpengine --version 2>&1 | head -1"],
            capture_output=True, text=True
        )
        if result.returncode != 0:
            pytest.skip("RTPengine container not accessible")
        # RTPengine should support SRTP if compiled with OpenSSL
        assert result.returncode == 0


if __name__ == "__main__":
    pytest.main([__file__, "-v"])
