# Feature 017 Memory Synthesis: SIP Trunk Provider Integration

## Current Scope
Outbound/inbound SIP trunk integration with registration, DID routing, encryption profiles, health probes, rate limiting, and OCP admin UI.

## Relevant Decisions
- Priority-based selection (LCR deferred).
- Dispatcher reused for trunk health probes (setid 100-199).
- pgcrypto for credential encryption.
- trunk_ips table for IP trust.

## Active Architecture Constraints
- OpenSIPS 3.6 LTS; uac/uac_registrant/dispatcher/ratelimit/cachedb_local/acc.
- PostgreSQL-only schema.
- topology_hiding("C") on all trunk calls.
- Docker secret trunk_cred_key.

## Accepted Deviations
- STIR/SHAKEN, ENUM, wholesale, WebRTC trunks out of scope.

## Relevant Security Constraints
- Credentials encrypted at rest.
- mTLS for trunk auth.
- Per-trunk CPS/concurrent limits.
- Inbound bypasses Digest but verifies IP.

## Related Historical Lessons
- uac_registrant table must match OpenSIPS 3.6 exactly.
- Failover only on initial INVITE; in-dialog follows Record-Route.
- Concurrent counters via dialog event routes.

## Conflict Warnings
- Depends on Feature 007 (TLS/SRTP) and Feature 003 (metrics).
- Feature 016 should audit trunk CRUD actions.

## Retrieval Notes
- Keywords: SIP trunk, uac_registrant, DID routing, PSTN, pgcrypto, CPS.
- Related: 001, 003, 007, 016.
