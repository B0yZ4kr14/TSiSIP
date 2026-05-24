#!/bin/bash
# TSiSIP 6h Stabilization — TDD Validation Script
# Run on VPS as root

set -euo pipefail
LOG="/tmp/tsisip-test-results.txt"
PASS=0
FAIL=0

log() {
    echo "[$(date '+%H:%M:%S')] $*" | tee -a "$LOG"
}

pass() {
    log "✅ PASS: $*"
    ((PASS++)) || true
}

fail() {
    log "❌ FAIL: $*"
    ((FAIL++)) || true
}

log "=== T4.1: Infraestructure Tests ==="

# Container health
if docker ps --format '{{.Names}}' | grep -q 'tsisip-opensips-1'; then
    pass "OpenSIPS container exists"
else
    fail "OpenSIPS container missing"
fi

if docker ps --format '{{.Names}}' | grep -q 'tsisip-postgres-1'; then
    pass "Postgres container exists"
else
    fail "Postgres container missing"
fi

if docker ps --format '{{.Names}}' | grep -q 'tsisip-ocp-1'; then
    pass "OCP container exists"
else
    fail "OCP container missing"
fi

# Port policy
PUBLIC_PORTS=$(ss -tlnp 2>/dev/null | grep -cE ':5060|:5061|:443' || echo 0)
if [ "$PUBLIC_PORTS" -ge 3 ]; then
    pass "Required ports listening"
else
    fail "Missing required ports (note: OCP accessed via nginx 443, not 8084, when userland-proxy=false)"
fi

# No postgres/asterisk public
if ss -tlnp 2>/dev/null | grep -q ':5432.*0\.0\.0\.0'; then
    fail "PostgreSQL exposed publicly"
else
    pass "PostgreSQL not public"
fi

log "=== T4.2: SIP Signaling Tests ==="

# OPTIONS 200 OK
if docker run --rm --network tsisip_sip_edge alpine sh -c 'apk add --no-cache sipsak >/dev/null 2>&1 && sipsak -s sip:opensips:5060 -vv' 2>&1 | grep -q 'SIP/2.0 200 OK'; then
    pass "SIP OPTIONS returns 200 OK"
else
    fail "SIP OPTIONS failed"
fi

# INVITE 407 (placeholder — needs Python probe)
log "T4.2 INVITE 407: requires manual Python probe (see AGENTS.md)"

log "=== T4.3: OCP E2E Tests ==="

rm -f /tmp/test_cookies.txt

# Login
curl -fsSkL -c /tmp/test_cookies.txt -b /tmp/test_cookies.txt -d 'username=Admin&pass=admin123!' https://127.0.0.1/TSiSIP/login.php -o /dev/null 2>&1
if [ -s /tmp/test_cookies.txt ]; then
    pass "OCP login successful"
else
    fail "OCP login failed"
fi

# Subscribers page
if curl -fsSkL -b /tmp/test_cookies.txt https://127.0.0.1/TSiSIP/subscribers.php 2>&1 | grep -q 'Subscriber Management'; then
    pass "OCP subscribers page accessible"
else
    fail "OCP subscribers page failed"
fi

# CDR viewer
if curl -fsSkL -b /tmp/test_cookies.txt https://127.0.0.1/TSiSIP/cdr-viewer.php 2>&1 | grep -q 'Call Detail Records'; then
    pass "OCP CDR viewer accessible"
else
    fail "OCP CDR viewer failed"
fi

# Dispatcher
if curl -fsSkL -b /tmp/test_cookies.txt https://127.0.0.1/TSiSIP/dispatcher.php 2>&1 | grep -q 'Dispatcher Targets'; then
    pass "OCP dispatcher page accessible"
else
    fail "OCP dispatcher page failed"
fi

log "=== T4.4: Security Tests ==="

# Auth required
if curl -fsSkL -I https://127.0.0.1/TSiSIP/subscribers.php 2>&1 | grep -q '302'; then
    pass "Auth redirect works"
else
    fail "Auth redirect failed"
fi

# CSRF protection
if curl -fsSkL -b /tmp/test_cookies.txt -X POST -d 'action=delete&id=1' https://127.0.0.1/TSiSIP/subscribers.php 2>&1 | grep -q 'Invalid CSRF token'; then
    pass "CSRF protection works"
else
    fail "CSRF protection failed"
fi

log "=== T4.5: Backup Tests ==="

if docker exec tsisip-backup-1 ls /backup/daily/*.enc 2>/dev/null | grep -q .; then
    pass "Backup files exist"
else
    fail "No backup files"
fi

log "=== SUMMARY ==="
log "PASS: $PASS | FAIL: $FAIL"
if [ "$FAIL" -eq 0 ]; then
    log "🎉 ALL TESTS PASSED"
    exit 0
else
    log "⚠️ SOME TESTS FAILED"
    exit 1
fi
