#!/usr/bin/env bash
# Test OCP Bookmarks
set -euo pipefail

BASE="${TSISIP_BASE_URL:-http://localhost}"
COOKIE_JAR="/tmp/test_cookies_$$"

echo "=== Test: OCP Bookmarks ==="

# Login
curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" \
  -X POST "$BASE/login.php" \
  -d "username=testadmin&password=testpass123" \
  -L | grep -q "dashboard" && echo "[PASS] Login"

# Dashboard shows bookmarks section
DASH=$(curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" "$BASE/dashboard.php")
echo "$DASH" | grep -q "Bookmarks" && echo "[PASS] Bookmarks section visible"

# Toggle bookmark
BM=$(curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" \
  -X POST "$BASE/common/bookmark-toggle.php" \
  -H "Content-Type: application/json" \
  -d '{"url":"gateway-health.php","label":"Gateway Health"}')
echo "$BM" | grep -q "bookmarked" && echo "[PASS] Bookmark toggle works"

# API docs page
API=$(curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" "$BASE/api-docs.php")
echo "$API" | grep -q "API Documentation" && echo "[PASS] API docs loads"
echo "$API" | grep -q "mi-http.php" && echo "[PASS] Documents MI endpoint"

rm -f "$COOKIE_JAR"
echo "=== All Bookmark tests passed ==="
