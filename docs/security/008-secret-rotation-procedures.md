# Secret Rotation Procedures — Feature 008: DevSecOps Deployment Automation

**Document ID**: SEC-008-ROT-001  
**Date**: 2026-05-19  
**Applies to**: All secrets in `secrets/` directory  
**Rotation cycle**: 90 days maximum  

---

## 1. Secrets Inventory

| Secret File | Purpose | Rotation Owner | Complexity |
|---|---|---|---|
| `secrets/db_password` | PostgreSQL opensips user password | @b0yz4kr14 | High (requires DB update + container restart) |
| `secrets/auth_secret` | SIP digest auth realm secret | @b0yz4kr14 | High (requires subscriber re-registration) |
| `secrets/topology_secret` | Topology hiding secret | @b0yz4kr14 | Medium (container restart only) |
| `secrets/grafana_admin_password` | Grafana admin login | @b0yz4kr14 | Low (Grafana UI or container restart) |
| `secrets/backup_encryption_key` | Backup encryption key | @b0yz4kr14 | Critical (requires re-encrypting all backups) |
| `secrets/ca.crt` | Certificate Authority cert | @b0yz4kr14 | Critical (requires full PKI rotation) |
| `secrets/server.crt` | Server TLS certificate | @b0yz4kr14 | High (requires certbot re-issue) |
| `secrets/server.key` | Server TLS private key | @b0yz4kr14 | High (requires certbot re-issue) |
| `secrets/trunk_cred_key` | Trunk credential encryption key | @b0yz4kr14 | High (requires trunk re-provisioning) |

---

## 2. Rotation Procedure (Generic)

### Step 1: Pre-rotation

1. Run `scripts/secret-age-audit.sh` to identify secrets exceeding 90 days.
2. Notify stakeholders if rotation affects live subscribers (auth_secret, db_password).
3. Schedule rotation during maintenance window for high-impact secrets.

### Step 2: Generate New Secret

```bash
# Random password (32 bytes)
openssl rand -base64 32 > secrets/db_password.new

# Auth secret (exactly 32 bytes)
openssl rand -hex 16 > secrets/auth_secret.new
```

### Step 3: Update Database (if required)

For `db_password`:
```bash
docker compose exec postgres psql -U opensips -c "ALTER USER opensips WITH PASSWORD '$(cat secrets/db_password.new)';"
```

For `auth_secret`: No DB update required (read at startup).

### Step 4: Atomic Swap

```bash
# Backup old secret
cp secrets/db_password secrets/db_password.$(date +%Y%m%d)
# Atomically replace
mv secrets/db_password.new secrets/db_password
```

### Step 5: Restart Affected Services

```bash
docker compose up -d --no-deps --force-recreate <service>
```

### Step 6: Verify

1. Check service health: `docker compose ps`
2. Run `scripts/verify-secrets-audit.sh`
3. Run integration tests

### Step 7: Cleanup

Remove `.old` and backup files after 7 days:
```bash
find secrets/ -name '*.2*' -mtime +7 -delete
```

---

## 3. Automation

- `scripts/secret-age-audit.sh` runs in CI and warns on secrets >90 days.
- No automated rotation (deliberate — secret rotation requires human verification for high-impact secrets).

---

## 4. Emergency Rotation

If secret compromise is suspected:

1. Immediately rotate the compromised secret.
2. Check `docs/security/008-incident-response-runbook.md` for containment steps.
3. Review audit logs (`web/audit-log.php`) for unauthorized access.

---

## 5. Sign-off

| Role | Name | Date | Status |
|---|---|---|---|
| Author | Security Governance | 2026-05-19 | Approved |
