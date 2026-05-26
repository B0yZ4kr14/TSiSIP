# Decisions — TSiSIP Durable Memory

## Active Decisions

### AD-001: OpenSIPS 3.6 LTS as Sole SIP Proxy
- **Date**: 2026-05-16
- **Context**: Need stable, LTS SIP proxy with PostgreSQL support
- **Decision**: Use OpenSIPS 3.6 LTS exclusively
- **Consequences**: No Kamailio modules; no 3.4 baseline
- **Status**: Immutable (L1 Constitution)

### AD-002: PostgreSQL as Sole Database
- **Date**: 2026-05-16
- **Context**: Need robust relational DB for auth and routing metadata
- **Decision**: Use PostgreSQL only; reject MySQL/MariaDB
- **Consequences**: All DDL uses PostgreSQL syntax; no db_mysql module
- **Status**: Immutable (L1 Constitution)

### AD-003: Docker-First Runtime Delivery
- **Date**: 2026-05-16
- **Context**: Need reproducible, portable deployment
- **Decision**: All components delivered via project-owned Docker images
- **Consequences**: No bare-metal install instructions; Compose is canonical
- **Status**: Immutable (L1 Constitution)

### AD-004: Pre-computed HA1 for Authentication
- **Date**: 2026-05-16
- **Context**: SIP Digest requires HA1; computing on-the-fly is slower
- **Decision**: Store HA1, HA1_SHA256, HA1_SHA512T256; set calculate_ha1=0
- **Consequences**: Password never stored; salt rotation requires re-hash
- **Status**: Immutable (L1 Constitution)

### AD-005: topology_hiding("C") as Baseline
- **Date**: 2026-05-16
- **Context**: Need to conceal backend Asterisk IPs from public Internet
- **Decision**: Use topology_hiding("C") for all forwarded dialogs
- **Consequences**: Slightly higher memory usage than "U"; more secure
- **Status**: Active (L3 Decision)

### AD-006: Explicit rtpengine_offer/answer/delete
- **Date**: 2026-05-16
- **Context**: rtpengine_manage() is convenient but less explicit
- **Decision**: Use explicit offer/answer/delete calls
- **Consequences**: More verbose config; clearer control flow
- **Status**: Active (L3 Decision)

### AD-007: OCP Admin Tools with RBAC + CSRF
- **Date**: 2026-05-19
- **Context**: Need web-based admin for subscribers and dispatcher
- **Decision**: Build PHP OCP pages with PDO prepared statements, CSRF tokens, role checks
- **Consequences**: Constitution V1 requires amendment for intentional subscriber writes
- **Status**: Active (L3 Decision) — see CUP-012-01

## Rejected Patterns

See AGENTS.md Section 10 for full list of rejected patterns.

Key rejections:
- db_mysql / MySQL / MariaDB
- Bare-metal / VM-first runtime
- calculate_ha1 = 1
- plaintext passwords in seed data
- Kamailio auth_check() / auth_challenge()
- Hard-coded ds_select_dst(1, ...)
- Custom CREATE TABLE subscriber (must ALTER stock schema)
- rtpengine_manage() as baseline
- RTPengine kernel DKMS as baseline

## Decision Amendments

### CUP-012-01: Permit OCP Writes to subscriber/dispatcher
- **Original Rule**: architecture_constitution.md line 40 prohibits OCP writes
- **Amendment**: Allow authenticated admin CRUD with CSRF + RBAC gates
- **Status**: Proposed, pending Architecture Review ratification
- **Date**: 2026-05-19

## Feature 020 Decisions

### AD-020-1: MI Command Whitelist in PHP
- **Date**: 2026-05-19
- **Context**: Need to prevent command injection via MI HTTP interface
- **Decision**: Hardcoded whitelist array in PHP ($miWhitelist) — never pass user input directly to MI
- **Commands**: ds_reload, domain_reload, get_statistics, dlg_list, dlg_end_dlg, tls_reload
- **Consequences**: New commands require code change, not config change
- **Status**: Active (L3 Decision)

### AD-020-2: Statistics Backpressure with Graceful Degradation
- **Date**: 2026-05-19
- **Context**: 30s auto-refresh could overwhelm MI HTTP or create retry storms
- **Decision**: 5s cURL timeout, freeze charts on error, warning banner, no retry storm
- **Consequences**: Operators see stale data during MI outages rather than empty charts
- **Status**: Active (L3 Decision)

### AD-020-3: Database-Driven Dialplan
- **Date**: 2026-05-19
- **Context**: Hardcoded dialplan rules require container restart to change
- **Decision**: Store dialplan rules in PostgreSQL; reload via ds_reload MI command
- **Consequences**: Runtime dialplan management enabled; schema must match OpenSIPS stock
- **Status**: Active (L3 Decision)

### AD-020-4: Read-Only Dialog Viewer
- **Date**: 2026-05-19
- **Context**: Dialog data is sensitive; accidental termination is high-risk
- **Decision**: dialog.php is strictly read-only; dlg_end_dlg requires admin role in mi-commands.php
- **Consequences**: Two-step process to terminate calls (MI Commands page + admin role)
- **Status**: Active (L3 Decision)

### AD-024-4: VARCHAR(36) for tenant_id Foreign Keys
- **Date**: 2026-05-26
- **Context**: Brownfield scan B19 identified that subscriber, header_routing_rules, and pbx_backends use VARCHAR(36) for tenant_id instead of UUID as specified in canonical spec Section 12.
- **Decision**: Accept VARCHAR(36) as a pragmatic deviation. The stock OpenSIPS schema (01-stock-opensips-schema.sql) runs before the tenants table is created, making a strict UUID foreign key impossible during bootstrap without reordering init scripts or using ALTER TABLE after tenant creation.
- **Consequences**: tenant_id remains VARCHAR(36) with default '00000000-0000-0000-0000-000000000000' in subscriber; UUID is used where bootstrap ordering permits (sip_trunk_did_mappings, trunk_ips). This deviation is documented and does not affect runtime correctness.
- **Status**: Accepted (L3 Decision)

### AD-024-5: OCP v9 Parity Schema Credential Columns
- **Date**: 2026-05-26
- **Context**: Brownfield scan B20 flagged that smpp.password and uacreg.auth_password columns may store plaintext credentials.
- **Decision**: Retain column names as required by OpenSIPS modules (smpp, uac_registrant). Populate ONLY via APIs that encrypt or hash credentials before storage. Never insert plaintext directly. Security comments are embedded in the SQL schema file.
- **Consequences**: Column names cannot be changed to password_hash without breaking OpenSIPS module compatibility. Credential protection is enforced at the API/OCP layer, not the DDL layer.
- **Status**: Accepted (L3 Decision)
