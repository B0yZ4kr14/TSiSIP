# Feature 014-A Tasks

## Wave 1: Infrastructure & Storage Layout

- [x] T1.1: Add `tls_certs`, `certbot_data`, and `tailscale_state` volume declarations to the global `volumes` section of `docker-compose.yml`.
  - **Files affected:** `docker-compose.yml`
  - **Dependencies:** None

- [x] T1.2: Mount `tls_certs:/certs/live:rw` into the `opensips` service and update its `depends_on` to include `certbot` (condition: service_started) if desired.
  - **Files affected:** `docker-compose.yml`
  - **Dependencies:** T1.1

- [x] T1.3: Mount `tls_certs:/certs/live:ro` into the `rtpengine` service and change `--dtls-cert-file` and `--dtls-key-file` from `/run/secrets/` to `/certs/live/`.
  - **Files affected:** `docker-compose.yml`
  - **Dependencies:** T1.1

- [x] T1.4: Mount `tls_certs:/certs/live:ro` into the `opensips-exporter` service and set environment variable `TLS_CERT_PATH=/certs/live/server.crt`.
  - **Files affected:** `docker-compose.yml`
  - **Dependencies:** T1.1

- [x] T1.5: Append `TLS_DOMAIN`, `ACME_EMAIL`, `ACME_STAGING`, and `TS_HOSTNAME` to `.env.example` with descriptive comments.
  - **Files affected:** `.env.example`
  - **Dependencies:** None

- [x] T1.6: Update `docker/entrypoint.sh` to check whether `/certs/live/server.crt` exists; if not, copy `server.crt`, `server.key`, and `ca.crt` from `/run/secrets/` into `/certs/live/` as a bootstrap step.
  - **Files affected:** `docker/entrypoint.sh`
  - **Dependencies:** None

- [x] T1.7: Run `docker compose config` to validate YAML syntax and service resolution after all Wave 1 compose edits.
  - **Files affected:** `docker-compose.yml`
  - **Dependencies:** T1.1, T1.2, T1.3, T1.4

## Wave 2: Core Implementation — Certbot & Tailscale

- [x] T2.1: Create `docker/certbot/Dockerfile` using Alpine base, installing `certbot`, `curl`, `openssl`, and `crontabs`.
  - **Files affected:** `docker/certbot/Dockerfile` (new)
  - **Dependencies:** T1.7

- [x] T2.2: Create `docker/certbot/entrypoint.sh` that performs initial ACME certificate issuance (with `--staging` support), sets up a daily cron job at 02:00 UTC, and starts `crond` in the foreground.
  - **Files affected:** `docker/certbot/entrypoint.sh` (new)
  - **Dependencies:** T2.1

- [x] T2.3: Create `docker/certbot/deploy-hook.sh` that validates the renewed certificate chain (`openssl x509 -checkend`), performs an atomic `mv` into `/certs/live/`, and calls `curl -fsSL -X POST "${OPENSIPS_MI_URL}/tls_reload"`.
  - **Files affected:** `docker/certbot/deploy-hook.sh` (new)
  - **Dependencies:** T2.1

- [x] T2.4: Create `docker/tailscale-cert/Dockerfile` using a minimal base image with the Tailscale CLI installed.
  - **Files affected:** `docker/tailscale-cert/Dockerfile` (new)
  - **Dependencies:** T1.7

- [x] T2.5: Create `docker/tailscale-cert/renew.sh` that runs `tailscale cert`, validates the output, atomically copies the certificate and key to `/certs/live/`, and triggers OpenSIPS `tls_reload` via MI HTTP.
  - **Files affected:** `docker/tailscale-cert/renew.sh` (new)
  - **Dependencies:** T2.4

- [x] T2.6: Append `certbot` and `tailscale-cert` service blocks to `docker-compose.yml`, including volume mounts, network membership (`sip_internal`), environment variables, resource limits, and restart policies.
  - **Files affected:** `docker-compose.yml`
  - **Dependencies:** T2.2, T2.3, T2.5

- [x] T2.7: Build the `certbot` and `tailscale-cert` images with `docker compose build certbot tailscale-cert` and verify both exit 0.
  - **Files affected:** `docker-compose.yml`
  - **Dependencies:** T2.6

## Wave 3: OpenSIPS Integration

- [x] T3.1: Add `loadmodule "httpd.so"` and `loadmodule "mi_http.so"`, with `modparam("httpd", "ip", "...")`, `modparam("httpd", "port", 8888)`, and `modparam("mi_http", "root", "/mi")` to `opensips/opensips.cfg.tpl`.
  - **Files affected:** `opensips/opensips.cfg.tpl`
  - **Dependencies:** None

- [x] T3.2: Update `tls_mgm` `certificate`, `private_key`, and `ca_list` paths in `opensips/opensips.cfg.tpl` from `/etc/opensips/tls/` to `/certs/live/`.
  - **Files affected:** `opensips/opensips.cfg.tpl`
  - **Dependencies:** T3.1

- [x] T3.3: Refactor `scripts/tls-reload.sh` to use MI HTTP (`curl -fsSL http://opensips:8888/mi/tls_reload`) as the primary reload mechanism, retaining SIGHUP as a fallback.
  - **Files affected:** `scripts/tls-reload.sh`
  - **Dependencies:** T3.1

- [x] T3.4: Update the default `TLS_CERT_PATH` in `docker/opensips-exporter/exporter.py` from `/run/secrets/server.crt` to `/certs/live/server.crt`.
  - **Files affected:** `docker/opensips-exporter/exporter.py`
  - **Dependencies:** T1.4

- [x] T3.5: Build the OpenSIPS image and run config validation (`opensips -c -f /etc/opensips/opensips.cfg`) inside a throwaway container to ensure `mi_http.so` and updated `tls_mgm` paths parse correctly.
  - **Files affected:** `Dockerfile`, `opensips/opensips.cfg.tpl`
  - **Dependencies:** T3.2, T3.4

## Wave 4: Monitoring & Alerting

- [x] T4.1: Create `docker/certbot-exporter/Dockerfile` (Python-based with `prometheus_client`) and `docker/certbot-exporter/exporter.py` that exposes `certbot_last_success_timestamp`, `certbot_renewal_failure_total{source}`, and `certbot_days_until_expiry{domain,source}` on port 9101.
  - **Files affected:** `docker/certbot-exporter/Dockerfile` (new), `docker/certbot-exporter/exporter.py` (new)
  - **Dependencies:** T2.7

- [x] T4.2: Add `certbot-exporter` service to `docker-compose.yml`, `docker-compose.prod.yml`, and `docker-compose.vps.yml` on `sip_internal` (and `db_internal` for Prometheus reachability), mounting `tls_certs:/certs/live:ro`, with resource limits (64M) and a healthcheck.
  - **Files affected:** `docker-compose.yml`, `docker-compose.prod.yml`, `docker-compose.vps.yml`
  - **Dependencies:** T4.1

- [x] T4.3: Append the six certificate alerts (`CertExpiry30d`, `CertExpiry14d`, `CertExpiry7d`, `CertExpiry1d`, `CertExpired`, `CertRenewalFailed`) to `docker/prometheus/alert-rules.yml` under a new `tsisip-certificates` group.
  - **Files affected:** `docker/prometheus/alert-rules.yml`
  - **Dependencies:** None

- [x] T4.4: Validate `docker/prometheus/alert-rules.yml` with `python -c "import yaml; yaml.safe_load(open('docker/prometheus/alert-rules.yml'))"` and verified `docker compose config` passes for all three compose files.
  - **Files affected:** `docker/prometheus/alert-rules.yml`
  - **Dependencies:** T4.3

## Wave 5: Testing & Validation

- [ ] T5.1: Run `docker compose config` after all service additions and verify no merge errors or missing environment variables.
  - **Files affected:** `docker-compose.yml`
  - **Dependencies:** T2.6, T3.5, T4.2

- [ ] T5.2: Execute an ACME staging run (`docker compose run --rm certbot --staging`) and inspect the `tls_certs` volume to confirm a valid certificate, key, and chain are present.
  - **Files affected:** `docker-compose.yml`, `docker/certbot/*`
  - **Dependencies:** T2.7

- [ ] T5.3: Run the deploy hook against a mock lineage directory and verify `server.crt` is replaced atomically (no zero-byte or truncated file observed during swap).
  - **Files affected:** `docker/certbot/deploy-hook.sh`
  - **Dependencies:** T2.3

- [ ] T5.4: From inside the `certbot` container, run `curl -fsSL http://opensips:8888/mi/version` and `curl -fsSL -X POST http://opensips:8888/mi/tls_reload`, confirming HTTP 200 responses.
  - **Files affected:** `opensips/opensips.cfg.tpl`
  - **Dependencies:** T3.5

- [ ] T5.5: Simulate an ACME failure (e.g., invalid `--server` or disconnected network), ensure the prior certificate remains in `tls_certs`, and confirm OpenSIPS continues to load the old certificate.
  - **Files affected:** `docker/certbot/*`
  - **Dependencies:** T5.2

- [ ] T5.6: Scrape `opensips-exporter:9442/metrics` and `certbot-exporter:9102/metrics`, verifying `opensips_tls_certificate_expiry_timestamp` and `certbot_days_until_expiry` are present and accurate.
  - **Files affected:** `docker/opensips-exporter/exporter.py`, `docker/certbot-exporter/exporter.py`
  - **Dependencies:** T4.1, T3.4

- [ ] T5.7: Execute `scripts/ci-scan.sh` and confirm no new blocking security or lint findings are introduced.
  - **Files affected:** `scripts/ci-scan.sh`
  - **Dependencies:** T5.1

- [ ] T5.8: Append Feature 014-A validation commands to the Build and Test Commands section of `AGENTS.md`.
  - **Files affected:** `AGENTS.md`
  - **Dependencies:** T5.7
