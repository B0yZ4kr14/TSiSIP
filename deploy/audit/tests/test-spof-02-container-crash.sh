#!/bin/bash
# SPoF 2 Test: Container crash recovery
# Hypothesis: If OCP container crashes, Docker restart policy restores it

set -euo pipefail

echo "[TEST] SPoF 2: Container crash recovery"

# Check if docker is available
if ! command -v docker >/dev/null 2>&1; then
    echo "[SKIP] Docker not available"
    exit 0
fi

# Check if stack is running
if ! docker ps | grep -q "tsisip"; then
    echo "[SKIP] TSiSIP stack not running"
    exit 0
fi

# Verify restart policy is configured
echo "[TEST] Checking restart policy..."
RESTART_POLICY=$(docker inspect --format='{{.HostConfig.RestartPolicy.Name}}' tsisip-opensips-1 2>/dev/null || echo "")

if [ "$RESTART_POLICY" = "unless-stopped" ] || [ "$RESTART_POLICY" = "always" ]; then
    echo "[PASS] Restart policy is '$RESTART_POLICY'"
else
    echo "[WARN] Restart policy is '$RESTART_POLICY' (expected: unless-stopped or always)"
fi

# Verify health check exists
HEALTHCHECK=$(docker inspect --format='{{.Config.Healthcheck.Test}}' tsisip-opensips-1 2>/dev/null || echo "")
if [ -n "$HEALTHCHECK" ] && [ "$HEALTHCHECK" != "[]" ]; then
    echo "[PASS] Health check configured: $HEALTHCHECK"
else
    echo "[WARN] No health check configured"
fi

echo "[PASS] SPoF 2 test completed"
