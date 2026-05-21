# Feature 015: Automated TLS Certificate Rotation — Implementation Plan

## Wave 1: Infrastructure & Storage Layout (Coder Agent)

Agent: `coder`

- [ ] W1.1: Add `tls_certs`, `certbot_data`, and `tailscale_state` named volumes to `docker-compose.yml`.
- [ ] W1.2: Update `opensips` service in `docker-compose.yml` to mount `tls_certs:/certs/live:rw` alongside existing credential mounts.
- [ ] W1.3: Update `rtpengine` service in `docker-compose.yml` to mount `tls_certs:/certs/live:ro` and update DTLS cert/key command flags to use `/certs/live/` paths.
- [ ] W1.4: Update `opensips-exporter` service in `docker-compose.yml` to mount `tls_certs:/certs/live:ro` and set `TLS_CERT_PATH=/certs/live/server.crt`.
- [ ] W1.5: Update `.env.example` with `TLS_DOMAIN`, `ACME_EMAIL`, `ACME_STAGING`, and `TS_HOSTNAME` variables.
- [ ] W1.6: Update `docker/entrypoint.sh` to bootstrap `/certs/live/server.crt`, `server.key`, and `chain.pem` from `/run/secrets/` when the volume is empty (initial startup).
- [ ] W1.7: Validate `docker compose config` syntax after compose changes.

## Wave 2: Core Implementation — Certbot & Tailscale (Coder Agent)

Agent: `coder`
Depends on: W1

- [ ] W2.1: Create `docker/certbot/Dockerfile` (Alpine-based with `certbot`, `curl`, `openssl`, `crontabs`).
- [ ] W2.2: Create `docker/certbot/entrypoint.sh` — handles initial issuance (`certbot certonly`), installs daily cron job at 02:00 UTC, and runs `crond -f`.
- [ ] W2.3: Create `docker/certbot/deploy-hook.sh` — validates new cert/key with `openssl`, atomically moves files into `/certs/live/`, and triggers `tls_reload` via MI HTTP.
- [ ] W2.4: Create `docker/tailscale-cert/Dockerfile` (lightweight image with Tailscale CLI).
- [ ] W2.5: Create `docker/tailscale-cert/renew.sh` — runs `tailscale cert`, validates output, atomically copies to `/certs/live/`, and triggers `tls_reload` via MI HTTP.
- [ ] W2.6: Add `certbot` and `tailscale-cert` service definitions to `docker-compose.yml` with correct networks, volumes, environment, and resource limits.
- [ ] W2.7: Build `certbot` and `tailscale-cert` images locally (`docker compose build certbot tailscale-cert`).

## Wave 3: OpenSIPS Integration (Coder Agent)

Agent: `coder`
Depends on: W1
Parallel with Wave 2 (config-only wave).

- [ ] W3.1: Add `mi_http.so` module load and `listen = http:0.0.0.0:8888` to `opensips/opensips.cfg.tpl`.
- [ ] W3.2: Update `tls_mgm` paths in `opensips/opensips.cfg.tpl` from `/etc/opensips/tls/` to `/certs/live/` for `certificate`, `private_key`, and `ca_list`.
- [ ] W3.3: Update `scripts/tls-reload.sh` to support MI HTTP (`curl -X POST http://opensips:8888/mi/tls_reload`) as the default reload path, with `opensipsctl fifo` as fallback.
- [ ] W3.4: Update `docker/opensips-exporter/exporter.py` `TLS_CERT_PATH` default to `/certs/live/server.crt` (align with new volume mount).
- [ ] W3.5: Validate rendered OpenSIPS config syntax inside the built image (`opensips -c`).

## Wave 4: Monitoring & Alerting (Coder Agent)

Agent: `coder`
Depends on: W2, W3

- [ ] W4.1: Create `docker/certbot-exporter/Dockerfile` and `docker/certbot-exporter/exporter.py` exposing `certbot_renewal_success_timestamp`, `certbot_renewal_failure_total`, and `certbot_days_until_expiry` on port 9102.
- [ ] W4.2: Add `certbot-exporter` service to `docker-compose.yml` with `tls_certs:/certs/live:ro` mount and scrape target registration.
- [ ] W4.3: Append certificate alerting rules to `docker/prometheus/alert-rules.yml`:
  - `CertificateExpiringSoon30d`
  - `CertificateExpiringSoon14d`
  - `CertificateExpiringSoon7d`
  - `CertificateExpired`
  - `CertRenewalFailed`
- [ ] W4.4: Validate Prometheus alert rule syntax (`promtool check rules` if available, or at least YAML lint).

## Wave 5: Testing & Validation (QA Agent)

Agent: `qa`
Depends on: W4

- [ ] W5.1: Run `docker compose config` and verify all services, volumes, and networks resolve without error.
- [ ] W5.2: Test ACME staging issuance against `docker compose up certbot` (use `--staging` flag) and verify certs land in `tls_certs`.
- [ ] W5.3: Verify atomic deploy hook — run `deploy-hook.sh` with a test lineage and confirm `server.crt` is never partially written (check via `lsof` or checksum before/after).
- [ ] W5.4: Simulate MI HTTP `tls_reload` by running `curl -fsSL -X POST http://opensips:8888/mi/tls_reload` from the certbot container and confirming HTTP 200.
- [ ] W5.5: Simulate renewal failure (invalid ACME auth) and confirm existing cert remains in `tls_certs` and OpenSIPS continues to serve it.
- [ ] W5.6: Verify Prometheus metrics endpoints (`opensips-exporter:9442`, `certbot-exporter:9102`) return expected TLS expiry metrics.
- [ ] W5.7: Run `scripts/ci-scan.sh` and confirm zero new blocking findings.
- [ ] W5.8: Update `AGENTS.md` Build and Test Commands section with Feature 015 validation commands if needed.

## Dependency Graph

```
W1 (Infrastructure) ──┬→ W2 (Certbot/Tailscale) ──┐
                      ├→ W3 (OpenSIPS Config) ─────┤
                      │                            ↓
                      │                       W4 (Monitoring)
                      │                            ↓
                      │                       W5 (QA)
```
