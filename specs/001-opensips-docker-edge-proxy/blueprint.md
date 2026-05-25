# Blueprint — TSiSIP SIP Edge Foundation

## Overview

The foundational containerized runtime for TSiSIP: a project-owned OpenSIPS 3.6 LTS Docker image, private PostgreSQL persistence, isolated Docker networks, and a syntax-validated configuration template that enforces edge authentication before any backend routing.

## Requirements

- **FR-001-001**: Project-owned container image for OpenSIPS 3.6 LTS with required modules compiled from source.
- **FR-001-002**: Runtime secret injection via Docker secrets; missing secrets cause clear startup failure.
- **FR-001-002A**: SIP Digest credentials use precomputed HA1 hashes only (`calculate_ha1 = 0`).
- **FR-001-003**: Private PostgreSQL with no host-published ports; standard DSN connectivity.
- **FR-001-004**: Network isolation by function: `sip_edge`, `sip_internal`, `db_internal`.
- **FR-001-005**: Edge authentication enforcement — all non-OPTIONS untrusted requests receive 401.
- **FR-001-006**: Configuration syntax validation inside the image before runtime startup.
- **FR-001-007**: Canonical routing logic skeleton with loop protection, auth, header routing, topology hiding.
- **FR-001-008**: Trusted gateway IP bypass via `permissions` module and `address` table.
- **FR-001-009**: Authentication event audit logging to `auth_audit_log` with 90-day retention.
- **FR-001-010**: Container health probe via SIP OPTIONS to `localhost:5060`.

**Success Criteria**: Image build <5 min; 100% invalid configs rejected; zero backend host-published ports; 1000 concurrent sessions; <50ms latency; 50 registrations/sec.

## Architecture

- **Container Platform**: Docker Engine with Docker Compose V2.
- **Base Images**: `debian:bookworm-slim` for OpenSIPS source build; `postgres:16` for database.
- **OpenSIPS Modules**: `db_postgres`, `sqlops`, `sl`, `tm`, `rr`, `maxfwd`, `sipmsgops`, `signaling`, `auth`, `auth_db`, `dialog`, `dispatcher`, `topology_hiding`, `permissions`, `proto_udp`, `proto_tcp`, `proto_tls`, `tls_mgm`, `tls_openssl`.
- **Configuration Rendering**: `envsubst` renders `/etc/opensips/opensips.cfg.tpl` at startup from runtime secrets.
- **Network Model**:
  - `sip_edge`: OpenSIPS + RTPengine; external access.
  - `sip_internal`: OpenSIPS + RTPengine + Asterisk; internal forwarding only.
  - `db_internal`: OpenSIPS + PostgreSQL + OCP; database access only.
- **Data Model**: Stock OpenSIPS tables (`subscriber`, `dispatcher`, `address`, `version`) plus TSiSIP extensions (`tenants`, `header_routing_rules`, `pbx_backends`, `auth_audit_log`).

## Implementation Plan

### Phase 1: OpenSIPS Docker Image and Configuration Template
- `Dockerfile`: Debian Bookworm slim, OpenSIPS 3.6 LTS source build, required modules, config template, entrypoint.
- `docker/entrypoint.sh`: Secret loading, `envsubst` rendering, fail-fast if secrets missing.
- `opensips/opensips.cfg.tpl`: Canonical module parameters, route skeleton, auth block, header routing, relay.
- Build and syntax validation gates.

### Phase 2: PostgreSQL Schema and Initialization
- `db/init/01-stock-opensips-schema.sql`: Minimal stock schema compatible with `db_postgres`.
- `db/init/02-tsisip-extensions.sql`: Custom tables, ALTER TABLE, indexes, constraints.
- `db/init/03-seed-data.sql`: Minimal seed data with HA1 hashes only.

### Phase 3: Docker Compose Topology and Networking
- `docker-compose.yml`: Services for `postgres`, `opensips`, `rtpengine`, `asterisk-pbx-1/2`.
- Networks: `sip_edge`, `sip_internal` (internal), `db_internal` (internal).
- Secrets: `db_password`, `auth_secret`, `topology_secret`.

### Phase 4: Validation and Hardening
- `.env.example` and secrets documentation.
- `docker compose config` validation.
- Port and network isolation verification.
- OpenSIPS syntax check, OPTIONS 200 OK, INVITE 401 challenge, authenticated INVITE routing validation.

## Tasks

**Phase 1** — OpenSIPS Docker Image and Configuration Template
- T1.1: Create OpenSIPS Dockerfile
- T1.2: Create container entrypoint script
- T1.3: Create OpenSIPS configuration template
- T1.4: Validate image build and syntax check

**Phase 2** — PostgreSQL Schema and Initialization
- T2.1: Generate stock OpenSIPS 3.6 PostgreSQL schema
- T2.2: Create TSiSIP custom schema extensions
- T2.3: Create seed data script (HA1 hashes only)
- T2.4: Validate PostgreSQL initialization in container

**Phase 3** — Docker Compose Topology and Networking
- T3.1: Define Docker Compose services and networks
- T3.2: Configure OpenSIPS service environment and secrets
- T3.3: Configure RTPengine service command and networking
- T3.4: Verify network and port isolation

**Phase 4** — Validation and Hardening
- T4.1: Create `.env.example` and secrets documentation
- T4.2: Run Compose config validation
- T4.3: Run OpenSIPS syntax check inside built image
- T4.4: Validate unauthenticated OPTIONS handling
- T4.5: Validate unauthenticated INVITE challenge
- T4.7: Validate authenticated production routing to Asterisk
- T4.6: Final documentation update and sign-off

**Phase 5** — Post-Foundation Implementation
- T5.1: Implement trusted gateway bypass (FR-001-008)
- T5.2: Implement auth audit logging (FR-001-009)
- T5.3: Align auth response code contract (401 vs 407)

## Validation

- `docker compose config` renders without errors.
- `docker build -t tsisip/opensips:test .` succeeds from clean checkout.
- OpenSIPS configuration syntax check passes inside the image.
- Only OpenSIPS publishes canonical host ports (5060/udp, 5060/tcp, 5061/tcp).
- Asterisk and PostgreSQL have no host-published ports.
- SIP OPTIONS returns `200 OK`; unauthenticated INVITE returns `401 Unauthorized`.
- Authenticated INVITE reaches Asterisk backends through dispatcher.

## Risks & Dependencies

| Risk | Mitigation |
|---|---|
| OpenSIPS package changes between builds | Pin base image digest; validate in CI |
| Configuration template syntax drift | `opensips -c` at build and startup |
| Secret management confusion | `.env.example` documents all vars; `.gitignore` blocks real secrets |
| Host port 5060 already bound | Document port conflict resolution; support override via environment |
| Foundation performance exceeds single-instance capacity | Treat targets as baseline; validate under performance test track |

**Dependencies**: Docker Engine + Compose V2; OpenSIPS 3.6 LTS source build; PostgreSQL 16; host privileges to bind UDP/TCP port 5060.
