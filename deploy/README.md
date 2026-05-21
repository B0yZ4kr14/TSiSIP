# TSiSIP DevSecOps Deployment

## Overview

This directory contains the complete DevSecOps automation for deploying TSiSIP OCP v9 to VPS **TSiAPP**.

| Parameter | Value |
|-----------|-------|
| Hostname | `TSiAPP` |
| Public IP | `179.190.15.116` |
| Tailscale IP | `100.111.74.69` |
| SSH Port | `22` |
| Default User | `tsi` |
| SSH Key | `TSiHomeLab` (Ed25519) |

See [`docs/VPS-TSiAPP-ACCESS.md`](../docs/VPS-TSiAPP-ACCESS.md) for full access instructions.

## Architecture

```
Developer Workstation
  |-- deploy/scripts/discover-and-secrets.sh  (secret discovery)
  |-- deploy/scripts/github-init-repo.sh      (repo initialization)
  |
  +-- Ansible Controller
        |-- deploy/ansible/inventory.yml           (target definition)
        |-- deploy/ansible/playbook-deploy.yml     (main deploy)
        |-- deploy/ansible/playbook-hardening.yml  (server hardening)
        |
        +-- TSiAPP VPS
              |-- Docker Engine + Compose V2
              |-- TSiSIP Stack (OCP, OpenSIPS, PostgreSQL, etc.)
              |
              +-- Nginx Reverse Proxy
                    |-- TLS 1.2/1.3 termination
                    |-- Rate limiting
                    +-- https://tsiapp.io/TSiSIP
```

## Secret Scope Separation

### Deploy Secrets (required for provisioning)
| Secret | Source | Used By |
|--------|--------|---------|
| GITHUB_TOKEN | ~/.env | github-init-repo.sh |
| TSiAPP_HOST | ~/.env | Ansible inventory |
| TSiAPP_USER | ~/.env | Ansible inventory |
| SSH private key | ~/.ssh | Ansible SSH connection |

### Operational Secrets (required for runtime)
| Secret | Source | Used By |
|--------|--------|---------|
| TSiHomeLab | ~/.tsi-vault | Backup/restore encryption |
| DB_PASSWORD | Docker secrets | PostgreSQL auth |
| AUTH_SECRET | Docker secrets | SIP digest auth |

**Important**: Deploy secrets and operational secrets must NEVER be mixed. The deploy scripts only access deploy secrets.

## Quick Start

### 1. Prepare Local Secrets

Create `~/.env`:
```bash
GITHUB_TOKEN=ghp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TSiAPP_HOST=179.190.15.116
TSiAPP_USER=tsi
TSiAPP_SSH_KEY=/home/YOURUSER/.ssh/TSiHomeLab
```

Ensure SSH key exists:
```bash
ls ~/.ssh/id_ed25519  # or id_rsa, tsiapp_key
```

### 2. Discover and Validate Secrets

```bash
cd deploy
make secrets
# Or directly:
./scripts/discover-and-secrets.sh
```

### 3. Initialize GitHub Repository (optional)

```bash
source /tmp/tsisip-secrets.XXXXXX  # from step 2
./scripts/github-init-repo.sh
```

### 4. Deploy to TSiAPP

```bash
make deploy
# Or directly:
cd ansible
ansible-playbook -i inventory.yml playbook-deploy.yml
```

### 5. Validate Deployment

```bash
make validate
# Or:
./validate.sh
```

## Makefile Targets

| Target | Description |
|--------|-------------|
| `make secrets` | Discover and validate secrets |
| `make check` | Ansible dry-run |
| `make deploy` | Full deployment |
| `make hardening` | Server hardening |
| `make validate` | Run all validation checks |
| `make audit` | Run security audit |
| `make clean` | Remove temporary files |

## Security Considerations

- Secrets are never logged by discovery scripts
- SSH keys should use Ed25519 format
- Key file permissions must be 600
- Ansible tasks use `no_log: true` for sensitive operations
- Nginx rate limiting prevents web-layer abuse

## Troubleshooting

### Secret discovery fails
- Verify `~/.env` exists with required variables
- Check SSH key permissions: `chmod 600 ~/.ssh/id_ed25519`
- Run with `--check-only` to identify missing secrets

### Ansible connection fails
- Verify SSH key is added to agent: `ssh-add -l`
- Test direct SSH: `ssh -i ~/.ssh/TSiHomeLab tsi@179.190.15.116`
- Check inventory.yml has correct host/user

### Nginx returns 502
- Verify OCP container is running: `docker ps`
- Check OCP logs: `docker logs tsisip-ocp-1`
- Verify proxy_pass points to correct port

## Files

| File | Purpose |
|------|---------|
| `scripts/discover-and-secrets.sh` | Secret discovery and validation |
| `scripts/github-init-repo.sh` | GitHub repository initialization |
| `ansible/inventory.yml` | Ansible target inventory |
| `ansible/playbook-deploy.yml` | Main deployment playbook |
| `ansible/playbook-hardening.yml` | Server hardening playbook |
| `nginx/tsisip-reverse-proxy.conf` | Nginx reverse proxy config |
| `audit/DEVSECOPS-AUDIT.md` | Security audit (Socratic + Popper) |
| `Makefile` | Convenience targets |
| `validate.sh` | Deployment validation script |
