# Encryption & Access Control Evidence — Feature 022

**Date**: 2026-05-23

---

## G18: Backup Encryption (AES-256-GCM)

```bash
# Verify backup encryption key exists
docker compose exec backup ls -la /run/secrets/backup_encryption_key

# Verify backup script references encryption
docker compose exec backup grep -E "encrypt|aes|gcm" /usr/local/bin/backup.sh
```

## G19: TLS 1.2+ Enforcement

```bash
# Verify OpenSIPS TLS config
docker compose exec opensips grep -E "tls_method|verify_cert" /etc/opensips/opensips.cfg
# Expected: tls_method = TLSv1_2+

# Verify OCP HTTPS detection
docker compose exec ocp grep "cookie_secure" /etc/php/8.2/apache2/php.ini
# Expected: session.cookie_secure = 1
```

## G20: Role-Based Access Control

```sql
-- Verify role hierarchy
SELECT username, role FROM ocp_users ORDER BY 
    CASE role 
        WHEN 'readonly' THEN 1 
        WHEN 'user' THEN 2 
        WHEN 'assistant' THEN 3 
        WHEN 'dentist' THEN 4 
        WHEN 'devops' THEN 5 
        WHEN 'admin' THEN 6 
    END;

-- Verify admin can access all tenants
-- Verify readonly can only read (no INSERT/UPDATE/DELETE)
```

## G21: SIP Digest Auth

```bash
# Run comprehensive auth test
bash scripts/test-invite-407.sh

# Verify no unauthorized REGISTER/INVITE succeeds
# (Requires failed auth attempt from unknown subscriber)
```

## Results

| Check | Expected | Actual | Status |
|---|---|---|---|
| Backup encryption | AES-256-GCM | [PENDING] | [PENDING] |
| TLS 1.2+ | Enforced | [PENDING] | [PENDING] |
| Role hierarchy | 6 levels | [PENDING] | [PENDING] |
| SIP digest | 407 for unauth | [PENDING] | [PENDING] |
