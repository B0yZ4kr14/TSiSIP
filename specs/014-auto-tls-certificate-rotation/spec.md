# Feature 014-A: Automated TLS Certificate Rotation

## Overview

TSiSIP currently relies on static X.509 certificates stored in `secrets/` and mounted into containers at startup (Feature 007 — TLS/SRTP Encryption). Certificate rotation is a fully manual, operator-driven process involving `docker/ca-tool/cert-rotate.sh`, file copies, and container restarts. This creates operational toil and increases the risk of expired certificates in production.

This feature introduces **fully automated certificate issuance, renewal, and zero-downtime reload** for OpenSIPS SIP-TLS (port 5061/tcp) and RTPengine DTLS-SRTP. It supports **dual certificate sources**:

1. **Let's Encrypt ACME v2** — for the public SIP endpoint (public IP configured via `HOST_PUBLIC_IP`).
2. **Tailscale internal certificates** — for the Tailscale tailnet endpoint (Tailscale IP configured via host networking).

A containerized certbot/renewal agent runs as a first-class Docker Compose service. It writes certificates to a shared Docker volume, validates them, and triggers OpenSIPS `tls_reload` via the MI HTTP interface. Existing TLS connections remain stable; only new handshakes use the updated certificate. Proactive renewal begins 30 days before expiry. If renewal fails, the system retains and continues using the last valid certificate, emitting alerts via Prometheus/Alertmanager.

---

## Goals

1. **Automated Public Certificate Lifecycle** — Integrate Let's Encrypt ACME v2 (HTTP-01 or DNS-01) via a containerized `certbot` service to issue and renew certificates without human intervention.
2. **Automated Internal Certificate Lifecycle** — Integrate Tailscale `tailscale cert` for machines on the Tailscale network, renewing automatically via the same orchestration path.
3. **Zero-Downtime Reload in OpenSIPS** — Reload the OpenSIPS TLS profile via MI `tls_reload` without process restart or dropped calls. Existing TLS connections continue with the old certificate; new connections use the new one.
4. **Shared Certificate Storage** — Store live certificates in a Docker volume (`tls_certs`) mounted into `opensips`, `rtpengine`, and the renewal service. Never bake certificates into image layers.
5. **Proactive Renewal** — Attempt renewal when certificates are within 30 days of expiry, with a daily check schedule.
6. **Graceful Fallback** — If ACME or Tailscale renewal fails (network outage, API error, auth failure), retain the current valid certificate and continue operations. Never replace a valid cert with a broken or empty one.
7. **Monitoring & Alerting** — Expose certificate expiry and renewal failure metrics to Prometheus. Trigger Alertmanager alerts at 30 days, 14 days, 7 days, and 1 day before expiry, plus on any renewal failure.
8. **Docker-First Deployment** — All renewal logic, scheduling, and hooks run inside containers. No host-level `certbot`, `cron`, or package installation is required.

---

## Non-Goals

- **Client certificate rotation for mTLS trunks** — Out of scope. Trunk client certs continue to be managed by `docker/ca-tool/cert-gen.sh` and the CA-tool container (Feature 007).
- **Certificate issuance for non-SIP services** — Web UIs (Grafana, OCP) are not covered by this feature.
- **Wildcard certificate issuance** — Unless DNS-01 is explicitly configured; baseline is a single-domain/SAN cert for the SIP FQDN.
- **Automatic CA root rotation** — The TSiSIP self-signed CA (`ca.crt`) used for mTLS remains manually managed.
- **HPKP / certificate pinning** — Not supported.
- **Host-level cron or systemd timers** — All scheduling is containerized.

---

## Architecture

### Certificate Sources

#### 1. Let's Encrypt (Public)

A dedicated `certbot` service runs an Alpine-based image with `certbot`, `cronie`, and `curl`.

- **Challenge type**: HTTP-01 preferred (baseline). An `acme-challenge` sidecar (or a temporary `certbot standalone` binding) exposes port 80 for the `.well-known/acme-challenge` path. DNS-01 is supported as an optional override via DNS provider plugins.
- **Renewal schedule**: Daily at 02:00 UTC via `crond` inside the container.
- **Certificate path**: Written to `/etc/letsencrypt/live/${TLS_DOMAIN}/` inside the `certbot` container, which is a bind mount (or volume mount) to the shared `tls_certs` volume.
- **Account key**: Stored in a Docker volume (`certbot_data`) so re-registration is not required on container recreation.

#### 2. Tailscale (Internal)

A lightweight `tailscale-cert` service (or the `certbot` service in "tailscale mode") runs `tailscale cert` periodically.

- **Prerequisite**: The container must be authenticated to the Tailscale network (via an auth key or pre-authenticated state volume injected at runtime).
- **Domain**: Automatically derived as `<hostname>.<tailnet>.ts.net`.
- **Output**: Full chain (`<domain>.crt`) and private key (`<domain>.key`) written to the shared `tls_certs` volume.
- **Schedule**: Daily check; `tailscale cert` is idempotent and only reissues when within 14 days of expiry.

### Certificate Storage & Distribution

```yaml
volumes:
  tls_certs:
    driver: local
  certbot_data:
    driver: local
```

| Service | Mount Point | Purpose |
|---------|-------------|---------|
| `certbot` | `/etc/letsencrypt` -> `certbot_data` | ACME account, renewal state, cached certs |
| `certbot` | `/certs/live` -> `tls_certs` | Final published certificate files |
| `opensips` | `/certs/live` -> `tls_certs` | TLS profile reads `server.crt`, `server.key`, `ca.crt` |
| `rtpengine` | `/certs/live` -> `tls_certs` | DTLS certificate files |

OpenSIPS `tls_mgm` module parameters are updated to point directly at the volume path:

```cfg
modparam("tls_mgm", "certificate", "[default]/certs/live/server.crt")
modparam("tls_mgm", "private_key", "[default]/certs/live/server.key")
modparam("tls_mgm", "ca_list",   "[default]/certs/live/ca.crt")
```

> **Note**: `server.crt` in the volume is a symlink or copy of the active certificate. The deploy-hook must update this atomically (write to temp, `mv` into place) to avoid OpenSIPS reading a partially written file during `tls_reload`.

### Zero-Downtime Reload

OpenSIPS must expose the MI HTTP interface for programmatic reload triggers.

1. **Load `mi_http.so`** in `opensips.cfg.tpl`:
   ```cfg
   loadmodule "mi_http.so"
   modparam("mi_http", "mi_http_root_path", "/mi")
   modparam("httpd", "ip", "${OPENSIPS_LISTEN_IP}")
   modparam("httpd", "port", 8888)
   ```
   Port 8888/tcp is bound on `0.0.0.0` but is only reachable via the internal Docker network (`sip_internal`), which has `internal: true`. No host port mapping is required.

2. **Reload trigger**: After successful renewal, the certbot container executes:
   ```bash
   curl -fsSL -X POST "http://opensips:8888/mi/tls_reload"
   ```
   This reloads the TLS domain configuration without restarting OpenSIPS. Existing TLS connections remain active.

3. **RTPengine reload**: RTPengine DTLS certificate reload behavior is version-dependent. The baseline approach is to send `SIGHUP` to the `rtpengine` process if supported; otherwise, document that RTPengine uses the same volume and will pick up the new cert on its next process start. A controlled rolling restart of RTPengine during a maintenance window is an acceptable fallback.

### Fallback & Safety

| Scenario | Behavior |
|----------|----------|
| ACME renewal fails (network, auth, rate limit) | Keep existing cert. Alert fires. Retry next day. |
| Tailscale renewal fails (auth expired, API down) | Keep existing cert. Alert fires. Retry next day. |
| New cert is written but `tls_reload` fails | Cert is on disk but not loaded. Alert fires. Operator can retry manually. |
| New cert is invalid (bad chain, mismatched key) | Pre-flight validation (`openssl x509 -checkend`, chain verification) blocks the deploy. |
| Cert expires without renewal | System continues using the expired cert (TLS handshakes will fail for new connections). Critical alert fires. |

### Monitoring & Alerting

Prometheus metrics (exposed by `opensips-exporter` and a new `certbot-exporter` sidecar):

| Metric | Type | Description |
|--------|------|-------------|
| `opensips_tls_certificate_expiry_timestamp` | Gauge | Unix timestamp of current TLS cert expiry (already exists in exporter.py) |
| `certbot_renewal_success_timestamp` | Gauge | Unix timestamp of last successful renewal |
| `certbot_renewal_failure_total` | Counter | Number of renewal failures (label: `source={letsencrypt,tailscale}`) |
| `certbot_days_until_expiry` | Gauge | Days until expiry (label: `domain`, `source`) |

Alertmanager rules (added to `docker/prometheus/alerts.yml`):

- `CertificateExpiringSoon30d` — `certbot_days_until_expiry < 30`
- `CertificateExpiringSoon14d` — `certbot_days_until_expiry < 14`
- `CertificateExpiringSoon7d`  — `certbot_days_until_expiry < 7`
- `CertificateExpired`         — `certbot_days_until_expiry < 1`
- `CertRenewalFailed`          — `increase(certbot_renewal_failure_total[1d]) > 0`

---

## Acceptance Criteria

- [ ] **AC1: Let's Encrypt certificate issuance** — Running `docker compose up certbot` for the first time successfully completes an ACME HTTP-01 challenge, writes a valid certificate chain to `tls_certs`, and exits 0. `openssl x509 -in /var/lib/docker/volumes/tsisip_tls_certs/_data/server.crt -noout -text` shows `Issuer: C = US, O = Let's Encrypt`.
- [ ] **AC2: Tailscale certificate issuance** — Running `docker compose up tailscale-cert` (with valid Tailscale auth injected) writes a valid Tailscale-issued certificate to `tls_certs`. `openssl x509 -in .../server.crt -noout -issuer` shows `Issuer: CN = Tailscale CA`.
- [ ] **AC3: OpenSIPS MI HTTP interface** — `curl -fsSL http://opensips:8888/mi/version` from the `certbot` container returns OpenSIPS version JSON. `curl -fsSL -X POST http://opensips:8888/mi/tls_reload` returns HTTP 200.
- [ ] **AC4: Zero-downtime reload** — Trigger `tls_reload` via MI HTTP (`curl -fsSL -X POST http://opensips:8888/mi/tls_reload`) and confirm HTTP 200 response. Verify with `opensips -c` that the TLS profile loads without error before and after reload. For staging environments, verify the certificate serial number changes in `openssl s_client` output after a simulated renewal.
- [ ] **AC5: Daily renewal check** — The certbot container cron job runs at 02:00 UTC daily. Logs show `certbot renew --quiet` executing. When no renewal is needed, it exits 0 and emits no alerts.
- [ ] **AC6: Proactive renewal at 30 days** — A certificate with 29 days remaining is detected by the daily check. Renewal is attempted, new cert is deployed, and `tls_reload` is triggered automatically.
- [ ] **AC7: Fallback on ACME failure** — Simulate ACME failure (disconnect network or use invalid auth). The existing certificate remains in `tls_certs` and continues to be served by OpenSIPS. Alertmanager fires `CertRenewalFailed`. After restoring connectivity, the next daily run succeeds and clears the alert.
- [ ] **AC8: Atomic certificate update** — During a simulated renewal, verify that `server.crt` is never in a partially written state observable by OpenSIPS (check via `lsof` or by reading the file during renewal). The deploy-hook must use `mv` after writing to a temp file.
- [ ] **AC9: Prometheus expiry metrics** — `curl http://opensips-exporter:9442/metrics` includes `opensips_tls_certificate_expiry_timestamp` with the correct Unix timestamp. `curl http://certbot-exporter:9101/metrics` includes `certbot_days_until_expiry`.
- [ ] **AC10: Alertmanager rules** — Triggering each alert condition (30d, 14d, 7d, 1d, renewal failure) causes Alertmanager to dispatch a notification to the configured webhook.
- [ ] **AC11: Docker-first constraint** — `which certbot` on the Docker host returns nothing. All certbot logic is inside the `certbot` container image.
- [ ] **AC12: Dual-mode coexistence** — The system can hold both a Let's Encrypt cert (for public FQDN) and a Tailscale cert (for tailnet FQDN) in `tls_certs` under separate filenames. OpenSIPS `tls_mgm` can select the appropriate cert based on listener domain if configured; baseline is a single active cert symlinked to `server.crt`.

---

## Risks & Mitigations

| ID | Risk | Likelihood | Impact | Mitigation |
|----|------|------------|--------|------------|
| R-001 | ACME rate-limiting blocks issuance after repeated failures | Medium | High | Implement exponential backoff in the renewal script; use staging environment for testing (`--staging` flag). |
| R-002 | `tls_reload` fails silently or drops connections on specific OpenSIPS 3.6 patch levels | Low | High | Test reload under load in staging; monitor active dialog count before/after reload; maintain manual restart runbook. |
| R-003 | Tailscale auth expires, breaking internal cert renewal | Medium | High | Monitor Tailscale node auth state; alert on `tailscale cert` failure; document re-auth procedure. |
| R-004 | Race condition: OpenSIPS reads partial cert during atomic `mv` | Low | Medium | Deploy-hook writes to `server.crt.new`, then `mv -f server.crt.new server.crt`. `mv` is atomic on the same filesystem. |
| R-005 | Port 80 conflict for ACME HTTP-01 on shared VPS | Low | High | Use `certbot standalone` with explicit `--http-01-port 80` and a small challenge-only nginx sidecar, or prefer DNS-01. |
| R-006 | Certbot container recreation loses ACME account / renewal state | Low | Medium | Mount `certbot_data` as a persistent Docker volume, not a bind mount that could be deleted. |

---

## Dependencies

| Dependency | Description | Impact if Missing |
|------------|-------------|-------------------|
| OpenSIPS 3.6 LTS with `mi_http.so` | Required for `tls_reload` via REST API | Cannot trigger zero-downtime reload programmatically |
| OpenSIPS 3.6 LTS with `tls_mgm.so` | Required for TLS profile management | Cannot terminate TLS |
| Docker Compose >= 2.20 | Named volumes and `internal` networks | Cannot isolate MI HTTP from public access |
| Let's Encrypt ACME v2 endpoint | Public certificate authority | Cannot issue public certs |
| Tailscale daemon / auth | Internal certificate authority | Cannot issue tailnet certs |
| Valid DNS A/AAAA record (public) | ACME HTTP-01 validation | Cannot complete public certificate challenge |
| Prometheus + Alertmanager (Feature 003) | Monitoring and alerting stack | Cannot observe or alert on expiry |

---

## Implementation Notes

### OpenSIPS Configuration Changes

Add to `opensips/opensips.cfg.tpl`:

```cfg
# MI HTTP for programmatic TLS reload (Feature 014-A)
loadmodule "mi_http.so"
modparam("mi_http", "mi_http_root_path", "/mi")
modparam("httpd", "ip", "${OPENSIPS_LISTEN_IP}")
modparam("httpd", "port", 8888)
```

Update `tls_mgm` paths:

```cfg
modparam("tls_mgm", "certificate", "[default]/certs/live/server.crt")
modparam("tls_mgm", "private_key", "[default]/certs/live/server.key")
modparam("tls_mgm", "ca_list",   "[default]/certs/live/ca.crt")
```

### Docker Compose Additions

```yaml
services:
  certbot:
    build:
      context: ./docker/certbot
    image: tsisip/certbot:${TSISIP_IMAGE_TAG:-latest}
    networks: [sip_internal]
    restart: unless-stopped
    environment:
      TLS_DOMAIN: ${TLS_DOMAIN}
      ACME_EMAIL: ${ACME_EMAIL}
      ACME_STAGING: "${ACME_STAGING:-0}"
      OPENSIPS_MI_URL: http://opensips:8888/mi
    volumes:
      - certbot_data:/etc/letsencrypt
      - tls_certs:/certs/live
    depends_on:
      opensips:
        condition: service_healthy
    deploy:
      resources:
        limits:
          memory: 256M

  tailscale-cert:
    build:
      context: ./docker/tailscale-cert
    image: tsisip/tailscale-cert:${TSISIP_IMAGE_TAG:-latest}
    networks: [sip_internal]
    restart: unless-stopped
    environment:
      TS_HOSTNAME: ${TS_HOSTNAME:-tsisip}
      CERT_OUTPUT_DIR: /certs/live
    volumes:
      - tls_certs:/certs/live
      - tailscale_state:/var/lib/tailscale
    deploy:
      resources:
        limits:
          memory: 128M

volumes:
  tls_certs:
  certbot_data:
  tailscale_state:
```

> The Tailscale authentication key is injected at runtime via the `.env` file or Docker secrets and is **never** committed to Git.

### Deploy Hook Script (inside certbot image)

```bash
#!/bin/sh
set -e

CERT_DIR="/certs/live"
TMP_CERT="${CERT_DIR}/server.crt.new"
TMP_KEY="${CERT_DIR}/server.key.new"
TMP_CA="${CERT_DIR}/ca.crt.new"

cp "$RENEWED_LINEAGE/fullchain.pem" "$TMP_CERT"
cp "$RENEWED_LINEAGE/privkey.pem"   "$TMP_KEY"
cp "$RENEWED_LINEAGE/chain.pem"     "$TMP_CA"

# Validate before swap
openssl x509 -in "$TMP_CERT" -noout -checkend 86400 >/dev/null
openssl rsa -in "$TMP_KEY" -check -noout >/dev/null

# Atomic swap
mv -f "$TMP_CERT" "${CERT_DIR}/server.crt"
mv -f "$TMP_KEY"  "${CERT_DIR}/server.key"
mv -f "$TMP_CA"   "${CERT_DIR}/ca.crt"

# Trigger OpenSIPS reload
curl -fsSL -X POST "${OPENSIPS_MI_URL}/tls_reload"

echo "[CERTBOT] Deployed new certificate and triggered tls_reload"
```

---

## References

- **Feature 007 Spec**: `specs/007-tls-srtp-encryption/spec.md` — TLS/SRTP baseline, self-signed CA, mTLS trunks.
- **OpenSIPS 3.6 TLS Module Docs**: https://opensips.org/docs/modules/3.6.x/tls_mgm.html
- **OpenSIPS 3.6 MI HTTP Docs**: https://opensips.org/docs/modules/3.6.x/mi_http.html
- **OpenSIPS 3.6 `tls_reload` MI Command**: https://opensips.org/docs/modules/3.6.x/tls_mgm.html#func_tls_reload
- **Let's Encrypt ACME v2**: https://letsencrypt.org/docs/acme-protocol-updates/
- **Certbot Deploy Hooks**: https://eff-certbot.readthedocs.io/en/stable/using.html#renewing-certificates
- **Tailscale `tailscale cert`**: https://tailscale.com/kb/1153/enabling-https
- **RFC 8555**: Automatic Certificate Management Environment (ACME)
- **Existing Rotation Scripts**:
  - `docker/ca-tool/cert-rotate.sh`
  - `scripts/tls-reload.sh`
  - `scripts/cert-expiry-monitor.sh`
- **OpenSIPS Config Template**: `opensips/opensips.cfg.tpl`
- **Docker Compose**: `docker-compose.yml`
- **Entrypoint**: `docker/entrypoint.sh`
- **Prometheus Exporter**: `docker/opensips-exporter/exporter.py`
