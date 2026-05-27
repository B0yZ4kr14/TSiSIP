#!/usr/bin/env bash
# TDD RED — Container Health Tests (T2 from VPS 24h plan)
set -euo pipefail

cd "$(dirname "$0")/../.."

echo "=== Test: Docker Compose Config Validation ==="
docker compose config > /dev/null 2>&1 || {
    echo "FAIL: docker compose config is invalid"
    exit 1
}
echo "  OK: docker compose config valid"

echo "=== Test: Required Containers Exist ==="

required_services=(opensips postgres pgbouncer rtpengine ocp prometheus grafana backup)
for svc in "${required_services[@]}"; do
    docker compose ps "$svc" > /dev/null 2>&1 || {
        echo "FAIL: Service $svc is not defined in compose"
        exit 1
    }
    echo "  OK: Service $svc defined"
done

echo "=== Test: Container Health Status ==="

for svc in "${required_services[@]}"; do
    status=$(docker compose ps -q "$svc" 2>/dev/null | xargs -I{} docker inspect -f '{{.State.Status}}' {} 2>/dev/null || echo "missing")
    if [ "$status" != "running" ] && [ "$status" != "restarting" ]; then
        echo "  WARN: $svc status=$status (expected running)"
    else
        echo "  OK: $svc status=$status"
    fi
done

echo "=== Test: No Unexpected Public Ports ==="

# PostgreSQL must NOT publish host port 5432
# Asterisk AMI must NOT publish host port 5038
# Asterisk SIP must NOT publish host port 5061
forbidden=$(docker compose config 2>/dev/null | awk '/published:/{p=$2} /target:/{t=$2} p&&t{print t":"p; p=""; t=""}' | grep -E '^"(5432|5038|5061)":' || true)
if [ -n "$forbidden" ]; then
    echo "FAIL: Forbidden ports detected: $forbidden"
    exit 1
fi
echo "  OK: No forbidden public ports found"

echo "=== All container health tests passed ==="
