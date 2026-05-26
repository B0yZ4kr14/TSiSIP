#!/bin/bash
# @req FR-001
# @req SC-001 @req SC-002 @req SC-003 @req SC-004 @req SC-005
# @req SC-006 @req SC-007 @req SC-008 @req SC-009
# @req FR-010 @req SC-010
# @req FR-011 @req SC-011
# @req FR-012 @req SC-012
# @req FR-013 @req SC-013
# @req FR-015 @req SC-015
# @req FR-016 @req SC-016
# @req FR-017 @req SC-017-001 @req SC-017-002 @req SC-017-003
# @req FR-018 @req SC-018
# @req FR-019 @req SC-019
# @req FR-023
# @req SC-023
# T3 — TDD RED/GREEN: SIP signaling critical path
# Tests: OPTIONS 200 OK, INVITE 407, authenticated INVITE -> 100 Trying/200 OK
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

RUNNING=$(docker compose -f "$PROJECT_ROOT/$COMPOSE_FILE" ps opensips -q 2>/dev/null | wc -l)
if [ "$RUNNING" -eq 0 ]; then
    fail "opensips container not running"
    echo "SIP: $PASS passed, $FAIL failed"
    exit 1
fi
pass "opensips container running"

PROBE_SCRIPT=$(mktemp /tmp/sip-probe-XXXXXX.py)
cat > "$PROBE_SCRIPT" << 'PYEOF'
import hashlib
import socket
import subprocess
import sys

result = subprocess.run(['hostname', '-i'], capture_output=True, text=True)
my_ip = result.stdout.strip().split()[0]
my_port = 15062

def ha1_md5(username, realm, password):
    return hashlib.md5(f"{username}:{realm}:{password}".encode()).hexdigest()

def send_msg(msg, expected_codes, timeout=5):
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

def test_options():
    msg = (
        f'OPTIONS sip:test@opensips:5060 SIP/2.0\r\n'
        f'Via: SIP/2.0/UDP {my_ip}:{my_port};branch=z9hG4bK-options123\r\n'
        f'From: <sip:test@{my_ip}>;tag=optionstag\r\n'
        f'To: <sip:test@opensips:5060>\r\n'
        f'Call-ID: tdd-options-001@{my_ip}\r\n'
        f'CSeq: 1 OPTIONS\r\n'
        f'Max-Forwards: 70\r\n'
        f'Contact: <sip:test@{my_ip}:{my_port}>\r\n'
        f'Content-Length: 0\r\n\r\n'
    ).encode()
    ok, first, _ = send_msg(msg, ['SIP/2.0 200'])
    if ok:
        print(f'PASS: OPTIONS -> {first}')
        return True
    print(f'FAIL: OPTIONS -> {first}')
    return False

def test_invite_407():
    msg = (
        f'INVITE sip:test@opensips:5060 SIP/2.0\r\n'
        f'Via: SIP/2.0/UDP {my_ip}:{my_port};branch=z9hG4bK-invite123\r\n'
        f'From: <sip:test@{my_ip}>;tag=invitetag\r\n'
        f'To: <sip:test@opensips:5060>\r\n'
        f'Call-ID: tdd-invite-001@{my_ip}\r\n'
        f'CSeq: 1 INVITE\r\n'
        f'Max-Forwards: 70\r\n'
        f'Contact: <sip:test@{my_ip}:{my_port}>\r\n'
        f'Content-Length: 0\r\n\r\n'
    ).encode()
    ok, first, _ = send_msg(msg, ['SIP/2.0 407'])
    if ok:
        print(f'PASS: INVITE -> {first}')
        return True
    print(f'FAIL: INVITE -> {first}')
    return False

def test_invite_authenticated():
    username = 'devuser'
    password = 'devpass'
    domain = 'dev.tsisip.local'
    to_user = '1000'

    msg1 = (
        f'INVITE sip:{to_user}@{domain} SIP/2.0\r\n'
        f'Via: SIP/2.0/UDP {my_ip}:{my_port};branch=z9hG4bK-auth001\r\n'
        f'From: <sip:{username}@{domain}>;tag=authtag\r\n'
        f'To: <sip:{to_user}@{domain}>\r\n'
        f'Call-ID: tdd-auth-001@{my_ip}\r\n'
        f'CSeq: 1 INVITE\r\n'
        f'Max-Forwards: 70\r\n'
        f'Contact: <sip:{username}@{my_ip}:{my_port}>\r\n'
        f'Content-Length: 0\r\n\r\n'
    ).encode()
    ok, first, full = send_msg(msg1, ['SIP/2.0 407'])
    if not ok:
        print(f'FAIL: AUTH-INVITE step 1 -> {first}')
        return False

    nonce = None
    for line in full.split('\r\n'):
        if 'nonce=' in line:
            nonce = line.split('nonce="')[1].split('"')[0]
            break
    if not nonce:
        print('FAIL: AUTH-INVITE no nonce found')
        return False

    ha1 = ha1_md5(username, domain, password)
    uri = f'sip:{to_user}@{domain}'
    ha2 = hashlib.md5(f'INVITE:{uri}'.encode()).hexdigest()
    response = hashlib.md5(f'{ha1}:{nonce}:{ha2}'.encode()).hexdigest()

    sdp = (
        f'v=0\r\n'
        f'o=- 0 0 IN IP4 {my_ip}\r\n'
        f's=Test\r\n'
        f'c=IN IP4 {my_ip}\r\n'
        f't=0 0\r\n'
        f'm=audio 10000 RTP/AVP 0\r\n'
        f'a=rtpmap:0 PCMU/8000\r\n'
    )

    msg2 = (
        f'INVITE sip:{to_user}@{domain} SIP/2.0\r\n'
        f'Via: SIP/2.0/UDP {my_ip}:{my_port};branch=z9hG4bK-auth002\r\n'
        f'From: <sip:{username}@{domain}>;tag=authtag\r\n'
        f'To: <sip:{to_user}@{domain}>\r\n'
        f'Call-ID: tdd-auth-001@{my_ip}\r\n'
        f'CSeq: 2 INVITE\r\n'
        f'Proxy-Authorization: Digest username="{username}", realm="{domain}", '
        f'nonce="{nonce}", uri="{uri}", response="{response}", algorithm=MD5\r\n'
        f'Contact: <sip:{username}@{my_ip}:{my_port}>\r\n'
        f'Content-Type: application/sdp\r\n'
        f'Max-Forwards: 70\r\n'
        f'Content-Length: {len(sdp)}\r\n\r\n'
        f'{sdp}'
    ).encode()

    ok, first, _ = send_msg(msg2, ['SIP/2.0 100', 'SIP/2.0 180', 'SIP/2.0 200'], timeout=8)
    if ok:
        print(f'PASS: AUTH-INVITE -> {first}')
        return True
    print(f'FAIL: AUTH-INVITE -> {first}')
    return False

success = True
if not test_options():
    success = False
if not test_invite_407():
    success = False
if not test_invite_authenticated():
    success = False

sys.exit(0 if success else 1)
PYEOF

RESPONSE=$(docker run --rm --network "tsisip_sip_edge" -v "$PROBE_SCRIPT:/tmp/sip-probe.py" alpine sh -c \
    'apk add python3 >/dev/null 2>&1 && python3 /tmp/sip-probe.py' 2>/dev/null || echo "PROBE_FAILED")

info "Sending OPTIONS probe..."
if echo "$RESPONSE" | grep -q "PASS: OPTIONS"; then
    pass "OPTIONS → 200 OK"
else
    fail "OPTIONS → $(echo "$RESPONSE" | grep 'OPTIONS' || echo 'PROBE_FAILED')"
fi

info "Sending INVITE probe..."
if echo "$RESPONSE" | grep -q "PASS: INVITE"; then
    pass "INVITE → 407 Proxy Authentication Required"
else
    fail "INVITE → $(echo "$RESPONSE" | grep 'INVITE' || echo 'PROBE_FAILED')"
fi

info "Sending authenticated INVITE probe..."
if echo "$RESPONSE" | grep -q "PASS: AUTH-INVITE"; then
    pass "AUTH-INVITE → 100 Trying / 180 Ringing / 200 OK (Asterisk reached)"
else
    fail "AUTH-INVITE → $(echo "$RESPONSE" | grep 'AUTH-INVITE' || echo 'PROBE_FAILED')"
fi

rm -f "$PROBE_SCRIPT"

echo ""
echo "SIP: $PASS passed, $FAIL failed"
[ $FAIL -eq 0 ] && exit 0 || exit 1
