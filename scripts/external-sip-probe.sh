#!/bin/bash
# TSiSIP External SIP Probe
# Validates public SIP reachability from outside the TSiSIP network.
#
# Usage:
#   TEST_IP=179.190.15.116 ./scripts/external-sip-probe.sh
#
# Tests:
#   1. OPTIONS -> 200 OK
#   2. INVITE -> 407 Proxy Authentication Required
#   3. Authenticated INVITE -> 100 Trying / 180 Ringing / 200 OK (if creds provided)

set -euo pipefail

TARGET_IP="${TEST_IP:-${1:-}}"
TARGET_PORT="${TEST_PORT:-5060}"
PROTO="${TEST_PROTO:-udp}"
SOURCE_PORT="${SOURCE_PORT:-15080}"
USER="${SIP_USER:-test}"
PASS="${SIP_PASS:-}"
REALM="${SIP_REALM:-tsiapp.io}"

if [ -z "$TARGET_IP" ]; then
    echo "Usage: TEST_IP=<ip> [TEST_PORT=5060] $0"
    echo "   or: $0 <ip> [port]"
    exit 1
fi

PASS=0
FAIL=0
pass() { echo "[PASS] $*"; ((PASS++)) || true; }
fail() { echo "[FAIL] $*"; ((FAIL++)) || true; }

echo "=== TSiSIP External SIP Probe ==="
echo "Target: ${TARGET_IP}:${TARGET_PORT}/${PROTO}"
echo "Source port: ${SOURCE_PORT}"
echo ""

# ---------------------------------------------------------------------------
# T1: OPTIONS 200 OK
# ---------------------------------------------------------------------------
echo "--- T1: OPTIONS 200 OK ---"
python3 -c "
import socket, sys
target = ('${TARGET_IP}', ${TARGET_PORT})
msg = (
    b'OPTIONS sip:${TARGET_IP}:${TARGET_PORT} SIP/2.0\r\n'
    b'Via: SIP/2.0/UDP 0.0.0.0:${SOURCE_PORT};branch=z9hG4bK-options-ext\r\n'
    b'From: <sip:probe@0.0.0.0>;tag=probetag\r\n'
    b'To: <sip:${TARGET_IP}:${TARGET_PORT}>\r\n'
    b'Call-ID: external-probe-options@0.0.0.0\r\n'
    b'CSeq: 1 OPTIONS\r\n'
    b'Max-Forwards: 70\r\n'
    b'Contact: <sip:probe@0.0.0.0:${SOURCE_PORT}>\r\n'
    b'Content-Length: 0\r\n\r\n'
)
sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
sock.settimeout(5)
try:
    sock.sendto(msg, target)
    data, _ = sock.recvfrom(4096)
    resp = data.decode()
    print(resp.splitlines()[0])
    if '200 OK' in resp:
        sys.exit(0)
    else:
        sys.exit(1)
except Exception as e:
    print(f'Exception: {e}')
    sys.exit(1)
finally:
    sock.close()
" && pass "OPTIONS returns 200 OK" || fail "OPTIONS did not return 200 OK"

# ---------------------------------------------------------------------------
# T2: INVITE 407 Proxy Authentication Required
# ---------------------------------------------------------------------------
echo ""
echo "--- T2: INVITE 407 ---"
python3 -c "
import socket, sys
target = ('${TARGET_IP}', ${TARGET_PORT})
msg = (
    b'INVITE sip:test@${TARGET_IP}:${TARGET_PORT} SIP/2.0\r\n'
    b'Via: SIP/2.0/UDP 0.0.0.0:${SOURCE_PORT};branch=z9hG4bK-invite-ext\r\n'
    b'From: <sip:test@0.0.0.0>;tag=invitetag\r\n'
    b'To: <sip:test@${TARGET_IP}:${TARGET_PORT}>\r\n'
    b'Call-ID: external-probe-invite@0.0.0.0\r\n'
    b'CSeq: 1 INVITE\r\n'
    b'Max-Forwards: 70\r\n'
    b'Contact: <sip:test@0.0.0.0:${SOURCE_PORT}>\r\n'
    b'Content-Length: 0\r\n\r\n'
)
sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
sock.settimeout(5)
try:
    sock.sendto(msg, target)
    data, _ = sock.recvfrom(4096)
    resp = data.decode()
    print(resp.splitlines()[0])
    if '407 Proxy Authentication Required' in resp:
        sys.exit(0)
    else:
        sys.exit(1)
except Exception as e:
    print(f'Exception: {e}')
    sys.exit(1)
finally:
    sock.close()
" && pass "INVITE returns 407 Proxy Authentication Required" || fail "INVITE did not return 407"

# ---------------------------------------------------------------------------
# T3: Authenticated INVITE (optional — requires SIP_USER / SIP_PASS)
# ---------------------------------------------------------------------------
if [ -n "$PASS" ]; then
    echo ""
    echo "--- T3: Authenticated INVITE ---"
    python3 -c "
import socket, sys, hashlib, re, uuid

def ha1(u, r, p):
    return hashlib.md5(f'{u}:{r}:{p}'.encode()).hexdigest()

def build_auth(values, method, uri, user, password):
    realm = values.get('realm', '')
    nonce = values['nonce']
    qop = values.get('qop', 'auth').strip('\"') or 'auth'
    alg = values.get('algorithm', 'MD5')
    nc = '00000001'
    cnonce = uuid.uuid4().hex[:8]
    a1 = ha1(user, realm, password)
    a2 = hashlib.md5(f'{method}:{uri}'.encode()).hexdigest()
    if qop == 'auth':
        response = hashlib.md5(f'{a1}:{nonce}:{nc}:{cnonce}:{qop}:{a2}'.encode()).hexdigest()
    else:
        response = hashlib.md5(f'{a1}:{nonce}:{a2}'.encode()).hexdigest()
    return (
        f'Proxy-Authorization: Digest username=\"{user}\", realm=\"{realm}\", '
        f'nonce=\"{nonce}\", uri=\"{uri}\", response=\"{response}\", '
        f'algorithm={alg}, cnonce=\"{cnonce}\", nc={nc}, qop={qop}'
    )

target = ('${TARGET_IP}', ${TARGET_PORT})
branch = 'z9hG4bK' + uuid.uuid4().hex[:10]
msg = (
    f'INVITE sip:${USER}@${TARGET_IP}:${TARGET_PORT} SIP/2.0\r\n'
    f'Via: SIP/2.0/UDP 0.0.0.0:${SOURCE_PORT};branch={branch}\r\n'
    f'From: <sip:${USER}@0.0.0.0>;tag=authtag\r\n'
    f'To: <sip:${USER}@${TARGET_IP}:${TARGET_PORT}>\r\n'
    f'Call-ID: external-probe-auth@0.0.0.0\r\n'
    f'CSeq: 1 INVITE\r\n'
    f'Max-Forwards: 70\r\n'
    f'Contact: <sip:${USER}@0.0.0.0:${SOURCE_PORT}>\r\n'
    f'Content-Length: 0\r\n\r\n'
).encode()

sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
sock.settimeout(5)
try:
    # Step 1: send unauthenticated INVITE to get 407
    sock.sendto(msg, target)
    data, _ = sock.recvfrom(4096)
    resp = data.decode()
    print('Step 1:', resp.splitlines()[0])
    if '407' not in resp:
        sys.exit(1)

    # Parse Proxy-Authenticate
    m = re.search(r'Proxy-Authenticate:\\s*Digest\\s+([^\r\n]+)', resp, re.I)
    if not m:
        print('Missing Proxy-Authenticate header')
        sys.exit(1)
    values = {}
    for key, quoted, bare in re.findall(r'(\w+)=(?:\"([^\"]*)\"|([^,\s]+))', m.group(1)):
        values[key] = quoted or bare

    # Step 2: send authenticated INVITE
    auth = build_auth(values, 'INVITE', f'sip:${USER}@${TARGET_IP}:${TARGET_PORT}', '${USER}', '${PASS}')
    branch2 = 'z9hG4bK' + uuid.uuid4().hex[:10]
    msg2 = (
        f'INVITE sip:${USER}@${TARGET_IP}:${TARGET_PORT} SIP/2.0\r\n'
        f'Via: SIP/2.0/UDP 0.0.0.0:${SOURCE_PORT};branch={branch2}\r\n'
        f'From: <sip:${USER}@0.0.0.0>;tag=authtag\r\n'
        f'To: <sip:${USER}@${TARGET_IP}:${TARGET_PORT}>\r\n'
        f'Call-ID: external-probe-auth@0.0.0.0\r\n'
        f'CSeq: 2 INVITE\r\n'
        f'Max-Forwards: 70\r\n'
        f'Contact: <sip:${USER}@0.0.0.0:${SOURCE_PORT}>\r\n'
        f'{auth}\r\n'
        f'Content-Length: 0\r\n\r\n'
    ).encode()
    sock.sendto(msg2, target)
    data2, _ = sock.recvfrom(4096)
    resp2 = data2.decode()
    print('Step 2:', resp2.splitlines()[0])
    if '100 Trying' in resp2 or '180 Ringing' in resp2 or '200 OK' in resp2:
        sys.exit(0)
    else:
        sys.exit(1)
except Exception as e:
    print(f'Exception: {e}')
    sys.exit(1)
finally:
    sock.close()
" && pass "Authenticated INVITE routes correctly" || fail "Authenticated INVITE failed"
fi

echo ""
echo "=== Results ==="
echo "Passed: ${PASS}"
echo "Failed: ${FAIL}"

[ "$FAIL" -eq 0 ]
