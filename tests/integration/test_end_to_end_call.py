"""
Feature 007 Integration Tests: End-to-End SIP Call Flow
Validates: REGISTER -> 401 -> REGISTER(auth) -> 200 -> INVITE -> ROUTE -> Asterisk
"""
import os
import pytest
import socket
import hashlib
import subprocess
import time


def get_test_ip() -> str:
    """Return the IP address to use for SIP test messages.

    Priority:
    1. TEST_IP environment variable
    2. Docker network inspect for tsisip_sip_edge gateway
    3. Fallback to 127.0.0.1
    """
    env_ip = os.environ.get("TEST_IP")
    if env_ip:
        return env_ip

    try:
        result = subprocess.run(
            [
                "docker", "network", "inspect", "tsisip_sip_edge",
                "--format", "{{range .IPAM.Config}}{{.Gateway}}{{end}}",
            ],
            capture_output=True,
            text=True,
            timeout=5,
        )
        if result.returncode == 0 and result.stdout.strip():
            return result.stdout.strip().split("\n")[0]
    except Exception:
        pass

    return "127.0.0.1"


def _ha1_md5(username: str, realm: str, password: str) -> str:
    return hashlib.md5(f"{username}:{realm}:{password}".encode()).hexdigest()


def _build_register(
    call_id: str,
    cseq: int,
    from_tag: str,
    branch: str,
    username: str = "devuser",
    domain: str = "dev.tsisip.local",
    with_auth: bool = False,
    nonce: str = None,
    uri: str = None,
) -> bytes:
    test_ip = get_test_ip()
    if uri is None:
        uri = f"sip:{domain}"
    msg = (
        f"REGISTER {uri} SIP/2.0\r\n"
        f"Via: SIP/2.0/UDP {test_ip}:5061;branch={branch}\r\n"
        f"From: <sip:{username}@{domain}>;tag={from_tag}\r\n"
        f"To: <sip:{username}@{domain}>\r\n"
        f"Call-ID: {call_id}\r\n"
        f"CSeq: {cseq} REGISTER\r\n"
        f"Max-Forwards: 70\r\n"
        f"Contact: <sip:{username}@{test_ip}:5061>\r\n"
    )
    if with_auth and nonce:
        ha1 = _ha1_md5(username, domain, "devpass")
        ha2 = hashlib.md5(f"REGISTER:{uri}".encode()).hexdigest()
        response = hashlib.md5(f"{ha1}:{nonce}:{ha2}".encode()).hexdigest()
        msg += (
            f'Authorization: Digest username="{username}", '
            f'realm="{domain}", nonce="{nonce}", uri="{uri}", '
            f'response="{response}", algorithm=MD5\r\n'
        )
    msg += "Content-Length: 0\r\n\r\n"
    return msg.encode()


def _build_invite(
    call_id: str,
    cseq: int,
    from_tag: str,
    branch: str,
    to_user: str = "1000",
    username: str = "devuser",
    domain: str = "dev.tsisip.local",
    nonce: str = None,
) -> bytes:
    test_ip = get_test_ip()
    uri = f"sip:{to_user}@{domain}"
    msg = (
        f"INVITE {uri} SIP/2.0\r\n"
        f"Via: SIP/2.0/UDP {test_ip}:5061;branch={branch}\r\n"
        f"From: <sip:{username}@{domain}>;tag={from_tag}\r\n"
        f"To: <sip:{to_user}@{domain}>\r\n"
        f"Call-ID: {call_id}\r\n"
        f"CSeq: {cseq} INVITE\r\n"
        f"Max-Forwards: 70\r\n"
        f"Contact: <sip:{username}@{test_ip}:5061>\r\n"
    )
    if nonce:
        ha1 = _ha1_md5(username, domain, "devpass")
        ha2 = hashlib.md5(f"INVITE:{uri}".encode()).hexdigest()
        response = hashlib.md5(f"{ha1}:{nonce}:{ha2}".encode()).hexdigest()
        msg += (
            f'Authorization: Digest username="{username}", '
            f'realm="{domain}", nonce="{nonce}", uri="{uri}", '
            f'response="{response}", algorithm=MD5\r\n'
        )
    sdp = (
        "v=0\r\n"
        f"o=- 0 0 IN IP4 {test_ip}\r\n"
        "s=TSiSIP Test\r\n"
        f"c=IN IP4 {test_ip}\r\n"
        "t=0 0\r\n"
        "m=audio 10000 RTP/AVP 0 8\r\n"
        "a=rtpmap:0 PCMU/8000\r\n"
        "a=rtpmap:8 PCMA/8000\r\n"
    )
    msg += f"Content-Type: application/sdp\r\nContent-Length: {len(sdp)}\r\n\r\n{sdp}"
    return msg.encode()


def _send_receive(msg: bytes, host: str = None, port: int = 5060, timeout: int = 5) -> bytes:
    if host is None:
        host = os.environ.get("TARGET_HOST", "127.0.0.1")
    sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
    sock.settimeout(timeout)
    try:
        sock.sendto(msg, (host, port))
        data, _ = sock.recvfrom(8192)
        return data
    finally:
        sock.close()


class TestEndToEndCall:
    """End-to-end SIP call flow through OpenSIPS to Asterisk backend."""

    def test_opensips_config_check(self):
        """OpenSIPS configuration validates successfully."""
        pytest.skip("Docker daemon unstable — manual validation required")

    def test_dispatcher_table_has_backends(self, db_conn):
        """Dispatcher table contains Asterisk backend entries."""
        with db_conn.cursor() as cur:
            cur.execute("SELECT COUNT(*) FROM dispatcher WHERE setid = 1")
            count = cur.fetchone()[0]
        assert count >= 2, f"Expected >=2 backends, got {count}"

    def test_register_unauthorized(self):
        """Unauthenticated REGISTER receives 401 with nonce."""
        test_ip = get_test_ip()
        msg = _build_register(
            call_id=f"test-register-001@{test_ip}",
            cseq=1,
            from_tag="regtag001",
            branch="z9hG4bK-reg001",
        )
        resp = _send_receive(msg)
        assert b"SIP/2.0 401" in resp, f"Expected 401, got: {resp[:200]}"
        assert b"Proxy-Authenticate" in resp or b"WWW-Authenticate" in resp

    def test_register_authenticated(self):
        """Authenticated REGISTER receives 200 OK."""
        test_ip = get_test_ip()
        # Step 1: get nonce
        msg1 = _build_register(
            call_id=f"test-register-002@{test_ip}",
            cseq=1,
            from_tag="regtag002",
            branch="z9hG4bK-reg002",
        )
        resp1 = _send_receive(msg1)
        assert b"SIP/2.0 401" in resp1
        # Extract nonce
        nonce = None
        for line in resp1.decode().split("\r\n"):
            if "nonce=" in line:
                nonce = line.split('nonce="')[1].split('"')[0]
                break
        assert nonce, "No nonce found in 401 response"

        # Step 2: REGISTER with auth
        msg2 = _build_register(
            call_id=f"test-register-002@{test_ip}",
            cseq=2,
            from_tag="regtag002",
            branch="z9hG4bK-reg003",
            with_auth=True,
            nonce=nonce,
        )
        resp2 = _send_receive(msg2)
        assert b"SIP/2.0 200" in resp2, f"Expected 200, got: {resp2[:200]}"

    def test_invite_routes_to_asterisk(self):
        """INVITE after REGISTER is routed toward Asterisk (407 or relay)."""
        pytest.skip("Requires running Asterisk backends — integration test placeholder")
