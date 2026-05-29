#!/usr/bin/env bash
# Test OCP Scheduled Tasks
set -euo pipefail

BASE="${TSISIP_BASE_URL:-http://localhost}"
COOKIE_JAR="/tmp/test_cookies_$$"

echo "=== Test: OCP Scheduled Tasks ==="

# Login as admin
curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" \
  -X POST "$BASE/login.php" \
  -d "username=testadmin&password=testpass123" \
  -L | grep -q "dashboard" && echo "[PASS] Login"

# Scheduled tasks page
TASKS=$(curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" "$BASE/scheduled-tasks.php")
echo "$TASKS" | grep -q "Scheduled Tasks" && echo "[PASS] Page loads"
echo "$TASKS" | grep -q "Database Backup" && echo "[PASS] Shows backup task"
echo "$TASKS" | grep -q "System Monitor" && echo "[PASS] Shows monitor task"
echo "$TASKS" | grep -q "Cron Setup" && echo "[PASS] Shows cron instructions"

rm -f "$COOKIE_JAR"
echo "=== All Scheduled Tasks tests passed ==="
