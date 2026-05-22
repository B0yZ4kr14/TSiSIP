#!/bin/bash
# TSiSIP Nginx TLS Configuration Audit (SG3.5)
set -uo pipefail

PROJECT_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PASS=0
FAIL=0

pass() { echo "[PASS] $*"; ((PASS++)) || true; }
fail() { echo "[FAIL] $*"; ((FAIL++)) || true; }
info() { echo "[INFO] $*"; }

info "=== Nginx TLS Configuration Audit ==="

NGINX_CONF="$PROJECT_ROOT/deploy/nginx/tsisip-reverse-proxy.conf"
NGINX_SSL="$PROJECT_ROOT/deploy/nginx/ssl-params.conf"

if [ ! -f "$NGINX_CONF" ]; then
    fail "No nginx configuration file found"
    echo "Nginx TLS: $PASS passed, $FAIL failed"
    exit 1
fi

# Build file list for grep
GREP_FILES="$NGINX_CONF"
[ -f "$NGINX_SSL" ] && GREP_FILES="$GREP_FILES $NGINX_SSL"

# Check 1: TLSv1.0/1.1 are disabled
info "Checking TLS protocol versions..."
if grep -qiE 'ssl_protocols.*TLSv1([[:space:]]|$|;)' $GREP_FILES 2>/dev/null; then
    fail "TLSv1.0 enabled"
else
    pass "TLSv1.0 disabled"
fi

if grep -qiE 'ssl_protocols.*TLSv1\.1' $GREP_FILES 2>/dev/null; then
    fail "TLSv1.1 enabled"
else
    pass "TLSv1.1 disabled"
fi

# Check 2: TLSv1.2/1.3 are present
if grep -qiE 'ssl_protocols.*TLSv1\.2' $GREP_FILES 2>/dev/null; then
    pass "TLSv1.2 enabled"
else
    fail "TLSv1.2 not found"
fi

if grep -qiE 'ssl_protocols.*TLSv1\.3' $GREP_FILES 2>/dev/null; then
    pass "TLSv1.3 enabled"
else
    info "TLSv1.3 not explicitly enabled"
fi

# Check 3: Strong cipher suite present
info "Checking cipher configuration..."
if grep -qiE 'ssl_ciphers' $GREP_FILES 2>/dev/null; then
    CIPHERS=$(grep -oiE 'ssl_ciphers[[:space:]]+[^;]+' $GREP_FILES 2>/dev/null | sed 's/^[^:]*://' | head -1 || true)
    if echo "$CIPHERS" | grep -qiE 'HIGH|AES|ECDHE|CHACHA20'; then
        pass "Strong cipher suite configured"
    else
        fail "Cipher suite may be weak: $CIPHERS"
    fi
else
    fail "No ssl_ciphers directive found"
fi

# Check 4: HSTS header with max-age >= 63072000
info "Checking HSTS configuration..."
# Use awk to extract the full HSTS value including semicolons inside quotes
HSTS=$(grep -oiE 'add_header[[:space:]]+Strict-Transport-Security[[:space:]]+"[^"]+"' $GREP_FILES 2>/dev/null | sed 's/^[^:]*://' | head -1 || true)
if [ -n "$HSTS" ]; then
    AGE=$(echo "$HSTS" | grep -oE 'max-age=[0-9]+' | grep -oE '[0-9]+' || true)
    if [ -n "$AGE" ] && [ "$AGE" -ge 63072000 ]; then
        pass "HSTS max-age is ${AGE}"
    else
        fail "HSTS max-age is ${AGE:-missing} (expected >= 63072000)"
    fi
    
    if echo "$HSTS" | grep -qiE 'includeSubDomains'; then
        pass "HSTS includes includeSubDomains"
    else
        fail "HSTS missing includeSubDomains"
    fi
    
    if echo "$HSTS" | grep -qiE 'preload'; then
        pass "HSTS includes preload"
    else
        info "HSTS missing preload directive"
    fi
else
    fail "No HSTS header found"
fi

# Check 5: OCSP stapling enabled
info "Checking OCSP stapling..."
if grep -qiE 'ssl_stapling[[:space:]]+on' $GREP_FILES 2>/dev/null; then
    pass "OCSP stapling enabled"
else
    fail "OCSP stapling not enabled"
fi

if grep -qiE 'ssl_stapling_verify[[:space:]]+on' $GREP_FILES 2>/dev/null; then
    pass "OCSP stapling verification enabled"
else
    info "OCSP stapling verification not explicitly enabled"
fi

# Check 6: Rate limiting for OCP path
info "Checking rate limiting configuration..."
if grep -qiE 'limit_req.*ocp|limit_req_zone.*ocp' "$NGINX_CONF" 2>/dev/null; then
    pass "Rate limiting configured for OCP"
else
    if grep -qiE 'limit_req' "$NGINX_CONF" 2>/dev/null; then
        pass "Rate limiting configured (generic)"
    else
        info "No rate limiting found for OCP path"
    fi
fi

# Check 7: SSL certificate and key paths
info "Checking SSL certificate configuration..."
if grep -qiE 'ssl_certificate[[:space:]]+' $GREP_FILES 2>/dev/null; then
    pass "ssl_certificate directive present"
else
    fail "ssl_certificate directive missing"
fi

if grep -qiE 'ssl_certificate_key[[:space:]]+' $GREP_FILES 2>/dev/null; then
    pass "ssl_certificate_key directive present"
else
    fail "ssl_certificate_key directive missing"
fi

echo ""
echo "Nginx TLS: $PASS passed, $FAIL failed"
[ $FAIL -eq 0 ] && { echo "All checks passed"; exit 0; } || { echo "Violations detected"; exit 1; }
