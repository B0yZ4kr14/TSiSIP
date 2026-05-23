#!/bin/bash
# T3.1 — RED SIP Signaling Test
# Expects: SIP OPTIONS returns 200 OK, INVITE returns 407

set -euo pipefail

echo "=== RED SIP Signaling Test ==="

# Test OPTIONS 200 OK
if docker run --rm --network tsisip_sip_edge alpine sh -c "apk add --no-cache sipsak >/dev/null 2>&1 && sipsak -s sip:opensips:5060 -vv" 2>/dev/null | grep -q "200 OK"; then
    echo "OPTIONS 200 OK detected"
    OPTIONS_OK=1
else
    echo "RED: OPTIONS 200 OK not detected (expected in RED phase)"
    OPTIONS_OK=0
fi

# Test INVITE 407
python3 -c "
import socket, sys
msg = b'INVITE sip:test@opensips:5060 SIP/2.0\r\nVia: SIP/2.0/UDP 172.22.0.1:5061;branch=z9hG4bK-invite123\r\nFrom: <sip:test@172.22.0.1>;tag=invitetag\r\nTo: <sip:test@opensips:5060>\r\nCall-ID: test-invite-001@172.22.0.1\r\nCSeq: 1 INVITE\r\nMax-Forwards: 70\r\nContact: <sip:test@172.22.0.1:5061>\r\nContent-Length: 0\r\n\r\n'
try:
    sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
    sock.settimeout(5)
    sock.sendto(msg, ('127.0.0.1', 5060))
    data, _ = sock.recvfrom(4096)
    if b'407' in data:
        print('INVITE 407 detected')
        sys.exit(0)
    else:
        print('RED: INVITE 407 not detected')
        sys.exit(1)
except Exception as e:
    print(f'RED: SIP test failed: {e}')
    sys.exit(1)
" 2>/dev/null && INVITE_OK=1 || INVITE_OK=0

if [ $OPTIONS_OK -eq 1 ] && [ $INVITE_OK -eq 1 ]; then
    echo "WARNING: Both SIP tests passed — RED phase may be complete"
    exit 0
else
    echo "RED CONFIRMED: SIP signaling not fully operational"
    exit 1
fi
