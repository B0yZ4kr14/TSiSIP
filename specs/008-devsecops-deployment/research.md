# Feature 008 — Research & Decisions

## Why Ansible Was Chosen for Provisioning

**Decision**: Use Ansible 2.15+ as the canonical configuration-management and deployment orchestration tool for TSiAPP.

**Rationale**:
- **Agentless**: Ansible requires only SSH and Python on the target host. No persistent daemon or agent installation on the VPS.
- **Idempotency**: Playbooks can be run multiple times without side effects. Re-running `playbook-deploy.yml` safely updates images and restarts only changed services.
- **Declarative state**: Infrastructure is described as desired state (packages installed, services running, files present) rather than imperative shell scripts.
- **Inventory flexibility**: The `inventory.yml` format is human-readable and version-controllable.
- **Ecosystem**: Native `community.general.ufw` and `ansible.builtin.systemd` modules simplify firewall and service management.

**Rejected alternatives**:
- **Shell scripts only**: Hard to make idempotent; error handling is verbose; no dry-run capability.
- **Terraform**: Overkill for a single VPS; better suited for multi-cloud infrastructure.
- **Puppet/Chef**: Require agent installation and persistent daemon, increasing attack surface.

## Docker Hardening Decisions

### Capability Dropping
All TSiSIP services run with `cap_drop: [ALL]` and only add back the minimum required capabilities:

| Service | Added Capabilities | Justification |
|---|---|---|
| `opensips` | `NET_BIND_SERVICE`, `SETUID`, `SETGID` | Bind to privileged SIP ports (5060/5061) and drop privileges internally |
| `rtpengine` | `NET_BIND_SERVICE`, `NET_ADMIN` | Bind to RTP port range and manage network interfaces for SDP rewriting |
| `ocp` | `SETUID`, `SETGID` | Privilege separation within the container |
| `backup` | — | No special capabilities required |

### No-New-Privileges
All services set `security_opt: ["no-new-privileges:true"]` to prevent `setuid` binaries from escalating privileges at runtime.

### Non-Root Execution
Where possible, services drop to unprivileged users inside the container after startup. OpenSIPS and RTPengine start as root to bind low ports, then drop privileges via their own internal mechanisms.

## VPS Selection Criteria (TSiAPP)

| Criterion | Minimum | TSiAPP Actual |
|---|---|---|
| OS | Ubuntu 22.04+ / Debian 12+ | Ubuntu 24.04 |
| RAM | 2 GB | ~3.8 GB |
| Disk | 10 GB free | >= 10 GB |
| Docker | 24.0+ | 29.5.0 |
| Compose | V2 plugin | v5.1.3 |
| Network | Public IPv4 + Tailscale | Public + Tailscale 100.111.74.69 |

**Profile rationale**: The vps-lite+PBX profile (7 services, ~2.9 GB RAM allocated) was chosen because the full observability stack (13 services) would exceed available RAM and risk kernel OOM kills. Memory limits (`mem_limit`) are enforced on every container.

## Reverse Proxy Architecture (Nginx + Cloudflare)

### Subdirectory vs. Subdomain
**Decision**: Serve OCP at `https://tsiapp.io/TSiSIP/` (subdirectory/path-based routing).

**Justification**:
- Single SSL certificate covers `tsiapp.io`; no wildcard or additional DNS records required.
- Lower operational overhead for a single production environment.
- Coherent with TSiAPP hosting multiple services under the same domain.

**Socratic counter-argument**: Subdomains offer better cookie isolation, CORS boundaries, and independent deployability. If TSiSIP scales beyond one production environment, migration to `tsisip.tsiapp.io` should be re-evaluated.

### Nginx Security Configuration
- **TLS**: TLSv1.2 and TLSv1.3 only; strong cipher suite (`ECDHE+AESGCM:ECDHE+CHACHA20`); HSTS with preload.
- **Rate limiting**: `30r/m` with `burst=10` per IP to mitigate web-layer DDoS.
- **Security headers**: `X-Frame-Options`, `X-Content-Type-Options`, `X-XSS-Protection`, `Referrer-Policy`, `Permissions-Policy`, `Content-Security-Policy`.
- **OCSP stapling**: Enabled for faster TLS handshake and privacy.
- **Access restriction**: `/nginx_status` and `/TSiSIP/health` limited to RFC-1918 ranges and localhost.

### Cloudflare Role
- DNS resolution for `tsiapp.io`.
- DDoS protection at the edge (layer 3/4).
- **Note**: SIP traffic (5060/5061/UDP) bypasses Cloudflare and arrives directly at the VPS. Cloudflare does not proxy non-HTTP(S) ports.

## Backup and Disaster Recovery Strategy

### Backup Scope
- **PostgreSQL base backups**: Daily encrypted backups at 02:00 UTC.
- **WAL archiving**: Continuous WAL archiving to `/backup/wal/` for point-in-time recovery (PITR).
- **Retention**: 30 days for base backups, 37 days for WAL.

### Encryption
- All backup artifacts are encrypted with AES-256-CBC + PBKDF2 + HMAC-SHA256 using a key injected via Docker secret (`backup_encryption_key`).
- The encryption key is stored in the TSiHomeLab vault and never committed.

### Validation
- Automated validation runs at 04:00 UTC: decrypts the latest backup and verifies PostgreSQL can start from it.
- RPO metrics exposed on loopback (`127.0.0.1:9101/metrics`) for local monitoring.

### Rollback Procedures
1. **Stack rollback**: `docker compose -f docker-compose.vps.yml down` + pull previous image tag + restart.
2. **Database rollback**: Restore from latest validated backup using `pg_restore`.
3. **Configuration rollback**: Git repository on VPS tracks all compose and config files.

## CI/CD Pipeline Overview

**Current state**: CI/CD via GitHub Actions is **out of scope** for Feature 008 and deferred to Phase 2.

**Planned pipeline** (future):
1. **Build**: Docker images built from committed Dockerfiles.
2. **Test**: OpenSIPS config syntax check, SIP auth probe, health checks.
3. **Tag**: Images tagged with git short-SHA for deterministic pinning.
4. **Push**: Images pushed to GHCR (`ghcr.io/b0yz4kr14/tsisip/*`).
5. **Deploy**: Ansible playbook triggered (manually or via webhook) to pull new images and restart stack.
6. **Validate**: Post-deploy health checks and SIP end-to-end validation.

**Gap**: The current vps-lite profile uses `:latest` tags. Deterministic SHA256-pinned deployment is a pending acceptance criterion (SC-004 follow-up).
