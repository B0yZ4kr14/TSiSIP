# TSiSIP DevSecOps Deployment Automation

This directory contains all infrastructure-as-code, automation scripts, and security auditing artifacts for deploying the TSiSIP OCP v9 Portal on the VPS `TSiAPP`.

## Directory Structure

```
deploy/
├── README.md                          # This file
├── ansible/
│   ├── inventory.yml                  # Ansible inventory for TSiAPP VPS
│   └── playbook-deploy.yml            # Main deployment playbook
├── audit/
│   └── DEVSECOPS-AUDIT.md             # Socratic + Popper security audit
├── nginx/
│   └── tsisip-reverse-proxy.conf      # Nginx reverse proxy config
└── scripts/
    ├── discover-and-secrets.sh        # Secret discovery & validation
    └── github-init-repo.sh            # GitHub repository initialization
```

## Prerequisites

1. **VPS Access**: SSH key-based access to `TSiAPP` (host configured in `~/.env`)
2. **GitHub Token**: Personal access token with `repo` scope in `~/.env`
3. **Vault Key**: `TSiHomeLab` key in `~/.tsi-vault` (for backup/restore operations)
4. **Ansible**: Installed locally (`pip install ansible`)
5. **Docker & Compose**: Available on target VPS

## Deployment Workflow

### Step 1: Discover & Validate Secrets

```bash
./deploy/scripts/discover-and-secrets.sh
# Source the generated temp file:
source /tmp/tsisip-secrets.XXXXXX
```

### Step 2: Initialize GitHub Repository (First Time Only)

```bash
./deploy/scripts/github-init-repo.sh
```

### Step 3: Deploy to VPS

```bash
cd deploy/ansible
ansible-playbook -i inventory.yml playbook-deploy.yml
```

### Step 4: Configure Reverse Proxy

Copy `deploy/nginx/tsisip-reverse-proxy.conf` to `/etc/nginx/sites-available/` on the VPS and reload Nginx.

## Security Notes

- **Secrets are never logged**: All scripts use `set +x` and redirect stderr
- **Temporary files**: `discover-and-secrets.sh` generates a temp file with `chmod 600`. Delete after sourcing.
- **SSH keys**: Use Ed25519, protect with passphrase, rotate every 90 days
- **Docker privileges**: The `tsi` user has Docker group access. Consider rootless Docker for production.

## Audit & Compliance

See `deploy/audit/DEVSECOPS-AUDIT.md` for:
- Socratic self-analysis of architectural decisions
- Popper falsification tests for single points of failure
- Hardening recommendations
- Automated test matrix

## Troubleshooting

| Issue | Solution |
|---|---|
| `GITHUB_TOKEN not set` | Run `discover-and-secrets.sh` and source the temp file |
| `ansible_host unreachable` | Verify `TSiAPP_HOST` in `~/.env` and SSH connectivity |
| `docker pull denied` | Ensure `tsisip/ocp:latest` is built and tagged locally |
| `502 Bad Gateway` | Check OCP container health: `docker ps` and logs |
| `permission denied` | Verify `tsi` user is in `docker` group on VPS |

## References

- `docs/TSiSIP-CANONICAL-SPEC.md` — Architecture & tech baseline
- `docs/TSiSIP-OPERATOR-RUNBOOK.md` — Operational procedures
- `AGENTS.md` — Repository guidelines
