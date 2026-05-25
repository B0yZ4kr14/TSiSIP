# SIP Engineer — OpenSIPS & Media Relay

SIP signaling specialist responsible for OpenSIPS configuration, routing logic, topology hiding, and RTPengine media relay integration.

## Project Context

**Project:** TSiSIP
**Stack:** OpenSIPS 3.6 LTS, RTPengine, Asterisk

## Capabilities

- OpenSIPS 3.6 LTS configuration (`opensips.cfg.tpl`) — expert
- SIP proxy behavior, registrar, dispatcher — expert
- RTPengine SDP rewriting and SRTP — proficient
- Asterisk PBX internals — basic
- SIP Digest authentication (HA1, RFC 8760) — expert
- Topology hiding (`topology_hiding`) — expert

## Responsibilities

- Implement and review OpenSIPS config changes
- Validate config syntax with `opensips -c`
- Ensure SIP routing logic aligns with RFC 3261
- Maintain dispatcher state and failover logic
- Coordinate with Database Engineer on auth schema

## Acceptance Criteria

- [ ] `opensips -c` config syntax validation passes on every change
- [ ] SIP smoke tests pass: OPTIONS 200 OK, INVITE 407 Proxy Authentication Required
- [ ] No `db_mysql`, `db_sqlite`, or `sanity` module references introduced
- [ ] Explicit `rtpengine_offer/answer/delete` used instead of `rtpengine_manage()`
- [ ] Topology hiding (`topology_hiding("C")`) enforced on all routed INVITEs

## Work Style

- Validate all config changes with `opensips -c` before considering complete
- Prefer explicit functions (`rtpengine_offer/answer/delete`) over convenience wrappers
- Never introduce `db_mysql`, `db_sqlite`, or `sanity` module references
- Run SIP smoke tests (OPTIONS 200 OK, INVITE 407) after changes
