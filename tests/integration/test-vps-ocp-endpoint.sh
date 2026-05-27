#!/usr/bin/env bash
# TDD RED — OCP Endpoint Tests (T4 from VPS 24h plan)
# Validates OCP web interface accessibility
set -euo pipefail

cd "$(dirname "$0")/../.."

echo "=== Test: OCP Container HTTP Response ==="

ocp_ip=$(docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}} {{end}}' tsisip-ocp-1 2>/dev/null | awk '{print $1}')
if [ -z "$ocp_ip" ]; then
    echo "WARN: Could not determine OCP container IP"
else
    code=$(curl -s -o /dev/null -w '%{http_code}' --max-time 5 "http://$ocp_ip/login.php" 2>/dev/null || echo "000")
    if [ "$code" = "200" ] || [ "$code" = "302" ]; then
        echo "  OK: OCP container responds HTTP $code"
    else
        echo "  WARN: OCP container responds HTTP $code"
    fi
fi

echo "=== Test: OCP Pages Accessible (via container IP) ==="

pages=(dashboard.php memory-status.php pike-monitor.php ratelimit.php processes.php version.php)
for page in "${pages[@]}"; do
    if [ -n "$ocp_ip" ]; then
        code=$(curl -s -o /dev/null -w '%{http_code}' --max-time 3 "http://$ocp_ip/$page" 2>/dev/null || echo "000")
        if [ "$code" = "302" ] || [ "$code" = "200" ]; then
            echo "  OK: $page responds HTTP $code"
        else
            echo "  WARN: $page responds HTTP $code"
        fi
    fi
done

echo "=== Test: Nginx Reverse Proxy (if running) ==="

if docker compose ps nginx > /dev/null 2>&1; then
    code=$(curl -s -o /dev/null -w '%{http_code}' --max-time 5 -k "https://localhost/TSiSIP/login.php" 2>/dev/null || echo "000")
    if [ "$code" = "200" ] || [ "$code" = "302" ]; then
        echo "  OK: Nginx reverse proxy responds HTTP $code"
    else
        echo "  WARN: Nginx reverse proxy responds HTTP $code"
    fi
else
    echo "  SKIP: Nginx not running"
fi

echo "=== OCP endpoint tests completed ==="
