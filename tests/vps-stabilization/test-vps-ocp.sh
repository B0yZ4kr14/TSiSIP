#!/bin/bash
# T4 — TDD RED/GREEN: OCP web endpoint verification
set -uo pipefail

PROFILE="${1:-vps}"
COMPOSE_FILE="docker-compose.${PROFILE}.yml"
PROJECT_ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
EVIDENCE_DIR="$PROJECT_ROOT/.sisyphus/evidence"
mkdir -p "$EVIDENCE_DIR"

PASS=0
FAIL=0

pass() { echo "[PASS] $*"; ((PASS++)) || true; }
fail() { echo "[FAIL] $*"; ((FAIL++)) || true; }
info() { echo "[INFO] $*"; }

echo "=== T4: OCP Web Endpoint ==="
echo "Profile: $PROFILE"
echo ""

# Check if OCP container is running
RUNNING=$(docker compose -f "$PROJECT_ROOT/$COMPOSE_FILE" ps ocp -q 2>/dev/null | wc -l)
if [ "$RUNNING" -eq 0 ]; then
    fail "ocp container not running"
    echo "OCP: $PASS passed, $FAIL failed"
    exit 1
fi
pass "ocp container running"

# Test OCP login page via internal nginx proxy (localhost:8084)
info "Probing OCP login page via internal proxy..."
HTTP_CODE=$(docker compose -f "$PROJECT_ROOT/$COMPOSE_FILE" exec -T ocp sh -c \
    'curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1/login.php 2>/dev/null || echo "000"')

if [ "$HTTP_CODE" = "200" ]; then
    pass "OCP login page → HTTP 200"
else
    fail "OCP login page → HTTP $HTTP_CODE"
fi

# Test OCP healthcheck endpoint
info "Probing OCP health endpoint..."
HEALTH=$(docker compose -f "$PROJECT_ROOT/$COMPOSE_FILE" exec -T ocp sh -c \
    'curl -fsS http://127.0.0.1/healthcheck-audit.php 2>/dev/null || echo "HEALTH_FAIL"')

if echo "$HEALTH" | grep -qiE 'status.*ok|ok'; then
    pass "OCP health endpoint → OK"
else
    fail "OCP health endpoint → $HEALTH"
fi

echo ""
echo "OCP: $PASS passed, $FAIL failed"
[ $FAIL -eq 0 ] && exit 0 || exit 1
