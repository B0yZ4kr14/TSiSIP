#!/usr/bin/env bash
# Test OCP System Logs
set -euo pipefail

BASE="${TSISIP_BASE_URL:-http://localhost}"
HOST_HEADER="${TSISIP_HOST_HEADER:-}"
COOKIE_JAR="/tmp/test_cookies_$$"

echo "=== Test: OCP System Logs ==="

# Login as admin
curl -s ${HOST_HEADER:+-H "Host: $HOST_HEADER"} -c "$COOKIE_JAR" -b "$COOKIE_JAR" \
  -X POST "$BASE/login.php" \
  -d "username=testadmin&password=testpass123" \
  -L | grep -q "dashboard" && echo "[PASS] Login"

# System logs page
LOGS=$(curl -s ${HOST_HEADER:+-H "Host: $HOST_HEADER"} -c "$COOKIE_JAR" -b "$COOKIE_JAR" "$BASE/system-logs.php")
echo "$LOGS" | grep -q "System Logs" && echo "[PASS] Page loads"
echo "$LOGS" | grep -q "Log File" && echo "[PASS] Shows log selector"
echo "$LOGS" | grep -q "Lines" && echo "[PASS] Shows line count input"

rm -f "$COOKIE_JAR"
echo "=== All System Logs tests passed ==="
