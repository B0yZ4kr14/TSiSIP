# Feature 014-C Tasks: SIP Trunk Provider Integration
**Last Updated**: 2026-05-19

---

## Wave 1: Database Schema

### T1.1: Create trunk provider schema file
**Description**: Create `db/init/04-trunk-schema.sql` containing `sip_trunk_providers` (with pgcrypto-encrypted credentials), `sip_trunk_did_mappings`, and `sip_trunk_registrations` tables. Include all indexes, check constraints, and foreign keys as specified in the feature spec (FR-001, FR-003).
**Files affected**: `db/init/04-trunk-schema.sql`
**Depends on**: —
**Status**: [x]

### T1.2: Seed dev trunk data
**Description**: Update `db/init/03-seed-data.sql` to insert one sample trunk provider (registration_required=false, transport=udp, priority=10) and one DID mapping to the default tenant for local integration testing.
**Files affected**: `db/init/03-seed-data.sql`
**Depends on**: T1.1
**Status**: [x]

### T1.3: Add Docker secret for credential encryption
**Description**: Add `trunk_cred_key` secret entry to `docker-compose.yml` under `secrets:` and mount it into the `opensips` and `ocp` service definitions. Ensure it is read-only.
**Files affected**: `docker-compose.yml`
**Depends on**: T1.1
**Status**: [x]

### T1.4: Create secret placeholder and env documentation
**Description**: Create an empty `secrets/trunk_cred_key` file. Update `.env.example` to document the secret purpose and generation command (e.g., `openssl rand -base64 32`).
**Files affected**: `secrets/trunk_cred_key`, `.env.example`
**Depends on**: T1.3
**Status**: [x]

### T1.5: Validate schema idempotency
**Description**: Run `docker compose up -d postgres`, wait for healthy state, and verify `psql -c "\dt sip_trunk_*"` returns all three tables with correct columns and constraints.
**Files affected**: `db/init/04-trunk-schema.sql`
**Depends on**: T1.2, T1.4
**Status**: [x]

---

## Wave 2: OpenSIPS Module Configuration

### T2.1: Load uac modules conditionally
**Description**: Update `opensips/opensips.cfg.tpl` to load `uac.so`, `uac_auth.so`, and `uac_registrant.so` after the existing module block. Gate loading behind a comment indicating these are required only when trunk providers are configured.
**Files affected**: `opensips/opensips.cfg.tpl`
**Depends on**: T1.5
**Status**: [x]

### T2.2: Configure uac_registrant module parameters
**Description**: Add `modparam("uac_registrant", "db_url", DB_URL)`, `modparam("uac_registrant", "table_name", "sip_trunk_registrations")`, and `modparam("uac_registrant", "timer_interval", 60)` to `opensips/opensips.cfg.tpl`.
**Files affected**: `opensips/opensips.cfg.tpl`
**Depends on**: T2.1
**Status**: [x]

### T2.3: Configure dispatcher trunk probe set
**Description**: Add dispatcher configuration for trunk health probe set (setid 100). Set `ds_ping_method=OPTIONS`, `ds_ping_interval=30`, `ds_probing_mode=1`, and `ds_probing_threshold=3` for this set. Document that actual destinations are populated via `dispatcher` table inserts or MI commands.
**Files affected**: `opensips/opensips.cfg.tpl`
**Depends on**: T2.2
**Status**: [x]

### T2.4: Update entrypoint for trunk secret
**Description**: Update `docker/entrypoint.sh` to read the trunk credential encryption secret from `/run/secrets/trunk_cred_key` (if present) and export it as an environment variable for config substitution.
**Files affected**: `docker/entrypoint.sh`
**Depends on**: T2.1
**Status**: [x]

### T2.5: Validate OpenSIPS config syntax
**Description**: Build the OpenSIPS image and run `opensips -c -f /etc/opensips/opensips.cfg` inside a container with all required secrets and environment variables mounted.
**Files affected**: `opensips/opensips.cfg.tpl`, `docker/entrypoint.sh`
**Depends on**: T2.3, T2.4
**Status**: [x]

---

## Wave 3: Outbound Routing Logic

### T3.1: Implement TRUNK_ROUTING route
**Description**: Add `route[TRUNK_ROUTING]` to `opensips/opensips.cfg.tpl`. It checks whether the destination domain exists in `tenants`; if not, queries `sip_trunk_providers` for the highest-priority enabled trunk, applies CPS rate limiting, rewrites the R-URI, applies caller ID prefix and From domain override, and sets SRTP branch route and failure route.
**Files affected**: `opensips/opensips.cfg.tpl`
**Depends on**: T2.5
**Status**: [x]

### T3.2: Implement TRUNK_FAILOVER failure route
**Description**: Add `failure_route[TRUNK_FAILOVER]` that triggers on `408|500|502|503|504`. On failure, query the next priority trunk from `sip_trunk_providers`, update `$ru`, and re-attempt relay with a new branch route. If no trunks remain, forward the final failure reply.
**Files affected**: `opensips/opensips.cfg.tpl`
**Depends on**: T3.1
**Status**: [x]

### T3.3: Implement per-trunk CPS rate limiting
**Description**: In `route[TRUNK_ROUTING]`, call `rl_check("trunk_$avp(trunk_id)", $avp(trunk_cps), "TAILDROP")` before relay. On exceed, return `503 Service Unavailable` and increment a Prometheus-compatible counter.
**Files affected**: `opensips/opensips.cfg.tpl`
**Depends on**: T3.1
**Status**: [x]

### T3.4: Add SRTP branch route for trunk calls
**Description**: Add `branch_route[BRANCH_TRUNK_SRTP]` that sets the correct RTPengine offer flags based on `$avp(trunk_srtp)`: none uses plain RTP, sdes uses `RTP/SAVP`, dtls uses `UDP/TLS/RTP/SAVP`.
**Files affected**: `opensips/opensips.cfg.tpl`
**Depends on**: T3.1
**Status**: [x]

### T3.5: Enrich CDR with trunk metadata
**Description**: Update the `acc` module configuration in `opensips/opensips.cfg.tpl` to include `$avp(trunk_id)`, `$avp(trunk_name)`, and `$avp(direction)` in logged CDR fields. Ensure `do_accounting("db", "cdr")` captures these AVPs.
**Files affected**: `opensips/opensips.cfg.tpl`
**Depends on**: T3.1
**Status**: [x]

### T3.6: Wire TRUNK_ROUTING into main request route
**Description**: Update the main `route{}` in `opensips/opensips.cfg.tpl` to call `route(TRUNK_ROUTING)` for authenticated INVITEs before `route(HEADER_ROUTING)`. Ensure local tenant domains bypass trunk routing.
**Files affected**: `opensips/opensips.cfg.tpl`
**Depends on**: T3.1, T3.4
**Status**: [x]

---

## Wave 4: Inbound DID Routing

### T4.1: Implement INBOUND_DID_ROUTING route
**Description**: Add `route[INBOUND_DID_ROUTING]` to `opensips/opensips.cfg.tpl`. It checks `check_source_address(3)` (trunk IPs group), queries `sip_trunk_did_mappings` by `$rU`, appends `X-Tenant-ID`, selects backend via `ds_select_dst`, and routes through `HANDLE_INVITE` + `RELAY`.
**Files affected**: `opensips/opensips.cfg.tpl`
**Depends on**: T2.5
**Status**: [x]

### T4.2: Wire INBOUND_DID_ROUTING into main request route
**Description**: Update the main `route{}` to call `route(INBOUND_DID_ROUTING)` immediately after `route(TRUNK_VERIFY)` and before `route(AUTH)` for INVITEs. Ensure non-trunk traffic falls through to normal auth.
**Files affected**: `opensips/opensips.cfg.tpl`
**Depends on**: T4.1
**Status**: [x]

### T4.3: Add topology hiding for inbound trunk calls
**Description**: Verify that `topology_hiding("C")` is applied in `route[HANDLE_INVITE]` for all INVITEs, including those from trunk sources. Ensure no internal Asterisk addresses leak to trunk providers.
**Files affected**: `opensips/opensips.cfg.tpl`
**Depends on**: T4.2
**Status**: [x]

### T4.4: Handle unknown DID responses
**Description**: Ensure `INBOUND_DID_ROUTING` returns `404 DID Not Found` for unmapped DIDs and `503 No Backend Available` when `ds_select_dst` fails.
**Files affected**: `opensips/opensips.cfg.tpl`
**Depends on**: T4.1
**Status**: [x]

---

## Wave 5: Health Monitoring

### T5.1: Populate dispatcher trunk probe destinations
**Description**: Create an initialization trigger or OCP action that inserts enabled trunk provider destinations into the `dispatcher` table with `setid=100`. Each row maps a trunk provider to its host:port destination for OPTIONS probing.
**Files affected**: `db/init/04-trunk-schema.sql` or `opensips/opensips.cfg.tpl`
**Depends on**: T3.6, T4.4
**Status**: [x]

### T5.2: Implement dispatcher health event handler
**Description**: Update `event_route[E_DISPATCHER_STATUS]` in `opensips/opensips.cfg.tpl` to store `trunk_health_<provider_id>` in `cachedb_local` when setid=100 destinations change state. On 3 consecutive failures, store `unhealthy`; on success, store `healthy`.
**Files affected**: `opensips/opensips.cfg.tpl`
**Depends on**: T5.1
**Status**: [x]

### T5.3: Filter unhealthy trunks from selection
**Description**: Update `route[TRUNK_ROUTING]` and `failure_route[TRUNK_FAILOVER]` to query `cache_fetch("local", "trunk_health_<id>")` and skip providers marked `unhealthy` during SQL selection.
**Files affected**: `opensips/opensips.cfg.tpl`
**Depends on**: T5.2
**Status**: [x]

### T5.4: Add Prometheus trunk metrics
**Description**: Update `docker/opensips-exporter/` (or `acc` configuration) to expose per-trunk counters and gauges: `tsisip_trunk_calls_total`, `tsisip_trunk_calls_active`, `tsisip_trunk_cps_throttled_total`, `tsisip_trunk_pdd_ms`, `tsisip_trunk_probe_latency_ms`, `tsisip_trunk_probe_failures_total`.
**Files affected**: `docker/opensips-exporter/`, `opensips/opensips.cfg.tpl`
**Depends on**: T5.3
**Status**: [x]

### T5.5: Create Grafana trunk dashboard
**Description**: Create `docker/grafana/provisioning/dashboards/tsisip/sip-trunk-provider.json` with panels for trunk health status, active calls per trunk, CPS throttled rate, probe latency, and registration state.
**Files affected**: `docker/grafana/provisioning/dashboards/tsisip/sip-trunk-provider.json`
**Depends on**: T5.4
**Status**: [ ]

---

## Wave 6: OCP Admin Pages

### T6.1: Add trunk pages to OCP navigation
**Description**: Update `web/common/role-nav.php` to add "Trunk Providers", "DID Mappings", and "Trunk Status" links under the Administration section for admin and devops roles.
**Files affected**: `web/common/role-nav.php`
**Depends on**: T1.5
**Status**: [x]

### T6.2: Create trunk provider CRUD page
**Description**: Create `web/trunk_providers.php` with list, add, edit, disable, and delete operations for `sip_trunk_providers`. Use pgcrypto `encrypt()` / `decrypt()` with the Docker secret key for `auth_password_encrypted`. Mask password input; decrypt only for authorized edits. Enforce role check (admin or devops).
**Files affected**: `web/trunk_providers.php`
**Depends on**: T6.1
**Status**: [x]

### T6.3: Create DID mapping CRUD page
**Description**: Create `web/trunk_dids.php` to manage `sip_trunk_did_mappings`. Allow selecting a trunk provider and tenant from dropdowns. Enforce `UNIQUE (did_number, trunk_provider_id)` at the UI level. Show enabled/disabled state.
**Files affected**: `web/trunk_dids.php`
**Depends on**: T6.2
**Status**: [x]

### T6.4: Create trunk status page
**Description**: Create `web/trunk_status.php` displaying real-time registration state (from `sip_trunk_registrations`), dispatcher health status, last probe time, and a button to trigger an MI-based health probe reload.
**Files affected**: `web/trunk_status.php`
**Depends on**: T6.3
**Status**: [x]

### T6.5: Add trunk audit logging
**Description**: Ensure every create, update, delete, and disable action in `web/trunk_providers.php` and `web/trunk_dids.php` writes an entry to `ocp_audit_log` (or `auth_audit_log` if ocp_audit_log does not exist; create it if needed).
**Files affected**: `web/trunk_providers.php`, `web/trunk_dids.php`, `db/init/04-trunk-schema.sql`
**Depends on**: T6.2
**Status**: [x]

### T6.6: Update OCP container for secret access
**Description**: Update `docker/ocp/Dockerfile` or `docker/ocp/entrypoint.sh` to copy the trunk credential encryption secret into `/tmp/` with `www-data`-readable permissions, matching the existing `db_password` pattern in `web/common/config.php`.
**Files affected**: `docker/ocp/Dockerfile` or `docker/ocp/entrypoint.sh`
**Depends on**: T6.2
**Status**: [x]

---

## Wave 7: Testing & Validation

### T7.1: Outbound trunk call test
**Description**: Create `tests/integration/test_sip_trunk_outbound.py`. Authenticate as `devuser`, send INVITE to a non-local domain (e.g., `+1234567890@pstn`), verify 100 Trying and forwarded INVITE reaches the mock trunk target.
**Files affected**: `tests/integration/test_sip_trunk_outbound.py`
**Depends on**: T3.6, T5.3
**Status**: [ ]

### T7.2: Trunk failover test
**Description**: Create `tests/integration/test_sip_trunk_failover.py`. Configure two trunk providers. Block the primary (e.g., via iptables DROP). Send INVITE and verify failover to secondary within 5 seconds (measured by final 180 Ringing or 200 OK).
**Files affected**: `tests/integration/test_sip_trunk_failover.py`
**Depends on**: T7.1
**Status**: [ ]

### T7.3: Inbound DID routing test
**Description**: Create `tests/integration/test_sip_trunk_inbound.py`. Send unauthenticated INVITE from a trunk IP with a known DID in the R-URI. Verify 200 OK and that the forwarded INVITE to Asterisk contains the correct `X-Tenant-ID` header.
**Files affected**: `tests/integration/test_sip_trunk_inbound.py`
**Depends on**: T4.4
**Status**: [ ]

### T7.4: CPS rate limit test
**Description**: Create `tests/integration/test_sip_trunk_rate_limit.py`. Send burst INVITEs (2x configured CPS) to a trunk destination. Verify that excess calls receive `503 Service Unavailable` and the throttled count matches expectations within 1% overshoot.
**Files affected**: `tests/integration/test_sip_trunk_rate_limit.py`
**Depends on**: T3.3, T7.1
**Status**: [ ]

### T7.5: Health probe exclusion test
**Description**: Create `tests/integration/test_sip_trunk_health_probe.py`. Simulate an unresponsive trunk (drop OPTIONS replies). Verify the trunk is excluded from selection after 3 consecutive missed probes and re-included within 30 seconds of recovery.
**Files affected**: `tests/integration/test_sip_trunk_health_probe.py`
**Depends on**: T5.3, T7.1
**Status**: [ ]

### T7.6: CI validation run
**Description**: Run `docker compose config`, build the OpenSIPS image, run `opensips -c`, execute `scripts/ci-scan.sh`, and verify zero new findings. Fix any lint or syntax errors.
**Files affected**: `docker-compose.yml`, `opensips/opensips.cfg.tpl`
**Depends on**: T7.2, T7.3, T7.4, T7.5
**Status**: [x]

### T7.7: Update operator runbook
**Description**: Update `docs/TSiSIP-OPERATOR-RUNBOOK.md` with procedures for: onboarding a new trunk provider, adding a DID mapping, troubleshooting registration failures, interpreting health probe results, and rotating the trunk credential encryption key.
**Files affected**: `docs/TSiSIP-OPERATOR-RUNBOOK.md`
**Depends on**: T7.6
**Status**: [ ]
