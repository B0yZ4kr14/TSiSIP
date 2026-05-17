# Feature Specification: DevSecOps Deployment Automation

## Overview

| Field | Value |
|-------|-------|
| **Feature** | DevSecOps Deployment Automation |
| **Short name** | devsecops-deployment |
| **Created** | 2026-05-17 |
| **Status** | In Progress |
| **Context** | TSiSIP stack requires automated, secure deployment to VPS 'TSiAPP' with credential isolation, reverse proxy, and resilience validation. |
| **Objective** | Coordinate multiple virtual agents to provision, secure, and orchestrate the TSiSIP web portal environment with zero credential exposure. |

## User Scenarios & Testing

### Scenario 1: Secure Discovery and Secret Management
**Given** a DevOps operator runs the discovery script,
**When** the script reads from ~/.env, ~/.tsi-vault, and ~/.ssh,
**Then** credentials are never logged, temp files are created with chmod 600, and missing secrets are clearly reported.

### Scenario 2: GitHub Repository Initialization
**Given** a GitHub admin token is available,
**When** the init script runs,
**Then** the repository github.com/B0yZ4kr14/TSiSIP is created with proper settings (private, auto-init, Docker gitignore).

### Scenario 3: Ansible Docker Deploy
**Given** secrets are discovered and validated,
**When** the Ansible playbook runs against TSiAPP,
**Then** Docker and docker-compose-plugin are installed, the TSiSIP stack starts, and the health check passes.

### Scenario 4: Reverse Proxy Configuration
**Given** the OCP container runs on localhost:8080,
**When** Nginx receives a request to https://tsiapp.io/TSiSIP,
**Then** it proxies securely with proper headers, SSL, rate limiting, and security headers.

### Scenario 5: Socratic Self-Audit
**Given** the deployment is complete,
**When** the audit document is reviewed,
**Then** architectural choices are justified, privilege minimization is verified, and secret scopes are separated.

### Scenario 6: Popper Falsification
**Given** the deployment claims resilience,
**When** SPoF tests are executed,
**Then** each failure scenario has a documented test and fallback mechanism.

## Functional Requirements

### FR-001: Secure Secret Discovery
- Script discovers VPS host, user, SSH key, GitHub token, and vault key without logging values.
- Missing secrets are reported individually with clear instructions.
- Temp secrets file uses chmod 600 and explicit deletion reminder.

### FR-002: GitHub Repository Automation
- Repository created under correct owner with description, privacy, and templates.
- Script is idempotent (handles existing repo gracefully).

### FR-003: Ansible Docker Orchestration
- Playbook installs Docker and docker-compose-plugin.
- Creates dedicated app directory with proper permissions.
- Deploys docker-compose.yml and web assets.
- Pulls latest image and starts stack.
- Health check verifies OCP responds with "TSiSIP" string.

### FR-004: Reverse Proxy Security
- Nginx listens on 443 with TLS 1.2/1.3.
- Security headers: X-Frame-Options, X-Content-Type-Options, X-XSS-Protection, Referrer-Policy.
- Rate limiting: 30r/m with burst=10.
- Path-based routing: /TSiSIP/ -> localhost:8080.
- WebSocket support for future real-time features.
- HTTP->HTTPS redirect.

### FR-005: Socratic Architecture Justification
- Document why subdirectory vs subdomain.
- Document privilege minimization gaps and mitigations.
- Document secret scope separation (deploy vs operational).

### FR-006: Popper Falsification Tests
- SPoF 1: ~/.env missing -> script exits with clear message.
- SPoF 2: Container crash -> Docker restart policy handles it.
- SPoF 3: Token leak -> verify no token in logs.
- SPoF 4: SSH key compromise -> Ed25519 + ForceCommand.
- SPoF 5: Nginx failure -> systemd restart + health checks.

## Success Criteria

| ID | Criterion | Target | Measurement |
|----|-----------|--------|-------------|
| SC-001 | Secret discovery without exposure | 100% | grep for secrets in logs = 0 |
| SC-002 | Ansible deploy success rate | 100% | playbook exit code 0 |
| SC-003 | Health check pass time | < 60s | curl login.php contains "TSiSIP" |
| SC-004 | Reverse proxy SSL grade | A+ | SSL Labs test |
| SC-005 | Audit document completeness | 100% | All SPoF have test + fallback |

## Scope

### In Scope
- Secret discovery scripts
- GitHub repo initialization
- Ansible playbooks and inventory
- Nginx reverse proxy config
- DevSecOps audit (Socratic + Popper)
- Makefile for convenience

### Out of Scope
- Actual VPS provisioning (assumed existing)
- DNS management (assumed configured)
- SSL certificate generation (assumed present)
- CI/CD pipeline (GitHub Actions not yet configured)
