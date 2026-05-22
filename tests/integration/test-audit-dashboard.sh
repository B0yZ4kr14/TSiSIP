#!/bin/bash
# TSiSIP OCP Audit Dashboard — Integration Test
# Tests: page load, auth guards, filter functionality, pagination.
#
# Prerequisites: Docker Compose stack running with ocp and postgres services.
# Environment:   COMPOSE_FILE, OCP_SERVICE, OCP_SEED_ADMIN_PASS

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
COMPOSE_FILE="${COMPOSE_FILE:-$PROJECT_ROOT/docker-compose.yml}"

OCP_SERVICE="${OCP_SERVICE:-ocp}"

TMPDIR="$(mktemp -d)"
trap 'rm -rf "$TMPDIR"' EXIT

PASS=0
FAIL=0

report_pass() { echo "  PASS: $1"; ((PASS++)) || true; }
report_fail() { echo "  FAIL: $1"; ((FAIL++)) || true; }

ocp_sh() {
    docker compose -f "$COMPOSE_FILE" exec -T "$OCP_SERVICE" sh -c "$1"
}

# ------------------------------------------------------------------
# Setup
# ------------------------------------------------------------------

echo "=== TSiSIP OCP Audit Dashboard Integration Test ==="
echo "Compose file: $COMPOSE_FILE"
echo ""

echo "[setup] Checking prerequisites..."
if ! docker compose -f "$COMPOSE_FILE" ps "$OCP_SERVICE" 2>/dev/null | grep -qE "running|Up"; then
    echo "SKIP: OCP service ($OCP_SERVICE) not running. Start the compose stack to run this test."
    exit 0
fi
report_pass "OCP service is running"

OCP_INTERNAL_URL="http://localhost"
FROM_DATE=$(date -d '-30 days' +%Y-%m-%d 2>/dev/null || date -v-30d +%Y-%m-%d)
TO_DATE=$(date +%Y-%m-%d)
SEED_PASS="${OCP_SEED_ADMIN_PASS:-}"

# ------------------------------------------------------------------
# Unauthenticated access
# ------------------------------------------------------------------

echo ""
echo "[test] Testing unauthenticated access is redirected..."

RESPONSE=$(ocp_sh "curl -s -w '\\nHTTP_CODE:%{http_code}' '${OCP_INTERNAL_URL}/audit-log.php'")
HTTP_CODE=$(echo "$RESPONSE" | grep 'HTTP_CODE:' | cut -d: -f2)
BODY=$(echo "$RESPONSE" | sed '/HTTP_CODE:/d')

if [ "$HTTP_CODE" = "302" ] || echo "$BODY" | grep -qi "login.php"; then
    report_pass "Unauthenticated access redirects to login"
else
    report_fail "Expected redirect for unauthenticated access, got HTTP $HTTP_CODE"
fi

# ------------------------------------------------------------------
# Authenticated dashboard load
# ------------------------------------------------------------------

echo ""
echo "[test] Simulating login..."

if [ -z "$SEED_PASS" ]; then
    report_fail "Cannot test dashboard: OCP_SEED_ADMIN_PASS not set"
else
    printf 'username=Admin&pass=%s' "$SEED_PASS" > "$TMPDIR/login_payload.txt"
    docker compose -f "$COMPOSE_FILE" cp "$TMPDIR/login_payload.txt" "$OCP_SERVICE:/tmp/login_payload.txt"
    LOGIN_CODE=$(ocp_sh "curl -s -o /dev/null -w '%{http_code}' -c /tmp/dashboard-cookies.txt -d @/tmp/login_payload.txt '${OCP_INTERNAL_URL}/login.php'")
    if [ "$LOGIN_CODE" = "302" ] || [ "$LOGIN_CODE" = "200" ]; then
        report_pass "Login simulated successfully (HTTP $LOGIN_CODE)"
    else
        report_fail "Login failed with HTTP $LOGIN_CODE"
    fi

    echo ""
    echo "[test] Testing dashboard page load..."

    DASH_RESPONSE=$(ocp_sh "curl -s -w '\\nHTTP_CODE:%{http_code}' -b /tmp/dashboard-cookies.txt '${OCP_INTERNAL_URL}/audit-log.php'")
    DASH_CODE=$(echo "$DASH_RESPONSE" | grep 'HTTP_CODE:' | cut -d: -f2)
    DASH_BODY=$(echo "$DASH_RESPONSE" | sed '/HTTP_CODE:/d')

    if [ "$DASH_CODE" = "200" ] && echo "$DASH_BODY" | grep -q "Audit Log & Compliance"; then
        report_pass "Dashboard loads with correct title"
    else
        report_fail "Dashboard did not load correctly (HTTP $DASH_CODE)"
    fi

    echo ""
    echo "[test] Testing filter form presence..."

    if echo "$DASH_BODY" | grep -q 'name="action"'; then
        report_pass "Filter form contains action dropdown"
    else
        report_fail "Filter form missing action dropdown"
    fi

    if echo "$DASH_BODY" | grep -q 'name="success"'; then
        report_pass "Filter form contains success dropdown"
    else
        report_fail "Filter form missing success dropdown"
    fi

    if echo "$DASH_BODY" | grep -q 'name="username"'; then
        report_pass "Filter form contains username input"
    else
        report_fail "Filter form missing username input"
    fi

    if echo "$DASH_BODY" | grep -q 'name="q"'; then
        report_pass "Filter form contains details search input"
    else
        report_fail "Filter form missing details search input"
    fi

    echo ""
    echo "[test] Testing filter functionality (action=LOGIN)..."

    FILTER_RESPONSE=$(ocp_sh "curl -s -w '\\nHTTP_CODE:%{http_code}' -b /tmp/dashboard-cookies.txt '${OCP_INTERNAL_URL}/audit-log.php?action=LOGIN&from=${FROM_DATE}&to=${TO_DATE}'")
    FILTER_CODE=$(echo "$FILTER_RESPONSE" | grep 'HTTP_CODE:' | cut -d: -f2)
    FILTER_BODY=$(echo "$FILTER_RESPONSE" | sed '/HTTP_CODE:/d')

    if [ "$FILTER_CODE" = "200" ]; then
        report_pass "Filtered dashboard returns HTTP 200"
    else
        report_fail "Filtered dashboard returned HTTP $FILTER_CODE"
    fi

    if echo "$FILTER_BODY" | grep -q 'value="LOGIN" selected'; then
        report_pass "Filter retains selected action=LOGIN"
    else
        report_fail "Filter did not retain action=LOGIN selection"
    fi

    echo ""
    echo "[test] Testing filter functionality (success=1)..."

    FILTER_RESPONSE2=$(ocp_sh "curl -s -w '\\nHTTP_CODE:%{http_code}' -b /tmp/dashboard-cookies.txt '${OCP_INTERNAL_URL}/audit-log.php?success=1&from=${FROM_DATE}&to=${TO_DATE}'")
    FILTER_CODE2=$(echo "$FILTER_RESPONSE2" | grep 'HTTP_CODE:' | cut -d: -f2)
    FILTER_BODY2=$(echo "$FILTER_RESPONSE2" | sed '/HTTP_CODE:/d')

    if [ "$FILTER_CODE2" = "200" ] && echo "$FILTER_BODY2" | grep -q 'value="1" selected'; then
        report_pass "Filter retains selected success=1"
    else
        report_fail "Filter did not retain success=1 selection"
    fi

    echo ""
    echo "[test] Testing pagination presence..."

    if echo "$DASH_BODY" | grep -qi "page\|pagination"; then
        report_pass "Pagination controls present"
    else
        report_fail "Pagination controls missing"
    fi
fi

# ------------------------------------------------------------------
# Report
# ------------------------------------------------------------------

echo ""
echo "=== Audit Dashboard Test Report ==="
echo "Passed: $PASS"
echo "Failed: $FAIL"
if [ "$FAIL" -gt 0 ]; then
    echo "=== CI SCAN FAILED ==="
    exit 1
fi
echo "=== ALL TESTS PASSED ==="
