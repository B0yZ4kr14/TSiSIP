# Cloudflare Routing Issue — TSiSIP vs OrthoPlus

## Problem Summary

**URL**: `https://tsiapp.io/TSiSIP/login.php`  
**Expected**: TSiSIP Control Panel login page (~1917 bytes)  
**Actual**: OrthoPlus Enterprise SPA (~2433 bytes)

## Root Cause Analysis

### 1. VPS is Working Correctly
```bash
# Local test on VPS → TSiSIP ✓
curl -H 'Host: tsiapp.io' http://127.0.0.1/TSiSIP/login.php
# Returns: TSiSIP login page (1917 bytes)

# Container test → TSiSIP ✓
docker exec tsisip-ocp-1 curl -sSL http://localhost/login.php
# Returns: TSiSIP login page
```

### 2. Nginx Configuration is Correct
```nginx
location /TSiSIP/ {
    proxy_pass http://172.20.0.9:80/;  # OCP container
    proxy_set_header Host \$host;
    proxy_set_header X-Real-IP \$remote_addr;
    proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto \$scheme;
}
```

### 3. Cloudflare Edge is NOT Proxying to VPS
```bash
# Remote test via Cloudflare → OrthoPlus ❌
curl https://tsiapp.io/TSiSIP/login.php
# Returns: OrthoPlus Enterprise SPA (2433 bytes)
# Headers: server: cloudflare, cf-cache-status: DYNAMIC
```

### 4. Cloudflare Pages is Intercepting ALL Requests

**Confirmed**: Cloudflare Pages (or a Worker) is configured for `tsiapp.io` and serves the OrthoPlus Enterprise SPA with history-based routing. This means Pages returns the OrthoPlus `index.html` for **ALL** paths, including `/TSiSIP/*` and even non-existent paths.

```bash
# Even non-existent paths return OrthoPlus
curl https://tsiapp.io/nonexistent-path-12345
# Returns: OrthoPlus SPA (same 2433 bytes)
```

### 5. DNS Configuration
```
tsiapp.io A  → Cloudflare anycast IPs
NS: riya.ns.cloudflare.com, oswald.ns.cloudflare.com
```

The A records point to Cloudflare anycast IPs, not the VPS. This is expected for Cloudflare-proxied domains.

### 6. Tailscale Workaround Confirmed
The Tailscale IP bypasses Cloudflare entirely and reaches nginx directly:
```bash
curl -H 'Host: tsiapp.io' http://100.111.74.69/TSiSIP/login.php
# Returns: TSiSIP login page (1917 bytes) ✓
```

---

## Immediate Workaround

**Use Tailscale** to access TSiSIP directly, bypassing Cloudflare entirely:
```bash
# Access directly via Tailscale IP:
curl -H 'Host: tsiapp.io' http://100.111.74.69/TSiSIP/login.php
```

---

## Permanent Solutions

### Option 1: Disable Cloudflare Pages for `tsiapp.io` (Recommended)

**Why**: The root cause is Cloudflare Pages intercepting all traffic. Disabling Pages allows Cloudflare to proxy requests to your VPS origin normally.

**Steps**:
1. Go to Cloudflare Dashboard → Pages
2. Find the project for `tsiapp.io` or `OrthoPlus-Enterprise`
3. Remove `tsiapp.io` from custom domains, or delete the project
4. Wait 1-2 minutes for DNS propagation
5. Verify: `curl https://tsiapp.io/TSiSIP/login.php` should now return TSiSIP

### Option 2: Create Dedicated Subdomain for TSiSIP

**Why**: Keeps both OrthoPlus (on root domain via Pages) and TSiSIP (on subdomain via VPS) working simultaneously.

**Steps**:
1. Go to Cloudflare Dashboard → DNS
2. Add A record:
   - **Name**: `tsisip`
   - **IPv4 address**: [VPS public IP]
   - **Proxy status**: DNS only (gray cloud) — bypasses Cloudflare entirely
3. Access TSiSIP at: `https://tsisip.tsiapp.io/TSiSIP/`

### Option 3: Cloudflare Worker Route Bypass

**Why**: Keeps OrthoPlus on root domain but adds a Worker route to bypass Pages for `/TSiSIP/*`.

**Steps**:
1. Go to Cloudflare Dashboard → Workers & Pages
2. Create a new Worker that routes `/TSiSIP/*` paths to the VPS origin
3. Add route binding: `tsiapp.io/TSiSIP/*`
4. Deploy and test

### Option 4: Cloudflare Tunnel (Most Robust)

**Why**: Eliminates the need for public VPS IP exposure, works behind NAT/firewall.

**Pre-requisite**: `cloudflared` is already installed on the VPS.

**Steps**:
1. Authenticate cloudflared on the VPS
2. Create and configure a tunnel
3. Add CNAME in Cloudflare DNS pointing to the tunnel
4. Verify tunnel health in Zero Trust dashboard

---

## Verification Steps

After applying any solution, run these checks:

```bash
# 1. Should return TSiSIP, not OrthoPlus
curl -sSL https://tsiapp.io/TSiSIP/login.php | grep -o 'TSiSIP'

# 2. Check response size (should be ~1917 bytes, not 2433)
curl -sSL -o /dev/null -w "%{size_download}\n" https://tsiapp.io/TSiSIP/login.php

# 3. Check headers (should NOT contain OrthoPlus paths)
curl -sSLI https://tsiapp.io/TSiSIP/login.php | grep -i orthoplus || echo "No OrthoPlus references"
```

---

## VPS Status

| Component | Status | Notes |
|-----------|--------|-------|
| Container OCP | ✅ Healthy | Responds on internal Docker IP |
| Nginx config | ✅ Correct | Proxies /TSiSIP/ to OCP container |
| Code deployed | ✅ Latest | Commit f198f46 |
| DNS resolution | ✅ Cloudflare | A records → Cloudflare anycast |
| Cloudflare Pages | ❌ Intercepting | Serves OrthoPlus for ALL paths |
| Cloudflared | ✅ Installed | Not yet configured |
| Tailscale | ✅ Working | Bypasses Cloudflare |

---

## Recommended Action Plan

1. **Immediate**: Use Tailscale to access TSiSIP for testing/development
2. **Short-term**: Apply Option 1 (disable Cloudflare Pages) or Option 2 (dedicated subdomain) via Cloudflare Dashboard
3. **Long-term**: Consider Option 4 (Cloudflare Tunnel) for enhanced security

---

*Last updated: 2026-05-29*
