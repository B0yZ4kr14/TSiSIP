# Feature 017: SIP Trunk Provider Integration

## Overview

| Field | Value |
|-------|-------|
| **Feature** | SIP Trunk Provider Integration |
| **Short name** | sip-trunk-provider-integration |
| **Created** | 2026-05-19 |
| **Status** | Draft |
| **Last Updated** | 2026-05-19 |
| **Context** | TSiSIP currently routes all calls internally to tenant-scoped Asterisk backends. There is no outbound PSTN/interconnect capability and no inbound DID routing from external carriers. This feature adds provider-agnostic SIP trunk integration for both inbound (DID) and outbound (LCR/failover) call flows while preserving the existing tenant-scoped Asterisk routing. |
| **Objective** | Enable TSiSIP to register with, authenticate to, and relay calls through external SIP trunk providers using OpenSIPS 3.6 LTS modules (uac_registrant, uac_auth, uac, drouting), PostgreSQL-backed trunk configuration, per-trunk health probes, rate limiting, and encryption profiles, all coexisting with the existing tenant-to-Asterisk routing model. |

## Goals

1. **Outbound PSTN Routing**: Route authenticated subscriber calls to external destinations (non-local domains) through prioritized SIP trunk providers with automatic failover to the next provider on 408|500|502|503|504.
2. **Trunk Registration**: Support trunk provider registration (if required by the provider) using OpenSIPS uac_registrant module with per-trunk credentials stored in PostgreSQL.
3. **Inbound DID Routing**: Map inbound calls from trunk provider peers to the correct tenant and Asterisk backend using DID-to-tenant mappings stored in PostgreSQL.
4. **Per-Trunk Encryption**: Allow each trunk provider to specify its own transport security level (none, tls, tls+srtp), enforced at the OpenSIPS socket and RTPengine level.
5. **Trunk Health Monitoring**: Send periodic SIP OPTIONS probes to each trunk provider, track response times and availability, and disable unhealthy trunks from active selection.
6. **Per-Trunk Rate Limiting**: Enforce configurable CPS (calls per second) and concurrent call limits per trunk provider using ratelimit or cachedb_local counters.
7. **QoS Metrics**: Emit per-trunk metrics (answer seizure ratio, post-dial delay, call duration, failure codes) compatible with the Feature 003 Prometheus/Grafana observability pipeline.
8. **Admin Configuration UI**: Provide an OCP admin page to CRUD trunk providers, DID mappings, view registration status, and trigger health probes.

## Non-Goals

- Wholesale trunk reseller management (multi-customer trunk sub-leasing).
- SS7 or ISDN gateway integration; this is SIP-only.
- Real-time billing or CDR mediation for provider calls (CDR records are emitted but not rated).
- WebRTC trunk interfaces; trunks are SIP over UDP/TCP/TLS only.
- Automatic trunk provider discovery or STIR/SHAKEN attestation (deferred to future feature).
- In-band DTMF-to-SIP INFO translation (handled by Asterisk or RTPengine, not OpenSIPS).

## Acceptance Criteria

- [ ] **AC1**: PostgreSQL schema includes sip_trunk_providers table with all required columns (id, name, host, port, transport, auth_username, auth_password_encrypted, from_domain, caller_id_prefix, priority, enabled, created_at) and a sip_trunk_did_mappings table for inbound DID routing.
- [ ] **AC2**: OpenSIPS configuration loads uac_registrant, uac_auth, and uac modules (when at least one trunk is configured) and initiates outbound REGISTER to trunk providers whose registration_required flag is true.
- [ ] **AC3**: An unauthenticated INVITE from a trunk provider IP (listed in trunk_ips) with a DID present in sip_trunk_did_mappings is routed to the correct tenant's Asterisk backend without requiring Digest auth, while preserving the X-Tenant-ID header.
- [ ] **AC4**: An authenticated internal subscriber INVITE to a non-local domain (e.g., +1234567890@pstn) triggers the trunk routing route, selects the highest-priority enabled trunk, and relays the INVITE. On failure (408|500|502|503|504), failover to the next provider succeeds within 5 seconds.
- [ ] **AC5**: Trunk provider with transport = tls enforces TLS on the outbound leg; tls+srtp additionally triggers RTP/SAVP offer via RTPengine. Plain udp or tcp uses unencrypted RTP.
- [ ] **AC6**: Per-trunk OPTIONS health probe runs every 30 seconds; a provider returning no response for 3 consecutive probes is marked disabled in cachedb_local and excluded from trunk selection until probes resume.
- [ ] **AC7**: Per-trunk CPS rate limit is enforced using rl_check with a trunk-specific pipe; exceeding the limit returns 503 Service Unavailable to the caller and does not consume trunk capacity.
- [ ] **AC8**: OCP admin page allows an authenticated admin or devops role user to add, edit, disable, and delete trunk providers and DID mappings; changes take effect without OpenSIPS restart (via cachedb reload or MI command).
- [ ] **AC9**: Trunk provider credentials (auth_password_encrypted) are encrypted at rest using pgcrypto symmetric encryption with a key injected via Docker secret; plaintext passwords never appear in logs, UI, or database dumps.
- [ ] **AC10**: All trunk-related CDR records include trunk_provider_id, trunk_name, and direction (inbound/outbound) columns; the acc module writes these fields for trunk-routed calls.

## Functional Requirements

### FR-001: SIP Trunk Provider Schema
**Description**: PostgreSQL stores trunk provider configuration and DID mappings.
**Schema**:
```sql
CREATE TABLE sip_trunk_providers (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(128) NOT NULL UNIQUE,
    host VARCHAR(255) NOT NULL,
    port INTEGER NOT NULL DEFAULT 5060 CHECK (port > 0 AND port <= 65535),
    transport VARCHAR(8) NOT NULL DEFAULT 'udp'
        CHECK (transport IN ('udp', 'tcp', 'tls')),
    auth_username VARCHAR(128),
    auth_password_encrypted BYTEA,
    from_domain VARCHAR(255),
    caller_id_prefix VARCHAR(32) DEFAULT '',
    priority INTEGER NOT NULL DEFAULT 100,
    enabled BOOLEAN NOT NULL DEFAULT TRUE,
    registration_required BOOLEAN NOT NULL DEFAULT FALSE,
    registration_expiry INTEGER NOT NULL DEFAULT 3600,
    max_cps INTEGER NOT NULL DEFAULT 10 CHECK (max_cps > 0),
    max_concurrent INTEGER NOT NULL DEFAULT 100 CHECK (max_concurrent > 0),
    require_mtls BOOLEAN NOT NULL DEFAULT FALSE,
    srtp_mode VARCHAR(16) NOT NULL DEFAULT 'none'
        CHECK (srtp_mode IN ('none', 'sdes', 'dtls')),
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_sip_trunk_providers_priority
    ON sip_trunk_providers(enabled, priority ASC);

CREATE TABLE sip_trunk_did_mappings (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    did_number VARCHAR(64) NOT NULL,
    tenant_id UUID NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
    trunk_provider_id UUID NOT NULL REFERENCES sip_trunk_providers(id) ON DELETE CASCADE,
    dispatcher_setid INTEGER NOT NULL,
    description VARCHAR(255),
    enabled BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE (did_number, trunk_provider_id)
);

CREATE INDEX idx_sip_trunk_did_lookup
    ON sip_trunk_did_mappings(did_number, enabled, tenant_id)
    WHERE enabled = true;
```
**Acceptance Criteria**:
- Schema initialization script is idempotent and placed in db/init/04-trunk-schema.sql.
- Foreign keys enforce referential integrity between DID mappings, tenants, and trunk providers.

### FR-002: Outbound Trunk Routing Logic
**Description**: OpenSIPS routes internal subscriber calls to non-local destinations via trunk providers with priority-based selection and failover.
**Route Logic**:
```cfg
route[TRUNK_ROUTING] {
    # Determine if destination is external (not a local tenant domain)
    if (!sql_query_one(
        "SELECT 1 FROM tenants WHERE sip_domain = '$td' AND enabled = true LIMIT 1",
        "$avp(is_local_domain)"
    )) {
        xlog("L_ERR", "TRUNK_ROUTING: DB unavailable during domain check\n");
        sl_send_reply(480, "Temporarily Unavailable");
        exit;
    }

    if ($avp(is_local_domain) != 0) {
        return; # Local domain; continue to HEADER_ROUTING -> Asterisk
    }

    # External destination: select highest-priority enabled trunk
    if (!sql_query_one(
        "SELECT id, name, host, port, transport, auth_username, from_domain, caller_id_prefix, srtp_mode "
        "FROM sip_trunk_providers WHERE enabled = true ORDER BY priority ASC LIMIT 1",
        "$avp(trunk_id)", "$avp(trunk_name)", "$avp(trunk_host)", "$avp(trunk_port)",
        "$avp(trunk_transport)", "$avp(trunk_user)", "$avp(trunk_from_domain)",
        "$avp(trunk_cid_prefix)", "$avp(trunk_srtp)"
    )) {
        sl_send_reply(503, "No Trunk Available");
        exit;
    }

    if ($avp(trunk_id) == NULL) {
        sl_send_reply(503, "No Trunk Available");
        exit;
    }

    # Per-trunk CPS rate limiting
    if (!rl_check("trunk_$avp(trunk_id)", $avp(trunk_cps), "TAILDROP")) {
        xlog("L_WARN", "Trunk $avp(trunk_name) CPS exceeded\n");
        sl_send_reply(503, "Trunk Capacity Exceeded");
        exit;
    }

    # Rewrite R-URI to trunk provider destination
    $ru = "sip:" + $rU + "@" + $avp(trunk_host) + ":" + $avp(trunk_port);
    if ($avp(trunk_transport) == "tls") {
        $ru = $ru + ";transport=tls";
    } else if ($avp(trunk_transport) == "tcp") {
        $ru = $ru + ";transport=tcp";
    }

    # Apply caller ID prefix if configured
    if ($avp(trunk_cid_prefix) != NULL && $avp(trunk_cid_prefix) != "") {
        $fU = $avp(trunk_cid_prefix) + $fU;
    }

    # Override From domain if configured
    if ($avp(trunk_from_domain) != NULL && $avp(trunk_from_domain) != "") {
        uac_replace_from("$fU", "sip:$fU@$avp(trunk_from_domain)");
    }

    # SRTP handling
    if ($avp(trunk_srtp) == "sdes") {
        t_on_branch("BRANCH_TRUNK_SRTP");
    }

    # Mark trunk for CDR and failure handling
    $avp(direction) = "outbound";
    t_on_failure("TRUNK_FAILOVER");
}
```
**Acceptance Criteria**:
- Calls to local tenant domains bypass trunk routing and follow existing HEADER_ROUTING logic.
- Calls to non-local domains route through the highest-priority enabled trunk.
- Failover retries the next priority trunk on transport/failure codes.

### FR-003: Trunk Registration via uac_registrant
**Description**: OpenSIPS registers to trunk providers that require it, using per-trunk credentials from PostgreSQL.
**Module Configuration**:
```cfg
loadmodule "uac.so"
loadmodule "uac_auth.so"
loadmodule "uac_registrant.so"

modparam("uac_registrant", "db_url", DB_URL)
modparam("uac_registrant", "table_name", "sip_trunk_registrations")
modparam("uac_registrant", "timer_interval", 60)
```
**Registration Table**:
```sql
CREATE TABLE sip_trunk_registrations (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    trunk_provider_id UUID NOT NULL REFERENCES sip_trunk_providers(id) ON DELETE CASCADE,
    registrar VARCHAR(255) NOT NULL,
    proxy VARCHAR(255),
    aor VARCHAR(255) NOT NULL,
    third_party_registrant VARCHAR(255),
    username VARCHAR(128) NOT NULL,
    password BYTEA,
    binding_uri VARCHAR(255) NOT NULL,
    expiry INTEGER NOT NULL DEFAULT 3600,
    forced_socket VARCHAR(128),
    cluster_shtag VARCHAR(128),
    state INTEGER NOT NULL DEFAULT 0,
    last_register_sent TIMESTAMPTZ,
    last_register_succ TIMESTAMPTZ,
    last_register_code INTEGER,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
```
**Acceptance Criteria**:
- Rows are auto-populated from sip_trunk_providers where registration_required = true via initialization trigger or OCP action.
- OpenSIPS uac_registrant sends REGISTER at timer_interval and tracks last_register_succ.
- OCP displays registration state (registered, pending, failed) per trunk.

### FR-004: Inbound DID Routing
**Description**: Calls arriving from trunk provider IPs are routed to the correct tenant/backend using the called DID.
**Route Logic**:
```cfg
route[INBOUND_DID_ROUTING] {
    # Only process INVITEs from known trunk IPs
    if (!check_source_address(3)) {
        return; # Not a trunk IP; continue to normal auth flow
    }

    if (!sql_query_one(
        "SELECT tenant_id, dispatcher_setid FROM sip_trunk_did_mappings "
        "WHERE did_number = '$rU' AND enabled = true LIMIT 1",
        "$avp(tenant_id)", "$avp(did_setid)"
    )) {
        sl_send_reply(480, "Temporarily Unavailable");
        exit;
    }

    if ($avp(did_setid) == NULL) {
        xlog("L_WARN", "INBOUND_DID: no mapping for DID $rU from trunk $si\n");
        sl_send_reply(404, "DID Not Found");
        exit;
    }

    # Bypass auth for trusted trunk traffic
    $avp(direction) = "inbound";
    append_hf("X-Tenant-ID: $avp(tenant_id)\r\n");

    if (!ds_select_dst($avp(did_setid), 4, "f")) {
        sl_send_reply(503, "No Backend Available");
        exit;
    }

    route(HANDLE_INVITE);
    route(RELAY);
    exit;
}
```
**Acceptance Criteria**:
- Trunk-originated INVITEs bypass Digest authentication.
- Correct X-Tenant-ID header is appended before forwarding to Asterisk.
- Unknown DIDs return 404 DID Not Found.

### FR-005: Trunk Healthcheck (OPTIONS Probe)
**Description**: OpenSIPS sends periodic SIP OPTIONS to each trunk provider and tracks health.
**Implementation**:
- Use OpenSIPS dispatcher module with trunk providers loaded into a dedicated dispatcher set (e.g., setid 100).
- ds_ping_method = OPTIONS, ds_ping_interval = 30.
- ds_probing_mode = 1 probes all targets.
- An event_route[E_DISPATCHER_STATUS] handler updates a cachedb_local key trunk_health_<provider_id>.
- Trunk routing queries skip providers marked unhealthy.
**Acceptance Criteria**:
- 3 consecutive missed OPTIONS responses mark trunk as unhealthy.
- Healthy trunk resumes selection within 30 seconds of successful probe.
- Health status is queryable via MI and visible in OCP.

### FR-006: Per-Trunk Rate Limiting and QoS Metrics
**Description**: Enforce CPS and concurrent call limits per trunk; emit Prometheus-compatible metrics.
**Rate Limiting**:
- rl_check("trunk_<uuid>", max_cps, "TAILDROP") before relay.
- Concurrent call counter incremented on INVITE, decremented on BYE/failure via dialog event routes.
**Metrics**:
| Metric | Type | Labels |
|--------|------|--------|
| tsisip_trunk_calls_total | counter | trunk_id, direction, status |
| tsisip_trunk_calls_active | gauge | trunk_id, direction |
| tsisip_trunk_cps_throttled_total | counter | trunk_id |
| tsisip_trunk_pdd_ms | histogram | trunk_id |
| tsisip_trunk_probe_latency_ms | gauge | trunk_id |
| tsisip_trunk_probe_failures_total | counter | trunk_id |
**Acceptance Criteria**:
- CPS limit enforced with less than or equal to 1% overshoot under burst.
- Concurrent counter never goes negative.
- Metrics endpoint returns trunk metrics when Feature 003 Prometheus scrape is enabled.

### FR-007: Trunk Encryption Profiles
**Description**: Each trunk specifies its transport and media encryption requirements.
**Mapping**:
| transport | OpenSIPS Socket | RTPengine Flags |
|---|---|---|
| udp | udp: | replace-origin replace-session-connection |
| tcp | tcp: | replace-origin replace-session-connection |
| tls | tls: | replace-origin replace-session-connection |
| tls + srtp_mode=sdes | tls: | RTP/SAVP replace-origin replace-session-connection |
| tls + srtp_mode=dtls | tls: | UDP/TLS/RTP/SAVP replace-origin replace-session-connection |
**Acceptance Criteria**:
- TLS trunk calls use proto_tls socket for outbound leg.
- SRTP mode triggers correct RTPengine offer flags.
- Packet capture confirms no plaintext RTP on SRTP-enabled trunks.

### FR-008: OCP Admin Page for Trunk Management
**Description**: The TSiSIP Control Panel provides a CRUD interface for trunk providers and DID mappings.
**Pages**:
- /admin/trunk_providers.php — list, add, edit, disable, delete trunk providers.
- /admin/trunk_dids.php — manage DID-to-tenant mappings per trunk.
- /admin/trunk_status.php — real-time registration and health status.
**Security**:
- Requires OCP login with role admin or devops.
- Password decryption uses the Docker-secret-injected key; the OCP PHP process reads /run/secrets/trunk_encryption_key.
- Audit log entries in ocp_audit_log for all trunk mutations.
**Acceptance Criteria**:
- CRUD operations persist to PostgreSQL and reflect in OpenSIPS within 60 seconds.
- Password field masks input and decrypts only for authorized edit operations.
- Disabled trunks are immediately excluded from new call routing.

## Success Criteria

| ID | Criterion | Target | Measurement Method |
|----|-----------|--------|-------------------|
| SC-001 | Outbound trunk call setup time | less than or equal to 500 ms P95 | Synthetic call from subscriber to PSTN number |
| SC-002 | Trunk failover latency | less than or equal to 5 seconds | Induce 503 on primary trunk; measure time to secondary |
| SC-003 | Inbound DID routing accuracy | 100% | Test each configured DID maps to correct tenant backend |
| SC-004 | Trunk registration uptime | greater than or equal to 99.5% | 24-hour observation of uac_registrant state |
| SC-005 | CPS rate limit accuracy | less than or equal to 1% overshoot | Burst test at 2x configured CPS |
| SC-006 | Health probe reaction time | less than or equal to 90 seconds | 3 missed probes + exclusion from selection |
| SC-007 | Encryption profile compliance | 100% | Packet capture and TLS handshake verification |
| SC-008 | Concurrent call counter consistency | 0 negative values | 24-hour stress test with abnormal terminations |

## Key Entities

| Entity | Description | Attributes |
|--------|-------------|------------|
| SIPTrunkProvider | External carrier SIP trunk configuration | id, name, host, port, transport, auth credentials, priority, encryption profile, rate limits |
| SIPTrunkDIDMapping | Inbound DID-to-tenant routing rule | did_number, tenant_id, trunk_provider_id, dispatcher_setid, enabled |
| SIPTrunkRegistration | Runtime registration state for UAC registrant | trunk_provider_id, registrar, aor, expiry, state, last_register_succ |
| TrunkHealthState | Runtime health probe result | trunk_provider_id, status, last_probe_time, consecutive_failures |
| TrunkCallCounter | Runtime per-trunk concurrent call gauge | trunk_provider_id, direction, count |

## Scope

### In Scope
- PostgreSQL schema for trunk providers, DID mappings, and registration state.
- OpenSIPS configuration changes for trunk routing, registration, inbound DID handling, and failover.
- Per-trunk rate limiting and concurrent call tracking.
- OPTIONS-based health probing with dispatcher integration.
- Trunk encryption profiles (TLS transport, SRTP modes).
- OCP admin pages for trunk and DID management.
- CDR enrichment with trunk_id and direction for trunk-routed calls.
- Docker secret for trunk credential encryption key.

### Out of Scope
- STIR/SHAKEN identity attestation and verification.
- ENUM or DNS-based carrier selection.
- Automatic least-cost routing (LCR) algorithms beyond simple priority fallback.
- Fax (T.38) negotiation specifics across trunks.
- SIP MESSAGE or other non-INVITE/REGISTER trunk traffic.
- Multi-tenancy at the trunk provider level (each trunk belongs to TSiSIP operator, not sub-tenanted).

## Dependencies

| Dependency | Description | Impact if Missing |
|------------|-------------|-------------------|
| OpenSIPS 3.6 LTS | uac, uac_auth, uac_registrant, dispatcher, ratelimit, cachedb_local modules | Cannot register to or authenticate with trunk providers |
| PostgreSQL | Trunk configuration and DID mapping tables | Routing is blind; no trunk selection possible |
| RTPengine | SRTP/DTLS-SRTP media relay | Cannot encrypt media on TLS+SRTP trunks |
| Feature 003 (Observability) | Prometheus/Grafana pipeline | Trunk QoS metrics have no sink |
| Feature 007 (TLS) | proto_tls, tls_mgm modules | Cannot establish TLS trunk connections |
| OCP Foundation | Authentication, RBAC, audit logging | No admin UI for trunk management |

## Assumptions

- Trunk providers accept standard SIP Digest authentication or IP-based trust (mTLS handled by Feature 007 trunk_ips).
- At least one trunk provider will require registration; others may accept static IP authentication.
- The operator can provision DID numbers and communicate them to the trunk provider for routing to TSiSIP.
- Asterisk backends are configured to accept inbound calls from TSiSIP with X-Tenant-ID headers for tenant scoping.
- The uac_registrant table format matches OpenSIPS 3.6 module expectations.

## Risks

| ID | Risk | Likelihood | Impact | Mitigation |
|----|------|------------|--------|------------|
| R-001 | Trunk provider changes IP or port without notice | Medium | High | Health probes detect quickly; OCP allows rapid update |
| R-002 | Credential decryption key rotation invalidates stored passwords | Low | High | Version encrypted payloads; re-provision passwords after key rotation |
| R-003 | Inbound DID overlap between providers causes routing ambiguity | Medium | Medium | UNIQUE constraint on (did_number, trunk_provider_id); alert on duplicate DID |
| R-004 | uac_registrant module incompatibility with specific provider REGISTER quirks | Medium | Medium | Support provider-specific registrar and proxy fields; test with provider sandbox |
| R-005 | Trunk failover during active dialog causes media path disruption | Low | High | Failover only on initial INVITE transaction; in-dialog re-INVITEs follow existing Record-Route |
| R-006 | Rate limit false positives during legitimate burst (e.g., call center) | Medium | Medium | Allow per-tenant trunk CPS overrides; monitor and alert |

## Notes

- The sip_trunk_providers table uses auth_password_encrypted (BYTEA) rather than plaintext. Encryption uses pgcrypto encrypt() with AES-256-CBC or equivalent; the decryption key is injected via Docker secret trunk_encryption_key and read by the OCP PHP layer and OpenSIPS entrypoint (if needed at config-render time).
- Trunk provider selection for outbound calls uses SQL ORDER BY priority ASC as a simple priority list. Future features may introduce drouting for prefix-based LCR.
- The existing trunk_ips table (Feature 007) remains authoritative for mTLS and IP-trust decisions. Inbound DID routing reuses check_source_address(3) where group 3 is populated from trunk_ips entries linked to sip_trunk_providers.
- The dispatcher module is reused for trunk health probing by loading trunk destinations into a dedicated dispatcher set range (e.g., 100-199). This avoids writing a custom probe timer.
- All trunk-routed calls must pass through topology_hiding("C") to prevent trunk providers from learning internal Asterisk addresses.
- CDR enrichment requires extending the acc module configuration to include AVPs for trunk_provider_id, trunk_name, and direction.

## References

- docs/TSiSIP-CANONICAL-SPEC.md — Sections 6 (modules), 8 (routing logic), 9 (auth), 11 (RTP relay), 12 (PostgreSQL schema), 14 (Docker Compose)
- docs/TSiSIP-OPERATOR-RUNBOOK.md — Operational procedures for trunk provider onboarding
- opensips/opensips.cfg.tpl — Baseline OpenSIPS configuration template
- db/init/02-tsisip-extensions.sql — Existing tenant, routing, and audit schema
- db/init/03-seed-data.sql — Dev tenant and dispatcher seed data
- OpenSIPS 3.6 Module Documentation:
  - https://opensips.org/docs/modules/3.6.x/uac.html
  - https://opensips.org/docs/modules/3.6.x/uac_auth.html
  - https://opensips.org/docs/modules/3.6.x/uac_registrant.html
  - https://opensips.org/docs/modules/3.6.x/dispatcher.html
  - https://opensips.org/docs/modules/3.6.x/ratelimit.html
