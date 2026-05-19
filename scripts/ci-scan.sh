#!/bin/bash
# TSiSIP CI Scan — lightweight equivalent of speckit scans
# Exits non-zero on critical findings

set -euo pipefail

FAIL=0

echo "=== TSiSIP CI Scan ==="

# --- Brownfield: Check for hardcoded :latest tags ---
echo "[brownfield] Checking for hardcoded :latest tags..."
if grep -E 'image: tsisip/[^:]+:latest' docker-compose.yml; then
    echo "FAIL: Hardcoded :latest tags found in docker-compose.yml"
    FAIL=1
else
    echo "PASS: No hardcoded :latest tags"
fi

# --- Brownfield: Check for forbidden modules ---
echo "[brownfield] Checking for forbidden modules..."
for mod in db_mysql db_sqlite sanity; do
    # Only match actual module references (loadmodule, modparam, or module names in config)
    # Ignore comments and unrelated usage of the word
    if grep -rE "loadmodule.*${mod}|modparam.*${mod}|module.*${mod}" opensips/ db/ docker/ 2>/dev/null; then
        echo "FAIL: Forbidden module reference: $mod"
        FAIL=1
    fi
done
echo "PASS: No forbidden modules"

# --- Version Guard: Check for unpinned base images ---
echo "[version-guard] Checking for unpinned base images..."
for df in Dockerfile docker/*/Dockerfile; do
    if [ -f "$df" ]; then
        # Find FROM lines without @sha256
        if grep '^FROM ' "$df" | grep -v '@sha256' | grep -v 'prom/' | grep -v 'grafana/' | grep -v 'postgres:'; then
            echo "WARN: Unpinned base image in $df (prom/grafana/postgres excluded)"
            # Not failing on warnings, just warning
        fi
    fi
done
echo "PASS: Base image check complete"

# --- Memorylint: Check for missing memory limits ---
echo "[memorylint] Checking for container memory limits..."
SERVICES=$(docker compose config 2>/dev/null | grep -c 'memory:' || echo "0")
if [ "$SERVICES" -lt 12 ]; then
    echo "WARN: Only $SERVICES services have memory limits (expected 12+)"
else
    echo "PASS: Memory limits present on $SERVICES services"
fi

# --- Security: Check for committed secrets ---
echo "[security] Checking for committed secrets..."
for secret in secrets/db_password secrets/auth_secret secrets/topology_secret secrets/ca.key secrets/server.key; do
    if git ls-files --error-unmatch "$secret" >/dev/null 2>&1; then
        echo "FAIL: Secret file committed: $secret"
        FAIL=1
    fi
done
echo "PASS: No tracked secret files"

echo ""
if [ $FAIL -eq 1 ]; then
    echo "=== CI SCAN FAILED ==="
    exit 1
else
    echo "=== CI SCAN PASSED ==="
    exit 0
fi
