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
AUTH_AVAILABLE=false

if [ -n "$SEED_PASS" ]; then
    TMPDIR="$(mktemp -d)"
    trap 'rm -rf "$TMPDIR"' EXIT

    printf 'username=Admin&pass=%s' "$SEED_PASS" > "$TMPDIR/login_payload.txt"
    docker compose -f "$COMPOSE_FILE" cp "$TMPDIR/login_payload.txt" "$OCP_SERVICE:/tmp/login_payload.txt"
    LOGIN_CODE=$(ocp_sh "curl -s -o /dev/null -w '%{http_code}' -c /tmp/users-cookies.txt -d @/tmp/login_payload.txt 'http://localhost/login.php'")
    if [ "$LOGIN_CODE" = "302" ] || [ "$LOGIN_CODE" = "200" ]; then
        report_pass "Admin login"
        AUTH_AVAILABLE=true
    else
        report_fail "Admin login failed (HTTP $LOGIN_CODE)"
    fi
else
    echo "  NOTE: OCP_SEED_ADMIN_PASS not set — skipping authenticated tests"
fi

# Test 1: Password policy library exists (public asset)
echo ""
echo "[test] Password policy library..."
if ocp_sh "test -f /var/www/html/common/password-policy.php"; then
    report_pass "password-policy.php exists"
else
    report_fail "password-policy.php missing"
fi

# Test 2: Schema file exists on host
if [ -f "$PROJECT_ROOT/db/init/08-user-management-schema.sql" ]; then
    report_pass "Schema file exists"
else
    report_fail "Schema file missing"
fi

# Test 3: User list PHP syntax
if ocp_sh "php -l /var/www/html/users.php" | grep -q "No syntax errors"; then
    report_pass "users.php syntax valid"
else
    report_fail "users.php has syntax errors"
fi

# Test 4: User edit PHP syntax
if ocp_sh "php -l /var/www/html/user-edit.php" | grep -q "No syntax errors"; then
    report_pass "user-edit.php syntax valid"
else
    report_fail "user-edit.php has syntax errors"
fi

# Test 5: User delete PHP syntax
if ocp_sh "php -l /var/www/html/user-delete.php" | grep -q "No syntax errors"; then
    report_pass "user-delete.php syntax valid"
else
    report_fail "user-delete.php has syntax errors"
fi

# Test 6: Profile PHP syntax
if ocp_sh "php -l /var/www/html/profile.php" | grep -q "No syntax errors"; then
    report_pass "profile.php syntax valid"
else
    report_fail "profile.php has syntax errors"
fi

# Authenticated tests
if [ "$AUTH_AVAILABLE" = true ]; then
    echo ""
    echo "[test] User list page..."
    BODY=$(ocp_sh "curl -s ${HOST_HEADER:+-H "Host: $HOST_HEADER"} -b /tmp/users-cookies.txt 'http://localhost/users.php'")
    if echo "$BODY" | grep -q 'User Management'; then
        report_pass "User list page accessible"
    else
        report_fail "User list page not accessible"
    fi

    echo ""
    echo "[test] User edit page..."
    BODY=$(ocp_sh "curl -s ${HOST_HEADER:+-H "Host: $HOST_HEADER"} -b /tmp/users-cookies.txt 'http://localhost/user-edit.php?id=1'")
    if echo "$BODY" | grep -q 'Edit User'; then
        report_pass "User edit page accessible"
    else
        report_fail "User edit page not accessible"
    fi

    echo ""
    echo "[test] Profile self-service..."
    BODY=$(ocp_sh "curl -s ${HOST_HEADER:+-H "Host: $HOST_HEADER"} -b /tmp/users-cookies.txt 'http://localhost/profile.php'")
    if echo "$BODY" | grep -q 'Change Password' && echo "$BODY" | grep -q 'Update Email'; then
        report_pass "Profile self-service forms present"
    else
        report_fail "Profile self-service forms missing"
    fi
else
    echo ""
    echo "[test] Skipping authenticated UI tests (no credentials)"
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
