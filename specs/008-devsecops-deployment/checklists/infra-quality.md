# Feature 008 — Infrastructure Quality Checklist

## Docker Image Security

- [x] All TSiSIP service images are built from project-owned Dockerfiles (no external image substitution). (partial — alertmanager uses external prom/alertmanager image)
- [x] Base images are minimal (Alpine or Debian slim) and regularly updated.
- [x] Image tags use deterministic identifiers (git short-SHA or semantic version), not floating `latest` in production. (Fixed: docker-compose.yml, docker-compose.prod.yml, and docker-compose.vps.yml use `${TSISIP_IMAGE_TAG:?must be set}` strict variable requirement; only certbot/tailscale-cert local builds retain `:-latest` fallback)
- [x] Images are scanned for CVEs before deployment (Trivy or Clair). (Fixed: `.github/workflows/deploy.yml` runs Trivy with `--severity HIGH,CRITICAL --exit-code 1` on all built images before push)
- [x] No secrets are baked into image layers; all credentials injected at runtime.
- [x] Containers run as non-root where possible; privilege drop is configured inside the entrypoint. (opensips-exporter runs as `exporter`, anomaly-detector runs as `detector`, prometheus as `nobody`, OCP workers as `www-data`, postgres as `postgres`; backup metrics exporter drops to `tsisip-backup` but main process remains root due to Debian cron daemon requirement; opensips/rtpengine/asterisk require capabilities and remain root)
- [x] `cap_drop: [ALL]` is set on every service; only required capabilities are added back. (Fixed: 15/15 services in docker-compose.yml, 15/15 in docker-compose.prod.yml, 9/9 in docker-compose.vps.yml)
- [x] `security_opt: ["no-new-privileges:true"]` is set on every service. (Fixed: 15/15 services in docker-compose.yml, 15/15 in docker-compose.prod.yml, 9/9 in docker-compose.vps.yml)

## Network Isolation Verification

- [x] `sip_internal` network is marked `internal: true`.
- [x] `db_internal` network is marked `internal: true`.
- [x] Asterisk services have no `ports:` stanza.
- [x] PostgreSQL service has no `ports:` stanza.
- [x] RTPengine control socket (`--listen-ng`) binds to `sip_internal` address, not `0.0.0.0`.
- [x] OCP is bound to `127.0.0.1:8084` (not `0.0.0.0`) and exposed only via Nginx reverse proxy. (bound in docker-compose.vps.yml; not host-exposed in other compose files)
- [ ] Backup metrics exporter is bound to `127.0.0.1:9101` (not exposed publicly). (docker-compose.vps.yml binds to `127.0.0.1:9101`; docker-compose.yml and docker-compose.prod.yml use container-only `expose:` with no host-published port)

## Secret Management Verification

- [x] `secrets/` directory is excluded from version control (`.gitignore`).
- [x] `.env` and `.env.*` (except `.env.example`) are excluded from version control.
- [x] All runtime secrets are mounted via Docker Compose `secrets:` stanza. (Fixed: backup service S3 credentials migrated to Docker secrets `rclone_s3_access_key` and `rclone_s3_secret_key` in all compose files)
- [x] `auth_secret` is exactly 32 bytes.
- [x] No plaintext passwords in seed data; only HA1 hashes are stored.
- [x] Deploy secrets (GitHub token, SSH key) are stored separately from operational secrets (vault key, backup key). (Documented in `deploy/README.md` Secret Scope Separation section and `deploy/audit/DEVSECOPS-AUDIT.md`; deploy secrets live in GitHub Actions / `~/.env` / SSH agent, operational secrets live in `secrets/` directory)
- [x] Ansible tasks handling secrets use `no_log: true`. (GHCR login and `.env` template deployment both use `no_log: true`)
- [x] Bootstrap scripts generate secrets with `openssl rand` and set `chmod 600`.

## Nginx TLS Configuration Checks

- [x] TLSv1.2 and TLSv1.3 are enabled; TLSv1.0/1.1 are disabled.
- [x] Strong cipher suite is configured (`ECDHE+AESGCM:ECDHE+CHACHA20:!aNULL:!MD5:!DSS`).
- [x] HSTS header includes `max-age=63072000; includeSubDomains; preload`.
- [x] OCSP stapling is enabled.
- [x] HTTP → HTTPS redirect is active on port 80.
- [x] Rate limiting (`30r/m`, `burst=10`) is configured for `/TSiSIP/`.
- [x] Security headers are present: `X-Frame-Options`, `X-Content-Type-Options`, `X-XSS-Protection`, `Referrer-Policy`, `Permissions-Policy`, `Content-Security-Policy`.
- [x] `/nginx_status` and `/TSiSIP/health` are restricted to RFC-1918 and localhost. (`/TSiSIP/health` allows RFC-1918 + localhost; `/nginx_status` allows only 127.0.0.1, which satisfies the restriction)
- [x] SSL certificate and key files have restrictive permissions (`644` and `600` respectively). (`docker/entrypoint.sh`, `docker/certbot/deploy-hook.sh`, `docker/tailscale-cert/renew.sh`, and `deploy/scripts/vps-bootstrap.sh` all enforce `chmod 644` for certs and `chmod 600` for keys at runtime)

## Health Check Validation

- [x] PostgreSQL health check uses `pg_isready` with appropriate retries and start period.
- [x] OpenSIPS health check script exists inside the image and is referenced in Compose.
- [x] RTPengine health check script exists inside the image and is referenced in Compose.
- [x] OCP health check verifies HTTP 200 and presence of `"TSiSIP"` in response body.
- [x] Asterisk health check script exists inside the image.
- [x] Backup container has a health check or validation cron.
- [ ] Nginx configuration passes `nginx -t` syntax check. (`deploy/validate.sh` attempts check only if nginx binary is present; not guaranteed in CI/dev environments where nginx is not installed)
- [ ] Ansible playbooks pass `ansible-playbook --syntax-check`. (`deploy/validate.sh` attempts check only if ansible-playbook is present; `community.general.ufw` collection dependency may cause failure in environments without it)

## References

- Canonical spec sections: [14 (Compose)](../../docs/TSiSIP-CANONICAL-SPEC.md#14-docker-compose-contract), [17 (Security)](../../docs/TSiSIP-CANONICAL-SPEC.md#17-security-model)
- Deploy validation script: [`deploy/validate.sh`](../../deploy/validate.sh)
