# T5.5 Rollback Restoration Test — Evidence Report

**Date:** 2026-05-19T15:55:00-03:00
**Feature:** 022-vps-go-live-stabilization
**Task:** T5.5 — Execute full rollback restoration test
**Status:** PASS

---

## 1. Test Objective

Verify that the PostgreSQL backup produced by the backup service can be restored to an isolated PostgreSQL container and that all subscriber data, schema, and auth integrity are preserved.

## 2. Test Environment

| Component | Value |
|---|---|
| Source Database | postgres service (PostgreSQL 15, Debian-based image) |
| Backup Tool | pg_dump from backup service container |
| Backup Format | Plain SQL (-Fp) with --no-owner --no-privileges |
| Restore Target | Isolated tsisip-rollback-test container (postgres:15-alpine) |
| Restore Tool | psql via PGPASSWORD env var |
| Network | None (isolated container) |

## 3. Procedure

### 3.1 Create Backup from Production Stack

Command:
```
docker compose exec backup bash -c \
  'PGPASSWORD=$(cat /run/secrets/db_password) pg_dump -h postgres -U opensips -d opensips --no-owner --no-privileges -Fp -f /tmp/rollback-test-plain.sql'
```

Result: Exit code 0, 46,970 bytes written.

### 3.2 Create Isolated PostgreSQL Container

Command:
```
docker run -d --name tsisip-rollback-test \
  -e POSTGRES_DB=opensips \
  -e POSTGRES_USER=opensips \
  -e POSTGRES_PASSWORD=REDACTED \
  postgres:15-alpine
```

Result: Container started successfully, PostgreSQL ready.

### 3.3 Restore Backup

Command:
```
docker exec tsisip-rollback-test bash -c \
  'PGPASSWORD=REDACTED psql -U opensips -d opensips -f /tmp/rollback-test-plain.sql'
```

Result: Exit code 0. All tables, sequences, indexes, and triggers restored. Data copied into all tables without errors.

### 3.4 Verify Integrity

#### Row Count Comparison (Original vs Restored)

| Table | Original | Restored | Match |
|---|---|---|---|
| subscriber | 1 | 1 | YES |
| tenants | 2 | 2 | YES |
| pbx_backends | 1 | 1 | YES |
| header_routing_rules | 2 | 2 | YES |
| dispatcher | 2 | 2 | YES |
| auth_audit_log | 3 | 3 | YES |
| version | 11 | 11 | YES |

#### Auth Contract Verification

| Check | Result |
|---|---|
| Total subscribers | 1 |
| HA1 hash present | 1 |
| Plaintext passwords | 0 |

All subscriber credentials stored as precomputed HA1 hashes. Zero plaintext passwords in restored database.

#### Schema Verification

subscriber table columns (in ordinal order):

| Column | Data Type |
|---|---|
| id | integer |
| username | character varying |
| domain | character varying |
| password | character varying |
| ha1 | character varying |
| ha1_sha256 | character varying |
| ha1_sha512t256 | character varying |
| email_address | character varying |
| rpid | character varying |
| tenant_id | uuid |
| routing_group | integer |
| enabled | boolean |

Schema matches canonical specification.

### 3.5 Cleanup

```
docker stop tsisip-rollback-test && docker rm tsisip-rollback-test
rm -f /tmp/rollback-test-plain.sql
```

Result: All temporary artifacts removed.

## 4. Conclusion

- Backup integrity: PASS
- Restore fidelity: PASS
- Data integrity: PASS
- Auth contract: PASS
- Schema integrity: PASS

**T5.5 — COMPLETE**
