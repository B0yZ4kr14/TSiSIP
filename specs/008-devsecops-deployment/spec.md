# Feature Specification: DevSecOps Deployment Automation

## Overview

**Feature**: DevSecOps Deployment Automation  
**Short name**: devsecops-deployment  
**Created**: 2026-05-17  
**Status**: Complete — VPS production stack running, all SG phases closed, TLS certificate deployed, SSL Labs grade B evidenced with A+ remediation plan, deterministic image pinning enforced.  
**Last Updated**: 2026-05-19

### Context

TSiSIP is a Docker-first SIP edge-proxy platform built on OpenSIPS 3.6 LTS. The stack requires automated, secure deployment to VPS **TSiAPP** with credential isolation, reverse proxy termination, and resilience validation. This feature coordinates provisioning, hardening, and orchestration of the TSiSIP web portal and SIP edge environment with zero credential exposure.

### Canonical VPS Parameters

| Parameter | Value | Source |
|-----------|-------|--------|
| Hostname | `TSiAPP` | docs/VPS-TSiAPP-ACCESS.md |
| Public IP | `179.190.15.116` | docs/VPS-TSiAPP-ACCESS.md |
| Tailscale IP | `100.111.74.69` | docs/VPS-TSiAPP-ACCESS.md |
| SSH Port | `22` | docs/VPS-TSiAPP-ACCESS.md |
| Default User | `tsi` | docs/VPS-TSiAPP-ACCESS.md |
| SSH Key | `TSiHomeLab` (Ed25519) | deploy/ssh/TSiAPP-config |
| Deploy Directory | `/opt/tsisip` | deploy/ansible/inventory.yml |
| Registry Prefix | `ghcr.io/b0yz4kr14/tsisip/*` | deploy/scripts/orchestrate-deploy.sh |

### Objective

Deliver a repeatable, auditable deployment pipeline that:
- Provisions the TSiAPP VPS with minimal attack surface (UFW, fail2ban, unattended-upgrades).
- Deploys the TSiSIP vps-lite+PBX stack via Ansible and Docker Compose.
- Exposes the OCP portal securely through an Nginx reverse proxy with TLS 1.2/1.3, rate limiting, and security headers.
- Validates resilience through Socratic self-analysis and Popper falsification tests.

---

## User Scenarios & Testing

### Primary Flows

#### Scenario 1: Secure Discovery and Secret Management
- **Given** a DevOps operator runs the discovery script,
- **When** the script reads from `~/.env`, `~/.tsi-vault`, and `~/.ssh`,
- **Then** credentials are never logged, temp files are created with `chmod 600`, and missing secrets are clearly reported.

#### Scenario 2: GitHub Repository Initialization
- **Given** a GitHub admin token is available,
- **When** the init script runs,
- **Then** the repository `github.com/B0yZ4kr14/TSiSIP` is created with proper settings (private, auto-init, Docker gitignore).

#### Scenario 3: Ansible Docker Deploy
- **Given** secrets are discovered and validated,
- **When** the Ansible playbook runs against TSiAPP,
- **Then** Docker and docker-compose-plugin are installed, the TSiSIP stack starts, and the health check passes.

**Live result 2026-05-19**: VPS TSiAPP runs the production `vps-lite+PBX` stack with the TSiSIP SIP edge service, RTPengine, PostgreSQL, OCP, backup, and two internal Asterisk PBX services healthy.

#### Scenario 4: Reverse Proxy Configuration
- **Given** the OCP container runs on `localhost:8080`,
- **When** Nginx receives a request to `https://tsiapp.io/TSiSIP`,
- **Then** it proxies securely with proper headers, SSL, rate limiting, and security headers.

#### Scenario 5: Socratic Self-Audit
- **Given** the deployment is complete,
- **When** the audit document is reviewed,
- **Then** architectural choices are justified, privilege minimization is verified, and secret scopes are separated.

#### Scenario 6: Popper Falsification
- **Given** the deployment claims resilience,
- **When** SPoF tests are executed,
- **Then** each failure scenario has a documented test and fallback mechanism.

### Edge Cases & Error Conditions

- **Edge case 1**: `~/.env` file is missing or malformed. The discovery script must exit non-zero with a clear, per-secret missing report rather than a generic failure.
- **Edge case 2**: Docker registry is unreachable during deploy. The Ansible playbook must fail fast after pre-flight registry reachability check and retry image pulls up to 3 times.
- **Edge case 3**: OCP container crashes or is killed. Docker `restart: unless-stopped` must restore it; health check must eventually return to passing state.
- **Edge case 4**: Nginx configuration syntax error. The validation script (`nginx -t`) must catch it before reload; systemd must not enter a crash loop.
- **Edge case 5**: Unauthorized image in Compose file. The playbook must reject any image not in the `tsisip/` or `prom/` namespaces.

---

## Functional Requirements

### FR-001: Secure Secret Discovery
**Description**: Script discovers VPS host, user, SSH key, GitHub token, and vault key without logging values. Missing secrets are reported individually with clear instructions. Temp secrets file uses `chmod 600` and explicit deletion reminder.
**Acceptance Criteria**:
- `--check-only` mode returns 0 when all secrets are present and non-zero with per-secret diagnostics when any are missing.
- No secret value appears in stdout, stderr, or log files.
- Temporary files are created with mode `600` and a deletion reminder is printed.

### FR-002: GitHub Repository Automation
**Description**: Repository created under correct owner with description, privacy, and templates. Script is idempotent (handles existing repo gracefully).
**Acceptance Criteria**:
- `--dry-run` validates token permissions without mutating GitHub state.
- Re-running the script against an existing repo updates settings to canonical values without error.

### FR-003: Ansible Docker Orchestration
**Description**: Playbook installs Docker and docker-compose-plugin. Creates dedicated app directory with proper permissions. Deploys docker-compose file and web assets. Pulls latest image and starts stack. Health check verifies OCP responds with `"TSiSIP"` string.
**Acceptance Criteria**:
- Pre-flight checks (disk space > 1 GB, Docker daemon reachable, registry reachable) fail fast with actionable messages.
- `docker compose config` validation runs before any containers are started.
- Sensitive tasks use `no_log: true`.
- OCP health check passes within 60 seconds of stack start.

### FR-004: Reverse Proxy Security
**Description**: Nginx listens on 443 with TLS 1.2/1.3. Security headers present. Rate limiting active. Path-based routing working. WebSocket support present. HTTP → HTTPS redirect active.
**Acceptance Criteria**:
- `securityheaders.com` scan returns A+ grade.
- `/TSiSIP/` proxies to OCP with all forwarded headers.
- Rate limit of `30r/m` with `burst=10` enforced per IP.
- Port 80 returns 301 to HTTPS for all paths except ACME challenge.

### FR-005: Socratic Architecture Justification
**Description**: Document why subdirectory vs. subdomain, privilege minimization gaps and mitigations, and secret scope separation (deploy vs. operational).
**Acceptance Criteria**:
- Audit document contains a Socratic Q&A for each architectural decision.
- Each answer includes a counter-argument and a falsifiable conclusion.

### FR-006: Popper Falsification Tests
**Description**: Executable test scripts for each SPoF scenario. Each test must verify both failure and fallback.
**Acceptance Criteria**:
- All 5 SPoF test scripts in `deploy/audit/tests/` exit 0 when run.
- Each script documents the hypothesis, the test procedure, and the fallback mechanism.

---

## Success Criteria

| ID | Criterion | Target | Measurement |
|---|---|---|---|
| SC-001 | Secret discovery without exposure | 100% | `grep` for secrets in logs = 0 |
| SC-002 | Ansible deploy success rate | 100% | playbook exit code 0 |
| SC-003 | Health check pass time | < 60 s | `curl login.php` contains `"TSiSIP"` |
| SC-004 | Reverse proxy security headers | A+ | securityheaders.com scan |
| SC-004b | Reverse proxy TLS config | A+ | SSL Labs test (requires real certificates; dummy certs do not qualify) |
| SC-005 | Audit document completeness | 100% | All SPoF have test + fallback |
| SC-006 | SIP authenticated route | Pass | Digest INVITE reaches Asterisk and returns final response |

---

## Key Entities

### Entity: VPS (TSiAPP)
- **Attributes**: host, OS (Ubuntu 24.04), public IP, Tailscale IP, SSH key, resource profile (vps-lite+PBX)
- **Relationships**: hosts all Docker services; targeted by Ansible inventory

### Entity: Deploy Pipeline
- **Attributes**: Ansible playbooks (`playbook-deploy.yml`, `playbook-hardening.yml`), inventory (`inventory.yml`), bootstrap scripts
- **Relationships**: provisions VPS; pulls images from GHCR; validates via `deploy/validate.sh`

### Entity: Reverse Proxy (Nginx)
- **Attributes**: TLS config, rate limiting zones, upstream backend (`127.0.0.1:8084`), security headers
- **Relationships**: terminates TLS for `tsiapp.io`; proxies to OCP container

### Entity: Secret Scope
- **Attributes**: deploy secrets (GitHub token, SSH key, VPS host/user), operational secrets (vault key, backup encryption key, DB password, auth secret)
- **Relationships**: deploy secrets consumed by Ansible/scripts; operational secrets consumed by running containers via Docker secrets

---

## Scope

### In Scope
- Secret discovery scripts
- GitHub repo initialization
- Ansible playbooks and inventory
- Nginx reverse proxy config
- DevSecOps audit (Socratic + Popper)
- Makefile for convenience

### Out of Scope
- Upstream/provider firewall or Tailscale ACL management outside the VPS host
- DNS management (assumed configured)
- SSL certificate generation (assumed present)
- CI/CD pipeline (GitHub Actions not yet configured)
- Full observability stack (Prometheus, Grafana, Alertmanager, opensips-exporter, anomaly-detector) — deferred to Phase 2

---

## Dependencies

- VPS TSiAPP provisioned with Ubuntu 22.04+ or Debian 12+ and SSH access
- Docker Hub / GHCR registry reachable from TSiAPP
- DNS A/AAAA record for `tsiapp.io` pointing to TSiAPP public IP
- SSL certificate and key files present on TSiAPP at `/etc/ssl/certs/tsiapp.io.crt` and `/etc/ssl/private/tsiapp.io.key`
- GitHub personal access token with `repo` and `delete_repo` scopes (for repo automation)

---

## Assumptions

- The operator has local access to `~/.env`, `~/.tsi-vault`, and the Ed25519 SSH private key for TSiAPP.
- TSiAPP has at least 2 GB RAM and 10 GB free disk space (vps-lite profile targets ~3.8 GB RAM).
- Docker 24.0+ and the Docker Compose V2 plugin are either pre-installed or installable via `apt`.
- Nginx is either pre-installed or installable via `apt`.
- The `tsisip/` namespace in GHCR contains trusted, up-to-date images.

---

## Risks

| Risk | Impact | Likelihood | Mitigation |
|---|---|---|---|
| Upstream ISP/cloud provider filters SIP ports before reaching TSiAPP | High (blocks core SIP functionality) | Medium | Confirm with `tcpdump` on VPS; open ACL/NAT at provider edge; document as external dependency |
| Deterministic image pinning not enforced (`:latest` used in production) | Medium (non-reproducible deploys) | High | Migrate to git short-SHA tags in CI/CD pipeline (Phase 2) |
| OOM kill on VPS due to memory overcommit | High (service crash) | Low | Enforce `mem_limit` on every container; use vps-lite profile; monitor with `docker stats` |
| TLS certificate expiry or misconfiguration | Medium (HTTPS downtime) | Low | Automate cert renewal (Let's Encrypt + systemd timer); monitor expiry |
| Secret leak via Ansible logs or temp files | High (credential compromise) | Low | Use `no_log: true` on all secret-handling tasks; `chmod 600` temp files; immediate cleanup |

---

## Notes

- Cross-references: Canonical spec sections [14 (Compose)](../../docs/TSiSIP-CANONICAL-SPEC.md#14-docker-compose-contract) and [17 (Security)](../../docs/TSiSIP-CANONICAL-SPEC.md#17-security-model).
- No secrets, passwords, or credentials are stored in this specification.
- The vps-lite+PBX profile (7 services, <3 GB RAM) is the current production baseline. The full stack (13 services) requires additional RAM and is planned for Phase 2.
- See [`deploy/VPS-DEPLOY-READINESS.md`](../../deploy/VPS-DEPLOY-READINESS.md) for the live operational checklist.
