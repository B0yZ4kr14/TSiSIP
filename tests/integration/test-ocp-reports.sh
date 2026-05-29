#!/usr/bin/env bash
# Test OCP System Reports
set -euo pipefail

BASE="${TSISIP_BASE_URL:-http://localhost}"
COOKIE_JAR="/tmp/test_cookies_$$"

echo "=== Test: OCP System Reports ==="

# Login
curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" \
  -X POST "$BASE/login.php" \
  -d "username=admin&password=${TSISIP_OCP_ADMIN_PASSWORD:?must be set}" \
  -L | grep -q "dashboard" && echo "[PASS] Login"

# Reports page
REPORTS=$(curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" "$BASE/reports.php")
echo "$REPORTS" | grep -q "System Reports" && echo "[PASS] Reports page loads"
echo "$REPORTS" | grep -q "Last Hour" && echo "[PASS] Time range buttons"
echo "$REPORTS" | grep -q "Most Active Users" && echo "[PASS] Active users section"
echo "$REPORTS" | grep -q "Action Distribution" && echo "[PASS] Action distribution section"

# Test different time ranges
for range in 1h 7d 30d; do
    R=$(curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" "$BASE/reports.php?range=$range")
    echo "$R" | grep -q "System Reports" && echo "[PASS] Range $range works"
done

rm -f "$COOKIE_JAR"
echo "=== All Reports tests passed ==="
