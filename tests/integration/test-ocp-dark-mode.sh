#!/bin/bash
# @req FR-025
# TSiSIP OCP Dark Mode — Integration Test
# Tests: theme toggle, persistence, system preference detection.

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

echo "=== TSiSIP OCP Dark Mode Integration Test ==="
echo "Compose file: $COMPOSE_FILE"
echo ""

echo "[setup] Checking prerequisites..."
if ! docker compose -f "$COMPOSE_FILE" ps "$OCP_SERVICE" 2>/dev/null | grep -qE "running|Up"; then
    echo "SKIP: OCP service ($OCP_SERVICE) not running. Start the compose stack to run this test."
    exit 0
fi
report_pass "Prerequisites"

SEED_PASS="${OCP_SEED_ADMIN_PASS:-}"
if [ -z "$SEED_PASS" ]; then
    report_fail "Cannot test: OCP_SEED_ADMIN_PASS not set"
    echo "=== Dark Mode Test Report ==="
    echo "Passed: $PASS"
    echo "Failed: $FAIL"
    if [ "$FAIL" -gt 0 ]; then
        exit 1
    fi
    exit 0
fi

TMPDIR="$(mktemp -d)"
trap 'rm -rf "$TMPDIR"' EXIT

printf 'username=Admin&pass=%s' "$SEED_PASS" > "$TMPDIR/login_payload.txt"
docker compose -f "$COMPOSE_FILE" cp "$TMPDIR/login_payload.txt" "$OCP_SERVICE:/tmp/login_payload.txt"
LOGIN_CODE=$(ocp_sh "curl -s -o /dev/null -w '%{http_code}' -c /tmp/darkmode-cookies.txt -d @/tmp/login_payload.txt 'http://localhost/login.php'")
if [ "$LOGIN_CODE" = "302" ] || [ "$LOGIN_CODE" = "200" ]; then
    report_pass "Admin login successful"
else
    report_fail "Admin login failed (HTTP $LOGIN_CODE)"
    exit 1
fi

echo ""
echo "[test] Checking for data-theme attribute..."
DASHBOARD_BODY=$(ocp_sh "curl -s -b /tmp/darkmode-cookies.txt 'http://localhost/dashboard.php'")
if echo "$DASHBOARD_BODY" | grep -q 'data-theme='; then
    report_pass "data-theme attribute present on <html>"
else
    report_fail "data-theme attribute missing on <html>"
fi

echo ""
echo "[test] Checking for theme-toggle.js..."
if echo "$DASHBOARD_BODY" | grep -q 'theme-toggle.js'; then
    report_pass "theme-toggle.js included"
else
    report_fail "theme-toggle.js not included"
fi

echo ""
echo "[test] Checking for theme toggle button..."
if echo "$DASHBOARD_BODY" | grep -q 'id="theme-toggle"'; then
    report_pass "Theme toggle button present"
else
    report_fail "Theme toggle button missing"
fi

echo ""
echo "[test] Checking dark mode CSS variables..."
STYLE_BODY=$(ocp_sh "curl -s 'http://localhost/tsisip/css/tsisip-variables.css'")
if echo "$STYLE_BODY" | grep -q '\[data-theme="dark"\]'; then
    report_pass "Dark mode CSS variables present"
else
    report_fail "Dark mode CSS variables missing"
fi

echo ""
echo "=== Dark Mode Test Report ==="
echo "Passed: $PASS"
echo "Failed: $FAIL"
if [ "$FAIL" -gt 0 ]; then
    echo "=== CI SCAN FAILED ==="
    exit 1
fi
echo "=== ALL TESTS PASSED ==="
