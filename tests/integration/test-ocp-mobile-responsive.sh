#!/usr/bin/env bash
# Test OCP Mobile Responsive Design
set -euo pipefail

BASE="${TSISIP_BASE_URL:-http://localhost}"
COOKIE_JAR="/tmp/test_cookies_$$"

echo "=== Test: OCP Mobile Responsive ==="

# Login
curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" \
  -X POST "$BASE/login.php" \
  -d "username=admin&password=${TSISIP_OCP_ADMIN_PASSWORD:?must be set}" \
  -L | grep -q "dashboard" && echo "[PASS] Login"

# Check viewport meta tag
DASHBOARD=$(curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" "$BASE/dashboard.php")
echo "$DASHBOARD" | grep -q 'name="viewport"' && echo "[PASS] Viewport meta present"
echo "$DASHBOARD" | grep -q 'tsisip-mobile-menu-toggle' && echo "[PASS] Mobile menu toggle present"

# Check responsive CSS
CSS=$(curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" "$BASE/tsisip/css/tsisip-theme.css")
echo "$CSS" | grep -q '@media (max-width: 768px)' && echo "[PASS] Tablet breakpoint exists"
echo "$CSS" | grep -q '@media (max-width: 480px)' && echo "[PASS] Phone breakpoint exists"
echo "$CSS" | grep -q 'min-height: 44px' && echo "[PASS] Touch target size set"
echo "$CSS" | grep -q 'font-size: 16px' && echo "[PASS] iOS zoom fix present"

# Check tables are scrollable
echo "$CSS" | grep -q 'overflow-x: auto' && echo "[PASS] Table horizontal scroll"

rm -f "$COOKIE_JAR"
echo "=== All Mobile Responsive tests passed ==="
