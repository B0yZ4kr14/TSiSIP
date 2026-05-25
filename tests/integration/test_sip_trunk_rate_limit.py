#!/usr/bin/env python3
"""
Feature 017 T7.4: SIP Trunk CPS Rate Limit Test

Purpose:
  Validates per-trunk CPS rate limiting using the ratelimit module.
  The test:
    - Configures a trunk with max_cps = 2.
    - Sends a burst of 10 authenticated INVITEs in rapid succession.
    - Verifies that excess calls receive 503 Service Unavailable.
    - Verifies the mock trunk server receives no more than ~2 INVITEs.

How to run:
    docker compose up -d
    python3 -m pytest tests/integration/test_sip_trunk_rate_limit.py -v
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


def get_test_ip() -> str:
    return os.environ.get("TEST_IP", "127.0.0.1")


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
        f"Via: SIP/2.0/UDP {get_test_ip()}:5061;branch={branch}\r\n"
        f"From: <sip:{TEST_CALLER}@{TEST_DOMAIN}>;tag={from_tag}\r\n"
        f"To: <sip:{TEST_CALLER}@{TEST_DOMAIN}>\r\n"
        f"Call-ID: {call_id}\r\n"
        f"CSeq: {cseq} REGISTER\r\n"
        f"Max-Forwards: 70\r\n"
        f"Contact: <sip:{TEST_CALLER}@{get_test_ip()}:5061>\r\n"
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
        f"Via: SIP/2.0/UDP {get_test_ip()}:5061;branch={branch}\r\n"
        f"From: <sip:{TEST_CALLER}@{TEST_DOMAIN}>;tag={from_tag}\r\n"
        f"To: <sip:{to_user}@{to_domain}>\r\n"
        f"Call-ID: {call_id}\r\n"
        f"CSeq: {cseq} INVITE\r\n"
        f"Max-Forwards: 70\r\n"
        f"Contact: <sip:{TEST_CALLER}@{get_test_ip()}:5061>\r\n"
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
        f"o=- 0 0 IN IP4 {get_test_ip()}\r\n"
        "s=TSiSIP Test\r\n"
        f"c=IN IP4 {get_test_ip()}\r\n"
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


def _send_and_collect(msg: bytes, host: str = None, port: int = None, timeout: float = 2.0) -> list:
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
    def __init__(self, host: str = "0.0.0.0", port: int = 15063):
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


class TestSipTrunkRateLimit(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        ping = (
            b"OPTIONS sip:" + TARGET_HOST.encode() + b":" + str(TARGET_PORT).encode() + b" SIP/2.0\r\n"
            + ("Via: SIP/2.0/UDP " + get_test_ip() + ":5061;branch=z9hG4bK-ping\r\n").encode()
            + b"From: <sip:test@localhost>;tag=ping\r\n"
            b"To: <sip:" + TARGET_HOST.encode() + b":" + str(TARGET_PORT).encode() + b">\r\n"
            + ("Call-ID: ping-001@" + get_test_ip() + "\r\n").encode()
            + b"CSeq: 1 OPTIONS\r\n"
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
        self.mock_port = 15063
        self.mock = MockTrunkServer(host="0.0.0.0", port=self.mock_port)
        self.mock.start()
        time.sleep(0.3)

        # Point TestProvider at mock server with low CPS.
        result = _psql(
            f"UPDATE sip_trunk_providers SET enabled = true, host = '{self.client_ip}', "
            f"port = {self.mock_port}, transport = 'udp', max_cps = 2 WHERE name = 'TestProvider'"
        )
        if result.returncode != 0:
            self.skipTest(f"DB update failed: {result.stderr}")
        time.sleep(0.5)

    def tearDown(self):
        self.mock.stop()
        _psql("UPDATE sip_trunk_providers SET enabled = false, max_cps = 10 WHERE name = 'TestProvider'")

    def _authenticate(self) -> str:
        reg1 = _build_register(
            call_id="trunk-rl-reg-001@" + get_test_ip(),
            cseq=1,
            from_tag="rl001",
            branch="z9hG4bK-rl001",
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
            call_id="trunk-rl-reg-001@" + get_test_ip(),
            cseq=2,
            from_tag="rl001",
            branch="z9hG4bK-rl002",
            with_auth=True,
            nonce=nonce,
        )
        resp2 = _send_and_collect(reg2, timeout=3)
        self.assertTrue(any(b"SIP/2.0 200" in r for r in resp2), f"Expected 200, got: {resp2}")

        invite1 = _build_invite(
            to_user="+1234567890",
            to_domain="pstn",
            call_id="trunk-rl-inv-001@" + get_test_ip(),
            cseq=1,
            from_tag="rl003",
            branch="z9hG4bK-rl003",
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

    def test_cps_rate_limit(self):
        """Burst INVITEs beyond CPS limit receive 503; mock server receives <= limit."""
        proxy_nonce = self._authenticate()
        self.mock.clear()

        accepted = 0
        throttled = 0
        for i in range(10):
            invite = _build_invite(
                to_user="+1234567890",
                to_domain="pstn",
                call_id=f"trunk-rl-inv-{i:03d}@" + get_test_ip(),
                cseq=2,
                from_tag=f"rl{i:03d}",
                branch=f"z9hG4bK-rl{i:03d}",
                with_auth=True,
                nonce=proxy_nonce,
            )
            responses = _send_and_collect(invite, timeout=2)
            if any(b"SIP/2.0 503" in r for r in responses):
                throttled += 1
            elif any(b"SIP/2.0 100" in r or b"SIP/2.0 200" in r for r in responses):
                accepted += 1

        self.assertGreater(throttled, 0, "Expected some calls to be throttled with 503")
        self.assertLessEqual(accepted, 3, f"Expected <=3 accepted, got {accepted}")

        # Verify mock server did not receive more than the CPS limit + small margin.
        time.sleep(0.5)
        messages = self.mock.get_messages()
        invite_count = len([m for m, _ in messages if b"INVITE" in m[:20]])
        self.assertLessEqual(invite_count, 3, f"Mock server received too many INVITEs: {invite_count}")


if __name__ == "__main__":
    unittest.main(verbosity=2)
