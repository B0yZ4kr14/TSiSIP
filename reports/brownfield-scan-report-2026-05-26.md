# Brownfield Scan Report

**Scan Date**: 2026-05-19T15:12:43-03:00
**Scope**: all
**Branch**: main
**Commit**: ce91dc8 docs(feature-017): add operational notes about db_postgres UUID warning
**Files Scanned**: 71404

---

## Findings Summary

| Severity | Count |
|---|---|
| CRITICAL | 0 |
| HIGH | 0 |
| MEDIUM | 2 |
| LOW | 3 |

---

## Detailed Findings

### M1: Missing Environment Variable in `.env.example`

| Field | Value |
|---|---|
| **ID** | M1 |
| **Category** | Configuration Rot |
| **Severity** | MEDIUM |
| **File** | `.env.example` |
| **Finding** | `RTPENGINE_INTERNAL_IP` is referenced in `docker-compose.yml`, `docker-compose.prod.yml`, and `docker-compose.vps.yml` (`--listen-ng=${RTPENGINE_INTERNAL_IP}:22222`) but is not defined in `.env.example`. |
| **Impact** | New deployments or environment rebuilds may fail with an unset variable, or Docker Compose may substitute an empty string, causing RTPengine control socket binding issues. |
| **Recommendation** | Add `RTPENGINE_INTERNAL_IP=rtpengine` (or the appropriate internal DNS name/IP) to `.env.example`. |
| **Constitution Gate** | Network isolation — RTPengine control socket must bind to a known internal address. |

### M2: Persistent `db_postgres` UUID Warning

| Field | Value |
|---|---|
| **ID** | M2 |
| **Category** | Spec Drift |
| **Severity** | MEDIUM |
| **File** | `db/init/02-tsisip-extensions.sql` |
| **Finding** | `sip_trunk_did_mappings.tenant_id` still uses PostgreSQL native `UUID` type (OID 2950). The `db_postgres` OpenSIPS module logs a warning: `unhandled data type column (tenant_id) type id (2950)`. Other tables were already migrated to `VARCHAR(36)` (subscriber, header_routing_rules, pbx_backends, trunk_ips). |
| **Impact** | Functionally harmless (falls back to DB_STRING), but noisy in logs and inconsistent with the project's VARCHAR(36) standard. |
| **Recommendation** | ~~Add `ALTER TABLE sip_trunk_did_mappings ALTER COLUMN tenant_id TYPE VARCHAR(36);`~~ **RESOLVED 2026-05-26**: Applied `tenant_id::VARCHAR(36)` cast in OpenSIPS query (`opensips.cfg.tpl:893`) and created migration script `db/init/05-uuid-cast-migration.sql` documenting the canonical decision. No schema ALTER required because `tenants.id` remains UUID with `gen_random_uuid()` default. |
| **Constitution Gate** | PostgreSQL-only — no rule violation, but inconsistent schema typing. **RESOLVED** |

### L1: UI Placeholders Using RFC 1918 Addresses

| Field | Value |
|---|---|
| **ID** | L1 |
| **Category** | Configuration Rot |
| **Severity** | LOW |
| **Files** | `web/rtpproxy.php`, `web/keepalived.php`, `web/clusterer.php`, `web/smpp-gateway.php`, `web/call-center.php` |
| **Finding** | HTML input placeholders use example RFC 1918 IPs (`10.0.0.1`, `10.0.0.100`, `172.22.0.1`) as user-facing hints. These are not hard-coded runtime values. |
| **Impact** | None — placeholders are cosmetic. May confuse security scanners. |
| **Recommendation** | Replace with clearly labeled placeholders like `e.g., 192.0.2.1 (EXAMPLE)` or use documentation links. |

### L2: `sleep` Statements in Test/Deploy Scripts

| Field | Value |
|---|---|
| **ID** | L2 |
| **Category** | Technical Debt |
| **Severity** | LOW |
| **Files** | `tests/integration/*.sh`, `tests/vps-stabilization/*.sh`, `deploy/scripts/*.sh` |
| **Finding** | Multiple `sleep` statements (2s–10s) used for timing synchronization in test and deploy scripts. No retry loops or health-check polling alternatives. |
| **Impact** | Makes tests and deployments slower than necessary; race conditions possible on slower systems. |
| **Recommendation** | Replace fixed `sleep` with active polling (e.g., `docker compose ps` health status, `curl` readiness probes) where feasible. |

### L3: VEX Generation Still Pending

| Field | Value |
|---|---|
| **ID** | L3 |
| **Category** | Technical Debt |
| **Severity** | LOW |
| **File** | N/A (cross-cutting) |
| **Finding** | SBOM and SLSA provenance are in CI. VEX (Vulnerability Exploitability eXchange) generation is still marked as TODO per `reports/speckit-consolidated-audit-2026-05-24.md`. |
| **Impact** | Incomplete supply-chain security documentation. |
| **Recommendation** | Add VEX generation step to CI pipeline after container image scanning. |

---

## Passed Checks

| Check | Result |
|---|---|
| Forbidden modules (`sanity`, `db_mysql`, `db_sqlite`) | ✅ PASS — Zero references in runtime code |
| OpenSIPS version baseline | ✅ PASS — Dockerfile uses 3.6 LTS |
| Network isolation (Asterisk/PostgreSQL ports) | ✅ PASS — No host-published ports |
| RTPengine control socket binding | ✅ PASS — All compose files use `${RTPENGINE_INTERNAL_IP}:22222` |
| Precomputed HA1 | ✅ PASS — `calculate_ha1 = 0` enforced; no `password_column = "password"` |
| Topology hiding | ✅ PASS — `topology_hiding("C")` in config |
| Health checks | ✅ PASS — All services in compose files have health checks |
| Image tag pinning | ✅ PASS — No `:latest` tags found in compose/Dockerfile |
| Secret hygiene | ✅ PASS — No plaintext secrets in committed runtime files |
| ARCH-PRE-001 (subscriber direct writes) | ✅ PASS — `web/subscribers.php` contains zero direct `INSERT/UPDATE/DELETE` on subscriber table |
| Trunk auth password encryption | ✅ PASS — `auth_password` encrypted with `pgp_sym_encrypt` before storage |

---

## Top 3 Action Items

1. **M1**: Add `RTPENGINE_INTERNAL_IP` to `.env.example` to prevent deployment failures.
2. **M2**: Migrate `sip_trunk_did_mappings.tenant_id` from `UUID` to `VARCHAR(36)` for schema consistency.
3. **L2**: Replace fixed `sleep` statements with active health-check polling in test scripts.

---

*Report generated by speckit-brownfield-scan*
