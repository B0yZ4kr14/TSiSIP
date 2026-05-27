#!/bin/bash
# TSiSIP OCP User Management — Integration Test
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
COMPOSE_FILE="${COMPOSE_FILE:-$PROJECT_ROOT/docker-compose.yml}"
OCP_SERVICE="${OCP_SERVICE:-ocp}"

PASS=0
FAIL=0
report_pass() { echo "  PASS: $1"; ((PASS++)) || true; }
report_fail() { echo "  FAIL: $1"; ((FAIL++)) || true; }

ocp_sh() { docker compose -f "$COMPOSE_FILE" exec -T "$OCP_SERVICE" sh -c "$1"; }

echo "=== TSiSIP OCP User Management Test ==="

if ! docker compose -f "$COMPOSE_FILE" ps "$OCP_SERVICE" 2>/dev/null | grep -qE "running|Up"; then
    echo "SKIP: OCP service not running"
    exit 0
fi
report_pass "Prerequisites"

SEED_PASS="${OCP_SEED_ADMIN_PASS:-}"
if [ -z "$SEED_PASS" ]; then
    report_fail "OCP_SEED_ADMIN_PASS not set"
    echo "Passed: $PASS | Failed: $FAIL"
    exit 1
fi

TMPDIR="$(mktemp -d)"
trap 'rm -rf "$TMPDIR"' EXIT

printf 'username=Admin&pass=%s' "$SEED_PASS" > "$TMPDIR/login_payload.txt"
docker compose -f "$COMPOSE_FILE" cp "$TMPDIR/login_payload.txt" "$OCP_SERVICE:/tmp/login_payload.txt"
LOGIN_CODE=$(ocp_sh "curl -s -o /dev/null -w '%{http_code}' -c /tmp/users-cookies.txt -d @/tmp/login_payload.txt 'http://localhost/login.php'")
if [ "$LOGIN_CODE" = "302" ] || [ "$LOGIN_CODE" = "200" ]; then
    report_pass "Admin login"
else
    report_fail "Admin login failed (HTTP $LOGIN_CODE)"
    exit 1
fi

# Test 1: User list page accessible
echo ""
echo "[test] User list page..."
BODY=$(ocp_sh "curl -s -b /tmp/users-cookies.txt 'http://localhost/users.php'")
if echo "$BODY" | grep -q 'User Management'; then
    report_pass "User list page accessible"
else
    report_fail "User list page not accessible"
fi

# Test 2: User edit page accessible
echo ""
echo "[test] User edit page..."
BODY=$(ocp_sh "curl -s -b /tmp/users-cookies.txt 'http://localhost/user-edit.php?id=1'")
if echo "$BODY" | grep -q 'Edit User'; then
    report_pass "User edit page accessible"
else
    report_fail "User edit page not accessible"
fi

# Test 3: Password policy library exists
echo ""
echo "[test] Password policy library..."
if ocp_sh "test -f /var/www/html/common/password-policy.php"; then
    report_pass "password-policy.php exists"
else
    report_fail "password-policy.php missing"
fi

# Test 4: Schema file exists
echo ""
echo "[test] User management schema..."
if ocp_sh "test -f /var/www/docs/db/init/08-user-management-schema.sql"; then
    report_pass "Schema file exists"
else
    report_fail "Schema file missing"
fi

# Test 5: Profile page has self-service forms
echo ""
echo "[test] Profile self-service..."
BODY=$(ocp_sh "curl -s -b /tmp/users-cookies.txt 'http://localhost/profile.php'")
if echo "$BODY" | grep -q 'Change Password' && echo "$BODY" | grep -q 'Update Email'; then
    report_pass "Profile self-service forms present"
else
    report_fail "Profile self-service forms missing"
fi

echo ""
echo "=== User Management Test Report ==="
echo "Passed: $PASS"
echo "Failed: $FAIL"
if [ "$FAIL" -gt 0 ]; then
    echo "=== CI SCAN FAILED ==="
    exit 1
fi
echo "=== ALL TESTS PASSED ==="
