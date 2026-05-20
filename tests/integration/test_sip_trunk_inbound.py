#!/usr/bin/env python3
"""
Feature 014-C T7.3: Inbound SIP Trunk DID Routing Test

Purpose:
  Validates that an unauthenticated INVITE arriving from a trusted trunk
  provider IP is routed to the correct tenant and Asterisk backend based on
  DID mapping.  The test:
    - Registers the test client IP as a trusted trunk provider.
    - Maps a DID to a tenant and a mock backend dispatcher set.
    - Sends an unauthenticated INVITE with the DID in the R-URI.
    - Verifies 200 OK is returned.
    - Verifies the forwarded INVITE contains the X-Tenant-ID header.

How to run:
    docker compose up -d
    python3 -m pytest tests/integration/test_sip_trunk_inbound.py -v
"""

import os
import socket
import subprocess
import threading
import time
import unittest


TARGET_HOST = os.environ.get("TARGET_HOST", "opensips")
TARGET_PORT = int(os.environ.get("TARGET_PORT", "5060"))
COMPOSE_FILE = os.environ.get("COMPOSE_FILE", "docker-compose.yml")
PG_USER = os.environ.get("PG_USER", "opensips")
PG_DB = os.environ.get("PG_DB", "opensips")
PG_HOST = os.environ.get("PG_HOST", "postgres")


def _psql(query: str) -> subprocess.CompletedProcess:
    return subprocess.run(
        [
            "docker", "compose", "-f", COMPOSE_FILE, "exec", "-T", "postgres",
            "psql", "-U", PG_USER, "-d", PG_DB, "-h", PG_HOST, "-t", "-c", query,
        ],
        capture_output=True,
        text=True,
    )


def _get_client_ip(target_host: str = TARGET_HOST, target_port: int = TARGET_PORT) -> str:
    sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
    try:
        sock.connect((target_host, target_port))
        return sock.getsockname()[0]
    except Exception:
        return "127.0.0.1"
    finally:
        sock.close()


def _build_inbound_invite(
    did: str,
    domain: str,
    call_id: str,
    cseq: int,
    from_tag: str,
    branch: str,
) -> bytes:
    uri = f"sip:{did}@{domain}"
    msg = (
        f"INVITE {uri} SIP/2.0\r\n"
        f"Via: SIP/2.0/UDP 172.22.0.1:5061;branch={branch}\r\n"
        f"From: <sip:trunk@{domain}>;tag={from_tag}\r\n"
        f"To: <sip:{did}@{domain}>\r\n"
        f"Call-ID: {call_id}\r\n"
        f"CSeq: {cseq} INVITE\r\n"
        f"Max-Forwards: 70\r\n"
        f"Contact: <sip:trunk@172.22.0.1:5061>\r\n"
        "Content-Length: 0\r\n\r\n"
    )
    return msg.encode()


def _build_sip_reply(request_data: bytes, status_line: str) -> bytes:
    req = request_data.decode("utf-8", errors="replace")
    lines = req.split("\r\n")
    via = from_hdr = to_hdr = call_id = cseq = None
    for line in lines:
        lower = line.lower()
        if lower.startswith("via:"):
            via = line
        elif lower.startswith("from:"):
            from_hdr = line
        elif lower.startswith("to:"):
            to_hdr = line
        elif lower.startswith("call-id:"):
            call_id = line
        elif lower.startswith("cseq:"):
            cseq = line

    reply = f"SIP/2.0 {status_line}\r\n"
    if via:
        reply += f"{via}\r\n"
    if from_hdr:
        reply += f"{from_hdr}\r\n"
    if to_hdr:
        if "tag=" not in to_hdr and not status_line.startswith("100"):
            to_hdr += ";tag=mockresp001"
        reply += f"{to_hdr}\r\n"
    if call_id:
        reply += f"{call_id}\r\n"
    if cseq:
        reply += f"{cseq}\r\n"
    reply += "Content-Length: 0\r\n\r\n"
    return reply.encode()


def _send_and_collect(msg: bytes, host: str = None, port: int = None, timeout: float = 5.0) -> list:
    if host is None:
        host = TARGET_HOST
    if port is None:
        port = TARGET_PORT
    sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
    sock.settimeout(timeout)
    responses = []
    try:
        sock.sendto(msg, (host, port))
        start = time.time()
        while time.time() - start < timeout:
            remaining = timeout - (time.time() - start)
            if remaining <= 0:
                break
            sock.settimeout(remaining)
            try:
                data, _ = sock.recvfrom(8192)
                responses.append(data)
            except socket.timeout:
                break
    finally:
        sock.close()
    return responses


class MockBackendServer(threading.Thread):
    def __init__(self, host: str = "0.0.0.0", port: int = 15062):
        super().__init__(daemon=True)
        self.host = host
        self.port = port
        self._stop_event = threading.Event()
        self._sock = None
        self._lock = threading.Lock()
        self._messages = []

    def run(self):
        self._sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        self._sock.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
        self._sock.bind((self.host, self.port))
        self._sock.settimeout(0.5)
        while not self._stop_event.is_set():
            try:
                data, addr = self._sock.recvfrom(8192)
                with self._lock:
                    self._messages.append((data, addr))
                if b"OPTIONS" in data[:20]:
                    resp = _build_sip_reply(data, "200 OK")
                    self._sock.sendto(resp, addr)
                elif b"INVITE" in data[:20]:
                    resp = _build_sip_reply(data, "100 Trying")
                    self._sock.sendto(resp, addr)
                    resp = _build_sip_reply(data, "200 OK")
                    self._sock.sendto(resp, addr)
            except socket.timeout:
                continue
            except OSError:
                break

    def stop(self):
        self._stop_event.set()
        if self._sock:
            try:
                self._sock.close()
            except OSError:
                pass

    def get_messages(self) -> list:
        with self._lock:
            return list(self._messages)

    def clear(self):
        with self._lock:
            self._messages.clear()


class TestSipTrunkInbound(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        ping = (
            b"OPTIONS sip:" + TARGET_HOST.encode() + b":" + str(TARGET_PORT).encode() + b" SIP/2.0\r\n"
            b"Via: SIP/2.0/UDP 172.22.0.1:5061;branch=z9hG4bK-ping\r\n"
            b"From: <sip:test@localhost>;tag=ping\r\n"
            b"To: <sip:" + TARGET_HOST.encode() + b":" + str(TARGET_PORT).encode() + b">\r\n"
            b"Call-ID: ping-001@172.22.0.1\r\n"
            b"CSeq: 1 OPTIONS\r\n"
            b"Max-Forwards: 70\r\n"
            b"Content-Length: 0\r\n\r\n"
        )
        try:
            resp = _send_and_collect(ping, timeout=3)
            if not resp or b"SIP/2.0 200" not in resp[0]:
                raise unittest.SkipTest("OpenSIPS not responding with 200 OK")
        except Exception as exc:
            raise unittest.SkipTest(f"OpenSIPS not reachable: {exc}")

    def setUp(self):
        self.client_ip = _get_client_ip()
        self.mock_port = 15062
        self.mock = MockBackendServer(host="0.0.0.0", port=self.mock_port)
        self.mock.start()
        time.sleep(0.3)

        # Insert a trunk provider with our client IP so OpenSIPS treats us as trusted.
        result = _psql(
            f"INSERT INTO sip_trunk_providers (name, host, port, transport, priority, enabled, max_cps) "
            f"VALUES ('InboundTrunk', '{self.client_ip}', 5060, 'udp', 1, true, 10) "
            f"ON CONFLICT (name) DO UPDATE SET enabled = true, host = '{self.client_ip}', port = 5060"
        )
        if result.returncode != 0:
            self.skipTest(f"DB insert failed: {result.stderr}")

        # Map DID +15551234567 to the default tenant and a custom dispatcher set.
        result = _psql(
            "INSERT INTO sip_trunk_did_mappings (trunk_provider_id, did_number, tenant_id, dispatcher_setid, enabled) "
            "SELECT id, '+15551234567', (SELECT id FROM tenants WHERE sip_domain = 'sip.tsisip.local' LIMIT 1), 99, true "
            "FROM sip_trunk_providers WHERE name = 'InboundTrunk' "
            "ON CONFLICT (did_number, trunk_provider_id) DO UPDATE SET enabled = true, dispatcher_setid = 99"
        )
        if result.returncode != 0:
            self.skipTest(f"DB insert failed: {result.stderr}")

        # Add mock backend to dispatcher set 99.
        result = _psql(
            f"INSERT INTO dispatcher (setid, destination, state, weight, priority, description) "
            f"VALUES (99, 'sip:{self.client_ip}:{self.mock_port}', 0, 1, 0, 'MockAsterisk') "
            f"ON CONFLICT DO NOTHING"
        )
        if result.returncode != 0:
            self.skipTest(f"DB insert failed: {result.stderr}")
        time.sleep(0.5)

    def tearDown(self):
        self.mock.stop()
        _psql("DELETE FROM sip_trunk_did_mappings WHERE did_number = '+15551234567'")
        _psql("DELETE FROM dispatcher WHERE description = 'MockAsterisk'")
        _psql("DELETE FROM sip_trunk_providers WHERE name = 'InboundTrunk'")

    def test_inbound_did_routing(self):
        """Unauthenticated INVITE from trunk IP is routed to correct tenant backend."""
        invite = _build_inbound_invite(
            did="+15551234567",
            domain="dev.tsisip.local",
            call_id="trunk-inb-inv-001@172.22.0.1",
            cseq=1,
            from_tag="inb001",
            branch="z9hG4bK-inb001",
        )
        responses = _send_and_collect(invite, timeout=5)
        self.assertTrue(
            any(b"SIP/2.0 200" in r for r in responses),
            f"Expected 200 OK, got: {responses}",
        )

    def test_forwarded_invite_has_x_tenant_id(self):
        """Forwarded INVITE to Asterisk backend contains X-Tenant-ID header."""
        self.mock.clear()
        invite = _build_inbound_invite(
            did="+15551234567",
            domain="dev.tsisip.local",
            call_id="trunk-inb-inv-002@172.22.0.1",
            cseq=1,
            from_tag="inb002",
            branch="z9hG4bK-inb002",
        )
        _send_and_collect(invite, timeout=3)

        time.sleep(0.5)
        messages = self.mock.get_messages()
        invite_messages = [m for m, _ in messages if b"INVITE" in m[:20]]
        self.assertTrue(
            len(invite_messages) > 0,
            "Mock backend did not receive the forwarded INVITE",
        )

        invite_text = invite_messages[0].decode("utf-8", errors="replace")
        self.assertIn(
            "X-Tenant-ID:",
            invite_text,
            "Forwarded INVITE missing X-Tenant-ID header",
        )


if __name__ == "__main__":
    unittest.main(verbosity=2)
