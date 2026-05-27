#!/usr/bin/env bash
# TSiSIP OCP — Container Health Check
# Verifies OCP pages directly via container IP (bypasses nginx reverse proxy)
set -uo pipefail

OCP_IP=$(docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}} {{end}}' tsisip-ocp-1 2>/dev/null | awk '{print $1}')
if [ -z "$OCP_IP" ]; then
    echo "ERROR: Could not determine OCP container IP"
    exit 1
fi

BASE_URL="http://$OCP_IP"
CURL_OPTS="-s --max-time 5 --connect-timeout 3 -o /dev/null -w %{http_code}"
FAIL=0
PASS=0

check_http() {
    local url="$1"
    local expected="${2:-200}"
    local code
    code=$(curl $CURL_OPTS "$url" 2>/dev/null || echo "000")
    if [ "$code" = "$expected" ]; then
        PASS=$((PASS + 1))
        echo "  OK: $url (HTTP $code)"
    else
        FAIL=$((FAIL + 1))
        echo "  FAIL: $url (expected $expected, got $code)"
    fi
}

echo "=== TSiSIP OCP Health Check ==="
echo "Target: $BASE_URL"
echo "Time: $(date)"
echo ""

echo "--- Core Pages ---"
check_http "$BASE_URL/login.php" 200
check_http "$BASE_URL/health.php" 200

echo "--- Auth Required Pages (expect 302) ---"
pages=(
    dashboard.php address.php aliases.php call-center.php clusterer.php
    config-table.php dialog.php dialplan.php dispatcher.php domains.php
    dynamic-routing.php groups.php header-routing.php load-balancer.php
    mi-commands.php rtpengine.php siptrace.php statistics.php status-report.php
    subscribers.php tenants.php tls-management.php trunk-dids.php trunk-providers.php
    trunk-status.php uac-registrant.php users.php
    memory-status.php pike-monitor.php ratelimit.php usrloc.php hash-tables.php
    nat-helper.php tcp-connections.php topology-hiding.php processes.php
    blacklists.php version.php timers.php presence.php avp-inspector.php
    system-events.php system-health.php search.php profile.php reports.php
    system-config.php help.php about.php feedback.php feedback-list.php notes.php
    alert-history.php gateway-health.php call-queue.php rtpengine-status.php
    subscriber-stats.php failover.php topology.php cache-manager.php
    system-logs.php scheduled-tasks.php api-docs.php audit-log.php audit-export.php
)

for page in "${pages[@]}"; do
    check_http "$BASE_URL/$page" 302
done

echo "--- Static Assets ---"
[ -f "web/tsisip/css/tsisip-theme.css" ] && echo "  OK: tsisip-theme.css" || { echo "  FAIL: tsisip-theme.css"; FAIL=$((FAIL + 1)); }
[ -f "web/tsisip/js/mi-actions.js" ] && echo "  OK: mi-actions.js" || { echo "  FAIL: mi-actions.js"; FAIL=$((FAIL + 1)); }

echo ""
echo "=== Results ==="
echo "Passed: $PASS"
echo "Failed: $FAIL"

if [ $FAIL -gt 0 ]; then
    echo "HEALTH CHECK FAILED"
    exit 1
else
    echo "ALL HEALTH CHECKS PASSED"
    exit 0
fi
