#!/bin/bash
# T3 — TDD RED/GREEN: SIP signaling critical path
# Tests: OPTIONS 200 OK, INVITE 407 Proxy Authentication Required
#
# NOTE: Uses a temporary Alpine container on the sip_edge network because
# the OpenSIPS image does not include python3. The probe script binds to
# the container's Docker-assigned IP to ensure the response is routable.
set -uo pipefail

PROFILE="${1:-vps}"
COMPOSE_FILE="docker-compose.${PROFILE}.yml"
PROJECT_ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
EVIDENCE_DIR="$PROJECT_ROOT/.sisyphus/evidence"
mkdir -p "$EVIDENCE_DIR"

PASS=0
FAIL=0

pass() { echo "[PASS] $*"; ((PASS++)) || true; }
fail() { echo "[FAIL] $*"; ((FAIL++)) || true; }
info() { echo "[INFO] $*"; }

echo "=== T3: SIP Signaling Critical Path ==="
echo "Profile: $PROFILE"
echo ""

# Check if opensips container is running
RUNNING=$(docker compose -f "$PROJECT_ROOT/$COMPOSE_FILE" ps opensips -q 2>/dev/null | wc -l)
if [ "$RUNNING" -eq 0 ]; then
    fail "opensips container not running"
    echo "SIP: $PASS passed, $FAIL failed"
    exit 1
fi
pass "opensips container running"

# Create probe script that discovers its own IP and binds before sending
PROBE_SCRIPT=$(mktemp /tmp/sip-probe-XXXXXX.py)
cat > "$PROBE_SCRIPT" << 'PYEOF'
import socket
import subprocess
import sys

# Get our container IP
result = subprocess.run(['hostname', '-i'], capture_output=True, text=True)
my_ip = result.stdout.strip().split()[0]
my_port = 15062

def send_sip(method, expected):
    msg = (
        f'{method} sip:test@opensips:5060 SIP/2.0\r\n'
        f'Via: SIP/2.0/UDP {my_ip}:{my_port};branch=z9hG4bK-{method.lower()}123\r\n'
        f'From: <sip:test@{my_ip}>;tag={method.lower()}tag\r\n'
        f'To: <sip:test@opensips:5060>\r\n'
        f'Call-ID: tdd-{method.lower()}-001@{my_ip}\r\n'
        f'CSeq: 1 {method}\r\n'
        f'Max-Forwards: 70\r\n'
        f'Contact: <sip:test@{my_ip}:{my_port}>\r\n'
        f'Content-Length: 0\r\n\r\n'
    ).encode()
    
    sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
    sock.bind((my_ip, my_port))
    sock.settimeout(5)
    sock.sendto(msg, ('opensips', 5060))
    try:
        data, _ = sock.recvfrom(4096)
        response = data.decode().splitlines()[0]
        if expected in response:
            print(f'PASS: {method} -> {response}')
            return True
        else:
            print(f'FAIL: {method} -> unexpected {response}')
            return False
    except Exception as e:
        print(f'FAIL: {method} -> TIMEOUT: {e}')
        return False
    finally:
        sock.close()

success = True
if not send_sip('OPTIONS', 'SIP/2.0 200'):
    success = False
if not send_sip('INVITE', 'SIP/2.0 407'):
    success = False

sys.exit(0 if success else 1)
PYEOF

# Run probe inside temporary Alpine container on sip_edge network
RESPONSE=$(docker run --rm --network "tsisip_sip_edge" -v "$PROBE_SCRIPT:/tmp/sip-probe.py" alpine sh -c \
    'apk add python3 >/dev/null 2>&1 && python3 /tmp/sip-probe.py' 2>/dev/null || echo "PROBE_FAILED")

# Test OPTIONS 200 OK
info "Sending OPTIONS probe..."
if echo "$RESPONSE" | grep -q "PASS: OPTIONS"; then
    pass "OPTIONS → 200 OK"
else
    fail "OPTIONS → $(echo "$RESPONSE" | grep 'OPTIONS' || echo 'PROBE_FAILED')"
fi

# Test INVITE 407
info "Sending INVITE probe..."
if echo "$RESPONSE" | grep -q "PASS: INVITE"; then
    pass "INVITE → 407 Proxy Authentication Required"
else
    fail "INVITE → $(echo "$RESPONSE" | grep 'INVITE' || echo 'PROBE_FAILED')"
fi

rm -f "$PROBE_SCRIPT"

echo ""
echo "SIP: $PASS passed, $FAIL failed"
[ $FAIL -eq 0 ] && exit 0 || exit 1
