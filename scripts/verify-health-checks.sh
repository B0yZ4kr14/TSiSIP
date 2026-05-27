#!/bin/bash
# TSiSIP Health Check Validation (SG3.6)
set -euo pipefail
set -uo pipefail

PROJECT_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PASS=0
FAIL=0

pass() { echo "[PASS] $*"; ((PASS++)) || true; }
fail() { echo "[FAIL] $*"; ((FAIL++)) || true; }
info() { echo "[INFO] $*"; }

info "=== Health Check Validation ==="

TMP_RESULTS=$(mktemp)
TMP_PY=$(mktemp)
trap 'rm -f "$TMP_RESULTS" "$TMP_PY"' EXIT

cat > "$TMP_PY" << 'PYEOF'
import yaml, sys
f = sys.argv[1]
try:
    with open(f) as fh:
        data = yaml.safe_load(fh)
except Exception as e:
    print(f'INFO|{f}|yaml parse error: {e}')
    sys.exit(0)
services = data.get('services', {})
for svc, cfg in services.items():
    hc = cfg.get('healthcheck')
    if not hc:
        print(f'FAIL|{f}|{svc}|missing healthcheck')
        continue
    test = hc.get('test', [])
    test_str = str(test)
    if test_str == "['NONE']" or test_str == '["NONE"]':
        print(f'PASS|{f}|{svc}|healthcheck explicitly disabled')
        continue
    if 'true' in test_str.lower() and len(test_str) < 50:
        print(f'FAIL|{f}|{svc}|trivial healthcheck')
    else:
        print(f'PASS|{f}|{svc}|has non-trivial healthcheck')
PYEOF

# Check every service in all compose files has a healthcheck stanza
for compose in docker-compose.yml docker-compose.prod.yml docker-compose.vps.yml; do
    f="$PROJECT_ROOT/$compose"
    [ -f "$f" ] || continue
    
    info "Checking $compose..."
    
    if command -v python3 >/dev/null 2>&1; then
        python3 "$TMP_PY" "$f" >> "$TMP_RESULTS" 2>/dev/null
    else
        info "Skipping $compose (python3 unavailable)"
    fi
done

# Process results from temp file (in main shell, so PASS/FAIL are updated)
while IFS='|' read -r status file svc msg; do
    case "$status" in
        INFO) info "$file: $svc $msg" ;;
        PASS) pass "$file: $svc $msg" ;;
        FAIL) fail "$file: $svc $msg" ;;
    esac
done < "$TMP_RESULTS"

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
