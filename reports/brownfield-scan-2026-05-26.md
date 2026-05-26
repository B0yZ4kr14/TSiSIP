# Brownfield Scan Report — TSiSIP

**Scan Date:** 2026-05-26  
**Scan Scope:** all (Dockerfiles, docker-compose, OpenSIPS config, PostgreSQL schema, tests, documentation, CI/CD)  
**Repository:** /home/b0yz4kr14/Projects/TSiSIP  
**Branch:** main  
**Commit:** `523c5ef` — fix(tests): end-to-end call — unique static IPs per test container  
**Files Scanned:** 34,707  
**Canonical References:** `docs/TSiSIP-CANONICAL-SPEC.md` (v1.1), `AGENTS.md` (2026-05-24), `.github/copilot-instructions.md`

> **Previous Scan:** 2026-05-20 — 16/16 findings addressed (B1–B16). See `reports/brownfield-scan-2026-05-20.md` and `reports/brownfield-scan-2026-05-20-post-remediation.md`.

---

## Executive Summary

| Severity | Count | Status |
|----------|------:|--------|
| CRITICAL | 0 | ✅ No violations of AGENTS.md non-negotiable rules |
| HIGH | 1 | ⚠️ 1 stale documentation file |
| MEDIUM | 5 | ⚠️ 3 security/config issues, 2 shell hardening gaps |
| LOW | 6 | ℹ️ 6 minor style, test, or metadata issues |
| **Total** | **12** | **0 blocking** |

**Overall Assessment:** The TSiSIP codebase is in excellent brownfield health. No CRITICAL findings exist. The single HIGH finding is a stale `.github/copilot-instructions.md` that incorrectly describes the repository as empty. All canonical architecture rules (OpenSIPS 3.6 LTS, PostgreSQL-only, Docker-first, HA1-only auth, explicit rtpengine_offer/answer/delete, topology_hiding("C")) are correctly enforced in implementation.

---

## Findings

| ID | Category | Severity | File | Line | Finding | Recommendation |
|----|----------|----------|------|------|---------|----------------|
| B17 | Documentation Drift | HIGH | `.github/copilot-instructions.md` | 5 | File claims repository is "greenfield: no source files, manifests, README, test configuration, or build system are present yet." This is factually incorrect — all of these exist and are actively maintained. | Update copilot-instructions.md to reflect current repository state, list actual build/test commands from Makefile and CI workflows, and remove the greenfield claim. |
| B18 | Security Surface | MEDIUM | `db/init/03-seed-data.sql` | 87 | Default OCP admin password `admin123!` is embedded as plaintext inside `crypt('admin123!', gen_salt('bf', 12))`. While the stored hash is bcrypt, the plaintext secret is visible in committed SQL. | Remove the plaintext password from the SQL file. Use a placeholder comment instructing operators to generate their own initial password via `psql` or an init script that reads from Docker secrets. |
| B19 | Spec Drift | MEDIUM | `db/init/02-tsisip-extensions.sql` | 17, 33, 52, 115, 169, 239 | `tenant_id` columns use `VARCHAR(36)` instead of `UUID` as specified in the canonical spec (Section 12). The canonical spec defines `tenants.id` as `UUID PRIMARY KEY DEFAULT gen_random_uuid()` and expects foreign keys to match `UUID`. | Align `tenant_id` types with the canonical spec. Either change to `UUID` (requires ensuring `pgcrypto`/`uuid-ossp` availability) or document the `VARCHAR(36)` deviation as an accepted architecture decision in `docs/memory/DECISIONS.md`. |
| B20 | Security Surface | MEDIUM | `db/init/04-ocp-parity-schema.sql` | 255, 280 | Tables `ocp_user_preferences` and `ocp_api_keys` define `password VARCHAR(64) NOT NULL` and `auth_password VARCHAR(64) NOT NULL DEFAULT ''` columns. These are legacy OCP v9 parity tables and may store or accept plaintext credentials. | Audit whether these tables are actively used. If unused, remove the file from `db/init/` or move to `db/init/rollback/`. If used, enforce bcrypt hashing and rename columns to `password_hash` to align with `ocp_users` schema. |
| B21 | Configuration Rot | MEDIUM | `docker/certbot/entrypoint.sh`, `docker/prometheus/entrypoint.sh`, `docker/tailscale_cert/renew.sh`, `docker/admin_api/entrypoint.sh`, `docker/ocp/entrypoint.sh` | 2 | Multiple entrypoint/renew scripts use `set -e` or `set -eu` but lack `set -euo pipefail`. Per AGENTS.md Section 7 shell conventions, bash-available scripts should use `set -euo pipefail`. | Add `set -euo pipefail` to all bash entrypoints. For POSIX `sh` scripts, use `set -eu`. |
| B22 | Configuration Rot | MEDIUM | `docker/backup/healthcheck.sh` | 1 | Healthcheck script lacks any `set -e` or `set -eu` directive. Failures may silently pass. | Add `set -eo pipefail` to the healthcheck script. |
| B23 | Technical Debt | LOW | `tests/vps-stabilization/test-vps-sip.sh` | 117 | Hard-coded test password `password = 'devpass'` in a Python test script. | Parameterize the test password via environment variable with a secure default or read from a test secrets file. |
| B24 | Configuration Rot | LOW | `.env.example` | 8 | `TSISIP_IMAGE_TAG=latest` is the default. While documented as a dev default, the file could more strongly warn against using `latest` in production. | Add an explicit warning comment above the line: "WARNING: Never use 'latest' in production. Pin to a specific version or git SHA." |
| B25 | Anti-Pattern | LOW | `docker/rtpengine/Dockerfile` | 18 | `EXPOSE 22222/udp` exposes the RTPengine ng-control port in Dockerfile metadata. While `EXPOSE` is informational and the port is not published in compose, it signals the control port externally. | Consider removing `EXPOSE 22222/udp` from the Dockerfile or adding a comment clarifying that this port is for internal Docker networks only and must never be host-published. |
| B26 | Anti-Pattern | LOW | `docker/asterisk/Dockerfile` | 69 | `EXPOSE 5038/tcp` exposes the Asterisk AMI management port in Dockerfile metadata. The container attaches only to `sip_internal`, but the EXPOSE directive is misleading. | Remove `5038/tcp` from `EXPOSE` or add a comment clarifying that AMI is for internal use only and must not be exposed externally. |
| B27 | Configuration Rot | LOW | `docker-compose.vps.yml` | 105 | RTPengine `--interface` falls back to `127.0.0.1` if `RTPENGINE_PRIVATE_IP` is unset: `${RTPENGINE_PRIVATE_IP:-127.0.0.1}`. Loopback binding for RTPengine interface is non-functional for media relay. | Remove the `:-127.0.0.1` fallback and require the variable to be set explicitly, or fail fast in the entrypoint if `RTPENGINE_PRIVATE_IP` is missing. |
| B28 | Technical Debt | LOW | Multiple scripts | — | Multiple TODO/FIXME markers exist in `.specify/` extension files (third-party Speckit tooling), not in TSiSIP application code. These are noise in brownfield scans. | Exclude `.specify/`, `.omk/`, `.claude/`, `.kimi/`, and `.agents/` from automated brownfield scans by adding `--exclude-dir` filters to `scripts/ci-scan.sh`. |

---

## Detailed Analysis by Category

### A. Spec Drift Scan

**Canonical Spec Compliance:**

| Rule | Status | Evidence |
|------|--------|----------|
| OpenSIPS 3.6 LTS baseline | ✅ PASS | `Dockerfile` uses `ARG OPENSIPS_VERSION=3.6`; `opensips/opensips.cfg.tpl` references 3.6 modules |
| `db_postgres` only | ✅ PASS | Zero `db_mysql`/`db_sqlite` references in application code; CI explicitly scans for these |
| Docker-first delivery | ✅ PASS | All services build from committed Dockerfiles; no bare-metal install docs in canonical path |
| `calculate_ha1 = 0` | ✅ PASS | `opensips/opensips.cfg.tpl:95` — `modparam("auth_db", "calculate_ha1", 0)` |
| `password_column = "ha1"` | ✅ PASS | `opensips/opensips.cfg.tpl:97` — `modparam("auth_db", "password_column", "ha1")` |
| `topology_hiding("C")` | ✅ PASS | `opensips/opensips.cfg.tpl:575`, `718` — explicit `"C"` flag |
| Explicit rtpengine_offer/answer/delete | ✅ PASS | No `rtpengine_manage()` in `opensips.cfg.tpl`; explicit functions in routes `HANDLE_INVITE`, `SRTP_REOFFER`, `REPLY_MANAGE`, `FAILURE_MANAGE` |
| No hard-coded `ds_select_dst(1, ...)` | ✅ PASS | Dispatcher set derived from `$var(ds_set)` populated by `header_routing_rules` or subscriber `routing_group` |
| `mf_process_maxfwd_header(70)` | ✅ PASS | `opensips/opensips.cfg.tpl:291` |
| No `sanity` module | ✅ PASS | CI gate explicitly rejects `loadmodule "sanity.so"`; zero references in configs |
| Network names snake_case | ✅ PASS | `sip_edge`, `sip_internal`, `db_internal`, `metrics_host` |

**Drift Detected:**
- **B19:** `tenant_id` uses `VARCHAR(36)` instead of `UUID` in multiple tables. The canonical spec Section 12 defines `tenants.id UUID PRIMARY KEY DEFAULT gen_random_uuid()` and all foreign keys as `UUID`. The drift was introduced to avoid bootstrap ordering issues (stock schema runs before tenant table creation), but it is undocumented.

### B. Technical Debt Scan

**Debt Markers:**

| Marker | Count | Location | Assessment |
|--------|------:|----------|------------|
| `TODO` | 0 | TSiSIP application code | ✅ Clean |
| `FIXME` | 0 | TSiSIP application code | ✅ Clean |
| `HACK` | 0 | TSiSIP application code | ✅ Clean |
| `XXX` | 0 | TSiSIP application code | ✅ Clean |
| `sleep` | 22 | Tests, deploy scripts, entrypoints | ℹ️ All have context (health probes, stack startup, cert rotation intervals). Acceptable. |

**Commented-out code blocks > 3 lines:** None found in application code.

### C. Anti-Pattern Scan

| Anti-Pattern | Status | Evidence |
|--------------|--------|----------|
| Plaintext `subscriber.password` population | ✅ PASS | `db/init/03-seed-data.sql` populates `password` as `''` and fills `ha1`, `ha1_sha256`, `ha1_sha512t256` |
| Missing TLS CA directory | ✅ PASS | `tls_mgm` module configures `ca_list`, `certificate`, `private_key` with `/certs/live/` paths |
| Host-published ports for Asterisk | ✅ PASS | No `ports:` stanza on any `asterisk_*` service; only `expose:` (informational) |
| Host-published ports for PostgreSQL | ✅ PASS | No `ports:` on `postgres` service |
| RTPengine control port exposed externally | ✅ PASS | `--listen-ng` binds to `${RTPENGINE_INTERNAL_IP}:22222`; not in `ports:` |
| Hard-coded dispatcher-only routing | ✅ PASS | `ds_select_dst($var(ds_set), 4, "f")` uses dynamic `$var(ds_set)` |
| Loopback RTPengine control | ✅ PASS | No `127.0.0.1:22222` in compose; uses `${RTPENGINE_INTERNAL_IP}` |
| Missing `maxfwd`/`rr` module | ✅ PASS | Both loaded and configured (`modparam("maxfwd", "max_limit", 70)`, `modparam("rr", "enable_double_rr", 1)`) |

### D. Configuration Rot Scan

| Check | Status | Evidence |
|-------|--------|----------|
| Env vars in compose with `.env.example` entry | ✅ PASS | All `${...}` variables in compose are documented in `.env.example` |
| Stale volume mounts | ✅ PASS | All volume mounts point to existing paths |
| Duplicate/conflicting env vars | ✅ PASS | No duplicates detected across compose files |
| Missing health checks | ⚠️ PARTIAL | `docker-compose.build.yml` (13 services) lacks healthchecks — acceptable as build-only overlay. All runtime compose files have healthchecks. |
| Image tags using `latest` | ⚠️ PARTIAL | `.env.example` defaults to `latest` for dev; production uses `${TSISIP_IMAGE_TAG:?must be set}` with GHCR SHA tags. Documented policy. |
| Services without `restart` policy | ✅ PASS | All runtime services define `restart`. `docker-compose.build.yml` services lack it (expected for build overlay). |

### E. Security Surface Scan

| Check | Status | Evidence |
|-------|--------|----------|
| Secrets in committed files | ✅ PASS | `secrets/` is `.gitignore`d; CI explicitly checks for committed secrets |
| Self-signed certs without rotation | ✅ PASS | `certbot` and `ca-tool` services handle automated rotation; `tailscale_cert` for Tailscale certs |
| Weak default passwords | ⚠️ PARTIAL | `db/init/03-seed-data.sql` contains `crypt('admin123!', ...)` — plaintext visible in committed seed data (B18) |
| Missing rate limiting | ✅ PASS | `pike` (IP throttling), `ratelimit` (auth/global anomaly), `userblacklist` (per-user bans) all configured |
| Missing backup encryption | ✅ PASS | `ENCRYPTION_KEY_FILE` mounted; AES-256-CBC + PBKDF2 + HMAC-SHA256 per Feature 005 |
| Exposed management interfaces | ✅ PASS | OCP on loopback-only (`127.0.0.1:8084:80` in VPS); Prometheus/Alertmanager on internal networks |

### F. Dockerfile Anti-Patterns

| Dockerfile | Base Image | USER | Anti-Pattern | Assessment |
|------------|------------|------|--------------|------------|
| `Dockerfile` | `debian:bookworm-slim@sha256:...` | root (drops privs) | None | ✅ Clean |
| `docker/admin_api/Dockerfile` | `php:8.2-apache@sha256:...` | root | None | ✅ Clean |
| `docker/anomaly_detector/Dockerfile` | `python:3.12-slim@sha256:...` | `detector` | None | ✅ Clean |
| `docker/asterisk/Dockerfile` | `debian:bookworm-slim@sha256:...` | root | `EXPOSE 5038/tcp` (B26) | ℹ️ LOW |
| `docker/backup/Dockerfile` | `postgres:16@sha256:...` | root | None | ✅ Clean |
| `docker/ca-tool/Dockerfile` | `alpine:3.19@sha256:...` | root | None | ✅ Clean |
| `docker/certbot/Dockerfile` | `certbot/certbot:v5.6.0@sha256:...` | root | None | ✅ Clean |
| `docker/certbot_exporter/Dockerfile` | `python:3.11-slim@sha256:...` | `certbot-exporter` | None | ✅ Clean |
| `docker/grafana/Dockerfile` | `grafana/grafana:10.4.0@sha256:...` | `nobody` | None | ✅ Clean |
| `docker/ocp/Dockerfile` | `php:8.2-apache-bookworm@sha256:...` | root | None | ✅ Clean |
| `docker/opensips_exporter/Dockerfile` | `python:3.11-slim-bookworm@sha256:...` | `exporter` | None | ✅ Clean |
| `docker/postgres/Dockerfile` | `postgres:16@sha256:...` | root | None | ✅ Clean |
| `docker/prometheus/Dockerfile` | `prom/prometheus:v2.51.0@sha256:...` | `nobody` | None | ✅ Clean |
| `docker/rtpengine/Dockerfile` | `debian:bookworm-slim@sha256:...` | root | `EXPOSE 22222/udp` (B25) | ℹ️ LOW |
| `docker/tailscale_cert/Dockerfile` | `tailscale/tailscale:v1.96.5@sha256:...` | root | None | ✅ Clean |

**All base images are pinned to SHA256 digests** — excellent practice.

### G. Docker Compose Drift

| File | Profile | Services | Healthchecks | Published Ports | Assessment |
|------|---------|----------|--------------|-----------------|------------|
| `docker-compose.yml` | Full dev | 16 | ✅ All | OpenSIPS 5060/udp,tcp,5061/tcp; RTPengine 10000-20000/udp; anomaly_detector 127.0.0.1:8082 | ✅ Clean |
| `docker-compose.prod.yml` | Production | 14 | ✅ All | Same as above + anomaly_detector 127.0.0.1:8080 | ✅ Clean |
| `docker-compose.vps.yml` | VPS-lite | 10 | ✅ All | OpenSIPS 5060/udp,tcp,5061/tcp; RTPengine 10000-10999/udp; OCP 127.0.0.1:8084 | ✅ Clean |
| `docker-compose.vps.override.yml` | VPS test override | 9 | N/A (overlay) | None | ✅ Clean |
| `docker-compose.build.yml` | Build overlay | 13 | ❌ None | None | ℹ️ Acceptable — build-only |
| `docker-compose.monitoring.yml` | Monitoring overlay | 4 | ✅ All | None (internal networks) | ✅ Clean |

**Isolation Checks:**
- ✅ `asterisk_pbx_1` and `asterisk_pbx_2`: no `ports:`; only `sip_internal`
- ✅ `postgres`: no `ports:`; only `db_internal` (plus `metrics_host` in VPS)
- ✅ `rtpengine`: `--listen-ng` binds to `${RTPENGINE_INTERNAL_IP}:22222`, not `0.0.0.0`

### H. OpenSIPS Config Anti-Patterns

| Check | Status | Evidence |
|-------|--------|----------|
| Forbidden modules (`sanity`, `db_mysql`) | ✅ PASS | Not loaded; CI rejects them |
| `rtpengine_manage()` | ✅ PASS | Not used |
| `topology_hiding("U")` | ✅ PASS | Uses `"C"` |
| `calculate_ha1 = 1` | ✅ PASS | Uses `0` |
| `password_column = "password"` | ✅ PASS | Uses `"ha1"` |
| Hard-coded `ds_select_dst(1, ...)` | ✅ PASS | Dynamic `$var(ds_set)` |
| Missing auth for non-OPTIONS | ✅ PASS | All non-OPTIONS initial requests route through `route(AUTH)` |
| Credential stripping | ✅ PASS | `remove_hf("Authorization")` and `remove_hf("Proxy-Authorization")` in `route[RELAY]` |
| Header sanitization | ✅ PASS | `route[SANITIZE]` removes `P-Asserted-Identity`, `P-Preferred-Identity`, `X-Tenant-ID`, `X-Backend-ID`, `X-Route-Override` |

### I. PostgreSQL Schema Inconsistencies

| Table/Column | Canonical Spec | Actual Schema | Drift |
|--------------|----------------|---------------|-------|
| `tenants.id` | `UUID PRIMARY KEY DEFAULT gen_random_uuid()` | `UUID PRIMARY KEY DEFAULT gen_random_uuid()` | ✅ Match |
| `subscriber.tenant_id` | `UUID NOT NULL REFERENCES tenants(id)` | `VARCHAR(36) NOT NULL DEFAULT '00000000-...'` | ⚠️ B19 |
| `header_routing_rules.tenant_id` | `UUID NOT NULL REFERENCES tenants(id)` | `VARCHAR(36) NOT NULL` | ⚠️ B19 |
| `pbx_backends.tenant_id` | `UUID NOT NULL REFERENCES tenants(id)` | `VARCHAR(36) NOT NULL` | ⚠️ B19 |
| `cdr.tenant_id` | `VARCHAR(36)` (not in spec) | `VARCHAR(36)` | ℹ️ Acceptable — CDR is append-only |
| `trunk_ips.tenant_id` | `UUID` (not in spec) | `UUID` | ✅ Match |
| `sip_trunk_did_mappings.tenant_id` | `UUID NOT NULL REFERENCES tenants(id)` | `UUID NOT NULL REFERENCES tenants(id)` | ✅ Match |

**Note:** The `VARCHAR(36)` drift in `subscriber`, `header_routing_rules`, and `pbx_backends` exists because the stock OpenSIPS schema (`01-stock-opensips-schema.sql`) runs before `tenants` is created, making a strict `UUID` foreign key impossible during bootstrap. The drift is pragmatic but undocumented.

### J. Test Files Deprecated Patterns

| Test File | Pattern | Assessment |
|-----------|---------|------------|
| `tests/d3-jquery-coexistence.test.js` | ES module scope isolation | ✅ Current |
| `tests/accessibility-audit.test.js` | WCAG 2.1 AA checks | ✅ Current |
| `tests/integration/*.py` | pytest + Python sockets | ✅ Current |
| `tests/vps-stabilization/test-vps-sip.sh` | Hard-coded `password = 'devpass'` | ⚠️ B23 |

### K. Documentation Stale References

| Document | Stale Claim | Impact |
|----------|-------------|--------|
| `.github/copilot-instructions.md` | "greenfield: no source files, manifests, README, test configuration, or build system are present yet" | **HIGH** — Misguides Copilot and any AI agent using it as primary context. | `AGENTS.md` | Claims 24 specs (001–024) but directory listing shows specs up to 022 only. Missing 023 and 024 directories. | ℹ️ LOW — Cosmetic; may indicate incomplete spec migration. |

### L. CI/CD Outdated Practices

| Check | Status | Evidence |
|-------|--------|----------|
| `actions/checkout` version | ✅ v4 | `.github/workflows/ci.yml`, `deploy.yml` |
| `docker/setup-buildx-action` | ✅ v3 | `.github/workflows/ci.yml`, `deploy.yml` |
| `github/codeql-action/upload-sarif` | ✅ v3 | `.github/workflows/ci.yml` |
| `aquasecurity/trivy-action` | ✅ 0.28.0 | `.github/workflows/ci.yml` |
| `webfactory/ssh-agent` | ✅ v0.9.0 | `.github/workflows/deploy.yml` |
| `anchore/sbom-action` | ✅ v0 | `.github/workflows/ci.yml` |
| `actions/attest-build-provenance` | ✅ v1 | `.github/workflows/ci.yml` |
| Trivy vulnerability scanning | ✅ Active | Scans all images for HIGH/CRITICAL CVEs before push |
| SLSA provenance attestation | ✅ Active | Signs build provenance |
| SBOM generation | ✅ Active | Generates SPDX JSON for OpenSIPS and OCP images |
| VEX generation/validation | ✅ Active | Validates OpenVEX structure and design decisions |
| Secret leak detection | ✅ Active | CI checks for committed secrets and plaintext passwords |

**CI/CD is modern and well-hardened.** No outdated action versions detected.

---

## Summary by Severity

- **Critical:** 0
- **High:** 1
- **Medium:** 5
- **Low:** 6

## Top 3 Action Items

1. **Update `.github/copilot-instructions.md`** (HIGH — B17): Remove the stale "greenfield" claim and document the actual build/test/deploy commands from the committed Makefile and CI workflows. This file is the primary context source for GitHub Copilot and is currently misleading.

2. **Remove plaintext default password from seed data** (MEDIUM — B18): The `crypt('admin123!', ...)` in `db/init/03-seed-data.sql` embeds a plaintext default password. Replace with a comment instructing operators to set their own initial password, or generate it via an external init script that reads from Docker secrets.

3. **Document or resolve schema drift** (MEDIUM — B19): The `VARCHAR(36)` vs `UUID` discrepancy for `tenant_id` foreign keys should be documented in `docs/memory/DECISIONS.md` as an accepted bootstrap-ordering deviation, or aligned with the canonical spec if feasible.

---

## Appendix: Methodology

This scan was performed using the `speckit-brownfield-scan` skill with the following detection passes:

1. **Spec Drift Scan:** Compared `Dockerfile`, `opensips/opensips.cfg.tpl`, and compose files against `docs/TSiSIP-CANONICAL-SPEC.md` Sections 2, 4, 5, 6, 7, 8, 12, 13, 14, and 20.
2. **Technical Debt Scan:** Searched for `TODO`, `FIXME`, `HACK`, `XXX`, hard-coded values, commented-out code blocks, and `sleep` statements across all source files.
3. **Anti-Pattern Scan:** Checked for forbidden modules, plaintext passwords, exposed backend ports, loopback RTPengine control, hard-coded dispatcher routing, and missing `maxfwd`/`rr` usage.
4. **Configuration Rot Scan:** Validated env var documentation, volume mount existence, duplicate env vars, missing health checks, image tag pinning, and restart policies.
5. **Security Surface Scan:** Checked for committed secrets, weak defaults, missing rate limiting, missing backup encryption, and exposed management interfaces.
6. **Dockerfile Scan:** Reviewed all 16 Dockerfiles for base image pinning, USER directives, package installation hygiene, and EXPOSE anti-patterns.
7. **Compose Drift Scan:** Compared all 6 compose files for service consistency, network isolation, port publishing compliance, and healthcheck coverage.
8. **OpenSIPS Config Scan:** Validated module list, parameter values, routing logic, auth contract, and RTP relay contract against canonical spec.
9. **PostgreSQL Schema Scan:** Compared `db/init/*.sql` against canonical spec Section 12 for column types, indexes, and foreign keys.
10. **Test File Scan:** Checked for deprecated testing patterns and hard-coded credentials.
11. **Documentation Scan:** Searched for stale version references, greenfield claims, and outdated architecture guidance.
12. **CI/CD Scan:** Validated GitHub Actions workflow versions, security scanning coverage, and artifact generation.

---

*Report generated by Kimi Code CLI using speckit-brownfield-scan skill.*  
*Previous remediation cycle: B1–B16 (COMPLETE). See `reports/brownfield-scan-2026-05-20-post-remediation.md`.*
