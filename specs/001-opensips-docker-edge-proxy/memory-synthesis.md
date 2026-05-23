# Feature 001 Memory Synthesis: TSiSIP SIP Edge Foundation

## Current Scope
Foundational OpenSIPS 3.6 LTS container with PostgreSQL auth, isolated Docker networks, and canonical routing.

## Relevant Decisions
- Source build from GitHub (APT caused transport module issues).
- envsubst config rendering at container startup.
- Fail-fast DB startup (no retry loop).
- Single instance per deployment; HA deferred.
- Precomputed HA1 hashes only (calculate_ha1 = 0).

## Active Architecture Constraints
- Docker-first, PostgreSQL-only (db_postgres).
- Three isolated networks: sip_edge, sip_internal, db_internal.
- Only OpenSIPS publishes ports; Asterisk/PostgreSQL have none.
- topology_hiding("C"); explicit rtpengine offer/answer/delete.

## Accepted Deviations
- RTPengine/Asterisk stubs exist; full runtime logic in later features.
- Auth returns 401 for all methods; 407 migration deferred.
- RTP port ranges not published in Compose (memory bloat).

## Relevant Security Constraints
- Docker secrets via /run/secrets/; missing secrets = startup failure.
- Capabilities dropped except NET_BIND_SERVICE, SETUID, SETGID.
- no-new-privileges:true.

## Related Historical Lessons
- version table required for db_postgres compatibility.
- proto_udp/proto_tcp need explicit loadmodule.
- permissions module enables trusted gateway bypass.

## Conflict Warnings
None.

## Retrieval Notes
- Keywords: edge proxy, OpenSIPS Dockerfile, db_postgres, sip_edge, entrypoint, HA1, auth_audit_log.
- Related: 004, 006, 007.
