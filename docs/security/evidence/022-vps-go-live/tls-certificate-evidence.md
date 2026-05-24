# TLS Certificate Chain Evidence

**Date**: 2026-05-23
**Target**: tsiapp.io

---

## Execution

```bash
bash scripts/verify-tls-chain.sh
```

## Results

| Check | Expected | Actual | Status |
|---|---|---|---|
| Certificate validity | Valid, expires in 90 days | [PENDING DNS] | BLOCKED |
| Certificate chain | Full chain present | [PENDING DNS] | BLOCKED |
| Auto-rotation | Configured (30 days before expiry) | [PENDING DNS] | BLOCKED |
| Deploy hook | OpenSIPS reload script present | [PENDING DNS] | BLOCKED |

## Blockers

- DNS A record for `tsiapp.io` → `179.190.15.116` must be configured
- `CERTBOT_STAGING=1` must be set to `0`
