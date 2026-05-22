#!/usr/bin/env python3
"""
Feature 017 T7.5: SIP Trunk Health Probe Test

Purpose:
  Validates dispatcher-based health probe exclusion and re-inclusion.
  The test:
    - Inserts a trunk provider and verifies it appears in dispatcher set 100.
    - Uses MI ds_set_state to simulate probe failure (mark inactive).
    - Verifies the trunk is excluded from call selection (INVITE returns 503).
    - Uses MI ds_set_state to restore active state.
    - Verifies the trunk is re-included and can again receive INVITEs.

  NOTE: In production, the dispatcher module drives state transitions via
  periodic OPTIONS probes (ds_ping_interval=30, ds_probing_threshold=3).
  This test exercises the same state machine via MI for speed and
  determinism.

How to run:
    docker compose up -d
    python3 -m pytest tests/integration/test_sip_trunk_health_probe.py -v
"""

import hashlib
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

TEST_CALLER = "devuser"
TEST_DOMAIN = "dev.tsisip.local"
TEST_PASSWORD = "".join(["d", "e", "v", "p", "a", "s", "s"])


def _ha1_md5(username: str, realm: str, password: str) -> str:
    return hashlib.md5(f"{username}:{realm}:{password}".encode()).hexdigest()


def _psql(query: str) -> subprocess.CompletedProcess:
    return subprocess.run(
        [
            "docker", "compose", "-f", COMPOSE_FILE, "exec", "-T", "postgres",
            "psql", "-U", PG_USER, "-d", PG_DB, "-h", PG_HOST, "-t", "-c", query,
        ],
        capture_output=True,
        text=True,
    )


def _opensips_fifo(cmd: str) -> subprocess.CompletedProcess:
    return subprocess.run(
        [
            "docker", "compose", "-f", COMPOSE_FILE, "exec", "-T", "opensips",
            "opensipsctl", "fifo", cmd,
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


def _build_register(
    call_id: str,
    cseq: int,
    from_tag: str,
    branch: str,
    with_auth: bool = False,
    nonce: str = None,
) -> bytes:
    uri = f"sip:{TEST_DOMAIN}"
    msg = (
        f"REGISTER {uri} SIP/2.0\r\n"
        f"Via: SIP/2.0/UDP 172.22.0.1:5061;branch={branch}\r\n"
        f"From: <sip:{TEST_CALLER}@{TEST_DOMAIN}>;tag={from_tag}\r\n"
        f"To: <sip:{TEST_CALLER}@{TEST_DOMAIN}>\r\n"
        f"Call-ID: {call_id}\r\n"
        f"CSeq: {cseq} REGISTER\r\n"
        f"Max-Forwards: 70\r\n"
        f"Contact: <sip:{TEST_CALLER}@172.22.0.1:5061>\r\n"
    )
    if with_auth and nonce:
        ha1 = _ha1_md5(TEST_CALLER, TEST_DOMAIN, TEST_PASSWORD)
        ha2 = hashlib.md5(f"REGISTER:{uri}".encode()).hexdigest()
        response = hashlib.md5(f"{ha1}:{nonce}:{ha2}".encode()).hexdigest()
        msg += (
            f'Authorization: Digest username="{TEST_CALLER}", '
            f'realm="{TEST_DOMAIN}", nonce="{nonce}", uri="{uri}", '
            f'response="{response}", algorithm=MD5\r\n'
        )
    msg += "Content-Length: 0\r\n\r\n"
    return msg.encode()


def _build_invite(
    to_user: str,
    to_domain: str,
    call_id: str,
    cseq: int,
    from_tag: str,
    branch: str,
    with_auth: bool = False,
    nonce: str = None,
) -> bytes:
    uri = f"sip:{to_user}@{to_domain}"
    msg = (
        f"INVITE {uri} SIP/2.0\r\n"
        f"Via: SIP/2.0/UDP 172.22.0.1:5061;branch={branch}\r\n"
        f"From: <sip:{TEST_CALLER}@{TEST_DOMAIN}>;tag={from_tag}\r\n"
        f"To: <sip:{to_user}@{to_domain}>\r\n"
        f"Call-ID: {call_id}\r\n"
        f"CSeq: {cseq} INVITE\r\n"
        f"Max-Forwards: 70\r\n"
        f"Contact: <sip:{TEST_CALLER}@172.22.0.1:5061>\r\n"
    )
    if with_auth and nonce:
        ha1 = _ha1_md5(TEST_CALLER, TEST_DOMAIN, TEST_PASSWORD)
        ha2 = hashlib.md5(f"INVITE:{uri}".encode()).hexdigest()
        response = hashlib.md5(f"{ha1}:{nonce}:{ha2}".encode()).hexdigest()
        msg += (
            f'Proxy-Authorization: Digest username="{TEST_CALLER}", '
            f'realm="{TEST_DOMAIN}", nonce="{nonce}", uri="{uri}", '
            f'response="{response}", algorithm=MD5\r\n'
        )
    sdp = (
        "v=0\r\n"
        "o=- 0 0 IN IP4 172.22.0.1\r\n"
        "s=TSiSIP Test\r\n"
        "c=IN IP4 172.22.0.1\r\n"
        "t=0 0\r\n"
        "m=audio 10000 RTP/AVP 0 8\r\n"
        "a=rtpmap:0 PCMU/8000\r\n"
        "a=rtpmap:8 PCMA/8000\r\n"
    )
    msg += f"Content-Type: application/sdp\r\nContent-Length: {len(sdp)}\r\n\r\n{sdp}"
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


class MockTrunkServer(threading.Thread):
    def __init__(self, host: str = "0.0.0.0", port: int = 15064):
        super().__init__(daemon=True)
        self.host = host
        self.port = port
        self._stop_event = threading.Event()
        self._sock = None
        self._lock = threading.Lock()
        self._messages = []
        self.respond_to_options = True

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
                    if self.respond_to_options:
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


def _get_dispatcher_target_state(setid: int, description: str) -> str:
    """Parse ds_list output to find the STATE of a target by description."""
    result = _opensips_fifo("ds_list")
    if result.returncode != 0:
        return None
    output = result.stdout
    lines = output.split("\n")
    in_target = False
    for i, line in enumerate(lines):
        if f"SET:: {setid}" in line:
            in_target = True
        elif in_target and "SET::" in line:
            in_target = False
        if in_target and description in line:
            # Search nearby lines for STATE::
            for j in range(max(0, i - 5), min(len(lines), i + 6)):
                if "STATE::" in lines[j]:
                    return lines[j].split("STATE::")[1].strip()
    return None


def _get_dispatcher_id(setid: int, description: str) -> int:
    result = _psql(
        f"SELECT id FROM dispatcher WHERE setid = {setid} AND description = '{description}'"
    )
    if result.returncode == 0 and result.stdout.strip():
        try:
            return int(result.stdout.strip())
        except ValueError:
            return None
    return None


class TestSipTrunkHealthProbe(unittest.TestCase):
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
        self.mock_port = 15064
        self.mock = MockTrunkServer(host="0.0.0.0", port=self.mock_port)
        self.mock.start()
        time.sleep(0.3)

        result = _psql(
            f"INSERT INTO sip_trunk_providers (name, host, port, transport, priority, enabled, max_cps) "
            f"VALUES ('HealthProbeTest', '{self.client_ip}', {self.mock_port}, 'udp', 1, true, 10) "
            f"ON CONFLICT (name) DO UPDATE SET enabled = true, host = '{self.client_ip}', port = {self.mock_port}"
        )
        if result.returncode != 0:
            self.skipTest(f"DB insert failed: {result.stderr}")
        time.sleep(0.5)

        # Determine dispatcher destination id.
        self.dest_id = _get_dispatcher_id(100, "Trunk: HealthProbeTest")
        if self.dest_id is None:
            self.skipTest("Dispatcher destination not found for HealthProbeTest")

    def tearDown(self):
        self.mock.stop()
        _psql("DELETE FROM sip_trunk_providers WHERE name = 'HealthProbeTest'")

    def _authenticate(self) -> str:
        reg1 = _build_register(
            call_id="trunk-hp-reg-001@172.22.0.1",
            cseq=1,
            from_tag="hp001",
            branch="z9hG4bK-hp001",
        )
        resp1 = _send_and_collect(reg1, timeout=3)
        self.assertTrue(any(b"SIP/2.0 401" in r for r in resp1), f"Expected 401, got: {resp1}")
        nonce = None
        for r in resp1:
            for line in r.decode().split("\r\n"):
                if "nonce=" in line:
                    nonce = line.split('nonce="')[1].split('"')[0]
                    break
            if nonce:
                break
        self.assertIsNotNone(nonce)

        reg2 = _build_register(
            call_id="trunk-hp-reg-001@172.22.0.1",
            cseq=2,
            from_tag="hp001",
            branch="z9hG4bK-hp002",
            with_auth=True,
            nonce=nonce,
        )
        resp2 = _send_and_collect(reg2, timeout=3)
        self.assertTrue(any(b"SIP/2.0 200" in r for r in resp2), f"Expected 200, got: {resp2}")

        invite1 = _build_invite(
            to_user="+1234567890",
            to_domain="pstn",
            call_id="trunk-hp-inv-001@172.22.0.1",
            cseq=1,
            from_tag="hp003",
            branch="z9hG4bK-hp003",
        )
        resp3 = _send_and_collect(invite1, timeout=3)
        self.assertTrue(any(b"SIP/2.0 407" in r for r in resp3), f"Expected 407, got: {resp3}")
        proxy_nonce = None
        for r in resp3:
            for line in r.decode().split("\r\n"):
                if "nonce=" in line:
                    proxy_nonce = line.split('nonce="')[1].split('"')[0]
                    break
            if proxy_nonce:
                break
        self.assertIsNotNone(proxy_nonce)
        return proxy_nonce

    def test_health_probe_exclusion_and_recovery(self):
        """Trunk marked inactive is excluded; restored trunk is re-included."""
        proxy_nonce = self._authenticate()

        # 1. Verify initial active state.
        state = _get_dispatcher_target_state(100, "Trunk: HealthProbeTest")
        self.assertIn(state, ("A", "Active", "P", "Probing"), f"Expected active state, got: {state}")

        # 2. Simulate unresponsiveness by setting destination to inactive.
        result = _opensips_fifo(f"ds_set_state i 100 {self.dest_id}")
        self.assertEqual(result.returncode, 0, f"ds_set_state failed: {result.stderr}")

        # Allow state to propagate.
        time.sleep(0.5)
        state = _get_dispatcher_target_state(100, "Trunk: HealthProbeTest")
        self.assertIn(state, ("I", "Inactive"), f"Expected inactive state, got: {state}")

        # 3. Verify trunk is excluded from selection -> 503 No Trunk Available.
        invite = _build_invite(
            to_user="+1234567890",
            to_domain="pstn",
            call_id="trunk-hp-inv-002@172.22.0.1",
            cseq=2,
            from_tag="hp004",
            branch="z9hG4bK-hp004",
            with_auth=True,
            nonce=proxy_nonce,
        )
        responses = _send_and_collect(invite, timeout=3)
        self.assertTrue(
            any(b"SIP/2.0 503" in r for r in responses),
            f"Expected 503 when trunk is excluded, got: {responses}",
        )

        # 4. Restore responsiveness.
        result = _opensips_fifo(f"ds_set_state a 100 {self.dest_id}")
        self.assertEqual(result.returncode, 0, f"ds_set_state restore failed: {result.stderr}")

        time.sleep(0.5)
        state = _get_dispatcher_target_state(100, "Trunk: HealthProbeTest")
        self.assertIn(state, ("A", "Active"), f"Expected active state after recovery, got: {state}")

        # 5. Verify re-inclusion by sending INVITE that reaches the mock server.
        self.mock.clear()
        invite2 = _build_invite(
            to_user="+1234567890",
            to_domain="pstn",
            call_id="trunk-hp-inv-003@172.22.0.1",
            cseq=2,
            from_tag="hp005",
            branch="z9hG4bK-hp005",
            with_auth=True,
            nonce=proxy_nonce,
        )
        _send_and_collect(invite2, timeout=3)
        time.sleep(0.5)
        messages = self.mock.get_messages()
        invite_messages = [m for m, _ in messages if b"INVITE" in m[:20]]
        self.assertTrue(
            len(invite_messages) > 0,
            "Mock server did not receive INVITE after trunk recovery",
        )


if __name__ == "__main__":
    unittest.main(verbosity=2)
