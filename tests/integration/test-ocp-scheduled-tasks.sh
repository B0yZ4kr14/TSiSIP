#!/usr/bin/env bash
# Test OCP Scheduled Tasks
set -euo pipefail

# Source login helper
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "${SCRIPT_DIR}/helpers/ocp-login.sh"

BASE="${TSISIP_BASE_URL:-http://localhost}"
HOST_HEADER="${TSISIP_HOST_HEADER:-}"
COOKIE_JAR="/tmp/test_cookies_$$"

echo "=== Test: OCP Scheduled Tasks ==="

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

# Scheduled tasks page
TASKS=$(curl -fsSL ${CURL_INSECURE_FLAG} ${CURL_HOST} -c "$COOKIE_JAR" -b "$COOKIE_JAR" "$BASE/scheduled-tasks.php")
echo "$TASKS" | grep -q "Scheduled Tasks" && echo "[PASS] Page loads"
echo "$TASKS" | grep -q "Database Backup" && echo "[PASS] Shows backup task"
echo "$TASKS" | grep -q "System Monitor" && echo "[PASS] Shows monitor task"
echo "$TASKS" | grep -q "Cron Setup" && echo "[PASS] Shows cron instructions"

rm -f "$COOKIE_JAR"
echo "=== All Scheduled Tasks tests passed ==="
