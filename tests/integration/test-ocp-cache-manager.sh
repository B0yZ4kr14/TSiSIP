#!/usr/bin/env bash
# Test OCP Cache Manager
set -euo pipefail

# Source login helper
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "${SCRIPT_DIR}/helpers/ocp-login.sh"

BASE="${TSISIP_BASE_URL:-http://localhost}"
HOST_HEADER="${TSISIP_HOST_HEADER:-}"
COOKIE_JAR="/tmp/test_cookies_$$"

echo "=== Test: OCP Cache Manager ==="

# Login with CSRF token handling
ocp_login "$BASE" "testadmin" "$COOKIE_JAR"

# Build common curl args
CURL_HOST=""
if [ -n "$HOST_HEADER" ]; then
    CURL_HOST="-H Host:$HOST_HEADER"
fi

# Cache manager page
CACHE=$(curl -fsSL ${CURL_HOST} -c "$COOKIE_JAR" -b "$COOKIE_JAR" "$BASE/cache-manager.php")
echo "$CACHE" | grep -q "Cache Manager" && echo "[PASS] Page loads"
echo "$CACHE" | grep -q "Cached Pages" && echo "[PASS] Shows cache stats"
echo "$CACHE" | grep -q "Clear All Cache" && echo "[PASS] Shows clear button"

rm -f "$COOKIE_JAR"
echo "=== All Cache Manager tests passed ==="
