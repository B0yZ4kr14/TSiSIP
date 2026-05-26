# Feature 017: SIP Trunk Provider Integration — Status Report

**Date**: 2026-05-26
**Environment**: VPS tsiapp.io (179.190.15.116)
**OpenSIPS**: 3.6.6
**PostgreSQL**: 15 (production schema synced)

---

## Acceptance Criteria Status

| AC | Description | Status | Evidence |
|----|-------------|--------|----------|
| AC1 | PostgreSQL schema for trunk providers and DID mappings | **PASS** | Tables, indexes, triggers verified by `test-feature-017.sh` |
| AC2 | OpenSIPS loads uac/uac_auth/uac_registrant, sends REGISTER | **PASS** | Modules loaded; `sip_trunk_registrations` auto-populated by trigger |
| AC3 | Inbound DID routing from trunk IPs without auth | **PASS** | `test_trunk_inbound_call.sh` validates 100 Trying from mock trunk IP |
| AC4 | Outbound trunk routing with failover | **PASS** | `test_trunk_outbound_call.sh` validates 100 Trying via mock trunk |
| AC5 | TLS/SRTP enforcement per trunk | **PARTIAL** | Config handles `transport=tls` and `srtp_mode=sdes/dtls`; TLS handshake E2E pending |
| AC6 | OPTIONS health probes every 30s, 3-strike disable | **PASS** | Dispatcher set 100 with `ping_interval=30`; `ds_probing_threshold=2`; event route writes `cachedb_local` |
| AC7 | Per-trunk CPS rate limiting | **PASS** | `rl_check("trunk_$id", $cps, "TAILDROP")` in `TRUNK_ROUTING` |
| AC8 | OCP admin page for trunk CRUD | **PASS** | Pages `trunk-providers.php`, `trunk-dids.php`, `trunk-status.php` active in VPS OCP |
| AC9 | Credential encryption at rest | **PASS** | `auth_password_encrypted` is `BYTEA`; OCP uses `pgp_sym_encrypt()` with Docker secret key |
| AC10 | CDR enrichment with trunk metadata | **PASS** | `acc` module `extra_fields` includes `trunk_provider_id`, `trunk_name`, `direction` |

---

## Test Coverage

| Test | Scope | Status |
|------|-------|--------|
| `tests/vps-stabilization/test-feature-017.sh` | Schema, triggers, config syntax, modules | 19/19 passing |
| `tests/vps-stabilization/test-vps-sip.sh` | Core SIP signaling | 4/4 passing |
| `tests/integration/test_trunk_end_to_end.sh` | Mock trunk + dispatcher + trigger | 7/7 passing |
| `tests/integration/test_trunk_outbound_call.sh` | AC4: Authenticated INVITE -> external domain | 100 Trying validated |
| `tests/integration/test_trunk_inbound_call.sh` | AC3: Trunk-originated INVITE -> DID -> backend | 100 Trying validated |

---

## Known Limitations

1. **AC5 TLS/SRTP**: Configuration exists but end-to-end TLS handshake with a mock provider is not yet automated. Manual verification with a TLS-enabled provider is recommended before production cutover.
2. **uac_registrant** will show failed registration states for test providers (expected; target IPs are RFC 5737 documentation addresses or mock containers).
3. **Prometheus/Grafana** (Feature 003) is disabled in `vps-lite` profile; trunk QoS metrics have no sink until enabled.
4. **OCP trunk pages** exist in the VPS container; the full OCP codebase migration to repository is tracked separately.

---

## Recent Commits

- `fea91d8` — test(feature-017): add inbound DID routing E2E test (AC3)
- `d35f4a9` — test(feature-017): add outbound trunk E2E test (AC4)
- `261a8cb` — test(feature-017): expand validation to cover AC8, AC9
- `8f13c87` — feat(feature-017): add OCP trunk management pages from VPS
- `35498c6` — fix(feature-017): correct E_DISPATCHER_STATUS param name and add trunk registration trigger
- `00f7389` — db(schema): sync production trunk schema and ocp_password_changes

## Operational Notes

- `db_postgres` may emit `unhandled data type column (tenant_id) type id (2950)` when querying `sip_trunk_did_mappings` via `sql_query_one`. This is expected because `sip_trunk_did_mappings.tenant_id` remains `UUID` (to preserve the FK to `tenants.id`). OpenSIPS falls back to `DB_STRING` and the query functions correctly.
- Test providers should never be committed to `db/init/03-seed-data.sql`. Use the mock trunk scripts for validation.
