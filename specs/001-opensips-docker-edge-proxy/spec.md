# Feature Specification: OpenSIPS Docker Edge Proxy Foundation

## Overview

**Feature**: OpenSIPS Docker Edge Proxy Foundation
**Short name**: opensips-docker-edge-proxy
**Created**: 2026-05-16
**Status**: Implemented (Pending T4.4/T4.5 SIP Integration Tests)

### Context

TSiSIP is a Docker-image-first SIP edge-proxy platform. Before any SIP traffic can be processed, authenticated, or routed, a foundational containerized runtime must exist. This feature establishes the first deployable unit: the OpenSIPS edge proxy delivered as a project-owned container image, backed by a relational database for subscriber credentials and routing metadata, orchestrated through container-native networking and composition.

This is the foundational layer upon which all subsequent TSiSIP capabilities—multi-tenant routing, media relay integration, backend PBX isolation, and security hardening—are built.

### Objective

Enable a platform operator to build, configure, and start a containerized OpenSIPS edge proxy with:
- A validated, reproducible container image owned by the project.
- A private PostgreSQL database for subscriber authentication and routing metadata.
- Isolated Docker networks separating public signaling, internal forwarding, and database access.
- A configuration template that enforces edge authentication before any backend route selection.
- Syntax-validated configuration before runtime startup.

---

## Clarifications

### Session 2026-05-16

- Q: What runtime performance/throughput targets should the OpenSIPS edge proxy foundation meet? → A: 100 concurrent SIP sessions, <50ms median response latency, 50 registrations/sec (foundation baseline; detailed benchmarking deferred to future performance feature).
- Q: How should health/ready checks be performed for the OpenSIPS container? → A: SIP OPTIONS to localhost:5060 expecting 200 OK; failure after 3 consecutive missed responses.
- Q: What retry semantics should apply when PostgreSQL is unavailable at OpenSIPS startup? → A: Fail-fast immediately with descriptive exit status (no retry loop); orchestrator/operator is responsible for restart policy.
- Q: Should secret rotation strategy be defined in this foundation spec? → A: Defer to future operational documentation; no secret rotation requirements in foundation spec.
- Q: Is multi-instance horizontal scaling supported in the foundation? → A: Single instance per deployment; multi-instance/HA deferred to future feature requiring shared dialog state or session affinity.
- Q: What fallback behavior should apply when RTPengine is unavailable during INVITE processing? → A: T4.4/T4.5 SIP validation is gated on implementing a lightweight RTPengine container first; fallback behavior undefined until media relay is functional.
- Q: What functional requirement justifies the presence of the permissions module in the OpenSIPS configuration? → A: FR-008 defines IP-based trusted gateway bypass using the permissions module and OpenSIPS address table.
- Q: What behavior should apply when concurrent SIP sessions exceed the foundation limit? → A: Limit raised to 1000 concurrent sessions; overload degradation behavior deferred to future performance feature.
- Q: What logging requirements should apply to the auth_audit_log table? → A: FR-009 requires all authentication events (success, failure, challenge) to be persisted in auth_audit_log with minimum 90-day retention.
- Q: Should the health check mechanism be classified as a formal functional requirement? → A: Promoted to FR-010 "Container health probe via SIP OPTIONS".

---

## User Scenarios & Testing

### Primary Flows

#### Scenario 1: Platform operator builds and validates the edge proxy image
- **Given** the project source is checked out and container tooling is available
- **When** the operator triggers the image build process
- **Then** a project-owned container image is produced containing OpenSIPS 3.6 LTS and the canonical configuration template
- **And** the configuration is syntactically validated inside the image before it is considered ready

#### Scenario 2: Platform operator starts the core infrastructure stack
- **Given** the container image is built and runtime secrets are supplied
- **When** the operator starts the composed stack
- **Then** OpenSIPS listens on the public SIP signaling ports
- **And** PostgreSQL is reachable only on an internal database network
- **And** no backend service is exposed to the host network

#### Scenario 3: SIP client attempts unauthenticated access
- **Given** the edge proxy is running and a SIP client sends an INVITE without credentials
- **When** the request reaches the proxy
- **Then** the proxy responds with an authentication challenge and does not forward the request to any backend

### Edge Cases & Error Conditions

- Invalid or missing runtime secrets prevent OpenSIPS from starting rather than falling back to unsafe defaults.
- Malformed SIP messages exceeding safe size bounds are rejected at the edge before processing.
- SIP loop detection triggers an error response when Max-Forwards is exhausted.
- Database unavailability at startup triggers immediate fail-fast with descriptive exit status (no retry loop).
- Container health checks use SIP OPTIONS to `localhost:5060` and expect `200 OK`; three consecutive failures mark the container as unhealthy.

---

## Functional Requirements

### FR-001: Project-owned container image for OpenSIPS
**Description**: The OpenSIPS edge proxy must be built from a committed project Dockerfile, producing a reproducible container image.
**Acceptance Criteria**:
- Image build completes without external image substitution for the core proxy.
- Image contains OpenSIPS 3.6 LTS and required database connectivity modules.
- Image includes a templated configuration file rendered at startup from runtime-supplied values.

### FR-002: Runtime secret injection
**Description**: Secrets such as database credentials, authentication secrets, and topology hiding keys must be injected at runtime without being committed to source control.
**Acceptance Criteria**:
- Configuration template references secrets through environment variables or container secret mounts.
- Secrets are never present in committed source files.
- Missing secrets cause a clear startup failure rather than silent unsafe defaults.

### FR-003: Private PostgreSQL persistence
**Description**: A PostgreSQL database service must be available exclusively on an internal Docker network for subscriber credentials, tenant metadata, and routing rules.
**Acceptance Criteria**:
- PostgreSQL service has no host-published ports.
- OpenSIPS can connect to PostgreSQL using a standard DSN.
- Database initialization scripts create the required stock tables and project-specific extensions.

### FR-004: Network isolation by function
**Description**: Docker networks must enforce separation between public signaling, internal SIP forwarding, and database access.
**Acceptance Criteria**:
- Public SIP ingress is confined to a dedicated network attaching only to edge-facing services.
- Internal SIP forwarding occurs on a separate network that does not allow external host access.
- Database traffic is restricted to a dedicated internal network.

### FR-005: Edge authentication enforcement
**Description**: Every SIP request from untrusted sources—except health-check OPTIONS—must be challenged for Digest authentication before any backend routing decision.
**Acceptance Criteria**:
- Unauthenticated REGISTER requests receive a 401 challenge.
- Unauthenticated INVITE and other requests receive a 407 challenge.
- OPTIONS requests are answered locally without backend routing and without exposing topology.

### FR-006: Configuration syntax validation
**Description**: The OpenSIPS configuration must be validated for syntax correctness inside the built image before the proxy process starts.
**Acceptance Criteria**:
- A syntax check command runs successfully inside the container.
- Syntax errors prevent container startup with a descriptive exit status.

### FR-007: Canonical routing logic skeleton
**Description**: The configuration must implement the canonical route flow: loop/size protection, in-dialog handling, CANCEL handling, unauthenticated OPTIONS, header sanitization, authentication, backend routing, dialog creation, topology hiding, and stateful relay.
**Acceptance Criteria**:
- Route blocks are named and sequenced according to the canonical contract.
- Authentication occurs before backend route resolution.
- Credentials are consumed before forwarding.

### FR-008: Trusted gateway IP bypass
**Description**: The `permissions` module must allow authentication bypass for SIP requests originating from pre-configured trusted gateway IP addresses, using the OpenSIPS `address` table for IP-based whitelist lookups.
**Acceptance Criteria**:
- Requests from IPs listed in the `address` table bypass Digest authentication.
- Non-trusted IPs continue to receive 401/407 challenges per FR-005.
- The `address` table is initialized empty in the foundation; gateway IPs are populated via migration or operational scripts.

### FR-009: Authentication event audit logging
**Description**: All authentication attempts (success, failure, challenge) must be persisted to the `auth_audit_log` table for operational debugging and security audit. Records must be retained for a minimum of 90 days.
**Acceptance Criteria**:
- Every Digest challenge, successful authentication, and failed authentication attempt generates an `auth_audit_log` record.
- Records include event_time, username, domain, source_ip, sip_method, result, and call_id.
- The table design supports 90-day retention without performance degradation.

### FR-010: Container health probe via SIP OPTIONS
**Description**: The OpenSIPS container must expose a health probe mechanism using SIP OPTIONS requests to `localhost:5060`. A successful probe returns `200 OK`; three consecutive failures mark the container as unhealthy.
**Acceptance Criteria**:
- SIP OPTIONS to `localhost:5060` returns `200 OK` when the proxy is operational.
- Three consecutive failed probes (no response or non-200) mark the container unhealthy.
- Health probe failures do not trigger backend routing or authentication.

---

## Success Criteria

| ID | Criterion | Measurement | Target |
|---|---|---|---|
| SC-001 | Image build reproducibility | Time to build from clean checkout | Under 5 minutes on standard CI runners |
| SC-002 | Configuration validation coverage | Percentage of startup attempts blocked by syntax errors | 100% of invalid configs rejected before runtime |
| SC-003 | Network isolation compliance | Number of backend services with host-published ports | Zero |
| SC-004 | Authentication enforcement | Percentage of unauthenticated non-OPTIONS requests challenged | 100% |
| SC-005 | Secret safety | Number of committed files containing plaintext secrets | Zero |
| SC-006 | Stack startup time | Time from compose up to ready state | Under 60 seconds on standard hardware |
| SC-007 | Concurrent SIP sessions | Max active dialog count per instance | 1000 concurrent sessions |
| SC-008 | Response latency | Median response time for authenticated INVITE | <50ms on standard hardware |
| SC-009 | Registration throughput | Successful REGISTER requests per second | 50 registrations/sec |

---

## Key Entities

### Entity: Subscriber
- **Attributes**: username, domain, HA1 hash credentials, tenant association, routing group, enabled flag
- **Relationships**: Belongs to one Tenant

### Entity: Tenant
- **Attributes**: name, SIP domain, enabled flag
- **Relationships**: Has many Subscribers, many Header Routing Rules, many PBX Backends

### Entity: Header Routing Rule
- **Attributes**: tenant, header name, match value, match type, dispatcher set identifier, priority, enabled flag
- **Relationships**: Belongs to one Tenant

### Entity: PBX Backend
- **Attributes**: tenant, dispatcher set identifier, label, enabled flag
- **Relationships**: Belongs to one Tenant; maps to dispatcher set entries

---

## Scope

### In Scope
- OpenSIPS Dockerfile and entrypoint
- OpenSIPS configuration template with canonical modules and route blocks
- PostgreSQL service definition with initialization scripts
- Docker Compose topology with three isolated networks
- Runtime secret management structure (template and documentation)
- Configuration syntax validation at build and startup time

### Out of Scope
- RTPengine media relay runtime logic and kernel integration (container stub exists in Compose)
- Asterisk backend runtime configuration and service logic (container stubs exist in Compose)
- Advanced routing logic such as prefix-based or LCR routing
- WebRTC/ICE profiles
- Monitoring, logging aggregation, and observability pipelines
- TLS/DTLS/SRTP encryption profiles
- Load testing and performance benchmarking infrastructure

---

## Dependencies

- Container runtime (Docker Engine or compatible) and compose tooling available on the host.
- OpenSIPS 3.6 LTS source build from official GitHub repository (branch `3.6`).
- PostgreSQL 16 image available from the canonical registry.

---

## Assumptions

- The platform operator has local access to create files under a secrets directory that is excluded from version control.
- The host environment has sufficient privileges to bind UDP/TCP port 5060.
- The target deployment uses IPv4 Docker bridge networking.
- Initial subscriber data and tenant metadata will be populated through database seeding or migration scripts, not through a self-service UI in this feature.
- The foundation runs as a single OpenSIPS instance per deployment; multi-instance horizontal scaling and HA are deferred to a future feature.

---

## Risks

| Risk | Impact | Likelihood | Mitigation |
|---|---|---|---|
| OpenSIPS 3.6 package availability changes | High | Low | Pin base image digest; validate package names in CI |
| Host port 5060 already bound | Medium | Medium | Document port conflict resolution; support override via environment |
| Docker network conflicts on common subnets | Low | Medium | Use explicit subnet definitions in Compose if collisions occur |

---

## Notes

- This specification is derived from docs/TSiSIP-CANONICAL-SPEC.md Section 20 (Canonical implementation sequence, items 1–5 and 8).
- The Dockerfile builds OpenSIPS from source (GitHub `3.6` branch) rather than using APT packages. This decision was made after discovering that APT packages caused config validation failures due to empty transport protocol module activation.
- `proto_udp` and `proto_tcp` modules are compiled into the core but require explicit `loadmodule` directives in the configuration to register transport protocols.
- The `version` table is required by `db_postgres` for schema compatibility checks and was added to the stock schema during implementation.
- All PostgreSQL DDL uses db_postgres-compatible types and naming conventions.

---

## Active Issues and Blockers

| Issue | Status | Description | Resolution Path |
|---|---|---|---|
| T4.4/T4.5 Runtime Validation | 🔴 Blocked | Full stack `docker compose up` cannot be tested because `rtpengine` service build fails. SIP validation (OPTIONS, INVITE challenge) is gated on implementing a lightweight RTPengine container first. Debian `rtpengine-daemon` package pulls 146+ dependencies (~229 MB), causing Docker build timeouts. | Replace Debian-packaged RTPengine with a purpose-built lightweight container or stub; validate SIP signaling only after media relay container is functional. |
| Asterisk Container Build | 🟡 Untested | `docker/asterisk/Dockerfile` exists but was not validated. It may require source-build treatment similar to OpenSIPS. | Validate and switch to source build if APT packages are insufficient. |
