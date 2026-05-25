# TSiSIP Version Guard Report

**Baseline:** `docs/TSiSIP-CANONICAL-SPEC.md`  
**Strict Mode:** `ON`  
**Format:** Markdown  
**Generated:** 2026-05-19  
**Project:** `/home/b0yz4kr14/Projects/TSiSIP`

---

## 1. Docker Base Images

| ID | Component | Dockerfile | Image Reference | Status | Severity | Notes |
|----|-----------|------------|-----------------|--------|----------|-------|
| V1 | OpenSIPS (builder) | `Dockerfile` | `debian:bookworm-slim@sha256:67b30a...` | **PASS** | — | Pinned to SHA256 digest |
| V2 | OpenSIPS (runtime) | `Dockerfile` | `debian:bookworm-slim@sha256:67b30a...` | **PASS** | — | Pinned to SHA256 digest |
| V3 | PostgreSQL | `docker/postgres/Dockerfile` | `postgres:16@sha256:b6ccf0...` | **PASS** | — | Pinned to SHA256 digest; matches spec |
| V4 | RTPengine | `docker/rtpengine/Dockerfile` | `debian:bookworm-slim@sha256:67b30a...` | **PASS** | — | Pinned to SHA256 digest |
| V5 | Asterisk (builder) | `docker/asterisk/Dockerfile` | `debian:bookworm-slim@sha256:67b30a...` | **PASS** | — | Pinned to SHA256 digest |
| V6 | Asterisk (runtime) | `docker/asterisk/Dockerfile` | `debian:bookworm-slim@sha256:67b30a...` | **PASS** | — | Pinned to SHA256 digest |
| V7 | OCP | `docker/ocp/Dockerfile` | `php:8.2-apache-bookworm@sha256:5e0eab...` | **PASS** | — | Pinned to SHA256 digest; PHP 8.2 matches spec |
| V8 | Prometheus (builder) | `docker/prometheus/Dockerfile` | `alpine:3.19@sha256:6baf43...` | **PASS** | — | Pinned to SHA256 digest |
| V9 | Prometheus (runtime) | `docker/prometheus/Dockerfile` | `prom/prometheus:v2.51.0@sha256:5ccad4...` | **PASS** | — | Pinned to SHA256 digest |
| V10 | Grafana | `docker/grafana/Dockerfile` | `grafana/grafana:10.4.0@sha256:f9811e...` | **PASS** | — | Pinned to SHA256 digest |
| V11 | Backup | `docker/backup/Dockerfile` | `postgres:16@sha256:b6ccf0...` | **PASS** | — | Pinned to SHA256 digest; consistent with PostgreSQL image |
| V12 | Anomaly Detector | `docker/anomaly-detector/Dockerfile` | `python:3.12-slim@sha256:401f6e...` | **PASS** | — | Pinned to SHA256 digest |
| V13 | Certbot | `docker/certbot/Dockerfile` | `certbot/certbot@sha256:0107d0...` | **FAIL** | **HIGH** | No version tag — only digest. Cannot determine semantic version from tag alone |
| V14 | OpenSIPS Exporter | `docker/opensips-exporter/Dockerfile` | `python:3.11-slim-bookworm@sha256:cd6733...` | **PASS** | — | Pinned to SHA256 digest |
| V15 | Certbot Exporter | `docker/certbot-exporter/Dockerfile` | `python:3.11-slim@sha256:2c285c...` | **PASS** | — | Pinned to SHA256 digest |
| V16 | Admin API | `docker/admin-api/Dockerfile` | `php:8.2-apache@sha256:bc7a50...` | **PASS** | — | Pinned to SHA256 digest; PHP 8.2 matches spec |
| V17 | CA Tool | `docker/ca-tool/Dockerfile` | `alpine:3.19@sha256:6baf43...` | **PASS** | — | Pinned to SHA256 digest |
| V18 | Tailscale Cert | `docker/tailscale-cert/Dockerfile` | `tailscale/tailscale@sha256:dbeff0...` | **FAIL** | **HIGH** | No version tag — only digest. Cannot determine semantic version from tag alone |

---

## 2. Docker Compose External Images

| ID | Service | Compose File | Image Reference | Status | Severity | Notes |
|----|---------|--------------|-----------------|--------|----------|-------|
| V19 | postgres-exporter | `docker-compose.yml` | `prometheuscommunity/postgres-exporter:v0.15.0@sha256:386b12...` | **PASS** | — | Pinned to SHA256 digest |
| V20 | node-exporter | `docker-compose.yml` | `prom/node-exporter:v1.8.0@sha256:8a57af...` | **PASS** | — | Pinned to SHA256 digest |
| V21 | alertmanager | `docker-compose.yml` | `prom/alertmanager:v0.27.0` (no digest) | **FAIL** | **MEDIUM** | Tag present but missing SHA256 digest |
| V22 | alertmanager | `docker-compose.prod.yml` | `prom/alertmanager:v0.27.0@sha256:e13b6e...` | **PASS** | — | Pinned to SHA256 digest |

---

## 3. Source Build / Git References

| ID | Component | Location | Reference | Status | Severity | Notes |
|----|-----------|----------|-----------|--------|----------|-------|
| V23 | OpenSIPS source | `Dockerfile` line 19 | `git clone --depth 1 --branch 3.6` | **FAIL** | **MEDIUM** | Floating branch reference — not pinned to specific commit or tag. Build reproducibility depends on upstream HEAD of 3.6 branch |

---

## 4. Language Dependencies

### 4.1 Python (anomaly-detector)

| ID | Package | Declared Version | Status | Severity | Notes |
|----|---------|------------------|--------|----------|-------|
| V24 | redis | `==5.0.8` | **PASS** | — | Exact pin |
| V25 | prometheus-client | `==0.20.0` | **PASS** | — | Exact pin |
| V26 | flask | `==3.0.3` | **PASS** | — | Exact pin |
| V27 | numpy | `==1.26.4` | **PASS** | — | Exact pin |
| V28 | requests | `==2.32.3` | **PASS** | — | Exact pin |

### 4.2 Python (opensips-exporter)

| ID | Package | Declared Version | Status | Severity | Notes |
|----|---------|------------------|--------|----------|-------|
| V29 | prometheus-client | `==0.20.0` | **PASS** | — | Exact pin |
| V30 | requests | `==2.31.0` | **PASS** | — | Exact pin |

### 4.3 Python (certbot-exporter)

| ID | Package | Declared Version | Status | Severity | Notes |
|----|---------|------------------|--------|----------|-------|
| V31 | prometheus_client | `==0.20.0` | **PASS** | — | Exact pin |

### 4.4 Node.js / npm

No project-local `package.json`, `.nvmrc`, or lockfiles found outside agent-orchestration directories (`.omk`, `.opencode`, `.specify`, `.squad`). TSiSIP frontend tests (`tests/d3-jquery-coexistence.test.js`, `tests/accessibility-audit.test.js`) are executed directly via `node` without a tracked dependency manifest.

| ID | Check | Status | Severity | Notes |
|----|-------|--------|----------|-------|
| V32 | Node.js dependency manifest | **WARN** | **LOW** | No root `package.json` or lockfile for test/runtime dependencies. Tests appear to be standalone scripts |

---

## 5. OS Packages (APT / APK)

Security-critical packages flagged when installed without version pin:

| ID | Dockerfile | Package Manager | Package | Status | Severity | Notes |
|----|------------|-----------------|---------|--------|----------|-------|
| V33 | `docker/rtpengine/Dockerfile` | `apt-get` | `rtpengine` | **FAIL** | **MEDIUM** | Security-critical media relay package installed without version pin |
| V34 | `docker/ocp/Dockerfile` | `apt-get` | `postgresql-client` | **FAIL** | **MEDIUM** | Security-critical DB client installed without version pin |
| V35 | `docker/backup/Dockerfile` | `apt-get` | `openssl` | **FAIL** | **MEDIUM** | Security-critical crypto package installed without version pin |
| V36 | `docker/ca-tool/Dockerfile` | `apk` | `openssl` | **FAIL** | **MEDIUM** | Security-critical crypto package installed without version pin |
| V37 | `docker/asterisk/Dockerfile` (runtime) | `apt-get` | `libssl3` | **WARN** | **LOW** | Runtime SSL library without version pin (Debian stable baseline) |
| V38 | `docker/anomaly-detector/Dockerfile` | `apt-get` | `curl` | **WARN** | **LOW** | Utility package without version pin |
| V39 | `docker/admin-api/Dockerfile` | `apt-get` | `libpq-dev` | **WARN** | **LOW** | Build/dev package without version pin |

---

## 6. Git Submodules / External Sources

| ID | Check | Status | Notes |
|----|-------|--------|-------|
| V40 | Git submodules configured | **PASS** | No `.gitmodules` file at project root; `git submodule status` returns empty |
| V41 | Floating git clone references | **FAIL** | OpenSIPS cloned from `https://github.com/OpenSIPS/opensips.git` with `--branch 3.6 --depth 1`. Not pinned to tag or commit hash. See V23 |

---

## 7. Consistency Issues

| ID | Finding | Files Affected | Severity |
|----|---------|----------------|----------|
| C1 | **Alertmanager digest mismatch** | `docker-compose.yml` uses `prom/alertmanager:v0.27.0` (no digest); `docker-compose.prod.yml` uses `prom/alertmanager:v0.27.0@sha256:e13b6e...` (with digest) | **MEDIUM** |
| C2 | **Requests library version drift** | `docker/opensips-exporter/Dockerfile` installs `requests==2.31.0`; `docker/anomaly-detector/requirements.txt` pins `requests==2.32.3` | **LOW** |
| C3 | **OpenSIPS source not reproducible** | `Dockerfile` clones `3.6` branch with `--depth 1`. The same commit is not guaranteed across builds | **MEDIUM** |
| C4 | **Debian base digest consistency** | All Debian-based images (`Dockerfile`, `docker/rtpengine/Dockerfile`, `docker/asterisk/Dockerfile`) share the same `debian:bookworm-slim@sha256:67b30a...` digest | **PASS** |
| C5 | **PostgreSQL image consistency** | `docker/postgres/Dockerfile` and `docker/backup/Dockerfile` both use `postgres:16@sha256:b6ccf0...` | **PASS** |
| C6 | **PHP version consistency** | `docker/ocp/Dockerfile` (`php:8.2-apache-bookworm`) and `docker/admin-api/Dockerfile` (`php:8.2-apache`) both use PHP 8.2 | **PASS** |
| C7 | **OpenSIPS version vs spec** | `ARG OPENSIPS_VERSION=3.6` in `Dockerfile` matches canonical spec requirement for OpenSIPS 3.6 LTS | **PASS** |
| C8 | **Asterisk version vs spec** | `ARG ASTERISK_VERSION=20.9.3` in `docker/asterisk/Dockerfile` is pinned; spec does not mandate a specific Asterisk version | **PASS** |
| C9 | **Prometheus client consistency** | All Python services use `prometheus-client`/`prometheus_client` `0.20.0` | **PASS** |
| C10 | **Compose image tag variable** | All TSiSIP project images in compose files reference `${TSISIP_IMAGE_TAG:?must be set}` — no hard-coded `:latest` or floating tags | **PASS** |

---

## 8. Forbidden Patterns Check

| ID | Pattern | Status | Notes |
|----|---------|--------|-------|
| F1 | `:latest` tag in production Dockerfiles / compose | **PASS** | No `:latest` tags found in canonical project files |
| F2 | OpenSIPS < 3.6 | **PASS** | Baseline is 3.6 LTS |
| F3 | `db_mysql` / MySQL / MariaDB references | **PASS** | Canonical PostgreSQL only |
| F4 | Bare-metal / VM-first runtime instructions | **PASS** | Docker-first as required |

---

## 9. Summary

| Category | Count |
|----------|-------|
| **Passed** | 29 |
| **Failed (Critical)** | 0 |
| **Failed (High)** | 2 |
| **Failed (Medium)** | 6 |
| **Warnings (Low)** | 4 |

### Strict Mode Result

Because `--strict` is enabled, **warnings are treated as failures**.

- **Total blocking findings:** **12** (2 High + 6 Medium + 4 Low)
- **Gate status:** **FAIL**

---

## 10. Next Actions

### High Priority

1. **Add version tags to Certbot and Tailscale images**
   - `docker/certbot/Dockerfile`: Change `FROM certbot/certbot@sha256:...` to `FROM certbot/certbot:<version>@sha256:...`
   - `docker/tailscale-cert/Dockerfile`: Change `FROM tailscale/tailscale@sha256:...` to `FROM tailscale/tailscale:<version>@sha256:...`
   - Verify the current digest maps to a specific upstream tag and pin it

### Medium Priority

2. **Pin OpenSIPS source to a specific commit or tag**
   - In `Dockerfile`, replace `git clone --depth 1 --branch ${OPENSIPS_VERSION} ...` with a clone of a specific annotated tag (e.g., `3.6.5`) or pin to a commit hash
   - If maintaining branch-level updates is intentional, document the trade-off in an ADR

3. **Add SHA256 digest to alertmanager in `docker-compose.yml`**
   - Align `docker-compose.yml` with `docker-compose.prod.yml` by adding `@sha256:e13b6ed5cb929eeaee733479dce55e10eb3bc2e9c4586c705a4e8da41e5eacf5` (or current verified digest)

4. **Pin security-critical OS packages in Dockerfiles**
   - `docker/rtpengine/Dockerfile`: Pin `rtpengine` to a specific Debian package version (e.g., `rtpengine=XX.Y.Z-...`)
   - `docker/ocp/Dockerfile`: Pin `postgresql-client` to a specific version (e.g., `postgresql-client=16+...`)
   - `docker/backup/Dockerfile`: Pin `openssl` to a specific version (e.g., `openssl=3.0.X-...`)
   - `docker/ca-tool/Dockerfile`: Pin `openssl` to a specific Alpine package version (e.g., `openssl=3.1.X-r...`)

### Low Priority

5. **Align `requests` library version across Python services**
   - Choose a single version (recommend `2.32.3` as the newer patch) and update `docker/opensips-exporter/Dockerfile`

6. **Consider pinning runtime APT packages in Asterisk image**
   - `docker/asterisk/Dockerfile` runtime stage installs `libssl3`, `libncurses6`, etc. without versions. While Debian stable reduces drift, explicit pins improve reproducibility

7. **Add a root `package.json` or test dependency manifest**
   - If frontend tests acquire runtime dependencies beyond Node.js builtins, commit a `package.json` with pinned `devDependencies` and a `package-lock.json`

---

*Report generated by speckit-version-guard*  
*Baseline: docs/TSiSIP-CANONICAL-SPEC.md (Canonical spec version: 1.1, Last Updated: 2026-05-19)*
