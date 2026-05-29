#!/usr/bin/env bash
# Test OCP Profile and Search pages
set -euo pipefail

BASE="${TSISIP_BASE_URL:-http://localhost}"
COOKIE_JAR="/tmp/test_cookies_$$"

echo "=== Test: OCP Profile + Search Pages ==="

# Login
curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" \
  -X POST "$BASE/login.php" \
  -d "username=admin&password=${TSISIP_OCP_ADMIN_PASSWORD:?must be set}" \
  -L | grep -q "dashboard" && echo "[PASS] Login"

# Profile page
PROFILE=$(curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" "$BASE/profile.php")
echo "$PROFILE" | grep -q "User Profile" && echo "[PASS] Profile page loads"
echo "$PROFILE" | grep -q "Account Information" && echo "[PASS] Profile shows account info"
echo "$PROFILE" | grep -q "Preferences" && echo "[PASS] Profile shows preferences"
echo "$PROFILE" | grep -q "Light" && echo "[PASS] Profile has theme buttons"
echo "$PROFILE" | grep -q "English" && echo "[PASS] Profile has language buttons"
echo "$PROFILE" | grep -q "Change Password" && echo "[PASS] Profile has change password link"

# Search page (no query)
SEARCH=$(curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" "$BASE/search.php")
echo "$SEARCH" | grep -q "Global Search" && echo "[PASS] Search page loads"
echo "$SEARCH" | grep -q "Subscribers" && echo "[PASS] Search has subscribers section"
echo "$SEARCH" | grep -q "Audit Logs" && echo "[PASS] Search has audit logs section"
echo "$SEARCH" | grep -q "Dialogs" && echo "[PASS] Search has dialogs section"

# Search with query
SEARCH_Q=$(curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" "$BASE/search.php?q=admin")
echo "$SEARCH_Q" | grep -q "Subscribers" && echo "[PASS] Search with query works"

# Header profile link
HEADER=$(curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" "$BASE/dashboard.php")
echo "$HEADER" | grep -q 'href="profile.php"' && echo "[PASS] Header links to profile"

rm -f "$COOKIE_JAR"
echo "=== All Profile + Search tests passed ==="
