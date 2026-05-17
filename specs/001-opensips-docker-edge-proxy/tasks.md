# Tasks: OpenSIPS Docker Edge Proxy Foundation

## Phase 1 — OpenSIPS Docker Image and Configuration Template

### [X] T1.1: Create OpenSIPS Dockerfile
**Description**: Create a project-owned `Dockerfile` that builds an OpenSIPS 3.6 LTS image from `debian:bookworm-slim`. Install `opensips`, `opensips-postgres-module`, and `opensips-auth-modules` from the official apt.opensips.org repository. Include `gettext-base` for `envsubst`. Copy the configuration template and entrypoint into the image. Expose `5060/udp` and `5060/tcp`. Set the entrypoint and default command.
**Phase**: 1
**Depends on**: —
**Parallel**: No
**Acceptance**: `docker build -t tsisip/opensips:test .` succeeds.

### [X] T1.2: Create container entrypoint script
**Description**: Create `docker/entrypoint.sh` that loads runtime secrets from `/run/secrets/` (db_password, auth_secret, topology_secret) into environment variables, renders `/etc/opensips/opensips.cfg.tpl` into `/etc/opensips/opensips.cfg` using `envsubst`, and then execs the provided command. Script must use `set -eu` and fail fast if required secrets are missing.
**Phase**: 1
**Depends on**: T1.1
**Parallel**: No
**Acceptance**: ShellCheck passes; script exits non-zero when a required secret file is absent.

### [X] T1.3: Create OpenSIPS configuration template
**Description**: Create `opensips/opensips.cfg.tpl` with canonical module parameters and route skeleton. Include: socket definitions with advertised public IP, `db_default_url`, all `modparam` blocks for required modules (auth, auth_db, sqlops, dispatcher, rtpengine, topology_hiding, tm, rr, maxfwd), and the canonical route flow (maxfwd, message size, totag/CANCEL/OPTIONS handling, SANITIZE, AUTH, HEADER_ROUTING, INVITE dialog+topology hiding, RELAY with rtpengine_offer/answer/delete, BRANCH_MANAGE, REPLY_MANAGE, FAILURE_MANAGE with ds_next_dst).
**Phase**: 1
**Depends on**: T1.2
**Parallel**: No
**Acceptance**: Template renders to valid OpenSIPS config; `opensips -c` passes inside a test container.

### [X] T1.4: Validate image build and syntax check
**Description**: Run `docker compose build opensips` and verify the image builds without errors. Run `opensips -c -f /etc/opensips/opensips.cfg` inside the built container and confirm exit status 0.
**Phase**: 1
**Depends on**: T1.3
**Parallel**: No
**Acceptance**: Build succeeds; syntax check returns 0.

---

## Phase 2 — PostgreSQL Schema and Initialization

### [X] T2.1: Generate stock OpenSIPS 3.6 PostgreSQL schema
**Description**: Use official OpenSIPS 3.6 database schema tooling to generate the stock PostgreSQL schema. Save as `db/init/01-stock-opensips-schema.sql`. Ensure `subscriber` includes `username`, `domain`, `ha1`, `ha1_sha256`, `ha1_sha512t256`. Ensure `dispatcher` includes `id`, `setid`, `destination`, `state`, `weight`, `priority`, `attrs`. Ensure `address` table is included for `permissions` module (FR-008).
**Phase**: 2
**Depends on**: T1.4
**Parallel**: [P] with T2.2
**Acceptance**: Schema file is present and contains required tables and columns.

### [X] T2.2: Create TSiSIP custom schema extensions
**Description**: Create `db/init/02-tsisip-extensions.sql` with: `CREATE EXTENSION IF NOT EXISTS pgcrypto;`, `tenants` table, `ALTER TABLE subscriber` for tenant_id and routing_group, `header_routing_rules` table with index, `pbx_backends` table with index, and `auth_audit_log` table with 90-day retention support (FR-009). Use `IF NOT EXISTS` for idempotent initialization.
**Phase**: 2
**Depends on**: T1.4
**Parallel**: [P] with T2.1
**Acceptance**: SQL file parses without errors in PostgreSQL 16.

### [X] T2.3: Create seed data script
**Description**: Create `db/init/03-seed-data.sql` with minimal development data: at least one tenant, one dispatcher set entry, and one subscriber with precomputed HA1 hashes (MD5, SHA-256, SHA-512/256). No plaintext passwords may appear in the seed file.
**Phase**: 2
**Depends on**: T2.1, T2.2
**Parallel**: No
**Acceptance**: Seed script executes without errors; subscriber row contains only HA1 columns.

### [X] T2.4: Validate PostgreSQL initialization in container
**Description**: Start the `postgres` service via Docker Compose, verify it reaches ready state (`pg_isready`), and confirm all tables and indexes are created correctly.
**Phase**: 2
**Depends on**: T2.3
**Parallel**: No
**Acceptance**: `pg_isready` succeeds; schema inspection confirms all expected objects exist.

---

## Phase 3 — Docker Compose Topology and Networking

### [X] T3.1: Define Docker Compose services and networks
**Description**: Create `docker-compose.yml` with services: `postgres` (db_internal only, no ports), `opensips` (sip_edge, sip_internal, db_internal; ports 5060/udp,tcp; secrets; cap_drop/cap_add; security_opt), `rtpengine` (sip_edge, sip_internal; ports 10000-20000/udp; ng-control on internal IP), `asterisk-pbx-1` and `asterisk-pbx-2` (sip_internal only; expose informational only). Define networks `sip_edge`, `sip_internal` (internal: true), `db_internal` (internal: true). Define volume `postgres_data` and secrets `db_password`, `auth_secret`, `topology_secret`.
**Phase**: 3
**Depends on**: T2.4
**Parallel**: No
**Acceptance**: `docker compose config` renders without errors.

### [X] T3.2: Configure OpenSIPS service environment and secrets
**Description**: Ensure `opensips` service in Compose references all required environment variables (`OPENSIPS_LISTEN_IP`, `HOST_PUBLIC_IP`, `DB_HOST`, `DB_NAME`, `DB_USER`, `RTPENGINE_HOST`, `RTPENGINE_INTERNAL_IP`) and mounts all three secrets. Ensure `depends_on` includes `postgres` and `rtpengine`.
**Phase**: 3
**Depends on**: T3.1
**Parallel**: No
**Acceptance**: Compose config inspection shows correct env vars and secret mounts.

### [X] T3.3: Configure RTPengine service command and networking
**Description**: Ensure `rtpengine` service command includes: `--interface`, `--listen-ng=udp:${RTPENGINE_INTERNAL_IP}:22222`, `--port-min=10000`, `--port-max=20000`, `--log-stderr`. Verify it attaches to `sip_edge` and `sip_internal` only.
**Phase**: 3
**Depends on**: T3.1
**Parallel**: No
**Acceptance**: Compose config inspection shows correct command and network attachments.

### [X] T3.4: Verify network and port isolation
**Description**: Inspect rendered Compose output to confirm: only `opensips` publishes 5060/udp,tcp; only `rtpengine` publishes 10000-20000/udp; no `asterisk-*` service has `ports:` or attaches to `sip_edge`; `postgres` has no `ports:` and attaches only to `db_internal`; `rtpengine` ng-control is not bound to `0.0.0.0`.
**Phase**: 3
**Depends on**: T3.2, T3.3
**Parallel**: No
**Acceptance**: Manual inspection checklist passes; all isolation rules satisfied.

---

## Phase 4 — Validation and Hardening

### [X] T4.1: Create `.env.example` and secrets documentation
**Description**: Create `.env.example` documenting all environment variables with dummy values. Add documentation or script instructing the operator to create `secrets/db_password`, `secrets/auth_secret` (exactly 32 chars), and `secrets/topology_secret`. Ensure `.gitignore` excludes `secrets/*` (except maybe a `.gitkeep`) and all `.env*` except `.env.example`.
**Phase**: 4
**Depends on**: T3.4
**Parallel**: [P] with T4.2
**Acceptance**: `.env.example` is present; `.gitignore` blocks real secrets; no committed file contains sensitive data.

### [X] T4.2: Run Compose config validation
**Description**: Execute `docker compose config` and verify it exits 0. Review the rendered YAML for any unexpected port publishing or network misconfiguration.
**Phase**: 4
**Depends on**: T3.4
**Parallel**: [P] with T4.1
**Acceptance**: Command exits 0; rendered output matches canonical isolation rules.

### [X] T4.3: Run OpenSIPS syntax check inside built image
**Description**: Build the OpenSIPS image with the final template and entrypoint, start it with test secrets, and run `opensips -c -f /etc/opensips/opensips.cfg`. Confirm exit status 0.
**Phase**: 4
**Depends on**: T4.1, T4.2
**Parallel**: No
**Acceptance**: Syntax check returns 0.

### [B] T4.4: Validate unauthenticated OPTIONS handling
**Description**: With the OpenSIPS container running, send an OPTIONS request from an external SIP client or tool (e.g., `sipsak`, `nc`, or a simple SIP tool). Verify the response is `200 OK` and that no backend routing occurs.
**Phase**: 4
**Depends on**: T4.3
**Parallel**: No
**Acceptance**: OPTIONS receives 200 OK; no backend traffic observed.
**Blocked by**: Debian `rtpengine-daemon` package pulls 146+ dependencies (~229 MB), causing Docker build timeouts and daemon unresponsiveness. Runtime validation requires a lightweight RTPengine container or stub. See `spec.md` Active Issues.

### [B] T4.5: Validate unauthenticated INVITE challenge
**Description**: Send an INVITE without credentials. Verify the response is `407 Proxy Authentication Required`.
**Phase**: 4
**Depends on**: T4.3
**Parallel**: [P] with T4.4
**Acceptance**: INVITE receives 407; no backend forwarding occurs.
**Blocked by**: Same as T4.4 — runtime stack cannot start due to RTPengine container build failure.

### [X] T4.6: Final documentation update and sign-off
**Description**: Update `AGENTS.md` with any new build/test commands discovered during implementation. Ensure the feature directory contains a completed `README.md` or equivalent operator guide for this foundation feature. Validate that FR-008 (permissions/address), FR-009 (audit log), and FR-010 (health probe) are documented in spec and plan.
**Phase**: 4
**Depends on**: T4.1, T4.2, T4.3
**Parallel**: No
**Acceptance**: Documentation is accurate and matches committed artifacts; all new FRs are traceable.
