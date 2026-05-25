# Blueprint — SIP Trunk Provider Integration

## Overview

Enable TSiSIP to register with, authenticate to, and relay calls through external SIP trunk providers using OpenSIPS 3.6 LTS modules (`uac_registrant`, `uac_auth`, `uac`, `drouting`), PostgreSQL-backed trunk configuration, per-trunk health probes, rate limiting, and encryption profiles — coexisting with the existing tenant-to-Asterisk routing model.

## Requirements

- **FR-017-001**: SIP Trunk Provider Schema — `sip_trunk_providers`, `sip_trunk_did_mappings`, `sip_trunk_registrations` tables with pgcrypto-encrypted credentials.
- **FR-017-002**: Outbound Trunk Routing — non-local domain detection, priority-based trunk selection, CPS rate limiting (`rl_check`), R-URI rewriting, caller ID prefix, From domain override, failover on 408|500|502|503|504.
- **FR-017-003**: Trunk Registration via `uac_registrant` — outbound REGISTER for providers with `registration_required=true`; tracks state.
- **FR-017-004**: Inbound DID Routing — trusted trunk IP bypass, DID-to-tenant lookup, `X-Tenant-ID` header, `topology_hiding("C")`.
- **FR-017-005**: Trunk Healthcheck — OPTIONS probes every 30s; 3 consecutive misses marks unhealthy; dispatcher setid 100.
- **FR-017-006**: Per-Trunk Rate Limiting and QoS Metrics — CPS via `rl_check`, concurrent call counters, Prometheus metrics.
- **FR-017-007**: Trunk Encryption Profiles — transport (`udp`/`tcp`/`tls`) and SRTP mode (`none`/`sdes`/`dtls`) with correct RTPengine flags.
- **FR-017-008**: OCP Admin Page — CRUD for trunk providers and DID mappings; password encryption via pgcrypto + Docker secret.

## Architecture

- **Modules**: `uac_registrant`, `uac_auth`, `uac`, `dispatcher`, `ratelimit`, `cachedb_local`, `acc`.
- **Schema**: `sip_trunk_providers`, `sip_trunk_did_mappings`, `sip_trunk_registrations`.
- **Outbound**: `route[TRUNK_ROUTING]` → SQL priority selection → `rl_check` → R-URI rewrite → `failure_route[TRUNK_FAILOVER]`.
- **Inbound**: `route[INBOUND_DID_ROUTING]` → `check_source_address(3)` → DID lookup → `ds_select_dst` → `HANDLE_INVITE`.
- **Health**: Dispatcher setid 100 with OPTIONS probing; `cachedb_local` stores `trunk_health_<id>`.
- **Metrics**: `tsisip_trunk_calls_total`, `tsisip_trunk_calls_active`, `tsisip_trunk_cps_throttled_total`, `tsisip_trunk_pdd_ms`, `tsisip_trunk_probe_latency_ms`, `tsisip_trunk_probe_failures_total`.
- **Encryption**: pgcrypto `encrypt()`/`decrypt()` with Docker secret `trunk_cred_key`.

## Implementation Plan

### Wave 1: Database Schema
- Create `db/init/04-trunk-schema.sql`.
- Update seed data with sample trunk and DID mapping.
- Add Docker secret for credential encryption.

### Wave 2: OpenSIPS Module Configuration
- Conditionally load `uac_registrant`, `uac_auth`, `ratelimit`.
- Configure `uac_registrant` modparams.
- Configure dispatcher setid 100 for trunk health probes.
- Update `docker/entrypoint.sh` for trunk secret.

### Wave 3: Outbound Routing Logic
- Implement `route[TRUNK_ROUTING]`.
- Implement `failure_route[TRUNK_FAILOVER]`.
- Add per-trunk CPS rate limiting.
- Add SRTP branch route.
- Enrich CDR with trunk metadata.

### Wave 4: Inbound DID Routing
- Implement `route[INBOUND_DID_ROUTING]`.
- Wire into main route after `TRUNK_VERIFY`.
- Ensure topology hiding and error responses.

### Wave 5: Health Monitoring
- Populate dispatcher trunk probe destinations.
- `event_route[E_DISPATCHER_STATUS]` to `cachedb_local`.
- Filter unhealthy trunks from selection.
- Add Prometheus trunk metrics and Grafana dashboard.

### Wave 6: OCP Admin Pages
- Add trunk nav entries to `role-nav.php`.
- Create `web/trunk_providers.php` (CRUD, password encryption).
- Create `web/trunk_dids.php` (DID mapping CRUD).
- Create `web/trunk_status.php` (registration, health, probe trigger).
- Audit logging for all trunk mutations.

### Wave 7: Testing & Validation
- Outbound trunk call test.
- Trunk failover test.
- Inbound DID routing test.
- CPS rate limit test.
- Health probe exclusion test.
- CI validation; update runbook.

## Tasks

**Wave 1: Database Schema**
- T1.1: Create trunk provider schema file
- T1.2: Seed dev trunk data
- T1.3: Add Docker secret for credential encryption
- T1.4: Create secret placeholder and env documentation
- T1.5: Validate schema idempotency

**Wave 2: OpenSIPS Module Configuration**
- T2.1: Load `uac` modules conditionally
- T2.2: Configure `uac_registrant` modparams
- T2.3: Configure dispatcher trunk probe set
- T2.4: Update entrypoint for trunk secret
- T2.5: Validate OpenSIPS config syntax

**Wave 3: Outbound Routing Logic**
- T3.1: Implement `TRUNK_ROUTING`
- T3.2: Implement `TRUNK_FAILOVER`
- T3.3: Implement per-trunk CPS rate limiting
- T3.4: Add SRTP branch route
- T3.5: Enrich CDR with trunk metadata
- T3.6: Wire `TRUNK_ROUTING` into main route

**Wave 4: Inbound DID Routing**
- T4.1: Implement `INBOUND_DID_ROUTING`
- T4.2: Wire into main route
- T4.3: Add topology hiding
- T4.4: Handle unknown DID responses

**Wave 5: Health Monitoring**
- T5.1: Populate dispatcher trunk probe destinations
- T5.2: Implement dispatcher health event handler
- T5.3: Filter unhealthy trunks from selection
- T5.4: Add Prometheus trunk metrics
- T5.5: Create Grafana trunk dashboard

**Wave 6: OCP Admin Pages**
- T6.1: Add trunk pages to OCP navigation
- T6.2: Create trunk provider CRUD page
- T6.3: Create DID mapping CRUD page
- T6.4: Create trunk status page
- T6.5: Add trunk audit logging
- T6.6: Update OCP container for secret access

**Wave 7: Testing & Validation**
- T7.1: Outbound trunk call test
- T7.2: Trunk failover test
- T7.3: Inbound DID routing test
- T7.4: CPS rate limit test
- T7.5: Health probe exclusion test
- T7.6: CI validation run
- T7.7: Update operator runbook

## Validation

- `psql -c "\dt sip_trunk_*"` returns all three tables with correct columns and constraints.
- `opensips -c` loads without error.
- Synthetic INVITE to `+1234@pstn` routes through highest-priority trunk.
- Blocking primary trunk causes failover to secondary within 5 seconds.
- Unauthenticated INVITE from trunk IP with known DID routes to correct tenant backend with `X-Tenant-ID`.
- Burst INVITEs at 2x CPS receive `503 Service Unavailable` with ≤1% overshoot.
- Unresponsive trunk excluded after 3 missed OPTIONS probes; re-included within 30s of recovery.
- `docker compose config`, `opensips -c`, and `scripts/ci-scan.sh` pass.

## Risks & Dependencies

| Risk | Mitigation |
|---|---|
| Trunk provider changes IP or port without notice | Health probes detect quickly; OCP allows rapid update |
| Credential decryption key rotation invalidates stored passwords | Version encrypted payloads; re-provision after rotation |
| Inbound DID overlap between providers | UNIQUE constraint on `(did_number, trunk_provider_id)` |
| `uac_registrant` incompatibility with provider REGISTER quirks | Support provider-specific registrar/proxy fields; sandbox test |
| Trunk failover during active dialog causes media disruption | Failover only on initial INVITE; in-dialog follows Record-Route |
| Rate limit false positives during legitimate burst | Allow per-tenant CPS overrides; monitor and alert |

**Dependencies**: OpenSIPS 3.6 LTS (`uac`, `uac_auth`, `uac_registrant`, `dispatcher`, `ratelimit`); PostgreSQL 16+ with pgcrypto; RTPengine; Feature 003 (Observability); Feature 007 (TLS); OCP Foundation (Auth, RBAC, Audit).
