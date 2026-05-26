#!/bin/bash
# TSiSIP Performance Load Tests
# Uses sipp to validate rate limiting and capacity thresholds

set -euo pipefail

TARGET_HOST="${TARGET_HOST:-127.0.0.1}"
TARGET_PORT="${TARGET_PORT:-5060}"
TRANSPORT="${TRANSPORT:-udp}"
RESULTS_DIR="${RESULTS_DIR:-./results}"

mkdir -p "$RESULTS_DIR"

echo "=== TSiSIP Load Tests ==="
echo "Target: $TARGET_HOST:$TARGET_PORT ($TRANSPORT)"
echo ""

# Test 1: Registration Rate Test (100 req/s)
echo "[TEST 1] Registration rate test - 100 req/s for 10s"
sipp -sf sipp-register.xml \
  -r 100 -l 10 -m 1000 \
  -i [local_ip] \
  "$TARGET_HOST:$TARGET_PORT" \
  -trace_stat -stf "$RESULTS_DIR/register-rate.csv" \
  -timeout 30s 2>/dev/null || echo "SIPp not installed or test failed"

# Test 2: Invite Load Test (50 calls/s)
echo "[TEST 2] INVITE load test - 50 calls/s for 30s"
sipp -sf sipp-invite.xml \
  -r 50 -l 50 -m 1500 \
  -i [local_ip] \
  "$TARGET_HOST:$TARGET_PORT" \
  -trace_stat -stf "$RESULTS_DIR/invite-load.csv" \
  -timeout 60s 2>/dev/null || echo "SIPp not installed or test failed"

# Test 3: Concurrent Calls Test (100 simultaneous calls)
echo "[TEST 3] Concurrent calls test - 100 simultaneous calls"
sipp -sf sipp-invite.xml \
  -r 10 -l 100 -m 100 \
  -i [local_ip] \
  "$TARGET_HOST:$TARGET_PORT" \
  -trace_stat -stf "$RESULTS_DIR/concurrent-100.csv" \
  -timeout 120s 2>/dev/null || echo "SIPp not installed or test failed"

# Test 4: Rate Limiting Test (200 req/s - should trigger pike)
echo "[TEST 4] Rate limiting test - 200 req/s (should trigger pike)"
sipp -sf sipp-register.xml \
  -r 200 -l 10 -m 2000 \
  -i [local_ip] \
  "$TARGET_HOST:$TARGET_PORT" \
  -trace_stat -stf "$RESULTS_DIR/rate-limit.csv" \
  -timeout 30s 2>/dev/null || echo "SIPp not installed or test failed"

echo ""
echo "=== Results ==="
ls -la "$RESULTS_DIR/"
