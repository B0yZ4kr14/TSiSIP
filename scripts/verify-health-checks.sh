#!/bin/bash
# TSiSIP Health Check Validation (SG3.6)
set -uo pipefail

PROJECT_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PASS=0
FAIL=0

pass() { echo "[PASS] $*"; ((PASS++)) || true; }
fail() { echo "[FAIL] $*"; ((FAIL++)) || true; }
info() { echo "[INFO] $*"; }

info "=== Health Check Validation ==="

# Check every service in all compose files has a healthcheck stanza
for compose in docker-compose.yml docker-compose.prod.yml docker-compose.vps.yml; do
    f="$PROJECT_ROOT/$compose"
    [ -f "$f" ] || continue
    
    info "Checking $compose..."
    
    # Use docker compose config if available for accurate service list
    if command -v docker >/dev/null 2>&1 && docker compose version >/dev/null 2>&1; then
        SERVICES=$(docker compose -f "$f" config 2>/dev/null | grep -E '^  [a-zA-Z0-9_-]+:' | sed 's/^  //;s/://' | grep -v '^networks$' | grep -v '^volumes$' | grep -v '^secrets$' | grep -v '^configs$' || true)
    else
        # Fallback: parse services block only (stop at networks/volumes/secrets)
        SERVICES=$(awk '/^services:/{in_services=1; next} /^[a-zA-Z]/{in_services=0} in_services && /^  [a-zA-Z0-9_-]+:/{print substr($0, 3); next}' "$f" | sed 's/://' || true)
    fi
    
    for svc in $SERVICES; do
        # Check if service block has healthcheck
        HAS_HC=$(awk "/^  ${svc}:/,/^  [a-zA-Z]/{print}" "$f" | grep -c '^    healthcheck:' || true)
        if [ "$HAS_HC" -eq 0 ]; then
            fail "$compose: ${svc} missing healthcheck"
            continue
        fi
        
        # Extract healthcheck test
        HC_TEST=$(awk "/^  ${svc}:/,/^  [a-zA-Z]/{print}" "$f" | awk '/^    healthcheck:/{found=1} found && /^      test:/{print; exit}' || true)
        
        if echo "$HC_TEST" | grep -qE 'CMD-SHELL.*true\s*\]'; then
            fail "$compose: ${svc} healthcheck is trivial (CMD-SHELL true)"
        elif echo "$HC_TEST" | grep -qE '\["CMD",\s*"true"\]|\["CMD-SHELL",\s*"true"\]'; then
            fail "$compose: ${svc} healthcheck is trivial"
        else
            pass "$compose: ${svc} has non-trivial healthcheck"
        fi
    done
done

# Check custom healthcheck scripts exist for services that reference them
info "Checking custom healthcheck scripts..."
for script in docker/rtpengine/healthcheck.sh docker/opensips/healthcheck.sh docker/asterisk/healthcheck.sh docker/backup/healthcheck.sh docker/certbot/healthcheck.sh; do
    sp="$PROJECT_ROOT/$script"
    if [ -f "$sp" ]; then
        pass "${script} exists"
    else
        BASENAME=$(basename "$script")
        FOUND=$(find "$PROJECT_ROOT/docker" -name "$BASENAME" 2>/dev/null | head -1)
        if [ -n "$FOUND" ]; then
            pass "${BASENAME} found at ${FOUND#$PROJECT_ROOT/}"
        else
            info "${script} not found (may be embedded in Dockerfile)"
        fi
    fi
done

echo ""
echo "Health Checks: $PASS passed, $FAIL failed"
[ $FAIL -eq 0 ] && { echo "All checks passed"; exit 0; } || { echo "Violations detected"; exit 1; }
