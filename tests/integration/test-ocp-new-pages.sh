#!/bin/bash
# @req FR-002
# TSiSIP OCP New Pages — Integration Test
# Tests: gateway-health, call-queue, topology, failover, alert-history pages load.
#
# Prerequisites: Docker Compose stack running with ocp service.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
COMPOSE_FILE="${COMPOSE_FILE:-$PROJECT_ROOT/docker-compose.yml}"

OCP_SERVICE="${OCP_SERVICE:-ocp}"

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

echo "=== TSiSIP OCP New Pages Integration Test ==="
echo "Compose file: $COMPOSE_FILE"
echo ""

echo "[setup] Checking prerequisites..."
if ! docker compose -f "$COMPOSE_FILE" ps "$OCP_SERVICE" 2>/dev/null | grep -qE "running|Up"; then
    echo "SKIP: OCP service ($OCP_SERVICE) not running. Start the compose stack to run this test."
    exit 0
fi
report_pass "Prerequisites"

# ------------------------------------------------------------------
# Login as Admin
# ------------------------------------------------------------------

SEED_PASS="${OCP_SEED_ADMIN_PASS:-}"
if [ -z "$SEED_PASS" ]; then
    report_fail "Cannot test pages: OCP_SEED_ADMIN_PASS not set"
    echo "=== New Pages Test Report ==="
    echo "Passed: $PASS"
    echo "Failed: $FAIL"
    if [ "$FAIL" -gt 0 ]; then
        echo "=== CI SCAN FAILED ==="
        exit 1
    fi
    echo "=== ALL TESTS PASSED ==="
    exit 0
fi

TMPDIR="$(mktemp -d)"
trap 'rm -rf "$TMPDIR"' EXIT

printf 'username=Admin&pass=%s' "$SEED_PASS" > "$TMPDIR/login_payload.txt"
docker compose -f "$COMPOSE_FILE" cp "$TMPDIR/login_payload.txt" "$OCP_SERVICE:/tmp/login_payload.txt"
LOGIN_CODE=$(ocp_sh "curl -s -o /dev/null -w '%{http_code}' -c /tmp/newpages-cookies.txt -d @/tmp/login_payload.txt 'http://localhost/login.php'")
if [ "$LOGIN_CODE" = "302" ] || [ "$LOGIN_CODE" = "200" ]; then
    report_pass "Admin login successful (HTTP $LOGIN_CODE)"
else
    report_fail "Admin login failed (HTTP $LOGIN_CODE)"
    exit 1
fi

# ------------------------------------------------------------------
# Page load tests
# ------------------------------------------------------------------

echo ""
echo "[test] Testing gateway-health.php..."
GATEWAY_STATUS=$(ocp_sh "curl -s -o /dev/null -w '%{http_code}' -b /tmp/newpages-cookies.txt 'http://localhost/gateway-health.php'")
if [ "$GATEWAY_STATUS" = "200" ]; then
    report_pass "gateway-health.php returns HTTP 200"
else
    report_fail "gateway-health.php returns HTTP $GATEWAY_STATUS"
fi

echo ""
echo "[test] Testing call-queue.php..."
QUEUE_STATUS=$(ocp_sh "curl -s -o /dev/null -w '%{http_code}' -b /tmp/newpages-cookies.txt 'http://localhost/call-queue.php'")
if [ "$QUEUE_STATUS" = "200" ]; then
    report_pass "call-queue.php returns HTTP 200"
else
    report_fail "call-queue.php returns HTTP $QUEUE_STATUS"
fi

echo ""
echo "[test] Testing topology.php..."
TOPO_STATUS=$(ocp_sh "curl -s -o /dev/null -w '%{http_code}' -b /tmp/newpages-cookies.txt 'http://localhost/topology.php'")
if [ "$TOPO_STATUS" = "200" ]; then
    report_pass "topology.php returns HTTP 200"
else
    report_fail "topology.php returns HTTP $TOPO_STATUS"
fi

echo ""
echo "[test] Testing failover.php (admin only)..."
FAILOVER_STATUS=$(ocp_sh "curl -s -o /dev/null -w '%{http_code}' -b /tmp/newpages-cookies.txt 'http://localhost/failover.php'")
if [ "$FAILOVER_STATUS" = "200" ]; then
    report_pass "failover.php returns HTTP 200 for admin"
else
    report_fail "failover.php returns HTTP $FAILOVER_STATUS for admin"
fi

echo ""
echo "[test] Testing alert-history.php..."
ALERT_STATUS=$(ocp_sh "curl -s -o /dev/null -w '%{http_code}' -b /tmp/newpages-cookies.txt 'http://localhost/alert-history.php'")
if [ "$ALERT_STATUS" = "200" ]; then
    report_pass "alert-history.php returns HTTP 200"
else
    report_fail "alert-history.php returns HTTP $ALERT_STATUS"
fi

# ------------------------------------------------------------------
# CSRF token presence check
# ------------------------------------------------------------------

echo ""
echo "[test] Verifying CSRF tokens on failover form..."
FAILOVER_BODY=$(ocp_sh "curl -s -b /tmp/newpages-cookies.txt 'http://localhost/failover.php'")
if echo "$FAILOVER_BODY" | grep -q 'csrf_token'; then
    report_pass "failover.php contains CSRF token"
else
    report_fail "failover.php missing CSRF token"
fi

# ------------------------------------------------------------------
# i18n check
# ------------------------------------------------------------------

echo ""
echo "[test] Verifying i18n strings present..."
GATEWAY_BODY=$(ocp_sh "curl -s -b /tmp/newpages-cookies.txt 'http://localhost/gateway-health.php'")
if echo "$GATEWAY_BODY" | grep -q 'Gateway Health Status'; then
    report_pass "i18n string 'Gateway Health Status' present"
else
    report_fail "i18n string 'Gateway Health Status' missing"
fi

# ------------------------------------------------------------------
# Report
# ------------------------------------------------------------------

echo ""
echo "=== New Pages Test Report ==="
echo "Passed: $PASS"
echo "Failed: $FAIL"
if [ "$FAIL" -gt 0 ]; then
    echo "=== CI SCAN FAILED ==="
    exit 1
fi
echo "=== ALL TESTS PASSED ==="
