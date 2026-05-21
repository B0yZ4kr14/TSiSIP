# VPS TSiAPP — Canonical Access Guide

> **Classification**: Infrastructure Canonical Reference  
> **Last Updated**: 2026-05-19  
> **Applies To**: TSiSIP operators, DevSecOps pipeline, Ansible controller  
> **Authority**: `AGENTS.md` Section 12 + `deploy/README.md`

---

## Canonical Parameters

| Parameter | Value | Description |
|-----------|-------|-------------|
| `VPS_TSiAPP_NAME` | `VPS TSiAPP` | Human-readable identifier |
| `VPS_TSiAPP_HOSTNAME` | `TSiAPP` | Server hostname |
| `VPS_TSiAPP_IP_PUBLIC` | `179.190.15.116` | Public IPv4 (primary ingress) |
| `VPS_TSiAPP_IP_TAILSCALE` | `100.111.74.69` | Tailscale mesh VPN address |
| `VPS_TSiAPP_PORT` | `22` | SSH daemon port |
| `VPS_TSiAPP_USER` | `tsi` | Primary non-root operator account |
| `DEFAULT_USER_ROOT` | `root` | Administrative superuser |
| `VPS_TSiAPP_KEY` | `TSiHomeLab` | SSH key pair name (Ed25519 recommended) |

### Password Policy (Bootstrap Only)

> ⚠️ **WARNING**: Password authentication is disabled after key-based setup.  
> Default bootstrap passwords are stored in the operator secure vault (`~/.tsi-vault`).  
> Change both passwords on first login and disable PasswordAuthentication immediately.

After initial setup, enforce:
```bash
# On TSiAPP
sudo sed -i 's/^#*PasswordAuthentication.*/PasswordAuthentication no/' /etc/ssh/sshd_config
sudo sed -i 's/^#*PermitRootLogin.*/PermitRootLogin prohibit-password/' /etc/ssh/sshd_config
sudo systemctl reload sshd
```

---

## SSH Access (Password-less)

### Prerequisites

1. **Generate TSiHomeLab key pair** (if not exists):
   ```bash
   ssh-keygen -t ed25519 -C "TSiHomeLab-$(whoami)@$(hostname)" -f ~/.ssh/TSiHomeLab -N ""
   chmod 600 ~/.ssh/TSiHomeLab
   chmod 644 ~/.ssh/TSiHomeLab.pub
   ```

2. **Install public key on TSiAPP** (one-time bootstrap):
   ```bash
   # As root (password from operator vault)
   ssh-copy-id -i ~/.ssh/TSiHomeLab.pub -p 22 root@179.190.15.116

   # As tsi (password from operator vault)
   ssh-copy-id -i ~/.ssh/TSiHomeLab.pub -p 22 tsi@179.190.15.116
   ```

### SSH Config Installation

Copy the canonical config to your local `~/.ssh/`:
```bash
# From project root
cp deploy/ssh/TSiAPP-config ~/.ssh/config.d/TSiAPP

# Update path placeholder
sed -i "s|__PROJECT_ROOT__|$(pwd)|g" ~/.ssh/config.d/TSiAPP

# Include in main config (if not already)
echo "Include ~/.ssh/config.d/TSiAPP" >> ~/.ssh/config
chmod 600 ~/.ssh/config.d/TSiAPP
```

### Connection Aliases

| Alias | User | Network | Command |
|-------|------|---------|---------|
| `tsia-root` | `root` | Public IP | `ssh tsia-root` |
| `tsia-tsi` | `tsi` | Public IP | `ssh tsia-tsi` |
| `tsia-root-tail` | `root` | Tailscale | `ssh tsia-root-tail` |
| `tsia-tsi-tail` | `tsi` | Tailscale | `ssh tsia-tsi-tail` |

### Verify Connectivity

```bash
# Public IP
ssh -q tsia-tsi 'echo "OK from $(hostname) via public IP"'

# Tailscale (requires tailscaled on client)
ssh -q tsia-tsi-tail 'echo "OK from $(hostname) via Tailscale"'

# Ansible inventory test
cd deploy/ansible
ansible -i inventory.yml tsisip_vps -m ping
```

---

## Environment Variables for Pipeline

The DevSecOps pipeline (`deploy/scripts/orchestrate-deploy.sh`) reads the following from `~/.env`:

```bash
# Required
GITHUB_TOKEN=ghp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TSiAPP_HOST=179.190.15.116
TSiAPP_USER=tsi
TSiAPP_SSH_KEY=/home/YOURUSER/.ssh/TSiHomeLab

# Optional overrides
TSISIP_IMAGE_TAG=v0.7.0
```

> **Security**: `~/.env` is in `.gitignore`. Never commit it.

---

## Network Topology

```text
[Operator Workstation]
        |
        | SSH (key-based, port 22)
        v
+---------------------------+
| 179.190.15.116 (Public)   |
| 100.111.74.69 (Tailscale) |
| TSiAPP VPS                |
|                           |
|  ┌─ Nginx (TLS 1.2/1.3)   |
|  ├─ Docker Engine         |
|  │   ├─ opensips          |
|  │   ├─ rtpengine         |
|  │   ├─ postgres          |
|  │   ├─ asterisk-pbx-*    |
|  │   ├─ ocp               |
|  │   └─ backup            |
|  └─ Certbot (ACME)        |
+---------------------------+
```

### Exposed Services

| Service | Port | Protocol | Bound To |
|---------|------|----------|----------|
| SSH | 22 | TCP | `0.0.0.0` |
| SIP Signaling | 5060 | UDP/TCP | `0.0.0.0` |
| SIP TLS | 5061 | TCP | `0.0.0.0` |
| RTP Media | 10000-10999 | UDP | `0.0.0.0` |
| OCP (reverse proxied) | 8084 | TCP | `127.0.0.1` |
| HTTPS | 443 | TCP | `0.0.0.0` (Nginx) |

---

## Ansible Inventory

The canonical inventory is at `deploy/ansible/inventory.yml`:

```yaml
all:
  children:
    tsisip_vps:
      hosts:
        tsiapp:
          ansible_host: 179.190.15.116
          ansible_user: tsi
          ansible_ssh_private_key_file: ~/.ssh/TSiHomeLab
          ansible_ssh_common_args: "-o StrictHostKeyChecking=accept-new"
          ansible_python_interpreter: /usr/bin/python3
          ansible_become: true
          ansible_become_method: sudo
          tsisip_app_dir: "/opt/tsisip"
```

> Note: `inventory.yml` uses `lookup('env', ...)` by default. Override via `~/.env` or edit locally.

---

## Security Checklist

| # | Check | Status |
|---|-------|--------|
| 1 | Password auth disabled (`PasswordAuthentication no`) | ☐ Verify |
| 2 | Root login requires key (`PermitRootLogin prohibit-password`) | ☐ Verify |
| 3 | TSiHomeLab private key is `chmod 600` | ☐ Verify |
| 4 | `~/.env` is NOT committed to git | ☐ Verify |
| 5 | Tailscale ACL restricts TSiAPP access to authorized devices | ☐ Verify |
| 6 | UFW/iptables allow only 22, 443, 5060, 5061, 10000-10999 | ☐ Verify |
| 7 | Fail2ban active on SSH port | ☐ Verify |

---

## Troubleshooting

### Connection Refused
```bash
# Check if SSH is listening
nc -zv 179.190.15.116 22

# Check Tailscale connectivity
ping 100.111.74.69
```

### Permission Denied (publickey)
```bash
# Verify key is loaded
ssh-add -l | grep TSiHomeLab

# If not loaded
ssh-add ~/.ssh/TSiHomeLab

# Debug SSH handshake
ssh -vvv tsia-tsi
```

### Host Key Changed
```bash
# Remove stale entry
ssh-keygen -R 179.190.15.116
ssh-keygen -R 100.111.74.69

# Re-accept on next connect
ssh tsia-tsi
```

---

## References

- [`deploy/ssh/TSiAPP-config`](deploy/ssh/TSiAPP-config) — SSH client config
- [`deploy/ansible/inventory.yml`](deploy/ansible/inventory.yml) — Ansible target
- [`deploy/scripts/orchestrate-deploy.sh`](deploy/scripts/orchestrate-deploy.sh) — Deploy pipeline
- [`AGENTS.md`](AGENTS.md) — Agent onboarding (Section 12)
