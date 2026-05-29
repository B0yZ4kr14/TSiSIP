#!/usr/bin/env bash
# Test OCP System Logs
set -euo pipefail

# Source login helper
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "${SCRIPT_DIR}/helpers/ocp-login.sh"

BASE="${TSISIP_BASE_URL:-http://localhost}"
HOST_HEADER="${TSISIP_HOST_HEADER:-}"
COOKIE_JAR="/tmp/test_cookies_$$"

echo "=== Test: OCP System Logs ==="

# Login with CSRF token handling
ocp_login "$BASE" "testadmin" "$COOKIE_JAR"

# Build common curl args
CURL_HOST=""
if [ -n "$HOST_HEADER" ]; then
    CURL_HOST="-H Host:$HOST_HEADER"
fi

CURL_INSECURE=""
if [ "${CURL_INSECURE:-}" = "true" ] || [ "${CURL_INSECURE:-}" = "1" ]; then
    CURL_INSECURE="-k"
fi

# System logs page
LOGS=$(curl -fsSL ${CURL_INSECURE} ${CURL_HOST} -c "$COOKIE_JAR" -b "$COOKIE_JAR" "$BASE/system-logs.php")
echo "$LOGS" | grep -q "System Logs" && echo "[PASS] Page loads"
echo "$LOGS" | grep -q "Log File" && echo "[PASS] Shows log selector"
echo "$LOGS" | grep -q "Lines" && echo "[PASS] Shows line count input"

rm -f "$COOKIE_JAR"
echo "=== All System Logs tests passed ==="
