# Compliance Evidence Checklist — Feature 008: DevSecOps Deployment Automation

**Document ID**: SEC-008-CHK-001  
**Date**: 2026-05-19  
**Purpose**: Ensure every security compliance requirement from `specs/008-devsecops-deployment/checklists/infra-quality.md` and `requirements.md` has a corresponding evidence artifact.

---

## Security Compliance Requirements

### Firewall & Access Control
- [ ] UFW firewall active with default-deny incoming — Evidence: `deploy/ansible/playbook-hardening.yml` + operator verification
- [ ] Only required ports open: 22/tcp, 443/tcp, 5060/udp+tcp, 5061/tcp, 10000-20000/udp — Evidence: `deploy/ansible/playbook-hardening.yml` + operator verification
- [ ] fail2ban configured for SSH with bantime=3600, maxretry=3 — Evidence: `deploy/ansible/playbook-hardening.yml` + `fail2ban-client status`
- [ ] Unattended-upgrades enabled for security packages — Evidence: `deploy/ansible/playbook-hardening.yml` + `systemctl status unattended-upgrades`
- [ ] Dedicated deploy user with limited sudo — Evidence: `deploy/ansible/playbook-hardening.yml`
- [ ] SSH restricted to Ed25519 keys — Evidence: `deploy/scripts/discover-and-secrets.sh` validation + `sshd_config`

### Container Security
- [ ] Docker containers run with dropped capabilities and no-new-privileges — Evidence: all `docker-compose*.yml` files + `scripts/verify-network-isolation.sh`
- [ ] No secrets committed to repository — Evidence: `scripts/verify-secrets-audit.sh`
- [ ] TLS certificates present — Evidence: `docker/certbot/deploy-hook.sh` + live cert files on TSiAPP

### Network Isolation
- [ ] sip_internal network marked internal: true — Evidence: `scripts/verify-network-isolation.sh`
- [ ] db_internal network marked internal: true — Evidence: `scripts/verify-network-isolation.sh`
- [ ] Asterisk services have no ports stanza — Evidence: `scripts/verify-network-isolation.sh`
- [ ] PostgreSQL service has no ports stanza — Evidence: `scripts/verify-network-isolation.sh`
- [ ] RTPengine control socket binds to sip_internal address — Evidence: `scripts/verify-network-isolation.sh`
- [ ] OCP bound to loopback and exposed only via Nginx — Evidence: `docker-compose.vps.yml` + operator verification
- [ ] Backup metrics exporter bound to loopback (VPS) or container-only (full) — Evidence: `docker-compose*.yml` + `docs/security/008-network-binding-decisions.md`

### Secret Management
- [ ] secrets/ directory excluded from version control — Evidence: `.gitignore` + `scripts/verify-secrets-audit.sh`
- [ ] .env and .env.* excluded from version control — Evidence: `.gitignore` + `scripts/verify-secrets-audit.sh`
- [ ] All runtime secrets mounted via Docker Compose secrets stanza — Evidence: `scripts/verify-secrets-audit.sh`
- [ ] Auth credential file is exactly 32 bytes — Evidence: `scripts/verify-secrets-audit.sh`
- [ ] No plaintext passwords in seed data — Evidence: `scripts/verify-secrets-audit.sh`
- [ ] Deploy secrets stored separately from operational secrets — Evidence: `deploy/README.md` + `deploy/audit/DEVSECOPS-AUDIT.md`
- [ ] Ansible tasks handling secrets use no_log — Evidence: `deploy/ansible/*.yml`
- [ ] Bootstrap scripts generate secrets with openssl rand and chmod 600 — Evidence: `deploy/scripts/vps-bootstrap.sh`

### Nginx TLS Configuration
- [ ] TLSv1.2 and TLSv1.3 enabled; TLSv1.0/1.1 disabled — Evidence: `scripts/verify-nginx-tls.sh`
- [ ] Strong cipher suite configured — Evidence: `scripts/verify-nginx-tls.sh`
- [ ] HSTS header with max-age=63072000, includeSubDomains, preload — Evidence: `scripts/verify-nginx-tls.sh`
- [ ] OCSP stapling enabled — Evidence: `scripts/verify-nginx-tls.sh`
- [ ] HTTP to HTTPS redirect active — Evidence: `deploy/nginx/tsiapp.conf` + operator curl test
- [ ] Rate limiting configured for OCP path — Evidence: `scripts/verify-nginx-tls.sh`
- [ ] Security headers present — Evidence: `scripts/verify-nginx-tls.sh` + live response headers
- [ ] Status endpoints restricted to RFC-1918 and localhost — Evidence: `deploy/nginx/tsiapp.conf`
- [ ] SSL certificate and key files have restrictive permissions — Evidence: `docker/entrypoint.sh` + `docker/certbot/deploy-hook.sh`

### Health Checks
- [ ] PostgreSQL health check uses pg_isready — Evidence: `docker-compose*.yml`
- [ ] OpenSIPS health check script exists and is referenced — Evidence: `docker-compose*.yml` + file existence
- [ ] RTPengine health check script exists and is referenced — Evidence: `docker-compose*.yml` + file existence
- [ ] OCP health check verifies HTTP 200 and TSiSIP string — Evidence: `docker-compose*.yml`
- [ ] Asterisk health check script exists — Evidence: `docker-compose*.yml` + file existence
- [ ] Backup container has validation cron — Evidence: `docker-compose*.yml`
- [ ] Nginx configuration passes syntax check — Evidence: `scripts/verify-nginx-tls.sh` + CI
- [ ] Ansible playbooks pass syntax-check — Evidence: CI workflow + `deploy/validate.sh`

### Monitoring & Alerting
- [ ] Health checks defined for all essential services — Evidence: `docker-compose*.yml`
- [ ] Docker restart policies configured — Evidence: `docker-compose*.yml`
- [ ] Backup RPO metrics exposed on loopback — Evidence: `docker-compose.vps.yml`
- [ ] Nginx access and error logs configured with rotation — Evidence: `deploy/ansible/playbook-hardening.yml`
- [ ] fail2ban active for SSH brute-force — Evidence: operator verification
- [ ] Prometheus + Grafana available in full profiles — Evidence: `docker-compose.yml` + `docker-compose.prod.yml`
- [ ] Alertmanager with templated webhook in full profiles — Evidence: `docker-compose*.yml`
- [ ] opensips-exporter metrics in full profiles — Evidence: `docker-compose*.yml`

### Image Security
- [ ] All TSiSIP service images built from project-owned Dockerfiles — Evidence: `docker-compose*.yml` + Dockerfiles
- [ ] Base images are minimal and regularly updated — Evidence: Dockerfiles + Trivy scans
- [ ] Image tags use deterministic identifiers — Evidence: `docker-compose*.yml` + `docs/security/008-image-pinning-policy.md`
- [ ] Images scanned for CVEs before deployment — Evidence: `.github/workflows/deploy.yml` + `docs/security/evidence/008-trivy-scan-latest.json`
- [ ] No secrets baked into image layers — Evidence: `scripts/verify-secrets-audit.sh`
- [ ] Containers run as non-root where possible — Evidence: Dockerfiles + `docker-compose*.yml`
- [ ] cap_drop: [ALL] set on every service — Evidence: `docker-compose*.yml`
- [ ] security_opt: ["no-new-privileges:true"] set on every service — Evidence: `docker-compose*.yml`
