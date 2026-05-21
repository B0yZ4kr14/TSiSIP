# TSiSIP VPS 24h Stabilization — Progress Report

**Date**: 2026-05-21  
**Phase**: Wave 0-2 (Baseline + RED + partial GREEN)  
**Status**: In Progress

---

## Correções Aplicadas (docker-compose.vps.yml)

### T6.1 — backup memswap_limit
**Problem**: `memswap_limit: 128m < mem_limit: 256m` causes Docker error:
`Minimum memoryswap limit should be larger than memory limit`

**Fix**: `memswap_limit: 128m → 256m`

### T6.2 — postgres capabilities
**Problem**: Official postgres:16 entrypoint requires `chown` and `su` to `postgres`
user. With `cap_drop: [ALL]`, entrypoint fails with:
```
chmod: changing permissions of '/var/lib/postgresql/data': Operation not permitted
error: failed switching to 'postgres': operation not permitted
```

**Fix**: Added `cap_add: [SETUID, SETGID, CHOWN, FOWNER, DAC_OVERRIDE]`

### T6.3 — rtpengine listen-ng default
**Problem**: `RTPENGINE_INTERNAL_IP` unset causes invalid argument:
`--listen-ng=:22222`

**Fix**: Added default `:-127.0.0.1` → `--listen-ng=${RTPENGINE_INTERNAL_IP:-127.0.0.1}:22222`

### T6.4 — opensips image alignment
**Problem**: `tsisip/opensips:test` missing compiled modules (`proto_wss.so`, `httpd.so`)
causing parse errors on startup.

**Fix**: Use `tsisip/opensips:latest` which includes all required modules.

### T6.5 — opensips healthcheck timing
**Problem**: OpenSIPS 3.6 module initialization exceeds `start_period: 25s` on first boot,
causing premature health check failures.

**Fix**: `start_period: 25s → 60s`, `retries: 3 → 5`

---

## Test Harness Created

| Test | File | Purpose |
|---|---|---|
| T2 | `tests/vps-stabilization/test-vps-health.sh` | Container health verification |
| T3 | `tests/vps-stabilization/test-vps-sip.sh` | OPTIONS 200 + INVITE 407 probes |
| T4 | `tests/vps-stabilization/test-vps-ocp.sh` | OCP login page + health endpoint |
| T5 | `tests/vps-stabilization/rollback-runbook.sh` | Abort triggers + rollback steps |
| — | `tests/vps-stabilization/diagnose-opensips.sh` | OpenSIPS startup diagnostic |

---

## Known Issues (require VPS environment)

### rtpengine — DTLS certificates
The rtpengine command includes:
```
--dtls-cert-file=/certs/live/server.crt
--dtls-key-file=/certs/live/server.key
```

If `tls_certs` volume is empty (no certbot has run), rtpengine exits with code 255.
**Mitigation**: Ensure certificates are provisioned before starting stack, or bootstrap
dummy certificates for initial testing.

### backup — healthcheck status
Backup container shows `unhealthy` in local tests. The healthcheck script
(`/usr/local/bin/healthcheck.sh`) may require a running PostgreSQL connection.
Further investigation needed on VPS.

### certbot — ACME variables
`TLS_DOMAIN` and `ACME_EMAIL` are unset in local environment. On VPS, these
must be configured in `.env` before starting certbot.

---

## Next Steps (VPS Go-Live)

1. **Provision secrets** on VPS: ensure all files in `/opt/tsisip/secrets/` exist
2. **Bootstrap TLS certificates** (or use dummy certs for initial smoke test)
3. **Run T2-T4 tests** against running stack
4. **Validate T6 fixes** under realistic VPS load
5. **Complete T7-T14** (DB schema, port security, refactoring, evidence)
6. **Execute F1-F4** final verification

---

## Commits

- `b47e294` — VPS-lite compose corrections + stabilization test harness
- `cc72e8e` — opensips healthcheck + image alignment
