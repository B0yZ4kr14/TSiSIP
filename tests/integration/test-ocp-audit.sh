#!/bin/bash
# TSiSIP OCP Audit Log — End-to-End Integration Test
# Tests: insert pipeline, immutability trigger, retention purge,
#        hash chain integrity, CSV/JSON export endpoints.
#
# Prerequisites: Docker Compose stack running with postgres and ocp services.
# Environment:   COMPOSE_FILE, PG_SERVICE, OCP_SERVICE, DB_NAME, DB_USER,
#                OCP_SEED_ADMIN_PASS (seeded Admin password from db/init/03-seed-data.sql)

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
COMPOSE_FILE="${COMPOSE_FILE:-$PROJECT_ROOT/docker-compose.yml}"

PG_SERVICE="${PG_SERVICE:-postgres}"
OCP_SERVICE="${OCP_SERVICE:-ocp}"
DB_NAME="${DB_NAME:-opensips}"
DB_USER="${DB_USER:-opensips}"

TMPDIR="$(mktemp -d)"
trap 'rm -rf "$TMPDIR"' EXIT

PASS=0
FAIL=0

report_pass() { echo "  PASS: $1"; ((PASS++)) || true; }
report_fail() { echo "  FAIL: $1"; ((FAIL++)) || true; }

# ------------------------------------------------------------------
# Helpers
# ------------------------------------------------------------------

psql_exec() {
    docker compose -f "$COMPOSE_FILE" exec -T "$PG_SERVICE" \
        psql -U "$DB_USER" -d "$DB_NAME" -t -A -c "$1"
}

ocp_php() {
    docker compose -f "$COMPOSE_FILE" exec -T "$OCP_SERVICE" php "$@"
}

ocp_sh() {
    docker compose -f "$COMPOSE_FILE" exec -T "$OCP_SERVICE" sh -c "$1"
}

# ------------------------------------------------------------------
# Setup
# ------------------------------------------------------------------

echo "=== TSiSIP OCP Audit Log Integration Test ==="
echo "Compose file: $COMPOSE_FILE"
echo ""

echo "[setup] Checking prerequisites..."
if ! docker compose -f "$COMPOSE_FILE" ps "$PG_SERVICE" 2>/dev/null | grep -qE "running|Up"; then
    echo "SKIP: Postgres service ($PG_SERVICE) not running. Start the compose stack to run this test."
    exit 0
fi
if ! docker compose -f "$COMPOSE_FILE" ps "$OCP_SERVICE" 2>/dev/null | grep -qE "running|Up"; then
    echo "SKIP: OCP service ($OCP_SERVICE) not running. Start the compose stack to run this test."
    exit 0
fi
report_pass "Prerequisites"

# ------------------------------------------------------------------
# T6.1 / T6.5: Insert events via PHP CLI and verify hash chain
# ------------------------------------------------------------------

echo ""
echo "[test] Inserting audit events via PHP CLI..."

cat > "$TMPDIR/insert-events.php" <<'PHPEOF'
<?php
require_once '/var/www/html/common/config.php';
require_once '/var/www/html/common/audit.php';

// Seed a chain of test events with deterministic details
logAuditEvent('LOGIN', 'ocp_user', 'testuser', true, ['source' => 'integration-test', 'step' => 1]);
logAuditEvent('SUBSCRIBER_CREATE', 'subscriber', 'sub-001', true, ['domain' => 'test.local', 'step' => 2]);
logAuditEvent('CONFIG_VIEW', 'system', null, true, ['page' => 'dashboard', 'step' => 3]);
logAuditEvent('LOGOUT', 'ocp_user', 'testuser', true, ['source' => 'integration-test', 'step' => 4]);

echo "INSERT_OK\n";
PHPEOF

docker compose -f "$COMPOSE_FILE" cp "$TMPDIR/insert-events.php" "$OCP_SERVICE:/tmp/insert-events.php"
if ocp_php /tmp/insert-events.php | grep -q 'INSERT_OK'; then
    report_pass "Inserted test events via PHP CLI"
else
    report_fail "Failed to insert test events via PHP CLI"
fi

echo ""
echo "[test] Verifying events in database..."
EVENT_COUNT=$(psql_exec "SELECT COUNT(*) FROM ocp_audit_log WHERE details->>'source' = 'integration-test';")
if [ "${EVENT_COUNT:-0}" -ge 4 ]; then
    report_pass "Found $EVENT_COUNT test events in ocp_audit_log"
else
    report_fail "Expected >= 4 test events, found ${EVENT_COUNT:-0}"
fi

# ------------------------------------------------------------------
# T6.4: Immutability trigger
# ------------------------------------------------------------------

echo ""
echo "[test] Verifying immutability trigger blocks UPDATE..."
UPDATE_RESULT=$(docker compose -f "$COMPOSE_FILE" exec -T "$PG_SERVICE" \
    psql -U "$DB_USER" -d "$DB_NAME" -t -A \
    -c "UPDATE ocp_audit_log SET action = 'HACKED' WHERE id = (SELECT MIN(id) FROM ocp_audit_log);" 2>&1 || true)
if echo "$UPDATE_RESULT" | grep -qi 'immutable'; then
    report_pass "UPDATE blocked by immutability trigger"
else
    report_fail "UPDATE was not blocked: $UPDATE_RESULT"
fi

echo ""
echo "[test] Verifying immutability trigger blocks DELETE (app role)..."
DELETE_RESULT=$(docker compose -f "$COMPOSE_FILE" exec -T "$PG_SERVICE" \
    psql -U "$DB_USER" -d "$DB_NAME" -t -A \
    -c "DELETE FROM ocp_audit_log WHERE id = (SELECT MIN(id) FROM ocp_audit_log);" 2>&1 || true)
if echo "$DELETE_RESULT" | grep -qi 'immutable'; then
    report_pass "DELETE blocked by immutability trigger (app role)"
else
    report_fail "DELETE was not blocked: $DELETE_RESULT"
fi

# ------------------------------------------------------------------
# T6.5: Hash chain integrity (verify BEFORE any purge)
# ------------------------------------------------------------------

echo ""
echo "[test] Verifying hash chain integrity..."

cat > "$TMPDIR/verify-chain.php" <<'PHPEOF'
<?php
require_once '/var/www/html/common/config.php';
require_once '/var/www/html/common/audit.php';

$results = verifyAuditLogIntegrity();
if (isset($results['error'])) {
    echo "ERROR: " . $results['error'] . "\n";
    exit(1);
}

$allValid = true;
$errors = [];
foreach ($results as $r) {
    if (!$r['valid']) {
        $allValid = false;
        $errors[] = "Row {$r['id']}: hash_valid={$r['hash_valid']} chain_valid={$r['chain_valid']}";
    }
}

if ($allValid) {
    echo "CHAIN_OK: " . count($results) . " rows\n";
} else {
    echo "CHAIN_FAIL: " . implode('; ', $errors) . "\n";
}
PHPEOF

docker compose -f "$COMPOSE_FILE" cp "$TMPDIR/verify-chain.php" "$OCP_SERVICE:/tmp/verify-chain.php"
CHAIN_RESULT=$(ocp_php /tmp/verify-chain.php)
if echo "$CHAIN_RESULT" | grep -q 'CHAIN_OK'; then
    ROWS_VERIFIED=$(echo "$CHAIN_RESULT" | grep -oE '[0-9]+ rows' | grep -oE '[0-9]+' || echo "?")
    report_pass "Hash chain integrity verified ($ROWS_VERIFIED rows)"
else
    report_fail "Hash chain integrity failed: $CHAIN_RESULT"
fi

# ------------------------------------------------------------------
# T6.4: Retention purge function
# ------------------------------------------------------------------

echo ""
echo "[test] Verifying retention purge function..."

# Insert a very old event that should be purged
psql_exec "INSERT INTO ocp_audit_log (event_time, username, action, ip_address, hash, prev_hash) VALUES (NOW() - INTERVAL '100 days', 'retention-test', 'RETENTION_TEST', '127.0.0.1'::inet, 'deadbeef' || repeat('0', 56), NULL);"
OLD_COUNT=$(psql_exec "SELECT COUNT(*) FROM ocp_audit_log WHERE action = 'RETENTION_TEST';")
if [ "${OLD_COUNT:-0}" -eq 1 ]; then
    report_pass "Inserted old test event for retention"
else
    report_fail "Failed to insert old test event (count=${OLD_COUNT:-0})"
fi

# Run retention purge for 30 days
PURGE_RESULT=$(psql_exec "SELECT ocp_audit_log_retention_purge(30);")
if [ "${PURGE_RESULT:-0}" -ge 1 ]; then
    report_pass "Retention purge deleted ${PURGE_RESULT} old row(s)"
else
    report_fail "Retention purge did not delete expected rows: ${PURGE_RESULT:-none}"
fi

POST_PURGE_COUNT=$(psql_exec "SELECT COUNT(*) FROM ocp_audit_log WHERE action = 'RETENTION_TEST';")
if [ "${POST_PURGE_COUNT:-0}" -eq 0 ]; then
    report_pass "Old event removed after retention purge"
else
    report_fail "Old event still present after retention purge"
fi

# ------------------------------------------------------------------
# T6.1: Export endpoints (CSV / JSON via curl)
# ------------------------------------------------------------------

echo ""
echo "[test] Testing export endpoints via HTTP..."

OCP_INTERNAL_URL="http://localhost"
FROM_DATE=$(date -d '-7 days' +%Y-%m-%d 2>/dev/null || date -v-7d +%Y-%m-%d)
TO_DATE=$(date +%Y-%m-%d)

# Simulate login as Admin using seed password from environment
SEED_PASS="${OCP_SEED_ADMIN_PASS:-}"
if [ -z "$SEED_PASS" ]; then
    report_fail "Cannot test exports: OCP_SEED_ADMIN_PASS not set (extract from db/init/03-seed-data.sql)"
else
    # Write credentials to a file to avoid shell-quoting issues
    printf 'username=Admin&pass=%s' "$SEED_PASS" > "$TMPDIR/login_payload.txt"
    docker compose -f "$COMPOSE_FILE" cp "$TMPDIR/login_payload.txt" "$OCP_SERVICE:/tmp/login_payload.txt"
    LOGIN_CODE=$(ocp_sh "curl -s -o /dev/null -w '%{http_code}' -c /tmp/audit-cookies.txt -d @/tmp/login_payload.txt '${OCP_INTERNAL_URL}/login.php'")
    if [ "$LOGIN_CODE" = "302" ] || [ "$LOGIN_CODE" = "200" ]; then
        report_pass "Login simulated successfully (HTTP $LOGIN_CODE)"
    else
        report_fail "Login failed with HTTP $LOGIN_CODE"
    fi

    # CSV export
    ocp_sh "curl -fsSL -b /tmp/audit-cookies.txt -o /tmp/audit-export.csv '${OCP_INTERNAL_URL}/audit-export.php?format=csv&from=${FROM_DATE}&to=${TO_DATE}'"
    if ocp_sh "test -s /tmp/audit-export.csv"; then
        CSV_HEADER=$(ocp_sh "head -n 1 /tmp/audit-export.csv")
        if echo "$CSV_HEADER" | grep -q 'id,event_time'; then
            report_pass "CSV export returned valid header"
        else
            report_fail "CSV export unexpected header: $CSV_HEADER"
        fi
    else
        report_fail "CSV export returned empty file"
    fi

    # JSON export
    ocp_sh "curl -fsSL -b /tmp/audit-cookies.txt -o /tmp/audit-export.json '${OCP_INTERNAL_URL}/audit-export.php?format=json&from=${FROM_DATE}&to=${TO_DATE}'"
    if ocp_sh "test -s /tmp/audit-export.json"; then
        FIRST_CHAR=$(ocp_sh "head -c 1 /tmp/audit-export.json")
        if [ "$FIRST_CHAR" = "[" ]; then
            report_pass "JSON export returned valid array"
        else
            report_fail "JSON export does not start with '[' (got '$FIRST_CHAR')"
        fi
    else
        report_fail "JSON export returned empty file"
    fi

    # Filtered export (action=LOGIN)
    ocp_sh "curl -fsSL -b /tmp/audit-cookies.txt -o /tmp/audit-export-filtered.csv '${OCP_INTERNAL_URL}/audit-export.php?format=csv&action=LOGIN&from=${FROM_DATE}&to=${TO_DATE}'"
    FILTERED_ROWS=$(ocp_sh "wc -l < /tmp/audit-export-filtered.csv")
    # Header + at least one data row
    if [ "${FILTERED_ROWS:-0}" -ge 2 ]; then
        report_pass "Filtered CSV export returned $((FILTERED_ROWS - 1)) data row(s)"
    else
        report_fail "Filtered CSV export returned no data rows"
    fi
fi

# ------------------------------------------------------------------
# Cleanup (best-effort: remove integration-test events via retention role)
# Note: middle-row deletion breaks the hash chain for subsequent rows,
# so we only clean up if these are the tail rows. In CI this is optional.
# ------------------------------------------------------------------

echo ""
echo "[cleanup] Attempting cleanup of test events..."
CLEANUP_RESULT=$(docker compose -f "$COMPOSE_FILE" exec -T "$PG_SERVICE" \
    psql -U "$DB_USER" -d "$DB_NAME" -t -A \
    -c "SET ROLE tsisip_retention; DELETE FROM ocp_audit_log WHERE details->>'source' = 'integration-test';" 2>&1 || true)
if echo "$CLEANUP_RESULT" | grep -qi 'immutable'; then
    echo "  WARN: Could not clean up (immutability). This is expected if other events were inserted after test events."
else
    report_pass "Cleaned up test events"
fi

# ------------------------------------------------------------------
# Report
# ------------------------------------------------------------------

echo ""
echo "=== Audit Log Test Report ==="
echo "Passed: $PASS"
echo "Failed: $FAIL"
if [ "$FAIL" -gt 0 ]; then
    echo "=== CI SCAN FAILED ==="
    exit 1
fi
echo "=== ALL TESTS PASSED ==="
