# Data Retention Verification — Feature 022

**Date**: 2026-05-23

---

## CDR Retention (7 Years)

```sql
-- Verify CDR table has retention configuration
SELECT 
    schemaname,
    tablename,
    pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) as size
FROM pg_tables 
WHERE tablename = 'cdr';

-- Check oldest CDR record
SELECT MIN(start_time) FROM cdr;
-- Should be within 7 years of current date
```

## Audit Log Retention (1 Year)

```sql
-- Verify audit log retention
SELECT MIN(timestamp) FROM auth_audit_log;
SELECT MIN(timestamp) FROM ocp_login_log;

-- Should be within 1 year of current date
```

## Purge Operation Logging

```sql
-- Verify purge operations are logged
SELECT * FROM audit_log WHERE action LIKE '%purge%' ORDER BY timestamp DESC LIMIT 5;
-- Should show admin/devops role for all purge operations
```

## Tenant Deletion Cascade

```sql
-- Test tenant deletion (dry run)
BEGIN;
DELETE FROM tenants WHERE id = 'test-tenant-id';
-- Verify cascade: subscriber, cdr, audit logs
ROLLBACK;
```

## Results

| Check | Expected | Actual | Status |
|---|---|---|---|
| CDR oldest record | < 7 years | [PENDING] | [PENDING] |
| Audit log oldest | < 1 year | [PENDING] | [PENDING] |
| Purge logged | admin/devops only | [PENDING] | [PENDING] |
| Tenant cascade | subscriber + CDR deleted | [PENDING] | [PENDING] |
