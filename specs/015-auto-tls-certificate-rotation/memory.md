# Feature Memory — 015: Automated TLS Certificate Rotation

> Maintained by memory-md extension.
> Created: 2026-05-20
> Last updated: 2026-05-20

---

## Current Scope

Fully automated certificate issuance, renewal, and zero-downtime reload for OpenSIPS SIP-TLS (port 5061/tcp) and RTPengine DTLS-SRTP. Supports dual certificate sources:

1. **Let's Encrypt ACME v2** — public SIP endpoint via `certbot` container (HTTP-01 standalone/webroot, staging support)
2. **Tailscale internal certificates** — tailnet endpoint via `tailscale-cert` container

Key implementation artefacts:
- `docker/certbot/Dockerfile`, `entrypoint.sh`, `deploy-hook.sh`, `healthcheck.sh`
- `docker/tailscale-cert/Dockerfile`, `renew.sh`
- `docker/certbot-exporter/Dockerfile`, `exporter.py`
- `docker-compose.yml` services: `certbot`, `tailscale-cert`, `certbot-exporter`
- `opensips/opensips.cfg.tpl` — `mi_http.so` on port 8888, `tls_mgm` paths pointing to `/certs/live/`
- `scripts/tls-reload.sh` — MI HTTP primary, SIGHUP fallback
- `docker/entrypoint.sh` — bootstraps `/certs/live` from runtime credential files if empty
- `tests/integration/test-tls-rotation.sh` — end-to-end validation pipeline

All 12 Acceptance Criteria verified and marked complete.

## Relevant Decisions

- **AD-015-1**: Atomic certificate swap via temp-file + `mv -f` — prevents OpenSIPS from reading a partially written certificate during `tls_reload`
- **AD-015-2**: MI HTTP as primary reload path — `curl -X POST http://opensips:8888/mi/tls_reload` with SIGHUP and `docker kill --signal=HUP` fallbacks
- **AD-015-3**: Shared `tls_certs` Docker volume — mounted into `opensips` (rw), `rtpengine` (ro), `certbot`, `tailscale-cert`, `opensips-exporter`, `certbot-exporter`
- **AD-015-4**: Bootstrap from credential files — `docker/entrypoint.sh` copies runtime credentials to `/certs/live` on first startup so OpenSIPS has valid certs before certbot issues its first LE cert
- **AD-015-5**: Certbot cron at 02:00 UTC inside container — no host-level cron or systemd timers

## Active Architecture Constraints

| Constraint | Evidence | Status |
|---|---|---|
| Docker-first | All renewal logic in containers; no host certbot/cron | Pass |
| PostgreSQL-only | No DB changes required for this feature | Pass |
| Module validity | `mi_http.so`, `httpd.so`, `tls_mgm.so`, `proto_tls.so` are OpenSIPS 3.6 LTS modules | Pass |
| Credential hygiene | Runtime credentials injected via Docker secrets or env vars; nothing committed | Pass |
| Network isolation | MI HTTP on port 8888 bound to `sip_internal` (internal: true); no host port mapping | Pass |
| Zero host-published ports for Asterisk/PostgreSQL | Unchanged by this feature | Pass |

## Accepted Deviations

- **Baseline single active cert**: Spec mentions symlink to `server.crt`; implementation uses atomic `mv` copy instead. Safer and achieves the same observable behavior.
- **RTPengine DTLS reload**: RTPengine picks up new certs on process restart; controlled rolling restart is the documented fallback since live DTLS cert reload is version-dependent.
- **Certbot-exporter port**: Plan specified 9102; implementation uses 9101 (consistent with `certbot-exporter` service in compose and avoids collision with backup exporter).

## Relevant Security Constraints

- Atomic deploy-hook prevents race conditions during certificate updates
- Certificate validation (`openssl x509 -checkend 86400`, `openssl rsa -check`) blocks deployment of invalid certs
- Secure file permissions: `644` for certs/chain, `600` for private keys
- Fallback on any renewal failure preserves existing valid certificate
- Alertmanager fires at 30d, 14d, 7d, 1d, and on renewal failure
- No credentials baked into image layers; all runtime-injected

## Related Historical Lessons

- ACME staging environment must be used for all non-production testing to avoid rate limits
- `mv` is atomic on the same filesystem — temp files must be written to the same volume as the target
- MI HTTP interface must bind to an internal Docker network only; exposing it publicly would create a management plane attack surface
- Cron inside containers requires a proper init system or foreground process (`crond -f`); background daemons may be reaped

## Conflict Warnings

- None at this time.

## Retrieval Notes

- Search terms: TLS, certificate, rotation, certbot, tailscale, ACME, letsencrypt, mi_http, tls_reload, tls_mgm, DTLS, zero-downtime
- Related features: 007 (TLS/SRTP baseline), 003 (Prometheus/Grafana observability), 014-A (wave 5 TLS tests), 016 (audit log compliance)
