#!/bin/bash
# TSiSIP SIP INVITE 407 Authentication Test
# Verifies that unauthenticated INVITEs receive 407 Proxy Authentication Required
# Requires: docker compose stack running with sip_edge network
#
# Derive test IP from Docker network or accept TEST_IP env var.
# No hard-coded fallback — fails fast if IP cannot be determined.

set -euo pipefail

# Attempt 1: derive from Docker network subnet (replace trailing 0 with 4)
TEST_IP="${TEST_IP:-}"
if [ -z "$TEST_IP" ]; then
    TEST_IP=$(docker network inspect tsisip_sip_edge --format="{{(index .IPAM.Config 0).Subnet}}" 2>/dev/null | sed "s|/.*||; s|0$|4|")
fi

# Attempt 2: use Docker network gateway
if [ -z "$TEST_IP" ]; then
    TEST_IP=$(docker network inspect tsisip_sip_edge --format='{{range .IPAM.Config}}{{.Gateway}}{{end}}' 2>/dev/null)
fi

if [ -z "$TEST_IP" ]; then
    echo "FAIL: Could not determine TEST_IP from Docker network 'tsisip_sip_edge'" >&2
    echo "Set TEST_IP explicitly: TEST_IP=10.0.0.4 $0" >&2
    exit 1
fi

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
      b'Via: SIP/2.0/UDP ${TEST_IP}:${TEST_PORT};branch=z9hG4bK-invite-test\r\n' \
      b'From: <sip:test@${TEST_IP}>;tag=invitetag\r\n' \
      b'To: <sip:test@opensips:5060>\r\n' \
      b'Call-ID: test-invite-407@${TEST_IP}\r\n' \
      b'CSeq: 1 INVITE\r\nMax-Forwards: 70\r\n' \
      b'Contact: <sip:test@${TEST_IP}:${TEST_PORT}>\r\n' \
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
