# Decisions

Record durable architecture, release, runtime, and safety decisions.

For each decision, include:

- date and short title
- affected files or surfaces
- evidence commands or artifacts
- rollback or revisit trigger

Never store secrets, raw MCP env, tokens, or private user data.

## 2026-05-19 — Canonical TSiAPP SSH Access

**Decision**: Standardize all TSiAPP operator access through a single SSH key pair (`TSiHomeLab`, Ed25519) with four connection aliases (public IP + Tailscale × root + tsi).

**Affected files**:
- `deploy/ssh/TSiAPP-config`
- `docs/VPS-TSiAPP-ACCESS.md`
- `.ssh/config.template`
- `deploy/ansible/inventory.yml`
- `deploy/scripts/orchestrate-deploy.sh`
- `.env.example`

**Evidence**:
- SSH config validates with `ssh -G tsia-tsi`
- Ansible ping succeeds after key distribution
- Deploy script defaults to public IP (179.190.15.116) and TSiHomeLab key

**Rollback trigger**: Key compromise, host migration, or Tailscale policy change.

**Security notes**:
- Password authentication disabled after bootstrap
- Bootstrap passwords live in operator vault only
- `.gitignore` excludes `.ssh/config` and `known_hosts`
