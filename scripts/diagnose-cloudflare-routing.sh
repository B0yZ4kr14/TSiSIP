#!/bin/bash
# TSiSIP Cloudflare Routing Diagnostic Script
# Run this on the VPS to verify local health

set -euo pipefail

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo "==================================="
echo "TSiSIP Routing Diagnostics"
echo "==================================="
echo ""

# Test 1: Local nginx
echo -n "1. Local nginx (127.0.0.1): "
if curl -sSL -H 'Host: tsiapp.io' --max-time 5 http://127.0.0.1/TSiSIP/login.php 2>/dev/null | grep -q 'TSiSIP'; then
    echo -e "${GREEN}PASS${NC} — TSiSIP reachable"
else
    echo -e "${RED}FAIL${NC} — TSiSIP not responding"
fi

# Test 2: Container health
echo -n "2. OCP container: "
if docker exec tsisip-ocp-1 curl -sSL --max-time 5 http://localhost/login.php 2>/dev/null | grep -q 'TSiSIP'; then
    echo -e "${GREEN}PASS${NC} — Container healthy"
else
    echo -e "${RED}FAIL${NC} — Container not responding"
fi

# Test 3: Nginx config
echo -n "3. Nginx /TSiSIP/ location: "
if grep -q 'location /TSiSIP/' /etc/nginx/nginx.conf 2>/dev/null; then
    echo -e "${GREEN}PASS${NC} — Config present"
else
    echo -e "${RED}FAIL${NC} — Config missing"
fi

# Test 4: Container IP
echo -n "4. OCP container IP: "
IP=$(docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' tsisip-ocp-1 2>/dev/null || echo "unknown")
if [ "$IP" != "unknown" ] && [ -n "$IP" ]; then
    echo -e "${GREEN}$IP${NC}"
else
    echo -e "${RED}unknown${NC}"
fi

# Test 5: Nginx proxy target
echo -n "5. Proxy target matches container: "
PROXY_IP=$(grep -A 2 'location /TSiSIP/' /etc/nginx/nginx.conf 2>/dev/null | grep proxy_pass | sed 's/.*http:\/\///;s/:.*//;s/\/.*//' || echo "none")
# Container may have multiple IPs (multiple networks), check if proxy IP is in the list
if echo "$IP" | grep -q "$PROXY_IP"; then
    echo -e "${GREEN}MATCH${NC} — $PROXY_IP"
else
    echo -e "${YELLOW}MISMATCH${NC} — nginx: $PROXY_IP, container: $IP"
fi

# Test 6: Tailscale (optional)
echo -n "6. Tailscale bypass: "
if curl -sSL -H 'Host: tsiapp.io' --max-time 3 http://100.111.74.69/TSiSIP/login.php 2>/dev/null | grep -q 'TSiSIP'; then
    echo -e "${GREEN}PASS${NC} — Tailscale working"
else
    echo -e "${YELLOW}SKIP${NC} — Tailscale not accessible from this host"
fi

echo ""
echo "==================================="
echo "Remote Test (run from your laptop):"
echo "==================================="
echo "  curl -sSL https://tsiapp.io/TSiSIP/login.php | wc -c"
echo "  Expected: ~1917 (TSiSIP)"
echo "  If getting ~2433: Cloudflare Pages is intercepting"
echo ""
