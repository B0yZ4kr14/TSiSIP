# Feature 007 Proposal: Asterisk PBX Backend Integration & End-to-End SIP Flow

## Context

Feature 006 (Rate Limiting & DDoS) is complete. The OpenSIPS edge proxy has:
- Authentication against PostgreSQL (`subscriber` table with HA1 hashes)
- Dispatcher module configured but **empty** (`WARNING: no dispatching data in the db`)
- Header-based routing skeleton (`HEADER_ROUTING` route)
- Topology hiding and RTPengine media relay
- Rate limiting (pike, ratelimit, userblacklist)

## Goal

Complete the SIP signaling pipeline by integrating Asterisk PBX backends:
1. Populate `dispatcher` table with Asterisk backend entries
2. Configure Asterisk containers for SIP trunking to OpenSIPS
3. Test end-to-end flow: REGISTER → 401 → REGISTER(auth) → 200 → INVITE → ROUTE → Asterisk
4. Add integration tests for the complete call flow
5. Update operator runbook with backend procedures

## Scope

### In Scope
- PostgreSQL seed data for `dispatcher` (Asterisk pbx-1, pbx-2)
- Asterisk `pjsip.conf` / `sip.conf` for trunk registration to OpenSIPS
- Asterisk dialplan for routing inbound calls
- OpenSIPS config validation for backend relay
- Integration tests: `test_end_to_end_call.py`
- Grafana dashboard panel for dispatcher state

### Out of Scope
- Advanced Asterisk features (queues, IVR, voicemail)
- WebRTC/WebSocket (Feature 008 candidate)
- CDR/billing (Feature 009 candidate)

## Success Criteria

| ID | Criterion | Verification |
|----|-----------|--------------|
| SC-1 | Dispatcher table has 2+ Asterisk backends | `SELECT * FROM dispatcher` returns entries |
| SC-2 | OpenSIPS routes INVITE to Asterisk after auth | Integration test passes |
| SC-3 | Asterisk receives call from OpenSIPS with correct headers | Packet capture or log verification |
| SC-4 | Topology hiding hides Asterisk IPs from external clients | Verify no backend IP in SIP responses |
| SC-5 | Failover works when pbx-1 is down | Stop pbx-1, call routes to pbx-2 |

## Architecture

```
SIP Client          OpenSIPS            PostgreSQL       Asterisk-pbx-1    Asterisk-pbx-2
    |                  |                      |                  |                |
    |--REGISTER------->|                      |                  |                |
    |<--401----------- |                      |                  |                |
    |--REGISTER(auth)->|                      |                  |                |
    |                  |--query subscriber--->|                  |                |
    |                  |<--HA1 match---------|                  |                |
    |<--200 OK---------|                      |                  |                |
    |--INVITE--------->|                      |                  |                |
    |                  |--query dispatcher--->|                  |                |
    |                  |<--pbx-1, pbx-2------|                  |                |
    |                  |--INVITE------------>|------------------>|                |
    |                  |<--100/180/200-------|<------------------|                |
    |<--100/180/200----|                      |                  |                |
```

## Tasks (Preliminary)

1. **Schema & Seed**: Add `dispatcher` entries to `03-seed-data.sql`
2. **Asterisk Config**: Create `docker/asterisk/pjsip.conf` and `extensions.conf`
3. **OpenSIPS Validation**: Ensure `ds_select_dst` works with populated table
4. **Integration Tests**: `test_end_to_end_call.py` with SIPp or Python raw sockets
5. **Grafana**: Add dispatcher state panel to existing dashboard
6. **Runbook**: Update `docs/TSiSIP-OPERATOR-RUNBOOK.md`

## Risk

- **Medium**: Asterisk PJSIP configuration complexity
- **Low**: Docker 29.5.0 instability may affect multi-container test runs
