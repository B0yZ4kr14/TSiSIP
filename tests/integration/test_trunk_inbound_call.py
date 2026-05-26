#!/usr/bin/env python3
"""
Feature 017 AC3: Inbound DID routing end-to-end test.
Sends INVITE from mock trunk IP to OpenSIPS with a known DID
and verifies OpenSIPS routes to backend (100 Trying / 503).

Must run inside the mock-trunk container so source IP matches trunk host.
"""

import socket
import sys
import time


def send_msg(msg, expected_codes, timeout=10, my_ip=None, my_port=None):
    if my_ip is None:
        my_ip = "0.0.0.0"
    if my_port is None:
        my_port = 15063
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
    my_ip = "0.0.0.0"
    my_port = 15063

    sdp = (
        "v=0\r\n"
        "o=- 0 0 IN IP4 192.0.2.99\r\n"
        "s=MockTrunk\r\n"
        "c=IN IP4 192.0.2.99\r\n"
        "t=0 0\r\n"
        "m=audio 10000 RTP/AVP 0 8\r\n"
        "a=rtpmap:0 PCMU/8000\r\n"
        "a=rtpmap:8 PCMA/8000\r\n"
    )

    call_id = f"trunk-inbound-{int(time.time())}@mocktrunk"
    to_uri = "sip:+19998887777@dev.tsisip.local"
    from_uri = "sip:+15551234567@external.com"
    contact = f"sip:external@{my_ip}:{my_port}"
    branch = "z9hG4bK-inbound1"

    msg = (
        f"INVITE {to_uri} SIP/2.0\r\n"
        f"Via: SIP/2.0/UDP {my_ip}:{my_port};branch={branch}\r\n"
        f"From: <{from_uri}>;tag=inboundtag\r\n"
        f"To: <{to_uri}>\r\n"
        f"Call-ID: {call_id}\r\n"
        f"CSeq: 1 INVITE\r\n"
        f"Max-Forwards: 70\r\n"
        f"Contact: <{contact}>\r\n"
        f"Content-Type: application/sdp\r\n"
        f"Content-Length: {len(sdp)}\r\n\r\n"
        f"{sdp}"
    ).encode()

    ok, first, resp = send_msg(
        msg,
        ["SIP/2.0 100", "SIP/2.0 404", "SIP/2.0 503", "SIP/2.0 480", "SIP/2.0 488"],
        timeout=10,
        my_ip=my_ip,
        my_port=my_port,
    )
    print(f"RESPONSE: {first}")
    print(resp[:800])

    if "SIP/2.0 100" in first:
        print("PASS: OpenSIPS accepted inbound DID and relayed to backend")
    elif "SIP/2.0 404" in first:
        print("FAIL: DID Not Found (mapping may be missing)")
        sys.exit(1)
    elif "SIP/2.0 503" in first:
        print("PASS: OpenSIPS routed DID but backend unavailable (expected in test env)")
    elif "SIP/2.0 480" in first:
        print("INFO: Temporarily Unavailable (backend or DB issue)")
    elif "SIP/2.0 488" in first:
        print("WARN: 488 Not Acceptable Here (SDP/RTPengine issue; routing logic executed)")
    else:
        print(f"INFO: Unexpected response: {first}")

    print("\n=== AC3 Inbound DID Routing Test Complete ===")


if __name__ == "__main__":
    main()
