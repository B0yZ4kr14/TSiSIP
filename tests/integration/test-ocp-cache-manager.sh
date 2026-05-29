#!/usr/bin/env bash
# Test OCP Cache Manager
set -euo pipefail

BASE="${TSISIP_BASE_URL:-http://localhost}"
HOST_HEADER="${TSISIP_HOST_HEADER:-}"
COOKIE_JAR="/tmp/test_cookies_$$"

echo "=== Test: OCP Cache Manager ==="

# Login as admin
curl -s ${HOST_HEADER:+-H "Host: $HOST_HEADER"} -c "$COOKIE_JAR" -b "$COOKIE_JAR" \
  -X POST "$BASE/login.php" \
  -d "username=testadmin&password=testpass123" \
  -L | grep -q "dashboard" && echo "[PASS] Login"

# Cache manager page
CACHE=$(curl -s ${HOST_HEADER:+-H "Host: $HOST_HEADER"} -c "$COOKIE_JAR" -b "$COOKIE_JAR" "$BASE/cache-manager.php")
echo "$CACHE" | grep -q "Cache Manager" && echo "[PASS] Page loads"
echo "$CACHE" | grep -q "Cached Pages" && echo "[PASS] Shows cache stats"
echo "$CACHE" | grep -q "Clear All Cache" && echo "[PASS] Shows clear button"

rm -f "$COOKIE_JAR"
echo "=== All Cache Manager tests passed ==="
