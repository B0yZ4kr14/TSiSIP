# Feature 008 — Infrastructure Quality Checklist

## Docker Image Security

- [ ] All TSiSIP service images are built from project-owned Dockerfiles (no external image substitution).
- [ ] Base images are minimal (Alpine or Debian slim) and regularly updated.
- [ ] Image tags use deterministic identifiers (git short-SHA or semantic version), not floating `latest` in production.
- [ ] Images are scanned for CVEs before deployment (Trivy or Clair).
- [ ] No secrets are baked into image layers; all credentials injected at runtime.
- [ ] Containers run as non-root where possible; privilege drop is configured inside the entrypoint.
- [ ] `cap_drop: [ALL]` is set on every service; only required capabilities are added back.
- [ ] `security_opt: ["no-new-privileges:true"]` is set on every service.

## Network Isolation Verification

- [ ] `sip_internal` network is marked `internal: true`.
- [ ] `db_internal` network is marked `internal: true`.
- [ ] Asterisk services have no `ports:` stanza.
- [ ] PostgreSQL service has no `ports:` stanza.
- [ ] RTPengine control socket (`--listen-ng`) binds to `sip_internal` address, not `0.0.0.0`.
- [ ] OCP is bound to `127.0.0.1:8084` (not `0.0.0.0`) and exposed only via Nginx reverse proxy.
- [ ] Backup metrics exporter is bound to `127.0.0.1:9101` (not exposed publicly).

## Secret Management Verification

- [ ] `secrets/` directory is excluded from version control (`.gitignore`).
- [ ] `.env` and `.env.*` (except `.env.example`) are excluded from version control.
- [ ] All runtime secrets are mounted via Docker Compose `secrets:` stanza.
- [ ] `auth_secret` is exactly 32 bytes.
- [ ] No plaintext passwords in seed data; only HA1 hashes are stored.
- [ ] Deploy secrets (GitHub token, SSH key) are stored separately from operational secrets (vault key, backup key).
- [ ] Ansible tasks handling secrets use `no_log: true`.
- [ ] Bootstrap scripts generate secrets with `openssl rand` and set `chmod 600`.

## Nginx TLS Configuration Checks

- [ ] TLSv1.2 and TLSv1.3 are enabled; TLSv1.0/1.1 are disabled.
- [ ] Strong cipher suite is configured (`ECDHE+AESGCM:ECDHE+CHACHA20:!aNULL:!MD5:!DSS`).
- [ ] HSTS header includes `max-age=63072000; includeSubDomains; preload`.
- [ ] OCSP stapling is enabled.
- [ ] HTTP → HTTPS redirect is active on port 80.
- [ ] Rate limiting (`30r/m`, `burst=10`) is configured for `/TSiSIP/`.
- [ ] Security headers are present: `X-Frame-Options`, `X-Content-Type-Options`, `X-XSS-Protection`, `Referrer-Policy`, `Permissions-Policy`, `Content-Security-Policy`.
- [ ] `/nginx_status` and `/TSiSIP/health` are restricted to RFC-1918 and localhost.
- [ ] SSL certificate and key files have restrictive permissions (`644` and `600` respectively).

## Health Check Validation

- [ ] PostgreSQL health check uses `pg_isready` with appropriate retries and start period.
- [ ] OpenSIPS health check script exists inside the image and is referenced in Compose.
- [ ] RTPengine health check script exists inside the image and is referenced in Compose.
- [ ] OCP health check verifies HTTP 200 and presence of `"TSiSIP"` in response body.
- [ ] Asterisk health check script exists inside the image.
- [ ] Backup container has a health check or validation cron.
- [ ] Nginx configuration passes `nginx -t` syntax check.
- [ ] Ansible playbooks pass `ansible-playbook --syntax-check`.

## References

- Canonical spec sections: [14 (Compose)](../../docs/TSiSIP-CANONICAL-SPEC.md#14-docker-compose-contract), [17 (Security)](../../docs/TSiSIP-CANONICAL-SPEC.md#17-security-model)
- Deploy validation script: [`deploy/validate.sh`](../../deploy/validate.sh)
