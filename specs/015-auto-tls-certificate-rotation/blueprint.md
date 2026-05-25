# Blueprint — Automated TLS Certificate Rotation

## Overview

Introduce fully automated certificate issuance, renewal, and zero-downtime reload for OpenSIPS SIP-TLS (port 5061/tcp) and RTPengine DTLS-SRTP. Supports dual certificate sources: Let's Encrypt ACME v2 for the public SIP endpoint, and Tailscale internal certificates for the tailnet endpoint.

## Requirements

1. **Automated Public Certificate Lifecycle**: Integrate Let's Encrypt ACME v2 via containerized `certbot` service.
2. **Automated Internal Certificate Lifecycle**: Integrate Tailscale `tailscale cert` for tailnet machines.
3. **Zero-Downtime Reload in OpenSIPS**: Reload TLS profile via MI `tls_reload` without process restart.
4. **Shared Certificate Storage**: Store live certificates in Docker volume `tls_certs`; never bake into image layers.
5. **Proactive Renewal**: Attempt renewal when within 30 days of expiry; daily check schedule.
6. **Graceful Fallback**: If renewal fails, retain current valid certificate and continue operations.
7. **Monitoring & Alerting**: Expose expiry and renewal failure metrics to Prometheus; Alertmanager alerts at 30d, 14d, 7d, 1d, and on renewal failure.
8. **Docker-First Deployment**: All renewal logic runs inside containers; no host-level `certbot` or `cron`.

## Architecture

- **Certificate Sources**:
  - Let's Encrypt (public): Alpine-based `certbot` container with `cronie`; HTTP-01 challenge; daily at 02:00 UTC.
  - Tailscale (internal): `tailscale-cert` container; daily check; idempotent reissue within 14 days of expiry.
- **Storage**: Named volume `tls_certs` mounted into `opensips`, `rtpengine`, `certbot`, `tailscale-cert`.
- **Zero-Downtime Reload**: OpenSIPS MI HTTP interface on port 8888 (internal network only); `curl -X POST http://opensips:8888/mi/tls_reload`.
- **Atomic Update**: Deploy-hook writes to temp file, then `mv` into place.
- **Monitoring**: `certbot-exporter` exposes `certbot_days_until_expiry`, `certbot_renewal_failure_total`; Alertmanager rules for expiry stages.

## Implementation Plan

### Wave 1: Infrastructure & Storage Layout
- Add `tls_certs`, `certbot_data`, `tailscale_state` named volumes.
- Mount `tls_certs` into `opensips`, `rtpengine`, `opensips-exporter`.
- Update `.env.example` with `TLS_DOMAIN`, `ACME_EMAIL`, `ACME_STAGING`, `TS_HOSTNAME`.
- Bootstrap `/certs/live/` from `/run/secrets/` on initial startup.

### Wave 2: Core Implementation — Certbot & Tailscale
- `docker/certbot/Dockerfile` (Alpine with `certbot`, `curl`, `openssl`, `crontabs`).
- `docker/certbot/entrypoint.sh`: initial issuance, daily cron at 02:00 UTC, `crond -f`.
- `docker/certbot/deploy-hook.sh`: validate cert, atomic `mv`, trigger `tls_reload`.
- `docker/tailscale-cert/Dockerfile` and `renew.sh`.

### Wave 3: OpenSIPS Integration
- Add `mi_http.so` and `httpd` to `opensips.cfg.tpl`; listen on 8888.
- Update `tls_mgm` paths to `/certs/live/`.
- Refactor `scripts/tls-reload.sh` to use MI HTTP as primary.

### Wave 4: Monitoring & Alerting
- `docker/certbot-exporter/` with Prometheus metrics on port 9102.
- Append certificate alerts to `docker/prometheus/alert-rules.yml`.

### Wave 5: Testing & Validation
- `docker compose config` validation.
- ACME staging issuance test.
- Atomic deploy hook verification.
- MI HTTP `tls_reload` test.
- Renewal failure fallback test.
- Prometheus metrics verification.
- CI scan passes.

## Tasks

**Wave 1: Infrastructure & Storage Layout**
- T1.1: Add named volumes to `docker-compose.yml`
- T1.2: Mount `tls_certs` into `opensips`
- T1.3: Mount `tls_certs` into `rtpengine`; update DTLS flags
- T1.4: Mount `tls_certs` into `opensips-exporter`
- T1.5: Append env vars to `.env.example`
- T1.6: Bootstrap certs in `docker/entrypoint.sh`
- T1.7: Validate `docker compose config`

**Wave 2: Core Implementation — Certbot & Tailscale**
- T2.1: Create `docker/certbot/Dockerfile`
- T2.2: Create `docker/certbot/entrypoint.sh`
- T2.3: Create `docker/certbot/deploy-hook.sh`
- T2.4: Create `docker/tailscale-cert/Dockerfile`
- T2.5: Create `docker/tailscale-cert/renew.sh`
- T2.6: Append service blocks to `docker-compose.yml`
- T2.7: Build certbot and tailscale-cert images

**Wave 3: OpenSIPS Integration**
- T3.1: Add `mi_http.so` and `httpd` to `opensips.cfg.tpl`
- T3.2: Update `tls_mgm` paths to `/certs/live/`
- T3.3: Refactor `scripts/tls-reload.sh` to MI HTTP
- T3.4: Update `TLS_CERT_PATH` in exporter
- T3.5: Build OpenSIPS and validate config

**Wave 4: Monitoring & Alerting**
- T4.1: Create `certbot-exporter` Dockerfile and exporter
- T4.2: Add `certbot-exporter` service to compose files
- T4.3: Append certificate alerts to `alert-rules.yml`
- T4.4: Validate alert rules YAML

**Wave 5: Testing & Validation**
- T5.1: Validate `docker compose config`
- T5.2: ACME staging issuance test
- T5.3: Atomic deploy hook verification
- T5.4: MI HTTP `tls_reload` test
- T5.5: Renewal failure fallback test
- T5.6: Prometheus metrics verification
- T5.7: CI scan passes
- T5.8: Update `AGENTS.md`

## Validation

- AC1: Let's Encrypt certificate issuance completes ACME HTTP-01 challenge; `openssl x509` shows `Issuer: C = US, O = Let's Encrypt`.
- AC2: Tailscale certificate issuance writes valid cert; `openssl x509` shows `Issuer: CN = Tailscale CA`.
- AC3: OpenSIPS MI HTTP responds to `curl /mi/version` and `/mi/tls_reload` with HTTP 200.
- AC4: `tls_reload` returns HTTP 200; active calls stable; new handshake uses new cert.
- AC5: Daily cron runs at 02:00 UTC; logs show `certbot renew --quiet`.
- AC6: Certificate with 29 days remaining triggers automatic renewal and deploy.
- AC7: ACME failure retains existing cert; Alertmanager fires `CertRenewalFailed`; next daily run clears alert.
- AC8: Atomic update verified — `server.crt` never partially written during renewal.
- AC9: Prometheus metrics include `opensips_tls_certificate_expiry_timestamp` and `certbot_days_until_expiry`.
- AC10: Alertmanager dispatches notifications for all alert conditions.
- AC11: `which certbot` on Docker host returns nothing (all containerized).
- AC12: Dual-mode coexistence — both Let's Encrypt and Tailscale certs held in `tls_certs`.

## Risks & Dependencies

| Risk | Mitigation |
|---|---|
| ACME rate-limiting blocks issuance | Exponential backoff; use `--staging` for testing |
| `tls_reload` drops connections on specific patch levels | Test under load in staging; maintain manual restart runbook |
| Tailscale auth expires | Monitor node auth state; alert on `tailscale cert` failure |
| Race condition during atomic `mv` | Write to temp file, then `mv -f`; `mv` is atomic on same filesystem |
| Port 80 conflict for ACME HTTP-01 | Use `certbot standalone` with explicit port or prefer DNS-01 |
| Certbot container recreation loses ACME account | Mount `certbot_data` as persistent Docker volume |

**Dependencies**: OpenSIPS 3.6 LTS (`mi_http.so`, `tls_mgm.so`); Docker Compose ≥2.20; Let's Encrypt ACME v2; Tailscale daemon/auth; valid DNS A/AAAA record; Prometheus + Alertmanager (Feature 003).
