# Cloudflare Routing Issue — TSiSIP vs OrthoPlus

## Problem Summary

**URL**: `https://tsiapp.io/TSiSIP/login.php`  
**Expected**: TSiSIP Control Panel login page  
**Actual**: OrthoPlus Enterprise SPA (React app)

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
    ...
}
```

### 3. Cloudflare is NOT Proxying to VPS
```bash
# Remote test via Cloudflare → OrthoPlus ❌
curl https://tsiapp.io/TSiSIP/login.php
# Returns: OrthoPlus Enterprise SPA
```

**Evidence**:
- Response size: 698 bytes (too small for TSiSIP)
- Content: `/OrthoPlus-Enterprise/` paths
- `cf-cache-status: DYNAMIC` (not cached)
- Server header: `cloudflare`

### 4. Cloudflare Pages is Intercepting Requests

The most likely cause is that **Cloudflare Pages** is configured for `tsiapp.io` and is serving the OrthoPlus Enterprise SPA with history-based routing. This means Pages returns the OrthoPlus `index.html` for ALL paths, including `/TSiSIP/*`.

### 5. DNS Configuration
```
tsiapp.io A     → 104.26.0.106  (Cloudflare)
tsiapp.io A     → 104.26.1.106  (Cloudflare)
tsiapp.io A     → 172.67.69.110 (Cloudflare)
```

## Solutions

### Option 1: Disable Cloudflare Pages (Recommended)
1. Go to Cloudflare Dashboard → Pages
2. Find the project for `tsiapp.io` or `OrthoPlus-Enterprise`
3. Either:
   - Delete the Pages project, OR
   - Change the custom domain to a subdomain (e.g., `orthoplus.tsiapp.io`)

### Option 2: Create Dedicated Subdomain for TSiSIP
1. Go to Cloudflare Dashboard → DNS
2. Add A record:
   - Name: `tsisip`
   - IPv4: `179.190.15.116`
   - Proxy status: DNS only (gray cloud) OR Proxied (orange cloud)
3. Access TSiSIP at: `https://tsisip.tsiapp.io/TSiSIP/`

### Option 3: Cloudflare Worker Route
1. Go to Cloudflare Dashboard → Workers & Pages
2. Create/edit a Worker for `tsiapp.io`
3. Add route rule:
   ```javascript
   if (url.pathname.startsWith('/TSiSIP/')) {
       return fetch('http://179.190.15.116/TSiSIP/' + url.pathname.slice(7), request);
   }
   ```
4. Add route: `tsiapp.io/TSiSIP/*`

### Option 4: Cloudflare Tunnel (Advanced)
1. Install `cloudflared` on VPS
2. Create tunnel pointing to `http://127.0.0.1:80`
3. Configure public hostname: `tsiapp.io/TSiSIP/*`

## Verification Steps

After applying any solution, verify:
```bash
# Should return TSiSIP, not OrthoPlus
curl -sSL https://tsiapp.io/TSiSIP/login.php | grep -o 'TSiSIP'
```

## VPS Status

- **Container OCP**: Healthy ✓
- **Nginx config**: Correct ✓
- **Code deployed**: Latest ✓
- **DNS resolution**: Cloudflare IPs (not VPS)
