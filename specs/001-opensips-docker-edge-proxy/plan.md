# Implementation Plan: OpenSIPS Docker Edge Proxy Foundation

## Overview

This plan translates the feature specification into an executable implementation roadmap. It defines the architecture, technology choices, data model, implementation phases, and validation gates required to deliver the foundational TSiSIP edge proxy infrastructure.

---

## Architecture & Stack Choices

### Container Platform
- **Docker Engine** with Docker Compose V2 as the canonical orchestration layer.
- All services are defined as Docker services with explicit network attachments.

### Base Images
- **OpenSIPS**: Built from `debian:bookworm-slim` (pinned to digest in production CI) by compiling OpenSIPS 3.6 LTS from the official GitHub repository (`3.6` branch) from source.
- **PostgreSQL**: `postgres:16` from official Docker Hub registry.

### OpenSIPS Module Set
| Module | Purpose |
|---|---|
| `db_postgres` | PostgreSQL connectivity |
| `sqlops` | SQL lookups for routing metadata |
| `sl` | Stateless replies for edge rejection |
| `tm` | Stateful transactions and retransmissions |
| `rr` | Record-Route and loose-route support |
| `maxfwd` | Max-Forwards loop protection |
| `sipmsgops` | SIP header operations |
| `signaling` | Unified reply API for auth flows |
| `auth` | Digest challenge generation |
| `auth_db` | PostgreSQL-backed credential verification |
| `dialog` | Dialog state for topology hiding |
| `dispatcher` | PBX target selection and failover |
| `topology_hiding` | Topology concealment |
| `permissions` | IP ACL for trusted gateways |

### Transport
- `proto_udp` and `proto_tcp` are compiled into the core binary but require explicit `loadmodule` directives to register transport protocols.

### Configuration Rendering
- `envsubst` (from `gettext-base`) renders `/etc/opensips/opensips.cfg.tpl` into `/etc/opensips/opensips.cfg` at container startup.
- Template variables: `OPENSIPS_LISTEN_IP`, `HOST_PUBLIC_IP`, `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`, `AUTH_SECRET_32_CHARS`, `TOPOLOGY_SECRET`, `RTPENGINE_HOST`.

### Health Checks
- Container health checks use SIP OPTIONS to `localhost:5060` and expect `200 OK`.
- Three consecutive failures mark the container as unhealthy.
- Orchestrator/operator is responsible for restart policy.

### Secrets Strategy
- Docker Compose `secrets:` stanza mounts files from `./secrets/` into `/run/secrets/` inside containers.
- Entrypoint script reads secret files and exports them as environment variables before `envsubst` runs.
- `.gitignore` excludes `./secrets/` and all `.env*` files except `.env.example`.

---

## Data Model

### Stock OpenSIPS Tables (from official OpenSIPS 3.6 schema tooling)
- `subscriber`: username, domain, ha1, ha1_sha256, ha1_sha512t256, plus TSiSIP extensions.
- `dispatcher`: id, setid, destination, state, weight, priority, attrs.
- `address`: id, ip, grp, port, mask, context, tag — used by `permissions` module for trusted gateway IP whitelist (FR-008).
- `version`: table_name, table_version — schema compatibility tracking for `db_postgres`.

### TSiSIP Custom Extensions
- `tenants`: UUID primary key, name, sip_domain, enabled, created_at.
- `subscriber` extensions: tenant_id (FK → tenants), routing_group, enabled.
- `header_routing_rules`: UUID primary key, tenant_id (FK), header_name, match_value, match_type, dispatcher_setid, priority, enabled.
- `pbx_backends`: UUID primary key, tenant_id (FK), dispatcher_setid, label, enabled.
- `auth_audit_log`: BIGSERIAL primary key, event_time, username, domain, source_ip, sip_method, result, call_id. Supports 90-day retention per FR-009.

### Indexes
- `uq_subscriber_tenant_username_domain` unique index on subscriber(tenant_id, username, domain).
- `idx_subscriber_tenant_domain` on subscriber(tenant_id, domain).
- `idx_header_routing_lookup` partial index on header_routing_rules(tenant_id, enabled, header_name, match_value, priority) WHERE enabled = true.
- `idx_pbx_backends_dispatcher_setid` on pbx_backends(tenant_id, dispatcher_setid).

---

## Implementation Phases

### Phase 1: OpenSIPS Docker Image and Configuration Template
**Objective**: Produce a buildable, syntax-validated OpenSIPS container image.

**Deliverables**:
1. `Dockerfile` — Debian Bookworm slim base, OpenSIPS 3.6 LTS source build from GitHub, required modules compiled in, config template copy, entrypoint setup.
2. `docker/entrypoint.sh` — Secret loading, envsubst rendering, exec proxy.
3. `opensips/opensips.cfg.tpl` — Canonical module parameters, route skeleton, auth block, header routing block, relay block, reply/failure branches.
4. Build validation: `docker compose build opensips` succeeds.
5. Syntax validation: `opensips -c -f /etc/opensips/opensips.cfg` exits 0 inside the image at startup.

**Technical Constraints**:
- Base image digest must be commented for CI pinning.
- Compile OpenSIPS from source with required modules: `db_postgres`, `auth`, `auth_db`, `dialog`, `dispatcher`, `rtpengine`, `topology_hiding`, `permissions`, `sqlops`, `rr`, `tm`, `maxfwd`, `sipmsgops`, `signaling`, `sl`, `proto_udp`, `proto_tcp`.
- `EXPOSE 5060/udp 5060/tcp` in Dockerfile.
- Entrypoint must fail fast if secrets are missing.
- OpenSIPS startup is fail-fast for DB unavailability (no retry loop); operator/orchestrator handles restart.

---

### Phase 2: PostgreSQL Schema and Initialization
**Objective**: Establish the database with stock OpenSIPS schema plus TSiSIP extensions.

**Deliverables**:
1. `db/init/01-stock-opensips-schema.sql` — Minimal stock schema (subscriber, dispatcher, version) compatible with OpenSIPS 3.6 `db_postgres` module expectations.
2. `db/init/02-tsisip-extensions.sql` — Custom tables, ALTER TABLE statements, indexes, and constraints.
3. `db/init/03-seed-data.sql` — Minimal seed tenants and dispatcher entries for local development (with HA1 hashes only, no plaintext passwords).

**Technical Constraints**:
- Stock schema must be generated first; custom migrations run after.
- `subscriber` must include `ha1`, `ha1_sha256`, `ha1_sha512t256` columns.
- `dispatcher` must use `state` column, not `flags`.
- `calculate_ha1 = 0`; OpenSIPS reads precomputed HA1 columns only.

---

### Phase 3: Docker Compose Topology and Networking
**Objective**: Define the multi-service composition with isolated networks and published ports.

**Deliverables**:
1. `docker-compose.yml` with services:
   - `postgres` — internal `db_internal` network only, no published ports, secret-mounted password, initialization volume.
   - `opensips` — attaches to `sip_edge`, `sip_internal`, `db_internal`; publishes `5060/udp` and `5060/tcp`; secret mounts; capability dropping.
   - `rtpengine` — attaches to `sip_edge`, `sip_internal`; publishes `10000-20000/udp`; ng-control bound to internal IP.
   - `asterisk-pbx-1` and `asterisk-pbx-2` — attach to `sip_internal` only; no published ports.
2. Network definitions:
   - `sip_edge`: bridge, external access allowed.
   - `sip_internal`: bridge, `internal: true`.
   - `db_internal`: bridge, `internal: true`.
3. Volume: `postgres_data`.
4. Secrets: `db_password`, `auth_secret`, `topology_secret`.

**Technical Constraints**:
- Only `opensips` may publish `5060/udp,tcp`.
- Only `rtpengine` may publish `10000-20000/udp`.
- No `asterisk-*` service may define `ports:` or attach to a public network.
- `postgres` must not define `ports:` and must attach only to `db_internal`.
- RTPengine `--listen-ng` must bind to `${RTPENGINE_INTERNAL_IP}:22222`, not `0.0.0.0`.
- OpenSIPS container drops all capabilities except `NET_BIND_SERVICE`, `SETUID`, `SETGID`.
- OpenSIPS container uses `security_opt: ["no-new-privileges:true"]`.

---

### Phase 4: Validation and Hardening
**Objective**: Verify the stack against acceptance criteria and security constraints.

**Deliverables**:
1. `.env.example` — Documented environment variables without real secrets.
2. `secrets/` directory (gitignored) with `.env.example` documenting required secret files.
3. Run `docker compose config` and verify rendered output.
4. Validate port isolation: inspect that only OpenSIPS and RTPengine publish host ports.
5. Validate network isolation: confirm Asterisk and PostgreSQL are on internal networks only.
6. Validate OpenSIPS syntax check passes inside the built image.
7. Validate container health check mechanism (SIP OPTIONS → 200 OK) per FR-010.
8. Validate trusted gateway bypass via `permissions` module and `address` table per FR-008.
9. Validate auth audit logging populates `auth_audit_log` per FR-009.
10. Validate that unauthenticated OPTIONS receives 200 OK locally (T4.4 — blocked by rtpengine container build).

**Technical Constraints**:
- All validation steps must be reproducible via documented shell commands.
- No committed file may contain plaintext credentials.
- Foundation runs as single OpenSIPS instance per deployment; multi-instance/HA deferred.

---

## Dependency Graph

```
Phase 1 (Docker Image)
    |
    v
Phase 2 (PostgreSQL Schema)
    |
    v
Phase 3 (Compose Topology)
    |
    v
Phase 4 (Validation)
```

Phases are strictly sequential. Phase 2 depends on Phase 1 because schema initialization scripts assume the OpenSIPS module contract. Phase 3 depends on Phase 2 because Compose services reference initialization volumes. Phase 4 depends on all preceding phases.

---

## Risk Mitigation

| Risk | Mitigation |
|---|---|
| OpenSIPS package changes between builds | Pin `debian:bookworm-slim` digest in production; validate package list in CI. |
| Configuration template syntax drift | `opensips -c` runs at image build time and at container startup as a gate. |
| Secret management confusion | `.env.example` documents all variables; `.gitignore` blocks real secrets; entrypoint fails fast. |
| Network subnet collision | Default bridge networking is used; explicit subnets can be added if collisions are observed. |
| Health check false positives | OPTIONS is locally handled and lightweight; 3-strike failure threshold reduces noise. |

---

## Definition of Done

- [x] `docker compose config` renders without errors.
- [x] `docker build -t tsisip/opensips:test .` succeeds from a clean checkout.
- [x] OpenSIPS configuration syntax check passes inside the image.
- [x] Only OpenSIPS and RTPengine publish canonical host ports.
- [x] Asterisk and PostgreSQL have no host-published ports and attach only to internal networks.
- [x] No plaintext secrets are committed.
- [x] All acceptance criteria from the specification are traceable to a validation step in Phase 4.
- [x] Performance targets defined: 1000 concurrent sessions, <50ms latency, 50 registrations/sec.
- [x] Health check mechanism documented (SIP OPTIONS → 200 OK).
- [x] Fail-fast DB startup semantics implemented.
- [x] Single-instance deployment explicitly documented.
- [ ] Unauthenticated OPTIONS receives 200 OK (T4.4 — blocked by rtpengine container build).
- [ ] Unauthenticated INVITE receives 407 Proxy Authentication Required (T4.5 — blocked by rtpengine container build).
