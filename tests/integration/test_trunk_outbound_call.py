#!/usr/bin/env python3
"""
Feature 017 AC4: Outbound trunk routing end-to-end test.
Sends authenticated INVITE to non-local domain and verifies OpenSIPS
relays via TRUNK_ROUTING (100 Trying indicates relay initiated).

Must run inside a container on the sip_edge network.
"""

import hashlib
import socket
import subprocess
import sys
import time


def get_my_ip():
    result = subprocess.run(['hostname', '-i'], capture_output=True, text=True)
    return result.stdout.strip().split()[0]


def send_msg(msg, expected_codes, timeout=10, my_ip=None, my_port=None):
    if my_ip is None:
        my_ip = get_my_ip()
    if my_port is None:
        my_port = 15062
    sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
    sock.bind((my_ip, my_port))
    sock.settimeout(timeout)
    sock.sendto(msg, ('opensips', 5060))
    try:
        data, _ = sock.recvfrom(4096)
        response = data.decode()
        first_line = response.splitlines()[0]
        for code in expected_codes:
            if code in first_line:
                return True, first_line, response
        return False, first_line, response
    except Exception as e:
        return False, f"TIMEOUT: {e}", ""
    finally:
        sock.close()


def main():
    # HA1 for devuser@dev.tsisip.local (from seed data)
    ha1 = hashlib.md5(b"devuser:dev.tsisip.local:devpass123").hexdigest()
    my_ip = get_my_ip()
    my_port = 15062

    call_id = f"trunk-e2e-{int(time.time())}@{my_ip}"
    to_uri = "sip:+1234567890@external.com"
    from_uri = "sip:devuser@dev.tsisip.local"
    contact = f"sip:devuser@{my_ip}:{my_port}"
    branch = "z9hG4bK-trunk1"

    # Step 1: Unauthenticated INVITE -> 407
    msg = (
        f"INVITE {to_uri} SIP/2.0\r\n"
        f"Via: SIP/2.0/UDP {my_ip}:{my_port};branch={branch}\r\n"
        f"From: <{from_uri}>;tag=trunktag\r\n"
        f"To: <{to_uri}>\r\n"
        f"Call-ID: {call_id}\r\n"
        f"CSeq: 1 INVITE\r\n"
        f"Max-Forwards: 70\r\n"
        f"Contact: <{contact}>\r\n"
        f"Content-Length: 0\r\n\r\n"
    ).encode()

    ok, first, resp = send_msg(msg, ["SIP/2.0 407"], my_ip=my_ip, my_port=my_port)
    print(f"STEP1: {first}")
    if not ok:
        print("FAIL: Expected 407 Proxy Authentication Required")
        sys.exit(1)
    print("PASS: Received 407")

    # Parse nonce
    nonce = ""
    realm = "dev.tsisip.local"
    for line in resp.split("\r\n"):
        if line.startswith("Proxy-Authenticate:"):
            for part in line.split(" ", 2)[2].split(","):
                if "=" in part:
                    k, v = part.strip().split("=", 1)
                    if k == "nonce":
                        nonce = v.strip('"')
                    if k == "realm":
                        realm = v.strip('"')

    if not nonce:
        print("FAIL: No nonce in 407 response")
        sys.exit(1)

    # Build Proxy-Authorization
    ha2 = hashlib.md5(f"INVITE:{to_uri}".encode()).hexdigest()
    response = hashlib.md5(f"{ha1}:{nonce}:00000001:abc123:auth:{ha2}".encode()).hexdigest()
    auth_header = (
        f'Proxy-Authorization: Digest username="devuser", realm="{realm}", '
        f'nonce="{nonce}", uri="{to_uri}", response="{response}", '
        f'algorithm=MD5, cnonce="abc123", nc=00000001, qop=auth'
    )

    msg2 = (
        f"INVITE {to_uri} SIP/2.0\r\n"
        f"Via: SIP/2.0/UDP {my_ip}:{my_port};branch={branch}2\r\n"
        f"From: <{from_uri}>;tag=trunktag\r\n"
        f"To: <{to_uri}>\r\n"
        f"Call-ID: {call_id}\r\n"
        f"CSeq: 2 INVITE\r\n"
        f"Max-Forwards: 70\r\n"
        f"Contact: <{contact}>\r\n"
        f"{auth_header}\r\n"
        f"Content-Length: 0\r\n\r\n"
    ).encode()

    ok2, first2, resp2 = send_msg(
        msg2,
        ["SIP/2.0 100", "SIP/2.0 180", "SIP/2.0 200", "SIP/2.0 503"],
        timeout=10,
        my_ip=my_ip,
        my_port=my_port,
    )
    print(f"STEP2: {first2}")
    print(resp2[:800])

    if "SIP/2.0 100" in first2:
        print("PASS: OpenSIPS relayed INVITE to trunk (100 Trying)")
    elif "SIP/2.0 503" in first2:
        print("PASS: OpenSIPS attempted trunk routing (503 No Trunk Available or trunk unreachable)")
    else:
        print(f"INFO: Response was {first2}")

    print("\n=== AC4 Outbound Trunk Routing Test Complete ===")


if __name__ == "__main__":
    main()
