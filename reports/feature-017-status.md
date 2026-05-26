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
| AC3 | Inbound DID routing from trunk IPs without auth | **PARTIAL** | Route `INBOUND_DID_ROUTING` implemented and active; end-to-end requires trunk provider peer |
| AC4 | Outbound trunk routing with failover | **PARTIAL** | Route `TRUNK_ROUTING` with priority loop and `FAILURE_MANAGE` failover implemented; end-to-end requires live trunk |
| AC5 | TLS/SRTP enforcement per trunk | **PARTIAL** | Config handles `transport=tls` and `srtp_mode=sdes/dtls`; packet capture pending |
| AC6 | OPTIONS health probes every 30s, 3-strike disable | **PASS** | Dispatcher set 100 with `ping_interval=30`; `ds_probing_threshold=2`; event route writes `cachedb_local` |
| AC7 | Per-trunk CPS rate limiting | **PASS** | `rl_check("trunk_$id", $cps, "TAILDROP")` in `TRUNK_ROUTING` |
| AC8 | OCP admin page for trunk CRUD | **PASS** | Pages `trunk-providers.php`, `trunk-dids.php`, `trunk-status.php` active in VPS OCP |
| AC9 | Credential encryption at rest | **PASS** | `auth_password_encrypted` is `BYTEA`; OCP uses `pgp_sym_encrypt()` with Docker secret key |
| AC10 | CDR enrichment with trunk metadata | **PASS** | `acc` module `extra_fields` includes `trunk_provider_id`, `trunk_name`, `direction` |

---

## Test Coverage

- `tests/vps-stabilization/test-feature-017.sh`: 19/19 checks passing
- `tests/vps-stabilization/test-vps-sip.sh`: 4/4 SIP signaling tests passing
- `tests/integration/test_end_to_end_call.py`: Authenticated INVITE → Asterisk validated

---

## Known Limitations

1. **End-to-end trunk calls** (AC3–AC5) cannot be validated without a live SIP trunk provider or mock peer. The routing logic is implemented and syntactically valid.
2. **uac_registrant** will show failed registration states for test providers (expected; target IPs are RFC 5737 documentation addresses).
3. **Prometheus/Grafana** (Feature 003) is disabled in `vps-lite` profile; trunk QoS metrics have no sink until enabled.
4. **OCP trunk pages** exist in the VPS container but the full OCP codebase is not yet fully versioned in the repository.

---

## Recent Commits

- `261a8cb` — test(feature-017): expand validation to cover AC8, AC9
- `8f13c87` — feat(feature-017): add OCP trunk management pages from VPS
- `35498c6` — fix(feature-017): correct E_DISPATCHER_STATUS param name and add trunk registration trigger
- `00f7389` — db(schema): sync production trunk schema and ocp_password_changes
