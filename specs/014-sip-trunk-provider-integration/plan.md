# Feature 014-C Implementation Plan: SIP Trunk Provider Integration

## Overview

This plan translates the Feature 014-C specification into an executable implementation roadmap for outbound PSTN routing, trunk registration, inbound DID routing, per-trunk encryption profiles, health monitoring, rate limiting, and OCP admin management.

---

## Architecture & Stack Choices

### Container Platform
- **Docker Engine** with Docker Compose V2
- PostgreSQL 16+ with pgcrypto for credential encryption
- OpenSIPS 3.6 LTS with uac_registrant, uac_auth, dispatcher, ratelimit, cachedb_local

### Trunk Modules
| Module | Purpose |
|---|---|
| `uac_registrant` | Outbound REGISTER to trunk providers |
| `uac_auth` | Per-trunk Digest authentication on outbound calls |
| `uac` | From-header rewriting (caller ID, domain) |
| `dispatcher` | Trunk health probes (OPTIONS) and failover |
| `ratelimit` | Per-trunk CPS throttling |
| `cachedb_local` | Runtime health state, concurrent call counters |
| `acc` | CDR enrichment with trunk_id and direction |

### Encryption Profiles
| transport | srtp_mode | OpenSIPS Socket | RTPengine Flags |
|---|---|---|---|
| udp | none | udp: | replace-origin replace-session-connection |
| tcp | none | tcp: | replace-origin replace-session-connection |
| tls | none | tls: | replace-origin replace-session-connection |
| tls | sdes | tls: | RTP/SAVP replace-origin replace-session-connection |
| tls | dtls | tls: | UDP/TLS/RTP/SAVP replace-origin replace-session-connection |

---

## Implementation Waves

### Wave 1: Database Schema

Agent: 

- [ ] W1.1: Create  with , , and  tables, indexes, and foreign keys.
- [ ] W1.2: Update  with a sample trunk provider and DID mapping for dev testing.
- [ ] W1.3: Add trunk credential encryption Docker secret to  and mount into  and  services.
- [ ] W1.4: Create placeholder secret file under  and update  with documentation.
- [ ] W1.5: Validate schema initialization idempotently with  and .

### Wave 2: OpenSIPS Module Configuration

Agent: 
Depends on: W1

- [ ] W2.1: Update  to conditionally load , , and .
- [ ] W2.2: Add  modparams (db_url, table_name, timer_interval) pointing to .
- [ ] W2.3: Add dispatcher modparams for trunk health probe set (setid 100) with ds_ping_method=OPTIONS, ds_ping_interval=30.
- [ ] W2.4: Update  hash_size if needed for trunk pipe proliferation.
- [ ] W2.5: Update  to read the trunk credential encryption secret and export for config substitution.
- [ ] W2.6: Validate OpenSIPS config syntax with  inside the built image.

### Wave 3: Outbound Routing Logic

Agent: 
Depends on: W2
Parallel with Wave 4.

- [ ] W3.1: Add  to  for non-local domain detection and priority-based trunk selection.
- [ ] W3.2: Implement per-trunk CPS rate limiting using .
- [ ] W3.3: Add R-URI rewriting, caller ID prefix, From domain override, and transport selection in TRUNK_ROUTING.
- [ ] W3.4: Add  for 408|500|502|503|504 failover to next priority trunk.
- [ ] W3.5: Add  for SRTP mode-specific RTPengine flags.
- [ ] W3.6: Update  module configuration to include , ,  in CDR records.
- [ ] W3.7: Update main  to call  before  for INVITEs to external domains.

### Wave 4: Inbound DID Routing

Agent: 
Depends on: W2
Parallel with Wave 3.

- [ ] W4.1: Add  to  for trusted trunk IP bypass and DID-to-tenant lookup.
- [ ] W4.2: Update main  to call  immediately after  for INVITEs from known trunk IPs.
- [ ] W4.3: Ensure  header is appended and  is applied before relay to Asterisk.
- [ ] W4.4: Add 404 DID Not Found and 503 No Backend Available replies for unknown/disabled DIDs.

### Wave 5: Health Monitoring

Agent: 
Depends on: W3, W4.

- [ ] W5.1: Configure dispatcher trunk set (setid 100) with provider destinations for OPTIONS probing.
- [ ] W5.2: Update  to store health state in  ().
- [ ] W5.3: Update  to skip trunks marked unhealthy in .
- [ ] W5.4: Update  or  configuration to emit per-trunk Prometheus metrics (calls_total, calls_active, cps_throttled_total, pdd_ms, probe_latency_ms, probe_failures_total).
- [ ] W5.5: Create Grafana dashboard  for trunk health, CPS, and call quality.

### Wave 6: OCP Admin Pages

Agent: 
Depends on: W1.
Parallel with Waves 3-5.

- [ ] W6.1: Add trunk navigation entries to  under Administration (Trunk Providers, DID Mappings, Trunk Status).
- [ ] W6.2: Create  with CRUD for , password encryption via pgcrypto + Docker secret.
- [ ] W6.3: Create  with CRUD for  per trunk.
- [ ] W6.4: Create  with real-time registration state, health status, and manual probe trigger.
- [ ] W6.5: Add audit logging to  for all trunk mutations.
- [ ] W6.6: Update  or  to ensure the trunk credential encryption secret is readable by www-data.

### Wave 7: Testing & Validation

Agent: 
Depends on: W3, W4, W5, W6.

- [ ] W7.1: Create  — authenticated INVITE to PSTN number via highest-priority trunk.
- [ ] W7.2: Create  — induce 503 on primary trunk, verify failover to secondary within 5 seconds.
- [ ] W7.3: Create  — unauthenticated INVITE from trunk IP with DID, verify routing to correct tenant backend.
- [ ] W7.4: Create  — burst test verifying CPS throttling returns 503 without overshoot.
- [ ] W7.5: Create  — simulate unresponsive trunk, verify exclusion after 3 missed probes.
- [ ] W7.6: Run , , and === TSiSIP CI Scan ===
[brownfield] Checking for hardcoded :latest tags...
PASS: No hardcoded :latest tags
[brownfield] Checking for forbidden modules...
PASS: No forbidden modules
[version-guard] Checking for unpinned base images...
PASS: Base image check complete
[memorylint] Checking for container memory limits...
PASS: Memory limits present on 0
0 services
[security] Checking for committed secrets...
PASS: No tracked secret files

=== CI SCAN PASSED ===; verify zero new findings.
- [ ] W7.7: Update  with trunk onboarding, DID mapping, and health troubleshooting procedures.

---

## Dependency Graph



---

## Validation Gates

| Gate | Check | Command / Method |
|---|---|---|
| Schema | Tables created with correct FKs | `psql -c "\\dt sip_trunk_*"` |
| Config | OpenSIPS loads without error | `opensips -c -f /etc/opensips/opensips.cfg` |
| Outbound | PSTN call routes through trunk | Synthetic INVITE to +1234@pstn |
| Inbound | DID call routes to tenant | Synthetic INVITE from trunk IP with DID |
| Failover | Secondary trunk selected on 503 | Block primary trunk IP; measure failover |
| Health | Unhealthy trunk excluded | Drop OPTIONS replies; verify exclusion |
| Rate Limit | CPS enforced | Burst INVITEs; verify 503 after threshold |
| OCP | CRUD persists and reflects | Create trunk in UI; query DB; place call |
