#!/bin/bash
set -euo pipefail
LOG="/tmp/tsisip-test-results.txt"
PASS=0
FAIL=0

log() { echo "[$(date '+%H:%M:%S')] $*" | tee -a "$LOG"; }
pass() { log "✅ PASS: $*"; ((PASS++)) || true; }
fail() { log "❌ FAIL: $*"; ((FAIL++)) || true; }

log "=== T4.1: Infrastructure ==="
docker ps --format '{{.Names}}' | grep -q 'tsisip-opensips-1' && pass "OpenSIPS container" || fail "OpenSIPS container"
docker ps --format '{{.Names}}' | grep -q 'tsisip-postgres-1' && pass "Postgres container" || fail "Postgres container"
docker ps --format '{{.Names}}' | grep -q 'tsisip-ocp-1' && pass "OCP container" || fail "OCP container"

# PostgreSQL must NOT be on 0.0.0.0
if ss -tlnp 2>/dev/null | grep ':5432' | grep -q '0.0.0.0'; then
    fail "PostgreSQL exposed on 0.0.0.0"
else
    pass "PostgreSQL not on 0.0.0.0"
fi

log "=== T4.2: SIP Signaling ==="
if docker run --rm --network tsisip_sip_edge alpine sh -c 'apk add --no-cache sipsak >/dev/null 2>&1 && sipsak -s sip:opensips:5060 -vv' 2>&1 | grep -q 'SIP/2.0 200 OK'; then
    pass "OPTIONS 200 OK"
else
    fail "OPTIONS failed"
fi

log "=== T4.3: OCP E2E ==="
rm -f /tmp/test_cookies.txt
curl -fsSL -c /tmp/test_cookies.txt -b /tmp/test_cookies.txt -L -d 'username=Admin&pass=admin123!' http://127.0.0.1:8084/login.php -o /dev/null 2>&1
[ -s /tmp/test_cookies.txt ] && pass "OCP login" || fail "OCP login"

HTML=$(curl -fsSL -b /tmp/test_cookies.txt http://127.0.0.1:8084/subscribers.php 2>&1)
echo "$HTML" | grep -q 'Subscriber Management' && pass "subscribers.php" || fail "subscribers.php"

HTML=$(curl -fsSL -b /tmp/test_cookies.txt http://127.0.0.1:8084/cdr-viewer.php 2>&1)
echo "$HTML" | grep -q 'Call Detail Records' && pass "cdr-viewer.php" || fail "cdr-viewer.php"

HTML=$(curl -fsSL -b /tmp/test_cookies.txt http://127.0.0.1:8084/dispatcher.php 2>&1)
echo "$HTML" | grep -q 'Dispatcher Targets' && pass "dispatcher.php" || fail "dispatcher.php"

log "=== T4.4: Security ==="
curl -fsSL -I http://127.0.0.1:8084/subscribers.php 2>&1 | grep -q '302' && pass "Auth redirect" || fail "Auth redirect"

HTML=$(curl -fsSL -b /tmp/test_cookies.txt -X POST -d 'action=delete&id=1' http://127.0.0.1:8084/subscribers.php 2>&1)
echo "$HTML" | grep -qi 'Invalid CSRF token' && pass "CSRF protection" || fail "CSRF protection"

log "=== T4.5: Backup ==="
if docker exec tsisip-backup-1 sh -c 'ls /backup/daily/*.enc 2>/dev/null' | grep -q .; then
    pass "Backup files exist"
else
    fail "No backup files"
fi

log "=== SUMMARY ==="
log "PASS: $PASS | FAIL: $FAIL"
[ "$FAIL" -eq 0 ] && log "🎉 ALL TESTS PASSED" && exit 0 || log "⚠️ $FAIL TEST(S) FAILED" && exit 1
