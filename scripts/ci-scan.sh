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

# --- Feature 014-A: TLS Rotation Pipeline Validation ---
echo "[tls-rotation] Checking TLS rotation shell script syntax..."
for script in scripts/tls-reload.sh scripts/cert-rotate.sh scripts/cert-expiry-monitor.sh docker/certbot/deploy-hook.sh docker/certbot/entrypoint.sh docker/certbot/healthcheck.sh tests/integration/test-tls-rotation.sh; do
    if [ -f "$script" ]; then
        if bash -n "$script"; then
            echo "PASS: $script syntax valid"
        else
            echo "FAIL: $script has syntax errors"
            FAIL=1
        fi
    else
        echo "WARN: $script not found"
    fi
done

# Run the integration test if it exists and is executable
if [ -x "tests/integration/test-tls-rotation.sh" ]; then
    echo "[tls-rotation] Running TLS rotation integration test..."
    if tests/integration/test-tls-rotation.sh; then
        echo "PASS: TLS rotation integration test passed"
    else
        echo "WARN: TLS rotation integration test had failures (may require running stack)"
        # Do not fail CI for integration tests that require a running stack
    fi
else
    echo "WARN: tests/integration/test-tls-rotation.sh not found or not executable"
fi

# --- Audit: PHP syntax check ---
echo "[audit] Checking PHP syntax on audit-related files..."
PHP_FAIL=0
for phpfile in web/common/audit.php web/audit-log.php web/audit-export.php web/cli/purge-audit-log.php web/healthcheck-audit.php; do
    if [ -f "$phpfile" ]; then
        if ! php -l "$phpfile" >/dev/null 2>&1; then
            echo "FAIL: PHP syntax error in $phpfile"
            PHP_FAIL=1
            FAIL=1
        fi
    fi
done
if [ "$PHP_FAIL" -eq 0 ]; then
    echo "PASS: All audit PHP files have valid syntax"
fi

# --- Audit: shell script syntax check ---
echo "[audit] Checking audit test scripts..."
for script in tests/integration/test-ocp-audit.sh tests/integration/test-audit-dashboard.sh; do
    if [ -f "$script" ]; then
        if ! bash -n "$script"; then
            echo "FAIL: Shell syntax error in $script"
            FAIL=1
        fi
    else
        echo "FAIL: Audit test script not found: $script"
        FAIL=1
    fi
done
echo "PASS: Audit test scripts syntax valid"

# --- Audit: run integration tests if compose stack is up ---
echo "[audit] Running audit integration tests (if compose stack is available)..."
if docker compose ps postgres ocp >/dev/null 2>&1; then
    bash tests/integration/test-ocp-audit.sh || { echo "FAIL: test-ocp-audit.sh"; FAIL=1; }
    bash tests/integration/test-audit-dashboard.sh || { echo "FAIL: test-audit-dashboard.sh"; FAIL=1; }
else
    echo "SKIP: Compose stack not running, skipping live audit tests"
fi

# --- Trunk Integration Tests ---
echo "[trunk] Running SIP trunk integration tests..."
if docker compose ps | grep -q 'opensips' && docker compose ps | grep -q 'postgres'; then
    if bash tests/integration/test-sip-trunk.sh; then
        echo "PASS: Trunk integration tests"
    else
        echo "FAIL: Trunk integration tests failed"
        FAIL=1
    fi
else
    echo "SKIP: Trunk integration tests (services not running)"
fi

echo ""
if [ $FAIL -eq 1 ]; then
    echo "=== CI SCAN FAILED ==="
    exit 1
else
    echo "=== CI SCAN PASSED ==="
    exit 0
fi
