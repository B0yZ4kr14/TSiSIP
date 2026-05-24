# Data Flow Diagram — TSiSIP vps-lite Stack

**Date**: 2026-05-23
**Scope**: Personal data flows across all vps-lite services

---

## Flow 1: SIP Subscriber Registration

```
Internet → OpenSIPS (5060/udp)
  → db_postgres → PostgreSQL (subscriber table)
    → tenant_id, username, HA1 hash, domain
```

**Personal Data**: Username (pseudonymized as SIP URI), HA1 hash (non-reversible)
**Retention**: Until contract termination
**Access Control**: SIP digest only; no plaintext password storage

## Flow 2: Call Detail Record (CDR)

```
OpenSIPS → sql_query → PostgreSQL (cdr table)
  → callid, caller_id, callee_id, start_time, duration, tenant_id
```

**Personal Data**: Caller/callee identifiers
**Retention**: 7 years (ANATEL requirement)
**Pseudonymization**: Optional per-tenant configuration

## Flow 3: OCP Administrative Access

```
Browser → Nginx → OCP (PHP)
  → PDO → PostgreSQL (ocp_users, subscriber read-only)
```

**Personal Data**: Admin username, role, login IP
**Retention**: 1 year (audit log)
**Access Control**: bcrypt, role hierarchy, CSRF token

## Flow 4: Backup & Recovery

```
PostgreSQL → pg_dump → Backup container
  → rclone → S3-compatible storage (encrypted)
```

**Personal Data**: All subscriber and CDR data
**Encryption**: TLS/SRTP uses AES-256-GCM; backup encryption uses AES-256-CBC + PBKDF2 + HMAC-SHA256 (Feature 005)
**Retention**: Aligned with source data retention policies

## Data Flow Matrix

| Source | Destination | Data | Protection | Justification |
|---|---|---|---|---|
| SIP Client | OpenSIPS | SIP URI, HA1 | TLS/Digest | Auth |
| OpenSIPS | PostgreSQL | CDR | Internal network | Billing/Compliance |
| OCP | PostgreSQL | Queries | PDO prepared statements | Administration |
| Backup | S3 Storage | Dump files | AES-256-CBC + PBKDF2 + HMAC-SHA256 | Disaster recovery |
