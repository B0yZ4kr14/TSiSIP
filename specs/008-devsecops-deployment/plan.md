# Implementation Plan: DevSecOps Deployment Automation

## Overview

This plan translates the feature specification into an executable implementation roadmap for secure, automated deployment of TSiSIP to VPS 'TSiAPP'.

## Architecture & Stack Choices

### Deployment Platform
- **Target**: VPS 'TSiAPP' (Debian/Ubuntu)
- **Orchestration**: Ansible 2.15+
- **Container Runtime**: Docker Engine + Docker Compose V2
- **Reverse Proxy**: Nginx 1.24+

### Security Stack
| Component | Tool | Purpose |
|---|---|---|
| Secret Discovery | Bash | Read ~/.env, ~/.tsi-vault, ~/.ssh without logging |
| GitHub API | curl + jq | Repository initialization |
| Configuration Mgmt | Ansible | Idempotent server configuration |
| TLS Termination | Nginx | SSL 1.2/1.3, HSTS, security headers |
| Rate Limiting | Nginx limit_req | Web-layer DDoS protection |

## Implementation Phases

### Phase 1 — Secret Discovery & Validation
- Enhance discover-and-secrets.sh with --check-only mode
- Add secret rotation warnings
- Validate SSH key format (Ed25519)

### Phase 2 — GitHub Repository Automation
- github-init-repo.sh: idempotent repo creation
- Add --dry-run mode
- Validate token permissions

### Phase 3 — Ansible Docker Orchestration
- Enhance playbook with docker rootless option
- Add pre-flight checks
- Improve health check with retry logic

### Phase 4 — Reverse Proxy Hardening
- Nginx config with security headers
- Rate limiting zone
- WebSocket support
- HTTP->HTTPS redirect

### Phase 5 — Audit & Validation
- Socratic self-analysis document
- Popper falsification tests
- Resilience matrix
- Makefile convenience targets

## File Structure

```
deploy/
  scripts/
    discover-and-secrets.sh    # Secret discovery (already exists)
    github-init-repo.sh        # GitHub repo init (already exists)
  ansible/
    inventory.yml              # Ansible inventory (already exists)
    playbook-deploy.yml        # Main deploy playbook (already exists)
    playbook-hardening.yml     # Server hardening (NEW)
  nginx/
    tsisip-reverse-proxy.conf  # Nginx config (already exists)
  audit/
    DEVSECOPS-AUDIT.md         # Audit document (already exists)
  Makefile                     # Convenience targets (already exists)
  README.md                    # Deploy documentation (already exists)
```

## Validation Gates

| Gate | Check | Command |
|---|---|---|
| Secrets | No credential exposure | grep -r token deploy/scripts/ |
| Ansible | Syntax valid | ansible-playbook --syntax-check |
| Nginx | Config valid | nginx -t |
| Health | OCP responds | curl -f http://localhost:8080/login.php |
| Audit | All SPoF covered | grep "SPoF" deploy/audit/DEVSECOPS-AUDIT.md |
