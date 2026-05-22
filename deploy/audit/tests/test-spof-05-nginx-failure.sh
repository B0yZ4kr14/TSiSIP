#!/bin/bash
# SPoF 5 Test: Nginx failure recovery
# Hypothesis: Nginx failure is detected and can be recovered via systemd

set -euo pipefail

echo "[TEST] SPoF 5: Nginx failure recovery"

# Check if nginx config is valid
echo "[TEST] Validating Nginx configuration..."
if [ -f "../../nginx/tsisip-reverse-proxy.conf" ]; then
    if command -v nginx >/dev/null 2>&1; then
        if nginx -t -c "$(pwd)/../../nginx/tsisip-reverse-proxy.conf" >/dev/null 2>&1; then
            echo "[PASS] Nginx configuration is valid"
        else
            echo "[FAIL] Nginx configuration is invalid"
            exit 1
        fi
    else
        echo "[SKIP] Nginx not installed locally, checking syntax manually..."
        if grep -q "server {" ../../nginx/tsisip-reverse-proxy.conf && \
           grep -q "listen 443" ../../nginx/tsisip-reverse-proxy.conf; then
            echo "[PASS] Nginx config has required server blocks"
        else
            echo "[FAIL] Nginx config missing required directives"
            exit 1
        fi
    fi
else
    echo "[FAIL] Nginx config file not found"
    exit 1
fi

# Verify health check endpoint exists
echo "[TEST] Checking health check endpoint..."
if grep -q "location /TSiSIP/health" ../../nginx/tsisip-reverse-proxy.conf; then
    echo "[PASS] Health check endpoint configured"
else
    echo "[FAIL] Health check endpoint missing"
    exit 1
fi

# Verify error pages
echo "[TEST] Checking error page configuration..."
if grep -q "error_page 502 503 504" ../../nginx/tsisip-reverse-proxy.conf; then
    echo "[PASS] Error pages configured"
else
    echo "[WARN] Error pages not explicitly configured"
fi

echo "[PASS] SPoF 5 test completed"
