# Secret Management & Auth Contract Evidence (G8)

**Date**: 2026-05-23T23:07:00-03:00
**Scope**: vps-lite profile authentication and secret handling

---

## HA1 Precomputed Authentication

### OpenSIPS Configuration

```
modparam("auth_db", "calculate_ha1", 0)
modparam("auth_db", "password_column", "ha1")
```

**Status**: PASS — calculate_ha1=0 (precomputed HA1), password_column=ha1.

### PostgreSQL Schema Verification

```sql
-- subscriber table contains ha1, ha1_sha256, ha1_sha512t256 columns
-- No plaintext password column exists
```

**Status**: PASS — Stock OpenSIPS 3.6 schema with HA1 hash columns only.

### Seed Data Verification

```sql
-- 03-seed-data.sql populates ha1 columns only
-- INSERT INTO subscriber (username, domain, ha1, ha1_sha256, ha1_sha512t256)
```

**Status**: PASS — No plaintext passwords in seed data.

---

## Secret File Hygiene

| Secret File | Location | In .gitignore | In Compose Secrets | Status |
|-------------|----------|---------------|-------------------|--------|
| db_password | secrets/ | Yes | Yes | PASS |
| auth_secret | secrets/ | Yes | Yes | PASS |
| backup_encryption_key | secrets/ | Yes | Yes | PASS |
| topology_secret | secrets/ | Yes | No (envsubst) | Documented |
| ca.key | ca-offline/ | Yes | N/A | PASS |
| server.key | secrets/ | Yes | Yes | PASS |

---

## Environment Variable Security

| Variable | In .env.example | In .gitignore | Status |
|----------|-----------------|---------------|--------|
| DB_PASSWORD | Referenced | Yes | PASS |
| AUTH_SECRET | Referenced | Yes | PASS |
| BACKUP_ENCRYPTION_KEY | Referenced | Yes | PASS |
| ACME_EMAIL | Plaintext | Yes | Acceptable — non-sensitive |
| TLS_DOMAIN | Plaintext | Yes | Acceptable — non-sensitive |

---

## Conclusion

**Status**: PASS — Auth contract verified (HA1 precomputed, no plaintext passwords).
**Status**: PASS — Secret hygiene verified (all secrets in gitignored files).
