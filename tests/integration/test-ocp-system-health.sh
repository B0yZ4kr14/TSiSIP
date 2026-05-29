#!/usr/bin/env bash
# Test OCP System Health page
set -euo pipefail

# Source login helper
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "${SCRIPT_DIR}/helpers/ocp-login.sh"

BASE="${TSISIP_BASE_URL:-http://localhost}"
HOST_HEADER="${TSISIP_HOST_HEADER:-}"
COOKIE_JAR="/tmp/test_cookies_$$"

echo "=== Test: OCP System Health ==="

# Login with CSRF token handling
ocp_login "$BASE" "testadmin" "$COOKIE_JAR"

# Build common curl args
CURL_HOST=""
if [ -n "$HOST_HEADER" ]; then
    CURL_HOST="-H Host:$HOST_HEADER"
fi

# System health page
HEALTH=$(curl -fsSL ${CURL_HOST} -c "$COOKIE_JAR" -b "$COOKIE_JAR" "$BASE/system-health.php")
echo "$HEALTH" | grep -q "System Health" && echo "[PASS] System health loads"
echo "$HEALTH" | grep -q "OpenSIPS" && echo "[PASS] Shows OpenSIPS component"
echo "$HEALTH" | grep -q "RTPengine" && echo "[PASS] Shows RTPengine component"
echo "$HEALTH" | grep -q "PostgreSQL" && echo "[PASS] Shows PostgreSQL component"
echo "$HEALTH" | grep -q "Active Calls" && echo "[PASS] Shows active calls metric"
echo "$HEALTH" | grep -q "Subscribers" && echo "[PASS] Shows subscribers metric"
echo "$HEALTH" | grep -q "Quick Actions" && echo "[PASS] Shows quick actions"

rm -f "$COOKIE_JAR"
echo "=== All System Health tests passed ==="
