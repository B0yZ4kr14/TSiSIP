# SSL Labs Evidence — tsiapp.io

**Date**: 2026-05-23
**Tool**: Qualys SSL Labs (https://www.ssllabs.com/ssltest/)
**Target**: https://tsiapp.io

---

## Test Results

| Metric | Result | Requirement | Status |
|---|---|---|---|
| Overall Grade | [PENDING DNS A RECORD] | A+ | BLOCKED |
| Certificate | Let's Encrypt (staging) | Valid, 90-day rotation | PENDING |
| TLS Version | [PENDING] | 1.2+ minimum | PENDING |
| HSTS | [PENDING] | Enabled with preload | PENDING |
| Forward Secrecy | [PENDING] | Required | PENDING |
| HTTP Strict Transport Security | [PENDING] | max-age=31536000 | PENDING |

## Blockers

- DNS A record for `tsiapp.io` → `179.190.15.116` must be configured
- `CERTBOT_STAGING=1` must be set to `0`
- Certbot container must successfully complete ACME challenge

## Evidence Artifacts

- [ ] Screenshot of SSL Labs report
- [ ] Certificate chain export
- [ ] HSTS header verification (`curl -I https://tsiapp.io`)
