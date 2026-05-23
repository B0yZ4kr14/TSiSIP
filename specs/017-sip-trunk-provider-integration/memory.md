# Feature 017 Memory: SIP Trunk Provider Integration

## Current Scope
Provider-agnostic SIP trunk integration for outbound PSTN routing (priority-based failover), trunk registration via uac_registrant, inbound DID-to-tenant mapping, per-trunk encryption profiles, health probes, CPS rate limiting, QoS metrics, and OCP admin CRUD pages. Status: Draft (tasks marked complete, spec says Draft).

## Relevant Decisions
- **Priority-based trunk selection**: Simple ORDER BY priority ASC list; prefix-based LCR deferred to future drouting enhancement.
- **Dispatcher reused for trunk health probes**: Trunk destinations loaded into dedicated dispatcher set range (e.g., 100-199) to avoid custom probe timers.
- **pgcrypto for credential encryption**: auth_password_encrypted stored as BYTEA; decryption key via Docker secret trunk_cred_key.
- **trunk_ips table for IP trust**: Reuses check_source_address(3) for inbound DID routing and mTLS decisions.

## Active Architecture Constraints
- OpenSIPS 3.6 LTS only; modules: uac, uac_auth, uac_registrant, dispatcher, ratelimit, cachedb_local, acc.
- PostgreSQL-only; schema in db/init/04-trunk-schema.sql.
- All trunk-routed calls must pass topology_hiding("C").
- CDR enrichment via acc module with trunk_provider_id, trunk_name, direction AVPs.
- Docker secret trunk_cred_key mounted into OpenSIPS and OCP containers.

## Accepted Deviations
- STIR/SHAKEN attestation deferred.
- ENUM/DNS-based carrier selection out of scope.
- Wholesale trunk reseller management out of scope.
- WebRTC trunk interfaces out of scope.

## Relevant Security Constraints
- Trunk credentials encrypted at rest (pgcrypto + Docker secret).
- Plaintext passwords never appear in logs, UI, or database dumps.
- mTLS for trunk authentication (Feature 007).
- Per-trunk CPS and concurrent call limits enforced.
- Inbound trunk traffic bypasses Digest auth but is IP-verified.

## Related Historical Lessons
- uac_registrant table format must match OpenSIPS 3.6 module expectations exactly.
- Dispatcher module reuse for health probes eliminates custom timer complexity.
- Failover only on initial INVITE transaction; in-dialog re-INVITEs follow existing Record-Route.
- Concurrent call counters incremented on INVITE, decremented on BYE/failure via dialog event routes.

## Conflict Warnings
- Depends on Feature 007 (TLS/SRTP) for encrypted trunk transport profiles.
- Depends on Feature 003 (Observability) for trunk QoS metrics sink.
- Feature 016 (Audit Log) should instrument trunk CRUD actions in OCP admin pages.

## Retrieval Notes
- Search terms: SIP trunk, uac_registrant, DID routing, PSTN, LCR, pgcrypto, trunk health probe, CPS rate limit.
- Related features: 001 (OpenSIPS foundation), 003 (metrics), 007 (TLS/SRTP), 016 (audit logging).
