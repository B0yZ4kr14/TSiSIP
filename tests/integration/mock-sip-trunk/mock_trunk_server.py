#!/usr/bin/env python3
"""
Mock SIP Trunk Provider for Feature 017 integration testing.
Responds to OPTIONS with 200 OK and INVITE with 100/180/200 sequence.
Usage: python3 mock_trunk_server.py --host 0.0.0.0 --port 5060
"""

import argparse
import socket
import threading
import time
import re


def parse_sip_message(data: bytes) -> tuple:
    """Parse first line and headers from SIP message."""
    try:
        lines = data.decode('utf-8', errors='replace').split('\r\n')
        first_line = lines[0]
        headers = {}
        for line in lines[1:]:
            if ':' in line:
                k, v = line.split(':', 1)
                headers[k.strip().lower()] = v.strip()
        return first_line, headers
    except Exception:
        return None, {}


def build_response(request_line: str, headers: dict, status_code: int, reason: str, body: str = "") -> bytes:
    via = headers.get('via', '')
    from_hdr = headers.get('from', '')
    to_hdr = headers.get('to', '')
    call_id = headers.get('call-id', '')
    cseq = headers.get('cseq', '')

    # Add tag to To header for 200 OK responses
    if status_code == 200 and 'tag=' not in to_hdr:
        to_hdr += ';tag=mocktrunk123'

    response = f"SIP/2.0 {status_code} {reason}\r\n"
    response += f"Via: {via}\r\n"
    response += f"From: {from_hdr}\r\n"
    response += f"To: {to_hdr}\r\n"
    response += f"Call-ID: {call_id}\r\n"
    response += f"CSeq: {cseq}\r\n"
    response += f"Content-Length: {len(body)}\r\n"
    if body:
        response += "Content-Type: application/sdp\r\n"
    response += "Server: MockTrunk/1.0\r\n"
    response += "\r\n"
    response += body
    return response.encode('utf-8')


def handle_request(sock: socket.socket, data: bytes, addr: tuple, args):
    request_line, headers = parse_sip_message(data)
    if not request_line:
        return

    print(f"[{addr[0]}:{addr[1]}] {request_line}")

    if request_line.startswith("OPTIONS"):
        resp = build_response(request_line, headers, 200, "OK")
        sock.sendto(resp, addr)
        print(f"  -> 200 OK")
    elif request_line.startswith("INVITE"):
        # 100 Trying
        resp100 = build_response(request_line, headers, 100, "Trying")
        sock.sendto(resp100, addr)
        print(f"  -> 100 Trying")
        time.sleep(0.1)
        # 180 Ringing
        resp180 = build_response(request_line, headers, 180, "Ringing")
        sock.sendto(resp180, addr)
        print(f"  -> 180 Ringing")
        time.sleep(0.2)
        # 200 OK with SDP
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
        resp200 = build_response(request_line, headers, 200, "OK", sdp)
        sock.sendto(resp200, addr)
        print(f"  -> 200 OK (with SDP)")
    elif request_line.startswith("BYE"):
        resp = build_response(request_line, headers, 200, "OK")
        sock.sendto(resp, addr)
        print(f"  -> 200 OK")
    elif request_line.startswith("REGISTER"):
        resp = build_response(request_line, headers, 200, "OK")
        sock.sendto(resp, addr)
        print(f"  -> 200 OK")
    else:
        resp = build_response(request_line, headers, 405, "Method Not Allowed")
        sock.sendto(resp, addr)
        print(f"  -> 405 Method Not Allowed")


def serve_udp(args):
    sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
    sock.bind((args.host, args.port))
    print(f"Mock SIP Trunk listening on UDP {args.host}:{args.port}")
    while True:
        data, addr = sock.recvfrom(4096)
        threading.Thread(target=handle_request, args=(sock, data, addr, args), daemon=True).start()


def main():
    parser = argparse.ArgumentParser(description="Mock SIP Trunk Provider")
    parser.add_argument("--host", default="0.0.0.0", help="Bind address")
    parser.add_argument("--port", type=int, default=5060, help="Bind port")
    args = parser.parse_args()
    serve_udp(args)


if __name__ == "__main__":
    main()
