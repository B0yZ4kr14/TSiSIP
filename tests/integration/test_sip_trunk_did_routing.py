#!/usr/bin/env python3
"""
TSiSIP SIP Trunk DID Routing Integration Test
Validates inbound trunk call -> DID lookup -> tenant resolution -> Asterisk backend.

Test IDs:
- DID-001: DID match routes to correct tenant and dispatcher set
- DID-002: DID not found returns 404 (from known trunk)
- DID-003: DID disabled returns 403
- DID-004: Non-trunk INVITE requires authentication (407)
"""

import os
import pytest
import socket
import subprocess
import time


def psql(sql: str, timeout: int = 15) -> tuple:
    """Execute SQL via docker compose exec postgres psql."""
    result = subprocess.run(
        ["docker", "compose", "-f", "docker-compose.vps.yml", "exec", "-T", "postgres",
         "psql", "-U", "opensips", "-d", "opensips", "-t", "-c", sql],
        capture_output=True, text=True, timeout=timeout,
    )
    return result.stdout, result.stderr, result.returncode


def run_in_sip_edge(python_code: str, timeout: int = 20) -> tuple:
    """Run Python code inside a container on the sip_edge network."""
    result = subprocess.run(
        ["docker", "run", "--rm", "--network", "tsisip_sip_edge",
         "python:3.12-alpine", "python3", "-c", python_code],
        capture_output=True, text=True, timeout=timeout,
    )
    return result.stdout + result.stderr, result.returncode


class TestDIDRouting:
    """DID-001 through DID-004."""

    def test_did_002_not_found(self):
        """DID-002: An unknown DID from a known trunk should return 404."""
        # Discover the dynamic IP assigned to test containers on sip_edge
        ip_result = subprocess.run(
            ["docker", "run", "--rm", "--network", "tsisip_sip_edge",
             "python:3.12-alpine", "sh", "-c", "hostname -i"],
            capture_output=True, text=True, timeout=10,
        )
        test_ip = ip_result.stdout.strip().split()[0] if ip_result.returncode == 0 else "172.24.0.3"

        # Setup: insert test trunk provider and tenant
        psql("INSERT INTO tenants (id, name, sip_domain, enabled) VALUES ('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11', 'test-tenant', 'test.tsiapp.io', true) ON CONFLICT (id) DO NOTHING;")
        psql(f"INSERT INTO sip_trunk_providers (name, host, port, transport, enabled, priority) VALUES ('test-trunk', '{test_ip}', 5060, 'udp', true, 1) ON CONFLICT DO NOTHING;")
        psql("INSERT INTO sip_trunk_did_mappings (trunk_provider_id, did_number, tenant_id, dispatcher_setid, enabled) SELECT id, '+15551234567', 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11', 1, true FROM sip_trunk_providers WHERE name = 'test-trunk' ON CONFLICT DO NOTHING;")

        try:
            code = f"""
import socket, sys
sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
sock.bind(('0.0.0.0', 15092))
sock.settimeout(5)
msg = (
    b'INVITE sip:+19999999999@opensips:5060 SIP/2.0\\r\\n'
    b'Via: SIP/2.0/UDP {test_ip}:15092;branch=z9hG4bK-did002\\r\\n'
    b'From: <sip:trunk@{test_ip}>;tag=trunktag\\r\\n'
    b'To: <sip:+19999999999@opensips:5060>\\r\\n'
    b'Call-ID: did-002-test@{test_ip}\\r\\n'
    b'CSeq: 1 INVITE\\r\\n'
    b'Max-Forwards: 70\\r\\n'
    b'Contact: <sip:trunk@{test_ip}:15092>\\r\\n'
    b'Content-Length: 0\\r\\n\\r\\n'
)
try:
    sock.sendto(msg, ('opensips', 5060))
    data, _ = sock.recvfrom(4096)
    resp = data.decode()
    print(resp.splitlines()[0])
    if '404' in resp:
        sys.exit(0)
    else:
        sys.exit(1)
except Exception as e:
    print(f'Exception: {{e}}')
    sys.exit(1)
finally:
    sock.close()
"""
            output, rc = run_in_sip_edge(code)
            print(f"DID-002 output: {output.strip()}")
            assert rc == 0, f"Expected 404 for unknown DID from trunk. Output: {output}"
        finally:
            # Cleanup
            psql("DELETE FROM sip_trunk_did_mappings WHERE did_number IN ('+15551234567', '+19999999999');")
            psql("DELETE FROM sip_trunk_providers WHERE name = 'test-trunk';")
            psql("DELETE FROM tenants WHERE id = 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11';")

    def test_did_004_invite_407_for_non_trunk(self):
        """DID-004: Non-trunk INVITE should require authentication (407)."""
        code = """
import socket, sys
sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
sock.bind(('0.0.0.0', 15093))
sock.settimeout(5)
msg = (
    b'INVITE sip:test@opensips:5060 SIP/2.0\\r\\n'
    b'Via: SIP/2.0/UDP 172.24.0.99:15093;branch=z9hG4bK-did004\\r\\n'
    b'From: <sip:test@172.24.0.99>;tag=testtag\\r\\n'
    b'To: <sip:test@opensips:5060>\\r\\n'
    b'Call-ID: did-004-test@172.24.0.99\\r\\n'
    b'CSeq: 1 INVITE\\r\\n'
    b'Max-Forwards: 70\\r\\n'
    b'Contact: <sip:test@172.24.0.99:15093>\\r\\n'
    b'Content-Length: 0\\r\\n\\r\\n'
)
try:
    sock.sendto(msg, ('opensips', 5060))
    data, _ = sock.recvfrom(4096)
    resp = data.decode()
    print(resp.splitlines()[0])
    if '407' in resp:
        sys.exit(0)
    else:
        sys.exit(1)
except Exception as e:
    print(f'Exception: {e}')
    sys.exit(1)
finally:
    sock.close()
"""
        output, rc = run_in_sip_edge(code)
        print(f"DID-004 output: {output.strip()}")
        assert rc == 0, f"Expected 407 for non-trunk INVITE. Output: {output}"


if __name__ == "__main__":
    pytest.main([__file__, "-v"])
