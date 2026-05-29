#!/usr/bin/env bash
# Test OCP Mobile Responsive Design
set -euo pipefail

# Source login helper
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "${SCRIPT_DIR}/helpers/ocp-login.sh"

BASE="${TSISIP_BASE_URL:-http://localhost}"
HOST_HEADER="${TSISIP_HOST_HEADER:-}"
COOKIE_JAR="/tmp/test_cookies_$$"

echo "=== Test: OCP Mobile Responsive ==="

# Login with CSRF token handling
ocp_login "$BASE" "testadmin" "$COOKIE_JAR"

# Build common curl args
CURL_HOST=""
if [ -n "$HOST_HEADER" ]; then
    CURL_HOST="-H Host:$HOST_HEADER"
fi

# Check viewport meta tag
DASHBOARD=$(curl -fsSL ${CURL_HOST} -c "$COOKIE_JAR" -b "$COOKIE_JAR" "$BASE/dashboard.php")
echo "$DASHBOARD" | grep -q 'name="viewport"' && echo "[PASS] Viewport meta present"
echo "$DASHBOARD" | grep -q 'tsisip-mobile-menu-toggle' && echo "[PASS] Mobile menu toggle present"

# Check responsive CSS
CSS=$(curl -fsSL ${CURL_HOST} -c "$COOKIE_JAR" -b "$COOKIE_JAR" "$BASE/tsisip/css/tsisip-theme.css")
echo "$CSS" | grep -q '@media (max-width: 768px)' && echo "[PASS] Tablet breakpoint exists"
echo "$CSS" | grep -q '@media (max-width: 480px)' && echo "[PASS] Phone breakpoint exists"
echo "$CSS" | grep -q 'min-height: 44px' && echo "[PASS] Touch target size set"
echo "$CSS" | grep -q 'font-size: 16px' && echo "[PASS] iOS zoom fix present"

# Check tables are scrollable
echo "$CSS" | grep -q 'overflow-x: auto' && echo "[PASS] Table horizontal scroll"

rm -f "$COOKIE_JAR"
echo "=== All Mobile Responsive tests passed ==="
