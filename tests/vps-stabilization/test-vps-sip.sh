#!/bin/bash
# T3 — TDD RED/GREEN: SIP signaling critical path
# Tests: OPTIONS 200 OK, INVITE 407 Proxy Authentication Required
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

# Test OPTIONS 200 OK
info "Sending OPTIONS probe..."
RESPONSE=$(docker compose -f "$PROJECT_ROOT/$COMPOSE_FILE" exec -T opensips sh -c '
python3 -c "
import socket
msg = b\"OPTIONS sip:opensips@127.0.0.1:5060 SIP/2.0\r\n\" \
      b\"Via: SIP/2.0/UDP 127.0.0.1:5062;branch=z9hG4bK-tdd123\r\n\" \
      b\"From: <sip:test@127.0.0.1>;tag=tddtag\r\n\" \
      b\"To: <sip:opensips@127.0.0.1>\r\n\" \
      b\"Call-ID: tdd-001@127.0.0.1\r\n\" \
      b\"CSeq: 1 OPTIONS\r\n\" \
      b\"Max-Forwards: 70\r\n\" \
      b\"Content-Length: 0\r\n\r\n\"
sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
sock.settimeout(5)
sock.sendto(msg, (\"127.0.0.1\", 5060))
try:
    data, _ = sock.recvfrom(4096)
    print(data.decode().splitlines()[0])
except Exception as e:
    print(f\"TIMEOUT: {e}\")
" 2>/dev/null' || echo "PROBE_FAILED")

if echo "$RESPONSE" | grep -q "SIP/2.0 200"; then
    pass "OPTIONS → 200 OK"
else
    fail "OPTIONS → $RESPONSE"
fi

# Test INVITE 407
info "Sending INVITE probe..."
RESPONSE=$(docker compose -f "$PROJECT_ROOT/$COMPOSE_FILE" exec -T opensips sh -c '
python3 -c "
import socket
msg = b\"INVITE sip:test@opensips:5060 SIP/2.0\r\n\" \
      b\"Via: SIP/2.0/UDP 127.0.0.1:5062;branch=z9hG4bK-invite123\r\n\" \
      b\"From: <sip:test@127.0.0.1>;tag=invitetag\r\n\" \
      b\"To: <sip:test@opensips:5060>\r\n\" \
      b\"Call-ID: tdd-invite-001@127.0.0.1\r\n\" \
      b\"CSeq: 1 INVITE\r\n\" \
      b\"Max-Forwards: 70\r\n\" \
      b\"Contact: <sip:test@127.0.0.1:5062>\r\n\" \
      b\"Content-Length: 0\r\n\r\n\"
sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
sock.settimeout(5)
sock.sendto(msg, (\"127.0.0.1\", 5060))
try:
    data, _ = sock.recvfrom(4096)
    print(data.decode().splitlines()[0])
except Exception as e:
    print(f\"TIMEOUT: {e}\")
" 2>/dev/null' || echo "PROBE_FAILED")

if echo "$RESPONSE" | grep -q "SIP/2.0 407"; then
    pass "INVITE → 407 Proxy Authentication Required"
else
    fail "INVITE → $RESPONSE"
fi

echo ""
echo "SIP: $PASS passed, $FAIL failed"
[ $FAIL -eq 0 ] && exit 0 || exit 1
