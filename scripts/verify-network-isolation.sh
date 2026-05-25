#!/bin/bash
# TSiSIP Network Isolation Verification (SG3.3)
set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PASS=0
FAIL=0

pass() { echo "[PASS] $*"; ((PASS++)) || true; }
fail() { echo "[FAIL] $*"; ((FAIL++)) || true; }

info() { echo "[INFO] $*"; }

info "=== Network Isolation Verification ==="

# Check 1: internal networks
for compose in docker-compose.yml docker-compose.prod.yml docker-compose.vps.yml; do
    f="$PROJECT_ROOT/$compose"
    [ -f "$f" ] || continue
    for net in sip_internal db_internal; do
        if grep -A2 "^  ${net}:" "$f" | grep -q 'internal: true'; then
            pass "$compose: ${net} is internal"
        else
            fail "$compose: ${net} missing internal flag"
        fi
    done
done

# Check 2: No host ports on asterisk/postgres
for svc in asterisk_pbx_1 asterisk_pbx_2 postgres; do
    HAS=$(awk "/^  ${svc}:/,/^  [a-z]/{print}" "$PROJECT_ROOT/docker-compose.yml" | grep -c '^    ports:' || true)
    if [ "$HAS" -eq 0 ]; then
        pass "${svc}: no host ports"
    else
        fail "${svc}: has host ports"
    fi
done

# Check 3: RTPengine control socket not wildcard
NG=$(grep -o 'listen-ng=[^[:space:]]*' "$PROJECT_ROOT/docker-compose.yml" | head -1 || true)
if echo "$NG" | grep -qE 'listen-ng=.*\{.*INTERNAL_IP.*\}'; then
    pass "RTPengine control socket: internal IP variable"
elif echo "$NG" | grep -qE 'listen-ng=127\.0\.0\.1'; then
    pass "RTPengine control socket: loopback"
elif echo "$NG" | grep -qE 'listen-ng=0\.0\.0\.0'; then
    fail "RTPengine control socket: wildcard"
else
    info "RTPengine control socket: manual review needed"
fi

# Check 4: Backup metrics binding
if grep -A20 "^  backup:" "$PROJECT_ROOT/docker-compose.yml" | grep -qE 'METRICS_ADDR:\s*0\.0\.0\.0'; then
    fail "backup: wildcard metrics address in docker-compose.yml"
else
    pass "backup: no wildcard metrics address"
fi

echo ""
echo "Network Isolation: $PASS passed, $FAIL failed"
[ $FAIL -eq 0 ] && { echo "All checks passed"; exit 0; } || { echo "Violations detected"; exit 1; }
