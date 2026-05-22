#!/bin/bash
# TSiSIP Secret Management Audit (SG3.4)
set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PASS=0
FAIL=0

pass() { echo "[PASS] $*"; ((PASS++)) || true; }
fail() { echo "[FAIL] $*"; ((FAIL++)) || true; }
info() { echo "[INFO] $*"; }

info "=== Secret Management Audit ==="

# Check 1: secrets directory is in .gitignore
if grep -qE '^secrets/?$' "$PROJECT_ROOT/.gitignore"; then
    pass "secrets/ is in .gitignore"
else
    fail "secrets/ not in .gitignore"
fi

# Check 2: No tracked files in secrets directory
TRACKED=$(git -C "$PROJECT_ROOT" ls-files secrets/ 2>/dev/null | wc -l)
if [ "$TRACKED" -eq 0 ]; then
    pass "No tracked files in secrets/"
else
    fail "Found $TRACKED tracked file(s) in secrets/"
fi

# Check 3: env files are gitignored
for envfile in .env .env.local .env.production; do
    if grep -q "^${envfile}" "$PROJECT_ROOT/.gitignore" 2>/dev/null; then
        pass "${envfile} is gitignored"
    else
        fail "${envfile} not gitignored"
    fi
done

# Check 4: All Docker Compose secrets mounts reference files under secrets directory
info "Checking Docker Compose secrets references..."
for compose in docker-compose.yml docker-compose.prod.yml docker-compose.vps.yml; do
    f="$PROJECT_ROOT/$compose"
    [ -f "$f" ] || continue
    BAD_REFS=$(grep -oE 'file: \./[^/]+/[^[:space:]]+' "$f" | grep -v 'file: \./secrets/' | wc -l || true)
    if [ "$BAD_REFS" -eq 0 ]; then
        pass "$compose: all secret files under secrets/"
    else
        fail "$compose: $BAD_REFS secret reference(s) outside secrets/"
    fi
done

# Check 5: Auth credential file is exactly 32 bytes if it exists
AUTH_FILE="$PROJECT_ROOT/secrets/auth_secret"
if [ -f "$AUTH_FILE" ]; then
    SIZE=$(stat -c%s "$AUTH_FILE" 2>/dev/null || stat -f%z "$AUTH_FILE" 2>/dev/null)
    if [ "$SIZE" -eq 32 ]; then
        pass "auth_secret is exactly 32 bytes"
    else
        fail "auth_secret is $SIZE bytes (expected 32)"
    fi
else
    info "auth_secret not present (expected in production)"
fi

# Check 6: No plaintext password columns in TSiSIP extension SQL
# The stock OpenSIPS schema contains a password column for backward compatibility;
# we verify TSiSIP extensions (02+, 03+) do not add plaintext columns
info "Checking TSiSIP extension SQL for plaintext passwords..."
SQL_DIR="$PROJECT_ROOT/db/init"
if [ -d "$SQL_DIR" ]; then
    PLAINTEXT=0
    for sqlfile in "$SQL_DIR"/0[2-9]-*.sql "$SQL_DIR"/[1-9]*.sql; do
        [ -f "$sqlfile" ] || continue
        if grep -qiE 'password\s+VARCHAR|password\s+TEXT|password\s+CHAR' "$sqlfile"; then
            PLAINTEXT=$((PLAINTEXT + 1))
            info "Plaintext column in: $(basename "$sqlfile")"
        fi
    done
    if [ "$PLAINTEXT" -eq 0 ]; then
        pass "No plaintext password columns in TSiSIP extension SQL"
    else
        fail "Found $PLAINTEXT TSiSIP extension file(s) with plaintext password columns"
    fi
    
    # Verify ha1 columns exist
    HA1_COLS=$(grep -riE 'ha1|ha1_sha256|ha1_sha512t256' "$SQL_DIR" | wc -l)
    if [ "$HA1_COLS" -ge 3 ]; then
        pass "HA1 hash columns present in schema"
    else
        fail "Missing HA1 hash columns in schema"
    fi
else
    info "db/init directory not found"
fi

# Check 7: No hardcoded secrets in source files
info "Scanning for hardcoded secrets in source..."
HARDCODED=0
for dir in opensips db docker; do
    [ -d "$PROJECT_ROOT/$dir" ] || continue
    # Use grep with line numbers, then filter out safe patterns
    grep -rinE 'password[[:space:]]*=[[:space:]]*["'"'"'][^"'"'"']+["'"'"']|secret[[:space:]]*=[[:space:]]*["'"'"'][^"'"'"']+["'"'"']|token[[:space:]]*=[[:space:]]*["'"'"'][^"'"'"']+["'"'"']' "$PROJECT_ROOT/$dir" 2>/dev/null | while IFS= read -r line; do
        # Skip lines that read from secret files or use variable expansion
        if echo "$line" | grep -qE 'read_secret|cat \$|PGPASSWORD_FILE|DB_PASS\b|\$\{[^}]+\}|\$[A-Z_]+'; then
            continue
        fi
        # Skip example/TODO/FIXME lines
        if echo "$line" | grep -qiE 'example|TODO|FIXME|placeholder'; then
            continue
        fi
        echo "$line"
    done > /tmp/hardcoded_$$.txt || true
    
    COUNT=$(wc -l < /tmp/hardcoded_$$.txt 2>/dev/null || echo 0)
    HARDCODED=$((HARDCODED + COUNT))
    if [ "$COUNT" -gt 0 ] && [ "$COUNT" -lt 20 ]; then
        while IFS= read -r line; do
            info "Potential: $line"
        done < /tmp/hardcoded_$$.txt
    fi
    rm -f /tmp/hardcoded_$$.txt
done

if [ "$HARDCODED" -eq 0 ]; then
    pass "No hardcoded secrets in opensips/docker/db source"
else
    fail "Found $HARDCODED potential hardcoded secret(s)"
fi

echo ""
echo "Secrets Audit: $PASS passed, $FAIL failed"
[ $FAIL -eq 0 ] && { echo "All checks passed"; exit 0; } || { echo "Violations detected"; exit 1; }
