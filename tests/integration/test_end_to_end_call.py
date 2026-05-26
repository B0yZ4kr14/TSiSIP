"""
Feature 007 Integration Tests: End-to-End SIP Call Flow
Validates: OPTIONS -> 200, REGISTER -> 401, INVITE -> 407, dispatcher routing

Uses sipsak inside temporary Alpine containers with unique static IPs on the
sip_edge Docker network. Unique IPs prevent ban_list / auth_throttle collisions
between test runs.
"""
import os
import pytest
import subprocess


COMPOSE_FILE = os.environ.get("COMPOSE_FILE", "docker-compose.yml")
# Unique static IPs for each test to avoid OpenSIPS state collisions
_TEST_IPS = ["172.24.0.100", "172.24.0.101", "172.24.0.102", "172.24.0.103"]
_test_ip_index = 0


def _next_test_ip() -> str:
    """Return the next unique static IP for a test container."""
    global _test_ip_index
    ip = _TEST_IPS[_test_ip_index % len(_TEST_IPS)]
    _test_ip_index += 1
    return ip


def _psql(sql: str) -> tuple[int, str, str]:
    """Run a SQL query inside the postgres container."""
    r = subprocess.run(
        [
            "docker", "compose", "-f", COMPOSE_FILE, "exec", "-T", "postgres",
            "psql", "-U", "opensips", "-d", "opensips", "-tAc", sql,
        ],
        capture_output=True, text=True,
    )
    return r.returncode, r.stdout, r.stderr


def _mi_rpc(method: str, params: list = None) -> dict:
    """Execute an OpenSIPS MI JSON-RPC command."""
    import json
    payload = {"jsonrpc": "2.0", "method": method, "id": 1}
    if params is not None:
        payload["params"] = params
    r = subprocess.run(
        [
            "docker", "compose", "-f", COMPOSE_FILE, "exec", "-T", "opensips",
            "curl", "-s", "-X", "POST",
            "-H", "Content-Type: application/json",
            "-d", json.dumps(payload),
            "http://localhost:8888/mi/",
        ],
        capture_output=True, text=True,
    )
    try:
        return json.loads(r.stdout)
    except json.JSONDecodeError:
        return {"error": f"invalid JSON: {r.stdout}"}


def _clear_ban(ip: str):
    """Remove cachedb_local ban_list entry for a specific IP."""
    _mi_rpc("cache_remove", ["local", f"ban_list_{ip}"])


def _run_sipsak_with_ip(ip: str, args: list, timeout: int = 10) -> subprocess.CompletedProcess:
    """Run sipsak inside a temporary Alpine container with a fixed IP."""
    r = subprocess.run(
        [
            "docker", "run", "--rm", "--network", "tsisip_sip_edge", "--ip", ip,
            "alpine",
            "sh", "-c",
            f"apk add --no-cache sipsak >/dev/null 2>&1 && sipsak {' '.join(args)}",
        ],
        capture_output=True, text=True, timeout=timeout,
    )
    return r


class TestEndToEndCall:
    """End-to-end SIP call flow through OpenSIPS to Asterisk backend."""

    def test_options_health_check(self):
        """OPTIONS probe returns 200 OK from OpenSIPS."""
        ip = _next_test_ip()
        _clear_ban(ip)
        r = _run_sipsak_with_ip(ip, ["-s", "sip:opensips:5060", "-vv"])
        combined = r.stdout + r.stderr
        assert "SIP/2.0 200 OK" in combined, f"Expected 200 OK, got stdout={r.stdout[:200]} stderr={r.stderr[:200]}"
        assert "Server: OpenSIPS" in combined

    def test_dispatcher_table_has_backends(self):
        """Dispatcher table contains Asterisk backend entries."""
        rc, out, err = _psql("SELECT COUNT(*) FROM dispatcher WHERE setid = 1")
        assert rc == 0, f"Query failed: {err}"
        count = int(out.strip())
        assert count >= 2, f"Expected >=2 backends, got {count}"

    def test_register_unauthorized(self):
        """Unauthenticated REGISTER receives 401 with nonce."""
        ip = _next_test_ip()
        _clear_ban(ip)
        r = _run_sipsak_with_ip(ip, ["-U", "-s", "sip:devuser@opensips:5060", "-vv"])
        combined = r.stdout + r.stderr
        assert "SIP/2.0 401 Unauthorized" in combined, \
            f"Expected 401, got stdout={r.stdout[:200]} stderr={r.stderr[:200]}"
        assert "WWW-Authenticate" in combined or "Proxy-Authenticate" in combined

    def test_invite_unauthorized(self):
        """Unauthenticated INVITE receives 407 Proxy Authentication Required."""
        ip = _next_test_ip()
        _clear_ban(ip)
        r = subprocess.run(
            [
                "docker", "run", "--rm", "--network", "tsisip_sip_edge", "--ip", ip,
                "alpine", "sh", "-c",
                (
                    "apk add --no-cache sipsak >/dev/null 2>&1 && "
                    "cat > /tmp/invite.txt << 'EOF'\n"
                    f"INVITE sip:1000@opensips:5060 SIP/2.0\n"
                    f"Via: SIP/2.0/UDP {ip}:5061;branch=z9hG4bK-inv001\n"
                    "From: <sip:devuser@opensips:5060>;tag=invtag001\n"
                    "To: <sip:1000@opensips:5060>\n"
                    "Call-ID: test-invite-auth-001@tsisip\n"
                    "CSeq: 1 INVITE\n"
                    "Max-Forwards: 70\n"
                    f"Contact: <sip:devuser@{ip}:5061>\n"
                    "Content-Length: 0\n"
                    "EOF\n"
                    "sipsak -f /tmp/invite.txt -s sip:1000@opensips:5060 -vv"
                ),
            ],
            capture_output=True, text=True, timeout=10,
        )
        combined = r.stdout + r.stderr
        assert "SIP/2.0 407 Proxy Authentication Required" in combined, \
            f"Expected 407, got stdout={r.stdout[:200]} stderr={r.stderr[:200]}"
        assert "Proxy-Authenticate" in combined

    def test_register_authenticated_routes(self):
        """Authenticated REGISTER reaches Asterisk (may return 503 if no AOR).

        Skipped in CI because proper Digest authentication requires the
        client realm to match the subscriber domain (dev.tsisip.local).
        """
        pytest.skip("Requires DNS resolution of dev.tsisip.local to OpenSIPS IP")

    def test_invite_authenticated_routes(self):
        """Authenticated INVITE with SDP is relayed to Asterisk backend.

        Skipped in CI because proper Digest authentication requires the
        client realm to match the subscriber domain (dev.tsisip.local).
        """
        pytest.skip("Requires DNS resolution of dev.tsisip.local to OpenSIPS IP")


if __name__ == "__main__":
    pytest.main([__file__, "-v"])
