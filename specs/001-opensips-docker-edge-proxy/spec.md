# Feature Specification: TSiSIP SIP Edge Foundation

## Overview

**Feature**: TSiSIP SIP Edge Foundation
**Short name**: opensips-docker-edge-proxy
**Created**: 2026-05-16
**Status**: Completed
**Last Updated**: 2026-05-19

### Context

TSiSIP is a Docker-image-first SIP edge-proxy platform. Before any SIP traffic can be processed, authenticated, or routed, a foundational containerized runtime must exist. This feature establishes the first deployable unit: the TSiSIP SIP edge service, implemented with OpenSIPS 3.6 LTS and delivered as a project-owned container image, backed by a relational database for subscriber credentials and routing metadata, orchestrated through container-native networking and composition.

This is the foundational layer upon which all subsequent TSiSIP capabilities—multi-tenant routing, media relay integration, backend PBX isolation, and security hardening—are built.

### Objective

Enable a platform operator to build, configure, and start the containerized TSiSIP SIP edge service with:
- A validated, reproducible container image owned by the project.
- A private PostgreSQL database for subscriber authentication and routing metadata.
- Isolated Docker networks separating public signaling, internal forwarding, and database access.
- A configuration template that enforces edge authentication before any backend route selection.
- Syntax-validated configuration before runtime startup.

---

## Clarifications

### Session 2026-05-16

- Q: What runtime performance/throughput targets should the TSiSIP SIP edge foundation meet? → A: 100 concurrent SIP sessions, <50ms median response latency, 50 registrations/sec (foundation baseline; detailed benchmarking deferred to future performance feature).
- Q: How should health/ready checks be performed for the OpenSIPS container? → A: SIP OPTIONS to localhost:5060 expecting 200 OK; failure after 3 consecutive missed responses.
- Q: What retry semantics should apply when PostgreSQL is unavailable at OpenSIPS startup? → A: Fail-fast immediately with descriptive exit status (no retry loop); orchestrator/operator is responsible for restart policy.
- Q: Should secret rotation strategy be defined in this foundation spec? → A: Defer to future operational documentation; no secret rotation requirements in foundation spec.
- Q: Is multi-instance horizontal scaling supported in the foundation? → A: Single instance per deployment; multi-instance/HA deferred to future feature requiring shared dialog state or session affinity.
- Q: What fallback behavior should apply when RTPengine is unavailable during INVITE processing? → A: Foundation validation requires RTPengine reachability for SIP runtime tests; graceful runtime fallback is specified in Feature 004 as `488 Not Acceptable Here` for calls requiring relay.
- Q: What functional requirement justifies the presence of the permissions module in the OpenSIPS configuration? → A: FR-001-008 defines IP-based trusted gateway bypass using the permissions module and OpenSIPS address table.
- Q: What behavior should apply when concurrent SIP sessions exceed the foundation limit? → A: Limit raised to 1000 concurrent sessions; overload degradation behavior deferred to future performance feature.
- Q: What logging requirements should apply to the auth_audit_log table? → A: FR-001-009 requires all authentication events (success, failure, challenge) to be persisted in auth_audit_log with minimum 90-day retention.
- Q: Should the health check mechanism be classified as a formal functional requirement? → A: Promoted to FR-001-010 "Container health probe via SIP OPTIONS".

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
- **Then** the TSiSIP SIP edge service listens on the public SIP signaling ports
- **And** PostgreSQL is reachable only on an internal database network
- **And** no backend service is exposed to the host network

#### Scenario 3: SIP client attempts unauthenticated access
- **Given** the edge proxy is running and a SIP client sends an INVITE without credentials
- **When** the request reaches the proxy
- **Then** the proxy responds with an authentication challenge and does not forward the request to any backend

### Edge Cases & Error Conditions

- Invalid or missing runtime secrets prevent OpenSIPS from starting rather than falling back to unsafe defaults.
- Malformed SIP messages exceeding 4096 bytes (RFC 3261 recommended max) are rejected at the edge before processing.
- SIP loop detection returns `483 Too Many Hops` when Max-Forwards is exhausted.
- Database unavailability at startup triggers immediate fail-fast with descriptive exit status (no retry loop).
- Runtime database unavailability is covered by Feature 004 graceful degradation with `480 Temporarily Unavailable` for registration-dependent requests.
- RTPengine unavailability is covered by Feature 004 graceful degradation with `488 Not Acceptable Here` for calls requiring media relay.
- Container health checks use SIP OPTIONS to `localhost:5060` and expect `200 OK`; canonical timing is interval 15s, timeout 5s, retries 3, start period 30s.

---

## Functional Requirements

### FR-001-001: Project-owned container image for OpenSIPS
**Description**: The TSiSIP SIP edge service must be built from a committed project Dockerfile, producing a reproducible container image.
**Acceptance Criteria**:
- Image build completes without external image substitution for the core proxy.
- Image contains OpenSIPS 3.6 LTS and required database connectivity modules.
- Image includes a templated configuration file rendered at startup from runtime-supplied values.

### FR-001-002: Runtime secret injection
**Description**: Secrets such as database credentials, authentication secrets, and topology hiding keys must be injected at runtime without being committed to source control.
**Acceptance Criteria**:
- Configuration template references secrets through environment variables or container secret mounts.
- Secrets are never present in committed source files.
- Missing secrets cause a clear startup failure rather than silent unsafe defaults.

### FR-001-002A: Digest credential hash policy
**Description**: SIP Digest credentials must use precomputed HA1-compatible hashes. Plaintext subscriber passwords are not operational credentials.
**Acceptance Criteria**:
- `ha1` is the mandatory compatibility baseline for legacy MD5 SIP Digest clients.
- `ha1_sha256` and `ha1_sha512t256` are populated by provisioning workflows when stronger digest algorithms are supported by the endpoint population.
- `calculate_ha1` remains disabled; OpenSIPS reads precomputed hashes only.
- The stock `subscriber.password` column remains empty or non-authoritative and is never used as a plaintext credential source.

### FR-001-003: Private PostgreSQL persistence
**Description**: A PostgreSQL database service must be available exclusively on an internal Docker network for subscriber credentials, tenant metadata, and routing rules.
**Acceptance Criteria**:
- PostgreSQL service has no host-published ports.
- OpenSIPS can connect to PostgreSQL using a standard DSN.
- Database initialization scripts create the required stock tables and project-specific extensions.

### FR-001-004: Network isolation by function
**Description**: Docker networks must enforce separation between public signaling, internal SIP forwarding, and database access.
**Acceptance Criteria**:
- Public SIP ingress is confined to a dedicated network attaching only to edge-facing services.
- Internal SIP forwarding occurs on a separate network that does not allow external host access.
- Database traffic is restricted to a dedicated internal network.

### FR-001-005: Edge authentication enforcement
**Description**: Every SIP request from untrusted sources—except health-check OPTIONS—must be challenged for Digest authentication before any backend routing decision.
**Acceptance Criteria**:
- Unauthenticated REGISTER, INVITE, and other authenticated requests receive a `401 Unauthorized` digest challenge from `www_challenge`.
- OPTIONS requests are answered locally without backend routing and without exposing topology.

### FR-001-006: Configuration syntax validation
**Description**: The OpenSIPS configuration must be validated for syntax correctness inside the built image before the proxy process starts.
**Acceptance Criteria**:
- A syntax check command runs successfully inside the container.
- Syntax errors prevent container startup with a descriptive exit status.

### FR-001-007: Canonical routing logic skeleton
**Description**: The configuration must implement the canonical route flow: loop/size protection, in-dialog handling, CANCEL handling, unauthenticated OPTIONS, header sanitization, authentication, backend routing, dialog creation, topology hiding, and stateful relay.
**Acceptance Criteria**:
- Route blocks are named and sequenced according to the canonical contract.
- Authentication occurs before backend route resolution.
- Credentials are consumed before forwarding.
- Topology hiding uses mode `"C"` to conceal backend Contact/Record-Route/Via-derived routing details and prevent public clients from learning private PBX addresses.

### FR-001-008: Trusted gateway IP bypass
**Description**: The `permissions` module must allow authentication bypass for SIP requests originating from pre-configured trusted gateway IP addresses, using the OpenSIPS `address` table for IP-based whitelist lookups.
**Acceptance Criteria**:
- Requests from IPs listed in the `address` table bypass Digest authentication.
- Non-trusted IPs continue to receive 401/407 challenges per FR-001-005.
- The `address` table is initialized empty in the foundation; gateway IPs are populated via migration or operational scripts.

### FR-001-009: Authentication event audit logging
**Description**: All authentication attempts (success, failure, challenge) must be persisted to the `auth_audit_log` table for operational debugging and security audit. Records must be retained for a minimum of 90 days.
**Acceptance Criteria**:
- Every Digest challenge, successful authentication, and failed authentication attempt generates an `auth_audit_log` record.
- Records include event_time, username, domain, source_ip, sip_method, result, and call_id.
- The table design supports 90-day retention without performance degradation.

### FR-001-010: Container health probe via SIP OPTIONS
**Description**: The OpenSIPS container must expose a health probe mechanism using SIP OPTIONS requests to `localhost:5060`. A successful probe returns `200 OK`; three consecutive failures mark the container as unhealthy. Canonical container timing is interval 15s, timeout 5s, retries 3, start period 30s.
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
| SC-006 | Stack startup time | Time from compose up to ready state | Under 60 seconds on 2 vCPU / 4GB RAM baseline |
| SC-007 | Concurrent SIP sessions | Max active dialog count per instance | 1000 concurrent sessions |
| SC-008 | Response latency | Median response time for authenticated INVITE on the same 2 vCPU / 4GB RAM baseline | <50ms |
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
- SIP validation can be performed with a container-based SIP tool or a Python socket script; full load testing belongs to the performance validation track.

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
| Foundation performance targets exceed single-instance capacity | Medium | Medium | Treat SC-007-SC-009 as single-instance baseline targets and validate under the performance test track before production scale-up |
| Asterisk container config path drift | Low | Medium | Bind and image-copy configs to both `/etc/asterisk` and `/usr/local/etc/asterisk` because the source-built runtime reads `/usr/local/etc/asterisk` |

---

## Notes

- This specification is derived from docs/TSiSIP-CANONICAL-SPEC.md Section 22 (Canonical implementation sequence, items 1–5 and 8).
- The Dockerfile builds OpenSIPS from source (GitHub `3.6` branch) rather than using APT packages. This decision was made after discovering that APT packages caused config validation failures due to empty transport protocol module activation.
- `proto_udp` and `proto_tcp` modules are compiled into the core but require explicit `loadmodule` directives in the configuration to register transport protocols.
- The `version` table is required by `db_postgres` for schema compatibility checks and was added to the stock schema during implementation.
- All PostgreSQL DDL uses db_postgres-compatible types and naming conventions.

---

## Active Issues and Blockers

| Issue | Status | Description | Resolution Path |
|---|---|---|---|
| T4.4/T4.5 Runtime Validation | ✅ Resolved | RTPengine container now builds successfully using custom Dockerfile. SIP validation (OPTIONS, INVITE challenge) passes. Media relay integration validated in subsequent features (003-007). | — |
| Asterisk Container Build | ✅ Resolved | VPS production validation on 2026-05-19 started `asterisk-pbx-1` and `asterisk-pbx-2`, loaded PJSIP UDP/TCP transports, and routed an authenticated INVITE to extension `1000`. | Keep configs mounted at both Asterisk config paths. |
