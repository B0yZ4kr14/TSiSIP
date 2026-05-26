#!/bin/bash
# @req FR-009
# @req FR-022
# @req SC-022
# T2 — TDD RED/GREEN: VPS container health verification
# Usage: ./test-vps-health.sh [vps|local]
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

echo "=== T2: VPS Container Health Check ==="
echo "Profile: $PROFILE"
echo "Compose: $COMPOSE_FILE"
echo ""

# Check compose file exists
if [ ! -f "$PROJECT_ROOT/$COMPOSE_FILE" ]; then
    fail "Compose file not found: $COMPOSE_FILE"
    echo "Health: $PASS passed, $FAIL failed"
    exit 1
fi
pass "Compose file exists"

# Check if stack is running
RUNNING=$(docker compose -f "$PROJECT_ROOT/$COMPOSE_FILE" ps -q 2>/dev/null | wc -l)
if [ "$RUNNING" -eq 0 ]; then
    fail "No containers running (stack is down)"
    echo "Health: $PASS passed, $FAIL failed"
    exit 1
fi
info "Containers running: $RUNNING"

# Check health status for each expected service
for svc in postgres rtpengine opensips asterisk_pbx_1 asterisk_pbx_2 ocp backup; do
    STATUS=$(docker compose -f "$PROJECT_ROOT/$COMPOSE_FILE" ps "$svc" --format '{{.Status}}' 2>/dev/null || echo "missing")
    if echo "$STATUS" | grep -qiE 'healthy|Up'; then
        pass "$svc is healthy/up ($STATUS)"
    else
        fail "$svc is not healthy/up ($STATUS)"
    fi
done

echo ""
echo "Health: $PASS passed, $FAIL failed"
[ $FAIL -eq 0 ] && exit 0 || exit 1
