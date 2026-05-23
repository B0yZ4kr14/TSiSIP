# Feature 001 Memory: TSiSIP SIP Edge Foundation

## Current Scope
Build the foundational OpenSIPS 3.6 LTS container image with PostgreSQL-backed subscriber authentication, three isolated Docker networks, and canonical routing logic that authenticates all non-OPTIONS requests before backend selection.

## Relevant Decisions
- **Source build over APT**: OpenSIPS is compiled from the official GitHub 3.6 branch because APT packages caused config validation failures due to empty transport protocol module activation.
- **envsubst configuration rendering**: The entrypoint uses envsubst to render /etc/opensips/opensips.cfg.tpl at startup from runtime-supplied values and Docker secrets.
- **Fail-fast DB startup**: No retry loop when PostgreSQL is unavailable at startup; the orchestrator/operator handles restart policy.
- **Single instance per deployment**: Multi-instance horizontal scaling and HA are explicitly deferred to a future feature.
- **Precomputed HA1 only**: calculate_ha1 = 0; OpenSIPS reads ha1, ha1_sha256, ha1_sha512t256 columns only.

## Active Architecture Constraints
- Docker-first delivery: project-owned container image, never bare-metal or VM-first.
- PostgreSQL-only: db_postgres is the only OpenSIPS database module.
- Network isolation: sip_edge (public), sip_internal (private), db_internal (private, internal: true).
- Only OpenSIPS publishes host ports (5060/udp, 5060/tcp, 5061/tcp); Asterisk and PostgreSQL have zero host-published ports.
- topology_hiding("C") as canonical baseline.
- Explicit rtpengine_offer() / rtpengine_answer() / rtpengine_delete() — not rtpengine_manage().

## Accepted Deviations
- RTPengine and Asterisk container stubs exist in Compose but their full runtime logic belongs to subsequent features (003–007).
- Auth response code contract uses www_authorize()/www_challenge() returning 401 for all methods; migration to proxy_authorize()/proxy_challenge() (407 for non-REGISTER) was documented as a deferred decision.
- RTP port ranges are not published in Docker Compose due to Docker 29.5.0 memory bloat; handled via host networking or external orchestration.

## Relevant Security Constraints
- Secrets injected via Docker Compose secrets: stanza into /run/secrets/; entrypoint reads with awk (not tr) to avoid busybox quirks.
- Missing secrets cause clear startup failure, not silent unsafe defaults.
- .gitignore excludes ./secrets/ and all .env* except .env.example.
- OpenSIPS container drops all capabilities except NET_BIND_SERVICE, SETUID, SETGID.
- security_opt: ["no-new-privileges:true"].

## Related Historical Lessons
- The version table is required by db_postgres for schema compatibility checks and was added to the stock schema during implementation.
- proto_udp and proto_tcp are compiled into the core but require explicit loadmodule directives to register transport protocols.
- The permissions module and address table enable IP-based trusted gateway bypass (FR-001-008).
- Auth audit logging (auth_audit_log) with 90-day retention was added as a foundation security requirement.

## Conflict Warnings
None currently.

## Retrieval Notes
- Search terms: edge proxy, OpenSIPS Dockerfile, db_postgres, sip_edge, entrypoint, envsubst, HA1, auth_audit_log, trusted gateway.
- Related features: 004 (health checks), 006 (rate limiting), 007 (TLS/SRTP).
