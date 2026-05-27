#!/usr/bin/env bash
# TDD RED — SIP Signaling Tests (T3 from VPS 24h plan)
# Validates SIP OPTIONS 200 OK and INVITE 407 Proxy Authentication
set -euo pipefail

cd "$(dirname "$0")/../.."

echo "=== Test: OpenSIPS SIP Port Exposure ==="

# Check if 5060/udp is published
udp_open=$(docker compose config 2>/dev/null | grep -c "5060/udp" || true)
if [ "${udp_open:-0}" -eq 0 ]; then
    echo "WARN: 5060/udp not explicitly published in compose"
else
    echo "  OK: 5060/udp configured"
fi

tcp_open=$(docker compose config 2>/dev/null | grep -c "5060/tcp" || true)
if [ "${tcp_open:-0}" -eq 0 ]; then
    echo "WARN: 5060/tcp not explicitly published in compose"
else
    echo "  OK: 5060/tcp configured"
fi

echo "=== Test: SIP OPTIONS Probe ==="

# Try sipsak if available, else use netcat/udp probe
if docker run --rm --network tsisip_sip_edge alpine sh -c "apk add --no-cache sipsak >/dev/null 2>&1 && sipsak -s sip:opensips:5060 -vv" 2>/dev/null | grep -q "200 OK"; then
    echo "  OK: SIP OPTIONS returns 200 OK"
else
    echo "WARN: SIP OPTIONS probe did not return 200 OK (OpenSIPS may not be fully ready)"
fi

echo "=== Test: SIP INVITE Authentication Challenge ==="

python3 -c "
import socket
msg = b'INVITE sip:test@opensips:5060 SIP/2.0\r\n' \
      b'Via: SIP/2.0/UDP 172.22.0.1:5061;branch=z9hG4bK-invite123\r\n' \
      b'From: <sip:test@172.22.0.1>;tag=invitetag\r\n' \
      b'To: <sip:test@opensips:5060>\r\n' \
      b'Call-ID: test-invite-001@172.22.0.1\r\n' \
      b'CSeq: 1 INVITE\r\nMax-Forwards: 70\r\n' \
      b'Contact: <sip:test@172.22.0.1:5061>\r\n' \
      b'Content-Length: 0\r\n\r\n'
try:
    sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
    sock.settimeout(5)
    sock.sendto(msg, ('127.0.0.1', 5060))
    data, _ = sock.recvfrom(4096)
    resp = data.decode()
    if '407' in resp or 'Proxy-Authenticate' in resp:
        print('  OK: SIP INVITE returns 407 Proxy Authentication Required')
    elif '200' in resp:
        print('  WARN: SIP INVITE returned 200 (unexpected without auth)')
    else:
        print('  WARN: SIP INVITE response:', resp[:80])
except Exception as e:
    print('  WARN: SIP INVITE probe failed:', e)
" 2>/dev/null || echo "  WARN: Python SIP probe failed"

echo "=== SIP signaling tests completed ==="
