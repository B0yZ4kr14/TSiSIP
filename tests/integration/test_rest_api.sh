#!/usr/bin/env bash
# TSiSIP REST API Integration Tests — Feature 031
# Tests the /api/v1/* endpoints via internal Docker network.
#
# Environment:
#   OCP_HOST    — OCP container host (default: 172.22.0.9)
#   OCP_SEED_ADMIN_PASS — optional; if set, authenticated tests run too.

set -euo pipefail

OCP_HOST="${OCP_HOST:-172.22.0.9}"
BASE="http://${OCP_HOST}"
PASS=0
FAIL=0

log_pass() { echo "  [PASS] $1"; ((PASS++)) || true; }
log_fail() { echo "  [FAIL] $1"; ((FAIL++)) || true; }

echo "=== Public Endpoint: GET /api/v1/status ==="
resp=$(curl -s -w "\n%{http_code}" "${BASE}/api/v1/status" 2>/dev/null || true)
http_code=$(echo "$resp" | tail -n1)
body=$(echo "$resp" | sed '$d')

if [[ "$http_code" == "200" ]]; then
    if echo "$body" | grep -q '"status"'; then
        log_pass "Status endpoint returns 200 with JSON status field"
    else
        log_fail "Status endpoint returns 200 but missing status field"
    fi
else
    log_fail "Status endpoint expected 200, got $http_code"
fi

echo "=== Auth Rejection Tests ==="
for endpoint in metrics users audit; do
    code=$(curl -s -o /dev/null -w "%{http_code}" "${BASE}/api/v1/${endpoint}" 2>/dev/null || true)
    if [[ "$code" == "401" ]]; then
        log_pass "GET /api/v1/${endpoint} without key → 401"
    else
        log_fail "GET /api/v1/${endpoint} without key → expected 401, got $code"
    fi
done

echo "=== 404 Handling ==="
code=$(curl -s -o /dev/null -w "%{http_code}" "${BASE}/api/v1/nonexistent" 2>/dev/null || true)
if [[ "$code" == "404" ]]; then
    log_pass "GET /api/v1/nonexistent → 404"
else
    log_fail "GET /api/v1/nonexistent → expected 404, got $code"
fi

echo "=== Authenticated Tests ==="
if [[ -z "${OCP_SEED_ADMIN_PASS:-}" ]]; then
    echo "  [SKIP] OCP_SEED_ADMIN_PASS not set — authenticated tests skipped"
else
    echo "  [SKIP] API-key authenticated tests not yet implemented"
fi

echo ""
echo "================================"
echo "  PASSED: $PASS"
echo "  FAILED: $FAIL"
echo "================================"

if [[ $FAIL -gt 0 ]]; then
    exit 1
fi
exit 0
