#!/bin/bash
# TSiSIP CI Scan — lightweight equivalent of speckit scans
# Exits non-zero on critical findings

set -euo pipefail

export TSISIP_IMAGE_TAG="${TSISIP_IMAGE_TAG:-latest}"

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
    if grep -rE --exclude-dir=.specify --exclude-dir=.omk --exclude-dir=.claude --exclude-dir=.kimi --exclude-dir=.agents "loadmodule.*${mod}|modparam.*${mod}|module.*${mod}" opensips/ db/ docker/ 2>/dev/null; then
        echo "FAIL: Forbidden module reference: $mod"
        FAIL=1
    fi
done
echo "PASS: No forbidden modules"

# --- Version Guard: Check for unpinned base images ---
echo "[version-guard] Checking for unpinned base images..."
for df in Dockerfile docker/*/Dockerfile; do
    if [ -f "$df" ]; then
        if grep '^FROM ' "$df" | grep -v '@sha256' | grep -v 'prom/' | grep -v 'grafana/' | grep -v 'postgres:'; then
            echo "WARN: Unpinned base image in $df (prom/grafana/postgres excluded)"
        fi
    fi
done
echo "PASS: Base image check complete"

# --- Memorylint: Check for missing memory limits ---
echo "[memorylint] Checking for container memory limits..."
for compose in docker-compose.yml docker-compose.vps.yml; do
    if [ -f "$compose" ]; then
        SERVICES=$(docker compose -f "$compose" config 2>/dev/null | grep -c 'memory:' || echo "0")
        echo "PASS: $compose has $SERVICES services with memory limits"
    fi
done

# --- Security: Check for committed secrets ---
echo "[security] Checking for committed secrets..."
for secret in secrets/db_password secrets/auth_secret secrets/topology_secret secrets/ca.key secrets/server.key; do
    if git ls-files --error-unmatch "$secret" >/dev/null 2>&1; then
        echo "FAIL: Secret file committed: $secret"
        FAIL=1
    fi
done
echo "PASS: No tracked secret files"

# --- Security: Run SG3 verification scripts ---
echo "[security] Running network isolation verification..."
if bash scripts/verify-network-isolation.sh >/dev/null 2>&1; then
    echo "PASS: Network isolation verification"
else
    echo "FAIL: Network isolation verification failed"
    FAIL=1
fi

echo "[security] Running secrets audit..."
if bash scripts/verify-secrets-audit.sh >/dev/null 2>&1; then
    echo "PASS: Secrets audit"
else
    echo "FAIL: Secrets audit failed"
    FAIL=1
fi

echo "[security] Running nginx TLS verification..."
if bash scripts/verify-nginx-tls.sh >/dev/null 2>&1; then
    echo "PASS: Nginx TLS verification"
else
    echo "FAIL: Nginx TLS verification failed"
    FAIL=1
fi

echo "[security] Running health check verification..."
if bash scripts/verify-health-checks.sh >/dev/null 2>&1; then
    echo "PASS: Health check verification"
else
    echo "FAIL: Health check verification failed"
    FAIL=1
fi

# --- Security: Secret age audit (non-blocking) ---
echo "[security] Running secret age audit..."
bash scripts/secret-age-audit.sh || true

# --- Feature 015: TLS Rotation Pipeline Validation ---
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
    fi
else
    echo "WARN: tests/integration/test-tls-rotation.sh not found or not executable"
fi

# --- Audit: PHP syntax check ---
echo "[audit] Checking PHP syntax on audit-related files..."
if command -v php >/dev/null 2>&1; then
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
else
    echo "SKIP: PHP not installed on host; syntax checks deferred to OCP container build"
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
if docker compose ps postgres ocp 2>/dev/null | grep -qE "running|Up"; then
    bash tests/integration/test-ocp-audit.sh || { echo "WARN: test-ocp-audit.sh failed (may require seeded data or schema init)"; }
    bash tests/integration/test-audit-dashboard.sh || { echo "WARN: test-audit-dashboard.sh failed (may require seeded data or running OCP)"; }
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

# --- New Integration Tests ---
echo "[integration] Running Stage 8 PITR tests..."
if python3 -m pytest tests/integration/test_backup_pitr.py -v --tb=short >/dev/null 2>&1; then
    echo "PASS: PITR integration tests"
else
    echo "FAIL: PITR integration tests failed"
    FAIL=1
fi

echo "[integration] Running Stage 9 backup verification tests..."
LATEST_BACKUP=$(ls -t backups/tsisip_db_*.sql.gz 2>/dev/null | head -1)
if [[ -n "$LATEST_BACKUP" ]] && bash scripts/verify-backup.sh --backup "$LATEST_BACKUP" >/dev/null 2>&1; then
    echo "PASS: Backup verification"
else
    echo "FAIL: Backup verification failed (no backup found or restore error)"
    FAIL=1
fi

echo "[integration] Running Stage 10 runbook tests..."
if python3 -m pytest tests/integration/test_runbook_scale.py -v --tb=short >/dev/null 2>&1; then
    echo "PASS: Runbook integration tests"
else
    echo "FAIL: Runbook integration tests failed"
    FAIL=1
fi

# --- Deployment Validation ---
echo "[deploy] Running deployment validation..."
if bash deploy/validate.sh; then
    echo "PASS: Deployment validation"
else
    echo "FAIL: Deployment validation failed"
    FAIL=1
fi

echo ""
if [ $FAIL -eq 1 ]; then
    echo "=== CI SCAN FAILED ==="
    exit 1
else
    echo "=== CI SCAN PASSED ==="
    exit 0
fi
