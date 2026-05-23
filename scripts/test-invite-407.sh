#!/bin/bash
# TSiSIP SIP INVITE 407 Authentication Test
# Verifies that unauthenticated INVITEs receive 407 Proxy Authentication Required
# Requires: docker compose stack running with sip_edge network

set -euo pipefail

OPENSIPS_IP=$(docker compose -f docker-compose.vps.yml exec opensips hostname -i 2>/dev/null || echo "opensips")
TEST_PORT=15070

echo "=== TSiSIP INVITE 407 Test ==="
echo "Target: ${OPENSIPS_IP}:5060"

# Run test from within sip_edge network
docker run --rm --network tsisip_sip_edge python:3-alpine python3 -c "
import socket
import time
import sys

sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
sock.bind(('0.0.0.0', ${TEST_PORT}))
sock.settimeout(10)

msg = b'INVITE sip:test@opensips:5060 SIP/2.0\r\n' \
      b'Via: SIP/2.0/UDP 172.19.0.4:${TEST_PORT};branch=z9hG4bK-invite-test\r\n' \
      b'From: <sip:test@172.19.0.4>;tag=invitetag\r\n' \
      b'To: <sip:test@opensips:5060>\r\n' \
      b'Call-ID: test-invite-407@172.19.0.4\r\n' \
      b'CSeq: 1 INVITE\r\nMax-Forwards: 70\r\n' \
      b'Contact: <sip:test@172.19.0.4:${TEST_PORT}>\r\n' \
      b'Content-Length: 0\r\n\r\n'

print('Sending INVITE...')
sock.sendto(msg, ('opensips', 5060))
try:
    data, addr = sock.recvfrom(4096)
    response = data.decode()
    print('RECEIVED:')
    print(response)
    if '407 Proxy Authentication Required' in response:
        print('\n✅ PASS: INVITE returns 407 Proxy Authentication Required')
        sys.exit(0)
    elif '480 Temporarily Unavailable' in response:
        print('\n❌ FAIL: INVITE returns 480 (sql_query bug or DB error)')
        sys.exit(1)
    elif '403 Forbidden' in response:
        print('\n❌ FAIL: INVITE returns 403 (trunk_verify false positive)')
        sys.exit(1)
    else:
        print(f'\n❌ FAIL: Unexpected response')
        sys.exit(1)
except socket.timeout:
    print('❌ FAIL: Timeout waiting for response')
    sys.exit(1)
except Exception as e:
    print(f'❌ FAIL: {e}')
    sys.exit(1)
finally:
    sock.close()
"
