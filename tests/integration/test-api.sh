#!/usr/bin/env bash
# TSiSIP REST API — Integration Test
set -euo pipefail

cd "$(dirname "$0")/../.."

PASS=0
FAIL=0

report_pass() { echo "  PASS: $1"; ((PASS++)) || true; }
report_fail() { echo "  FAIL: $1"; ((FAIL++)) || true; }

echo "=== TSiSIP REST API Test ==="

# Generate a test API key via PHP in the OCP container
TEST_KEY=$(openssl rand -hex 32)
TEST_HASH=$(docker compose exec -T ocp php -r "echo password_hash('$TEST_KEY', PASSWORD_BCRYPT);")

# Insert key into database
docker compose exec -T postgres psql -U opensips -d opensips -c "
INSERT INTO ocp_api_keys (name, key_hash, scope, is_active)
VALUES ('integration-test', '$TEST_HASH', 'readwrite', true)
ON CONFLICT DO NOTHING;
" >/dev/null 2>&1

API_BASE="http://localhost/api"

# Helper to call API from within OCP container
api_call() {
    local method="$1"
    local endpoint="$2"
    local auth_header="${3:-}"
    if [ -n "$auth_header" ]; then
        docker compose exec -T ocp curl -s -o /tmp/api_resp -w "%{http_code}" -H "$auth_header" "$API_BASE$endpoint" 2>/dev/null || echo "000"
    else
        docker compose exec -T ocp curl -s -o /tmp/api_resp -w "%{http_code}" "$API_BASE$endpoint" 2>/dev/null || echo "000"
    fi
}

# T001: Status endpoint without auth (public)
echo "--- Test: GET /v1/status ---"
HTTP_CODE=$(api_call "GET" "/v1/status")
if [ "$HTTP_CODE" = "200" ]; then
    report_pass "Status endpoint is public (200)"
    BODY=$(docker compose exec -T ocp cat /tmp/api_resp)
    if echo "$BODY" | grep -q '"opensips"'; then
        report_pass "Status contains opensips field"
    else
        report_fail "Status missing opensips field"
    fi
else
    report_fail "Expected 200, got $HTTP_CODE"
fi

# T002: Status with valid key
echo "--- Test: GET /v1/status with auth ---"
HTTP_CODE=$(api_call "GET" "/v1/status" "Authorization: Bearer $TEST_KEY")
if [ "$HTTP_CODE" = "200" ]; then
    report_pass "Status returns 200"
    BODY=$(docker compose exec -T ocp cat /tmp/api_resp)
    if echo "$BODY" | grep -q '"opensips"'; then
        report_pass "Status contains opensips field"
    else
        report_fail "Status missing opensips field"
    fi
else
    report_fail "Expected 200, got $HTTP_CODE"
    docker compose exec -T ocp cat /tmp/api_resp 2>/dev/null || true
fi

# T003: Metrics endpoint
echo "--- Test: GET /v1/metrics ---"
HTTP_CODE=$(api_call "GET" "/v1/metrics" "Authorization: Bearer $TEST_KEY")
if [ "$HTTP_CODE" = "200" ]; then
    report_pass "Metrics returns 200"
else
    report_fail "Expected 200, got $HTTP_CODE"
fi

# T004: Users list
echo "--- Test: GET /v1/users ---"
HTTP_CODE=$(api_call "GET" "/v1/users" "Authorization: Bearer $TEST_KEY")
if [ "$HTTP_CODE" = "200" ]; then
    report_pass "Users list returns 200"
else
    report_fail "Expected 200, got $HTTP_CODE"
fi

# T005: Invalid key on protected endpoint
echo "--- Test: Invalid key ---"
HTTP_CODE=$(api_call "GET" "/v1/metrics" "Authorization: Bearer invalid_key_12345")
if [ "$HTTP_CODE" = "401" ]; then
    report_pass "Invalid key rejected (401)"
else
    report_fail "Expected 401, got $HTTP_CODE"
fi

# T006: Protected endpoints reject unauthenticated
echo "--- Test: Protected endpoints without auth ---"
for endpoint in /v1/metrics /v1/users /v1/audit; do
    HTTP_CODE=$(api_call "GET" "$endpoint")
    if [ "$HTTP_CODE" = "401" ]; then
        report_pass "${endpoint} requires auth (401)"
    else
        report_fail "${endpoint} expected 401, got $HTTP_CODE"
    fi
done

# Cleanup
docker compose exec -T postgres psql -U opensips -d opensips -c "
UPDATE ocp_api_keys SET deleted_at = NOW() WHERE name = 'integration-test';
" >/dev/null 2>&1

echo ""
echo "=== Results ==="
echo "Passed: $PASS"
echo "Failed: $FAIL"

if [ $FAIL -gt 0 ]; then
    exit 1
fi
