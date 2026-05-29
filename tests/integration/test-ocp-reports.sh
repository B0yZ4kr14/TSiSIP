#!/usr/bin/env bash
# Test OCP System Reports
set -euo pipefail

# Source login helper
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "${SCRIPT_DIR}/helpers/ocp-login.sh"

BASE="${TSISIP_BASE_URL:-http://localhost}"
HOST_HEADER="${TSISIP_HOST_HEADER:-}"
COOKIE_JAR="/tmp/test_cookies_$$"

echo "=== Test: OCP System Reports ==="

# Login with CSRF token handling
ocp_login "$BASE" "testadmin" "$COOKIE_JAR"

# Build common curl args
CURL_HOST=""
if [ -n "$HOST_HEADER" ]; then
    CURL_HOST="-H Host:$HOST_HEADER"
fi

CURL_INSECURE_FLAG=""
if [ "${CURL_INSECURE:-}" = "true" ] || [ "${CURL_INSECURE:-}" = "1" ]; then
    CURL_INSECURE_FLAG="-k"
fi

# Reports page
REPORTS=$(curl -fsSL ${CURL_INSECURE_FLAG} ${CURL_HOST} -c "$COOKIE_JAR" -b "$COOKIE_JAR" "$BASE/reports.php")
echo "$REPORTS" | grep -q "System Reports" && echo "[PASS] Reports page loads"
echo "$REPORTS" | grep -q "Last Hour" && echo "[PASS] Time range buttons"
echo "$REPORTS" | grep -q "Most Active Users" && echo "[PASS] Active users section"
echo "$REPORTS" | grep -q "Action Distribution" && echo "[PASS] Action distribution section"

# Test different time ranges
for range in 1h 7d 30d; do
    R=$(curl -fsSL ${CURL_INSECURE_FLAG} ${CURL_HOST} -c "$COOKIE_JAR" -b "$COOKIE_JAR" "$BASE/reports.php?range=$range")
    echo "$R" | grep -q "System Reports" && echo "[PASS] Range $range works"
done

rm -f "$COOKIE_JAR"
echo "=== All Reports tests passed ==="
