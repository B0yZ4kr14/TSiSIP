#!/usr/bin/env bash
# Test OCP System Health page
set -euo pipefail

BASE="${TSISIP_BASE_URL:-http://localhost}"
COOKIE_JAR="/tmp/test_cookies_$$"

echo "=== Test: OCP System Health ==="

# Login
curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" \
  -X POST "$BASE/login.php" \
  -d "username=testadmin&password=testpass123" \
  -L | grep -q "dashboard" && echo "[PASS] Login"

# System health page
HEALTH=$(curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" "$BASE/system-health.php")
echo "$HEALTH" | grep -q "System Health" && echo "[PASS] System health loads"
echo "$HEALTH" | grep -q "OpenSIPS" && echo "[PASS] Shows OpenSIPS component"
echo "$HEALTH" | grep -q "RTPengine" && echo "[PASS] Shows RTPengine component"
echo "$HEALTH" | grep -q "PostgreSQL" && echo "[PASS] Shows PostgreSQL component"
echo "$HEALTH" | grep -q "Active Calls" && echo "[PASS] Shows active calls metric"
echo "$HEALTH" | grep -q "Subscribers" && echo "[PASS] Shows subscribers metric"
echo "$HEALTH" | grep -q "Quick Actions" && echo "[PASS] Shows quick actions"

rm -f "$COOKIE_JAR"
echo "=== All System Health tests passed ==="
