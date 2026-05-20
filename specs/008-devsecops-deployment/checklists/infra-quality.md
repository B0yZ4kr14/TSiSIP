# Feature 008 — Infrastructure Quality Checklist

## Docker Image Security

- [x] All TSiSIP service images are built from project-owned Dockerfiles (no external image substitution). (partial — alertmanager uses external prom/alertmanager image)
- [x] Base images are minimal (Alpine or Debian slim) and regularly updated.
- [ ] Image tags use deterministic identifiers (git short-SHA or semantic version), not floating `latest` in production. (compose files default to `latest` when TSISIP_IMAGE_TAG is unset)
- [ ] Images are scanned for CVEs before deployment (Trivy or Clair). (not yet implemented)
- [x] No secrets are baked into image layers; all credentials injected at runtime.
- [x] Containers run as non-root where possible; privilege drop is configured inside the entrypoint. (opensips-exporter runs as `exporter`, anomaly-detector runs as `detector`, prometheus as `nobody`, OCP workers as `www-data`, postgres as `postgres`; backup metrics exporter drops to `tsisip-backup` but main process remains root due to Debian cron daemon requirement; opensips/rtpengine/asterisk require capabilities and remain root)
- [ ] `cap_drop: [ALL]` is set on every service; only required capabilities are added back. (partial — only opensips, rtpengine, and ocp have it; postgres, asterisk, prometheus, alertmanager, anomaly-detector, grafana, opensips-exporter, and backup do not)
- [ ] `security_opt: ["no-new-privileges:true"]` is set on every service. (partial — only opensips, rtpengine, and ocp have it)

## Network Isolation Verification

- [x] `sip_internal` network is marked `internal: true`.
- [x] `db_internal` network is marked `internal: true`.
- [x] Asterisk services have no `ports:` stanza.
- [x] PostgreSQL service has no `ports:` stanza.
- [x] RTPengine control socket (`--listen-ng`) binds to `sip_internal` address, not `0.0.0.0`.
- [x] OCP is bound to `127.0.0.1:8084` (not `0.0.0.0`) and exposed only via Nginx reverse proxy. (bound in docker-compose.vps.yml; not host-exposed in other compose files)
- [ ] Backup metrics exporter is bound to `127.0.0.1:9101` (not exposed publicly). (only bound in docker-compose.vps.yml; docker-compose.yml uses container-only expose; docker-compose.prod.yml has no host binding at all)

## Secret Management Verification

- [x] `secrets/` directory is excluded from version control (`.gitignore`).
- [x] `.env` and `.env.*` (except `.env.example`) are excluded from version control.
- [ ] All runtime secrets are mounted via Docker Compose `secrets:` stanza. (partial — backup service cloud storage credentials are plain environment variables, not secrets)
- [x] `auth_secret` is exactly 32 bytes.
- [x] No plaintext passwords in seed data; only HA1 hashes are stored.
- [ ] Deploy secrets (GitHub token, SSH key) are stored separately from operational secrets (vault key, backup key). (partial — documented in audit but not enforced in discovery scripts)
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
- [ ] `/nginx_status` and `/TSiSIP/health` are restricted to RFC-1918 and localhost. (partial — /TSiSIP/health allows RFC-1918 + localhost; /nginx_status allows only 127.0.0.1)
- [ ] SSL certificate and key files have restrictive permissions (`644` and `600` respectively). (dummy certs generated with correct permissions in bootstrap; real deployed cert permissions not verifiable in repo)

## Health Check Validation

- [x] PostgreSQL health check uses `pg_isready` with appropriate retries and start period.
- [x] OpenSIPS health check script exists inside the image and is referenced in Compose.
- [x] RTPengine health check script exists inside the image and is referenced in Compose.
- [x] OCP health check verifies HTTP 200 and presence of `"TSiSIP"` in response body.
- [x] Asterisk health check script exists inside the image.
- [x] Backup container has a health check or validation cron.
- [ ] Nginx configuration passes `nginx -t` syntax check. (validate.sh attempts check only if nginx binary is present; not guaranteed in CI/dev environments)
- [ ] Ansible playbooks pass `ansible-playbook --syntax-check`. (validate.sh attempts check only if ansible-playbook is present; community.general.ufw collection dependency may cause failure)

## References

- Canonical spec sections: [14 (Compose)](../../docs/TSiSIP-CANONICAL-SPEC.md#14-docker-compose-contract), [17 (Security)](../../docs/TSiSIP-CANONICAL-SPEC.md#17-security-model)
- Deploy validation script: [`deploy/validate.sh`](../../deploy/validate.sh)
