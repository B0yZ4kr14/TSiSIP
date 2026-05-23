#!/bin/bash
# T2.1 — RED Health Test
# Expects: containers are NOT healthy before fixes
# Should FAIL (RED) initially, then PASS (GREEN) after stabilization

set -euo pipefail

COMPOSE_FILE="docker-compose.vps.yml"
FAILED=0

echo "=== RED Health Test ==="
echo "Date: $(date -Iseconds)"

# Check if stack is running
if ! docker compose -f "$COMPOSE_FILE" ps >/dev/null 2>&1; then
    echo "RED: Stack not running (expected in RED phase)"
    exit 1
fi

# Check health status for critical services
for service in opensips rtpengine postgres ocp; do
    STATUS=$(docker compose -f "$COMPOSE_FILE" ps "$service" --format json 2>/dev/null | grep -o '"Health":"[^"]*"' | cut -d'"' -f4 || echo "unknown")
    if [ "$STATUS" = "healthy" ]; then
        echo "UNEXPECTED: $service is healthy (should be unhealthy/failing in RED)"
    else
        echo "RED: $service status=$STATUS (expected)"
        FAILED=1
    fi
done

if [ $FAILED -eq 1 ]; then
    echo "RED CONFIRMED: At least one service is not healthy"
    exit 1
else
    echo "WARNING: All services are healthy — RED phase may be complete"
    exit 0
fi
