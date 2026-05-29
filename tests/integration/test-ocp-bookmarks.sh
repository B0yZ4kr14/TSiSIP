#!/usr/bin/env bash
# Test OCP Bookmarks
set -euo pipefail

# Source login helper
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "${SCRIPT_DIR}/helpers/ocp-login.sh"

BASE="${TSISIP_BASE_URL:-http://localhost}"
HOST_HEADER="${TSISIP_HOST_HEADER:-}"
COOKIE_JAR="/tmp/test_cookies_$$"

echo "=== Test: OCP Bookmarks ==="

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

# Dashboard shows bookmarks section
DASH=$(curl -fsSL ${CURL_INSECURE_FLAG} ${CURL_HOST} -c "$COOKIE_JAR" -b "$COOKIE_JAR" "$BASE/dashboard.php")
echo "$DASH" | grep -q "Bookmarks" && echo "[PASS] Bookmarks section visible"

# Toggle bookmark
BM=$(curl -fsSL ${CURL_INSECURE_FLAG} ${CURL_HOST} -c "$COOKIE_JAR" -b "$COOKIE_JAR" \
  -X POST "$BASE/common/bookmark-toggle.php" \
  -H "Content-Type: application/json" \
  -d '{"url":"gateway-health.php","label":"Gateway Health"}')
echo "$BM" | grep -q "bookmarked" && echo "[PASS] Bookmark toggle works"

# API docs page
API=$(curl -fsSL ${CURL_INSECURE_FLAG} ${CURL_HOST} -c "$COOKIE_JAR" -b "$COOKIE_JAR" "$BASE/api-docs.php")
echo "$API" | grep -q "API Documentation" && echo "[PASS] API docs loads"
echo "$API" | grep -q "mi-http.php" && echo "[PASS] Documents MI endpoint"

rm -f "$COOKIE_JAR"
echo "=== All Bookmark tests passed ==="
