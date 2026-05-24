# TLS Certificate & SSL Labs Evidence (G5 + G9)

**Date**: 2026-05-23T23:07:00-03:00
**Domain**: tsiapp.io
**VPS IP**: 179.190.15.116

---

## DNS Configuration Status

### Current DNS Resolution

```
$ dig +short A tsiapp.io
104.26.1.106
104.26.0.106
172.67.69.110
```

**Status**: BLOCKED — DNS A record points to Cloudflare edge (104.26.x.x, 172.67.x.x), NOT to VPS IP (179.190.15.116).

### Required DNS Change

| Record | Current Value | Required Value | Status |
|--------|--------------|----------------|--------|
| A (tsiapp.io) | 104.26.1.106 | 179.190.15.116 | PENDING |
| A (tsiapp.io) | 104.26.0.106 | 179.190.15.116 | PENDING |
| A (tsiapp.io) | 172.67.69.110 | 179.190.15.116 | PENDING |

**Action Required**: Update A record at DNS provider (Cloudflare) to point to 179.190.15.116.

---

## Certificate Status (Certbot)

### Current Certificate State

```
Certificate not yet issued
```

**Reason**: Certbot cannot complete ACME challenge because DNS does not resolve to VPS IP.

### Certificate Configuration

| Parameter | Value | Status |
|-----------|-------|--------|
| TLS_DOMAIN | tsiapp.io | Configured |
| ACME_EMAIL | admin@tsiapp.io | Configured |
| CERTBOT_STAGING | 1 | Staging mode (safe for testing) |
| Auto-renewal | Enabled via cron | Configured |
| Deploy hook | docker compose restart nginx | Configured |

### Expected Post-DNS Certificate Chain

```
Client → Cloudflare (edge TLS) → Nginx (origin TLS via Let's Encrypt)
```

| Layer | Certificate Source | Protocol | Status |
|-------|-------------------|----------|--------|
| Origin (Nginx) | Let's Encrypt | TLS 1.2+ | Pending DNS |
| Edge (Cloudflare) | Cloudflare | TLS 1.0/1.1/1.2+ | Active (currently Grade B) |

---

## SSL Labs Target (G5)

**Target Grade**: A+

**Current Blockers**:
1. DNS A record must point to VPS IP
2. Certbot must issue valid certificate
3. Cloudflare Minimum TLS Version must be set to 1.2 (currently allows 1.0/1.1)
4. HSTS must be enabled and preloaded

**Remediation**:
- Step 1: Update DNS A record to 179.190.15.116
- Step 2: Run `CERTBOT_STAGING=0 docker compose up certbot` to issue production certificate
- Step 3: Run SSL Labs scan: `https://www.ssllabs.com/ssltest/analyze.html?d=tsiapp.io`
- Step 4: Update Cloudflare dashboard: SSL/TLS → Minimum TLS Version → 1.2
- Step 5: Enable HSTS preload in Cloudflare dashboard

---

## Conclusion

**Status**: BLOCKED — DNS A record misconfiguration prevents certificate issuance and SSL Labs verification.

**G5 (SSL Labs)**: Cannot execute until DNS is fixed.
**G9 (TLS Chain)**: Cannot verify live certificate until DNS is fixed. Configuration is correct.

**Next Action**: Configure DNS A record for tsiapp.io → 179.190.15.116 at Cloudflare dashboard.
