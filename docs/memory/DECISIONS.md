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
