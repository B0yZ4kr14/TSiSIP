# Data Model: TSiSIP SIP Edge Foundation

## Stock OpenSIPS Tables

### subscriber
OpenSIPS-native subscriber table with TSiSIP extensions.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | SERIAL | PK | Internal identifier |
| username | VARCHAR(64) | NOT NULL | SIP username |
| domain | VARCHAR(64) | NOT NULL | SIP domain |
| password | VARCHAR(25) | | Legacy field (unused, empty) |
| ha1 | VARCHAR(64) | NOT NULL | HA1 hash (MD5) |
| ha1_sha256 | VARCHAR(64) | | HA1 hash (SHA-256) |
| ha1_sha512t256 | VARCHAR(64) | | HA1 hash (SHA-512/256) |
| tenant_id | UUID | FK → tenants.id | Tenant association (TSiSIP ext) |
| routing_group | VARCHAR(64) | | Dispatcher routing group (TSiSIP ext) |
| enabled | BOOLEAN | DEFAULT true | Account active flag (TSiSIP ext) |

**Indexes**:
- uq_subscriber_tenant_username_domain UNIQUE (tenant_id, username, domain)
- idx_subscriber_tenant_domain (tenant_id, domain)

### dispatcher
OpenSIPS-native target selection table.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | SERIAL | PK | Internal identifier |
| setid | INTEGER | NOT NULL | Dispatcher set identifier |
| destination | VARCHAR(192) | NOT NULL | Target URI (sip:host:port) |
| state | INTEGER | DEFAULT 0 | Target state (0=active, 1=inactive, 2=probing) |
| weight | INTEGER | DEFAULT 1 | Load balancing weight |
| priority | INTEGER | DEFAULT 0 | Selection priority |
| attrs | VARCHAR(128) | | Additional attributes |

### address
Trusted IP whitelist for permissions module (FR-001-008).

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | SERIAL | PK | Internal identifier |
| ip | VARCHAR(50) | NOT NULL | IP address or CIDR |
| grp | INTEGER | DEFAULT 1 | Permission group identifier |
| port | INTEGER | DEFAULT 0 | Port (0 = any) |
| mask | INTEGER | DEFAULT 32 | CIDR mask length |
| context_info | VARCHAR(32) | | Context label |
| ip_addr | INET | | Parsed IP (optional) |

### version
Schema compatibility tracking for db_postgres module.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| table_name | VARCHAR(32) | PK | Table identifier |
| table_version | INTEGER | NOT NULL | Schema version number |

---

## TSiSIP Custom Tables

### tenants
Multi-tenant boundary definition.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PK, DEFAULT gen_random_uuid() | Tenant identifier |
| name | VARCHAR(128) | NOT NULL | Human-readable tenant name |
| sip_domain | VARCHAR(128) | NOT NULL, UNIQUE | Canonical SIP domain |
| enabled | BOOLEAN | DEFAULT true | Tenant active flag |
| created_at | TIMESTAMPTZ | DEFAULT now() | Creation timestamp |

### header_routing_rules
Header-based routing metadata for dispatcher set selection.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PK, DEFAULT gen_random_uuid() | Rule identifier |
| tenant_id | UUID | FK → tenants.id, NOT NULL | Owning tenant |
| header_name | VARCHAR(64) | NOT NULL | SIP header to inspect |
| match_value | VARCHAR(256) | NOT NULL | Value to match |
| match_type | VARCHAR(16) | NOT NULL | Match operator (exact, prefix, regex) |
| dispatcher_setid | INTEGER | NOT NULL | Target dispatcher set |
| priority | INTEGER | DEFAULT 0 | Evaluation order |
| enabled | BOOLEAN | DEFAULT true | Rule active flag |

**Indexes**:
- idx_header_routing_lookup (tenant_id, enabled, header_name, match_value, priority) WHERE enabled = true

### pbx_backends
Tenant-to-PBX backend mapping.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PK, DEFAULT gen_random_uuid() | Backend identifier |
| tenant_id | UUID | FK → tenants.id, NOT NULL | Owning tenant |
| dispatcher_setid | INTEGER | NOT NULL | Linked dispatcher set |
| label | VARCHAR(128) | | Human-readable label |
| enabled | BOOLEAN | DEFAULT true | Backend active flag |

**Indexes**:
- idx_pbx_backends_dispatcher_setid (tenant_id, dispatcher_setid)

### auth_audit_log
Authentication event tracking for security audit.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGSERIAL | PK | Event identifier |
| event_time | TIMESTAMPTZ | DEFAULT now() | Event timestamp |
| username | VARCHAR(64) | NOT NULL | Attempted username |
| domain | VARCHAR(128) | NOT NULL | Attempted domain |
| source_ip | INET | NOT NULL | Client IP address |
| sip_method | VARCHAR(16) | NOT NULL | SIP method (INVITE, REGISTER, etc.) |
| result | VARCHAR(16) | NOT NULL | Outcome (success, failure, challenge) |
| call_id | VARCHAR(128) | | SIP Call-ID header |

**Indexes**:
- idx_auth_audit_event_time (event_time)
- idx_auth_audit_username_domain (username, domain)
- idx_auth_audit_source_ip (source_ip)

---

## Entity Relationships

```
tenants ||--o{ subscriber : "has many"
tenants ||--o{ header_routing_rules : "has many"
tenants ||--o{ pbx_backends : "has many"
header_routing_rules }o--|| dispatcher : "references setid"
pbx_backends }o--|| dispatcher : "references setid"
subscriber .o--|| auth_audit_log : "generates events"
address }o--|| permissions : "feeds whitelist"
```

## Constraints

- calculate_ha1 = 0 — OpenSIPS reads precomputed HA1 columns only; never stores plaintext passwords.
- All PostgreSQL DDL uses db_postgres-compatible types and snake_case identifiers.
- version table entries required for db_postgres schema compatibility checks.
- address table initialized empty; populated via migration or operational scripts per FR-001-008.
