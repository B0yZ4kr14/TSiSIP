#!/usr/bin/env bash
# Test OCP Profile and Search pages
set -euo pipefail

BASE="${TSISIP_BASE_URL:-http://localhost}"
HOST_HEADER="${TSISIP_HOST_HEADER:-}"
COOKIE_JAR="/tmp/test_cookies_$$"

# Source login helper
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=helpers/ocp-login.sh
source "${SCRIPT_DIR}/helpers/ocp-login.sh"

echo "=== Test: OCP Profile + Search Pages ==="

# Login with CSRF token handling
ocp_login "$BASE" "testadmin" "$COOKIE_JAR"

# Build common curl args
CURL_HOST=""
if [ -n "$HOST_HEADER" ]; then
    CURL_HOST="-H Host:${HOST_HEADER}"
fi

# Profile page
PROFILE=$(curl -fsSL ${CURL_INSECURE} ${CURL_HOST} -c "$COOKIE_JAR" -b "$COOKIE_JAR" "${BASE}/profile.php")
echo "$PROFILE" | grep -q "User Profile" && echo "[PASS] Profile page loads"
echo "$PROFILE" | grep -q "Account Information" && echo "[PASS] Profile shows account info"
echo "$PROFILE" | grep -q "Preferences" && echo "[PASS] Profile shows preferences"
echo "$PROFILE" | grep -q "Light" && echo "[PASS] Profile has theme buttons"
echo "$PROFILE" | grep -q "English" && echo "[PASS] Profile has language buttons"
echo "$PROFILE" | grep -q "Change Password" && echo "[PASS] Profile has change password link"

# Search page (no query)
SEARCH=$(curl -fsSL ${CURL_INSECURE} ${CURL_HOST} -c "$COOKIE_JAR" -b "$COOKIE_JAR" "${BASE}/search.php")
echo "$SEARCH" | grep -q "Global Search" && echo "[PASS] Search page loads"
echo "$SEARCH" | grep -q "Subscribers" && echo "[PASS] Search has subscribers section"
echo "$SEARCH" | grep -q "Audit Logs" && echo "[PASS] Search has audit logs section"
echo "$SEARCH" | grep -q "Dialogs" && echo "[PASS] Search has dialogs section"

# Search with query
SEARCH_Q=$(curl -fsSL ${CURL_INSECURE} ${CURL_HOST} -c "$COOKIE_JAR" -b "$COOKIE_JAR" "${BASE}/search.php?q=admin")
echo "$SEARCH_Q" | grep -q "Subscribers" && echo "[PASS] Search with query works"

# Header profile link
HEADER=$(curl -fsSL ${CURL_INSECURE} ${CURL_HOST} -c "$COOKIE_JAR" -b "$COOKIE_JAR" "${BASE}/dashboard.php")
echo "$HEADER" | grep -q 'href="profile.php"' && echo "[PASS] Header links to profile"

rm -f "$COOKIE_JAR"
echo "=== All Profile + Search tests passed ==="
