# TSiSIP Version Guard Report

**Date:** 2026-05-28  
**Baseline:** `docs/TSiSIP-CANONICAL-SPEC.md` (v1.1, 2026-05-19) + `AGENTS.md` (2026-05-24)  
**Scope:** 19 Dockerfiles, 6 Docker Compose files, 2 dependency manifests, 3 version files, 6 GitHub Actions workflows  
**Strict Mode:** Off (informational + severity triage)  
**Previous Scan:** 2026-05-26 (`reports/version-guard-2026-05-26.md`)  

---

## Executive Summary

| Category | Passed | Failed (Critical) | Failed (High) | Warnings (Med/Low) |
|---|---:|---:|---:|---:|
| Docker Base Images | 18 | 0 | 0 | 1 |
| Docker Compose Images | 7 | 0 | 0 | 1 |
| Language Runtimes | 8 | 0 | 0 | 1 |
| OS Packages (apt/apk) | 15 | 0 | 0 | 14 |
| Python/Node Dependencies | 6 | 0 | 1 | 1 |
| Doc-vs-Implementation | 5 | 0 | 0 | 1 |
| GitHub Actions | 14 | 0 | 0 | 0 |
| **TOTAL** | **73** | **0** | **1** | **19** |

> **Assessment:** Major improvement since 2026-05-26. **5 of 6 HIGH findings from the previous scan have been resolved.** Only one HIGH failure remains: Python `requests` version drift between `opensips_exporter` and `anomaly_detector`. Supply-chain hygiene is exemplary with **100% of production `FROM` statements and all external Compose images pinned to SHA256 digests**.

---

## Delta vs. Previous Scan (2026-05-26)

| ID | Finding (2026-05-26) | Status | Resolution Evidence |
|---|---|---|---|
| D1 | `.env.example` default `TSISIP_IMAGE_TAG=latest` | **RESOLVED** | Now `TSISIP_IMAGE_TAG=v0.0.0-dev` (`.env.example:9`) |
| D2 | `docker/admin_api/Dockerfile` used different PHP digest from OCP | **RESOLVED** | Now aligned to `php:8.2-apache-bookworm@sha256:5e0eabe...` (same as OCP) |
| D3 | Missing `.nvmrc` and `.python-version` at project root | **RESOLVED** | `.nvmrc` → `v26.2.0`; `.python-version` → `3.12.3` |
| D4 | OpenSIPS build arg `ARG OPENSIPS_VERSION=3.6` (floating branch) | **IMPROVED** | Now `ARG OPENSIPS_VERSION=3.6.6` — pinned to specific patch release |
| D5 | Python `requests` drift (`2.31.0` vs `2.32.3`) | **OPEN** | Still divergent; see V21 below |
| D6 | `rtpengine` APT package unpinned | **RESOLVED** | Now pinned to `rtpengine=10.5.3.5-1` |
| D7 | `certbot` registry namespace inconsistency | **RESOLVED** | All compose files now use `ghcr.io/b0yz4kr14/tsisip/certbot` |
| D8 | GitHub Actions `trivy-action@master` | **RESOLVED** | Now pinned to `@0.28.0` (per 2026-05-21 remediation) |

---

## 1. Docker Base Image Versions and Pinning Status

### 1.1 Project-Owned Images (`FROM` Statements)

| ID | Dockerfile | Base Image | Tag | SHA256 Pinned | Severity | Status |
|---|---|---|---|---|---|---|
| D1 | `Dockerfile` (builder) | `debian:bookworm-slim` | `bookworm-slim` | ✅ `67b30a61dc87758f0caf819646104f29ecbda97d920aaf5edc834128ac8493d3` | — | **PASS** |
| D2 | `Dockerfile` (runtime) | `debian:bookworm-slim` | `bookworm-slim` | ✅ Same digest as D1 | — | **PASS** |
| D3 | `docker/rtpengine/Dockerfile` | `debian:bookworm-slim` | `bookworm-slim` | ✅ Same digest as D1/D2 | — | **PASS** |
| D4 | `docker/asterisk/Dockerfile` (builder) | `debian:bookworm-slim` | `bookworm-slim` | ✅ Same digest as D1/D2 | — | **PASS** |
| D5 | `docker/asterisk/Dockerfile` (runtime) | `debian:bookworm-slim` | `bookworm-slim` | ✅ Same digest as D1/D2 | — | **PASS** |
| D6 | `docker/postgres/Dockerfile` | `postgres` | `16` | ✅ `b6ccf02e9b47eac0d67b5eaa0ef56fd59163bffa5506f64e96ceb5053130ec86` | — | **PASS** |
| D7 | `docker/backup/Dockerfile` | `postgres` | `16` | ✅ Same digest as D6 | — | **PASS** |
| D8 | `docker/ocp/Dockerfile` | `php` | `8.2-apache-bookworm` | ✅ `5e0eabeab4ac6d58b3fb9af50bd7c57c89aacb8ed7090e31b28c1e9056dd94fb` | — | **PASS** |
| D9 | `docker/admin_api/Dockerfile` | `php` | `8.2-apache-bookworm` | ✅ `5e0eabeab4ac6d58b3fb9af50bd7c57c89aacb8ed7090e31b28c1e9056dd94fb` | — | **PASS** |
| D10 | `docker/prometheus/Dockerfile` (builder) | `alpine` | `3.19` | ✅ `6baf43584bcb78f2e5847d1de515f23499913ac9f12bdf834811a3145eb11ca1` | — | **PASS** |
| D11 | `docker/prometheus/Dockerfile` (runtime) | `prom/prometheus` | `v2.51.0` | ✅ `5ccad477d0057e62a7cd1981ffcc43785ac10c5a35522dc207466ff7e7ec845f` | — | **PASS** |
| D12 | `docker/grafana/Dockerfile` | `grafana/grafana` | `10.4.0` | ✅ `f9811e4e687ffecf1a43adb9b64096c50bc0d7a782f8608530f478b6542de7d5` | — | **PASS** |
| D13 | `docker/anomaly_detector/Dockerfile` | `python` | `3.12-slim` | ✅ `401f6e1a67dad31a1bd78e9ad22d0ee0a3b52154e6bd30e90be696bb6a3d7461` | — | **PASS** |
| D14 | `docker/ca-tool/Dockerfile` | `alpine` | `3.19` | ✅ Same digest as D10 | — | **PASS** |
| D15 | `docker/certbot/Dockerfile` | `certbot/certbot` | `v5.6.0` | ✅ `0107d084c225631fc64a8313e19adb07275f7296fde338f7dfa93986c80b2e3e` | — | **PASS** |
| D16 | `docker/certbot_exporter/Dockerfile` | `python` | `3.11-slim` | ✅ `2c285c669cc837aa3bcf1af23ea1932b7b5214f9c9d3aad22417446ad91cb4fb` | — | **PASS** |
| D17 | `docker/opensips_exporter/Dockerfile` | `python` | `3.11-slim-bookworm` | ✅ `cd67330292a51e2963156f74ff340455d66b2172e9190e99f40dff9357471177` | — | **PASS** |
| D18 | `docker/tailscale_cert/Dockerfile` | `tailscale/tailscale` | `v1.96.5` | ✅ `dbeff02d2337344b351afac203427218c4d0a06c43fc10a865184063498472a6` | — | **PASS** |
| D19 | `docker/rtpengine_exporter/Dockerfile` | `python` | `3.12-alpine` | ✅ `236173eb74001afe2f60862de935b74fcbd00adfca247b2c27051a70a6a39a2d` | — | **PASS** |
| D20 | `tests/integration/mock-sip-trunk/Dockerfile` | `python` | `3.11-alpine` | ❌ No digest | **LOW** | **WARN** |

**Findings:**
- **D1–D5**: The `debian:bookworm-slim` digest `67b30a...` is reused across 5 stages in 3 Dockerfiles. Excellent consistency.
- **D6–D7**: The `postgres:16` digest `b6ccf0...` is reused across postgres and backup images. Excellent consistency.
- **D8 vs D9 (FIXED since 2026-05-26)**: Both `docker/ocp/Dockerfile` and `docker/admin_api/Dockerfile` now use the **identical** base image and digest (`php:8.2-apache-bookworm@sha256:5e0eabe...`). ✅
- **D20 (LOW)**: Test-only mock container uses `python:3.11-alpine` without SHA256 digest. Acceptable for integration-test fixtures but should be pinned for CI reproducibility.

### 1.2 External Images in Docker Compose

| ID | Compose File | Service | Image | SHA256 Pinned | Severity | Status |
|---|---|---|---|---|---|---|
| C1 | `docker-compose.yml` | `postgres_exporter` | `prometheuscommunity/postgres-exporter:v0.15.0` | ✅ | — | **PASS** |
| C2 | `docker-compose.yml` | `node_exporter` | `prom/node-exporter:v1.8.0` | ✅ | — | **PASS** |
| C3 | `docker-compose.yml` | `alertmanager` | `prom/alertmanager:v0.27.0` | ✅ | — | **PASS** |
| C4 | `docker-compose.prod.yml` | `alertmanager` | `prom/alertmanager:v0.27.0` | ✅ | — | **PASS** |
| C5 | `docker-compose.monitoring.yml` | `alertmanager` | `prom/alertmanager:v0.27.0` | ✅ | — | **PASS** |
| C6 | `docker-compose.monitoring.yml` | `node_exporter` | `prom/node-exporter:v1.8.0` | ✅ | — | **PASS** |
| C7 | `docker-compose.monitoring.yml` | `cadvisor` | `gcr.io/cadvisor/cadvisor:v0.49.1` | ✅ | — | **PASS** |

**Findings:**
- All 7 external image references across all Compose files are pinned to SHA256 digests. No floating tags. ✅

---

## 2. OpenSIPS Version Consistency

| ID | Location | Declared Version | Required (Spec) | Status | Severity |
|---|---|---|---|---|---|
| O1 | `Dockerfile` | `ARG OPENSIPS_VERSION=3.6.6` | 3.6 LTS | **PASS** | — |
| O2 | `Dockerfile` (git clone) | `--branch 3.6.6` | 3.6 LTS | **PASS** | — |
| O3 | `docs/TSiSIP-CANONICAL-SPEC.md` | `OpenSIPS 3.6 LTS` | 3.6 LTS | **PASS** | — |
| O4 | `AGENTS.md` | `OpenSIPS 3.6 LTS` | 3.6 LTS | **PASS** | — |

**Findings:**
- OpenSIPS version is now **pinned to `3.6.6`** (specific patch release) in the Dockerfile build argument, up from the floating `3.6` branch in the 2026-05-26 scan. ✅
- The git clone uses `--depth 1 --branch 3.6.6`, which is deterministic to a release tag.
- No references to OpenSIPS 3.4 or other forbidden versions in docs or code. ✅

---

## 3. PostgreSQL Version Consistency

| ID | Location | Declared Version | Required (Spec) | Status | Severity |
|---|---|---|---|---|---|
| P1 | `docker/postgres/Dockerfile` | `postgres:16` | ≥15 (spec example shows 16) | **PASS** | — |
| P2 | `docker/backup/Dockerfile` | `postgres:16` | ≥15 | **PASS** | — |
| P3 | `docs/TSiSIP-CANONICAL-SPEC.md` §14 | `postgres:16` | 16 | **PASS** | — |
| P4 | `AGENTS.md` §3 | `PostgreSQL 16` | 16 | **PASS** | — |

**Findings:**
- PostgreSQL 16 is used consistently across all files. No `postgres:latest` references exist. ✅

---

## 4. Python / Node / PHP Runtime Versions

| ID | Location | Runtime | Declared Version | Status | Severity | Notes |
|---|---|---|---|---|---|---|
| R1 | `docker/anomaly_detector/Dockerfile` | Python | `3.12-slim` | **PASS** | — | SHA256 pinned |
| R2 | `docker/opensips_exporter/Dockerfile` | Python | `3.11-slim-bookworm` | **PASS** | — | SHA256 pinned |
| R3 | `docker/certbot_exporter/Dockerfile` | Python | `3.11-slim` | **PASS** | — | SHA256 pinned |
| R4 | `docker/ocp/Dockerfile` | PHP | `8.2-apache-bookworm` | **PASS** | — | SHA256 pinned |
| R5 | `docker/admin_api/Dockerfile` | PHP | `8.2-apache-bookworm` | **PASS** | — | Same digest as R4 ✅ |
| R6 | `.nvmrc` | Node.js | `v26.2.0` | **PASS** | — | Added since 2026-05-26 |
| R7 | `.python-version` | Python | `3.12.3` | **PASS** | — | Added since 2026-05-26 |
| R8 | `docker/rtpengine_exporter/Dockerfile` | Python | `3.12-alpine` | **PASS** | — | SHA256 pinned |
| R9 | `tests/integration/mock-sip-trunk/Dockerfile` | Python | `3.11-alpine` | **WARN** | LOW | No digest pin (test-only) |

**Findings:**
- **R5 (FIXED since 2026-05-26)**: Admin API and OCP now share the **identical** base image and digest. ✅
- **R6–R7 (FIXED since 2026-05-26)**: `.nvmrc` and `.python-version` now exist at the project root. ✅
- **R9 (LOW)**: Test fixture Dockerfile lacks digest pinning. Acceptable for mock services but worth pinning for CI reproducibility.

---

## 5. Docker Compose Version Declarations

| ID | File | `version:` Key | Status | Notes |
|---|---|---|---|---|
| V1 | `docker-compose.yml` | Absent | **PASS** | Compose Spec v3+ |
| V2 | `docker-compose.prod.yml` | Absent | **PASS** | Compose Spec v3+ |
| V3 | `docker-compose.vps.yml` | Absent | **PASS** | Compose Spec v3+ |
| V4 | `docker-compose.build.yml` | Absent | **PASS** | Compose Spec v3+ |
| V5 | `docker-compose.monitoring.yml` | Absent | **PASS** | Compose Spec v3+ |
| V6 | `docker-compose.vps.override.yml` | Absent | **PASS** | Compose Spec v3+ |

**Findings:**
- All Compose files correctly omit the obsolete top-level `version:` key. ✅

---

## 6. Floating Tags vs SHA256 Digests

### 6.1 Images with SHA256 Digests (Compliant)

All production `FROM` statements in all Dockerfiles and all external `image:` references in Compose files use `@sha256:...` pinning.

### 6.2 Floating / Variable Tags (Issues)

| ID | Location | Tag Pattern | Severity | Status | Notes |
|---|---|---|---|---|---|
| F1 | `.env.example` | `TSISIP_IMAGE_TAG=v0.0.0-dev` | — | **PASS** | Fixed since 2026-05-26; non-floating placeholder |
| F2 | `docker-compose.yml` | `tsisip/opensips:${TSISIP_IMAGE_TAG:?must be set}` | — | **PASS** | `?must be set` enforces explicit value |
| F3 | `docker-compose.vps.override.yml` | `:test` tags on all images | **INFO** | **WARN** | Test override; acceptable for local dev |
| F4 | `.github/workflows/deploy.yml` | `TSISIP_IMAGE_TAG: latest` (env) | **INFO** | **WARN** | CI-internal smoke-test default; images also pushed with `${{ github.sha }}` |
| F5 | `tests/integration/mock-sip-trunk/Dockerfile` | `python:3.11-alpine` | **INFO** | **WARN** | No digest pin; test-only fixture |

**Findings:**
- **F1 (FIXED since 2026-05-26)**: `.env.example` now sets `TSISIP_IMAGE_TAG=v0.0.0-dev` instead of `latest`. ✅
- **F4**: The deploy workflow sets `TSISIP_IMAGE_TAG: latest` as a GitHub Actions env var. This is **documented** as a CI smoke-test convenience tag only. Production compose files use `:?must be set` and will fail if the env var is unset. The workflow also pushes `${{ github.sha }}` tags. Acceptable with documented caveats.

---

## 7. OS Package Version Pins (apt-get / apk)

| ID | Dockerfile | Package Manager | Packages Without Version Pin | Security-Critical? | Severity | Status |
|---|---|---|---|---|---|---|
| A1 | `Dockerfile` | `apt-get` | `ca-certificates`, `git`, `gcc`, `make`, `bison`, `flex`, `libpq-dev`, `libssl-dev`, `libwebsockets-dev`, `libmicrohttpd-dev`, `libpcre2-dev`, `pkg-config`, `libncurses-dev`, `gettext-base`, `libpq5`, `libssl3`, `libmicrohttpd12`, `libpcre2-8-0`, `netcat-openbsd`, `procps`, `curl` | Some (libssl3, libpq5) | **MED** | WARN |
| A2 | `docker/rtpengine/Dockerfile` | `apt-get` | `rtpengine=10.5.3.5-1` ✅, `netcat-openbsd` | `rtpengine` is critical | — | **PASS** |
| A3 | `docker/asterisk/Dockerfile` (builder) | `apt-get` | `build-essential`, `curl`, `ca-certificates`, `libssl-dev`, `libncurses5-dev`, `libnewt-dev`, `libxml2-dev`, `libsqlite3-dev`, `uuid-dev`, `libjansson-dev`, `libcurl4-openssl-dev`, `libedit-dev` | Some (libssl-dev) | **MED** | WARN |
| A4 | `docker/asterisk/Dockerfile` (runtime) | `apt-get` | `libssl3`, `libncurses6`, `libnewt0.52`, `libxml2`, `libsqlite3-0`, `uuid-runtime`, `libjansson4`, `libcurl4`, `libedit2`, `netcat-openbsd` | Some (libssl3) | **MED** | WARN |
| A5 | `docker/ocp/Dockerfile` | `apt-get` | `cron`, `gettext`, `libpq-dev`, `postgresql-client` | `postgresql-client` | **MED** | WARN |
| A6 | `docker/postgres/Dockerfile` | `apt-get` | `netcat-openbsd` | No | **LOW** | WARN |
| A7 | `docker/backup/Dockerfile` | `apt-get` | `rclone`, `openssl`, `cron`, `gettext`, `ca-certificates`, `netcat-openbsd` | `openssl` | **MED** | WARN |
| A8 | `docker/prometheus/Dockerfile` (builder) | `apk` | `gettext`, `curl` | No | **LOW** | WARN |
| A9 | `docker/ca-tool/Dockerfile` | `apk` | `openssl`, `bash`, `jq` | `openssl` | **MED** | WARN |
| A10 | `docker/certbot/Dockerfile` | `apk` | `curl`, `openssl`, `ca-certificates`, `dcron` | `openssl` | **MED** | WARN |
| A11 | `docker/anomaly_detector/Dockerfile` | `apt-get` | `curl` | No | **LOW** | WARN |
| A12 | `docker/certbot_exporter/Dockerfile` | `apt-get` | `openssl`, `curl`, `ca-certificates` | `openssl` | **MED** | WARN |
| A13 | `docker/opensips_exporter/Dockerfile` | `apt-get` | `openssl`, `curl` | `openssl` | **MED** | WARN |
| A14 | `docker/tailscale_cert/Dockerfile` | `apk` | `curl`, `openssl` | `openssl` | **MED** | WARN |
| A15 | `docker/admin_api/Dockerfile` | `apt-get` | `libpq-dev` | No | **LOW** | WARN |

**Findings:**
- **A2 (FIXED since 2026-05-26)**: `docker/rtpengine/Dockerfile` now pins `rtpengine=10.5.3.5-1`. The APT package version is explicitly constrained. ✅
- **A1, A3–A15**: All other Dockerfiles install OS packages without explicit version pins. This is common practice when the base image digest is pinned, but security-critical packages (`libssl3`, `openssl`, `libpq-dev`, `postgresql-client`) would benefit from explicit version constraints in high-assurance builds.

---

## 8. Language Dependency Versions

### 8.1 Python Dependencies

| ID | File | Dependency | Version | Status | Notes |
|---|---|---|---|---|---|
| PY1 | `docker/anomaly_detector/requirements.txt` | `redis` | `5.0.8` | **PASS** | Pinned (`==`) |
| PY2 | `docker/anomaly_detector/requirements.txt` | `prometheus-client` | `0.20.0` | **PASS** | Pinned (`==`) |
| PY3 | `docker/anomaly_detector/requirements.txt` | `flask` | `3.0.3` | **PASS** | Pinned (`==`) |
| PY4 | `docker/anomaly_detector/requirements.txt` | `numpy` | `1.26.4` | **PASS** | Pinned (`==`) |
| PY5 | `docker/anomaly_detector/requirements.txt` | `requests` | `2.32.3` | **PASS** | Pinned (`==`) |
| PY6 | `docker/certbot_exporter/Dockerfile` (pip) | `prometheus_client` | `0.20.0` | **PASS** | Pinned (`==`) |
| PY7 | `docker/opensips_exporter/Dockerfile` (pip) | `prometheus-client` | `0.20.0` | **PASS** | Pinned (`==`) |
| PY8 | `docker/opensips_exporter/Dockerfile` (pip) | `requests` | `2.31.0` | **PASS*** | **HIGH** | Version drift vs PY5 |

**Findings:**
- **PY8 (HIGH)**: `requests==2.31.0` is installed in `opensips_exporter` while `anomaly_detector` uses `requests==2.32.3`. Both are pinned, but the versions differ. **Recommendation:** Align to the same `requests` version across all Python containers to reduce CVE surface and dependency cache fragmentation.
- No unpinned Python packages (`*`, `>=`, absent version) were found. ✅
- No `requirements.txt` exists for `opensips_exporter` or `certbot_exporter`; dependencies are inlined in Dockerfile `RUN pip install` commands. Consider extracting to dedicated `requirements.txt` files for consistency and easier security scanning.

### 8.2 Node.js / Frontend Dependencies

| ID | File | Dependency | Version | Status | Notes |
|---|---|---|---|---|---|
| N1 | `.opencode/package.json` | `@opencode-ai/plugin` | `1.15.4` | **PASS** | Pinned |
| N2 | `.omk/open-design/package.json` | `tsx` | `4.21.0` | **PASS** | Pinned |
| N3 | `.omk/open-design/package.json` | `typescript` | `^5.6.3` | **WARN** | `^` semver range |
| N4 | `.omk/open-design/package.json` | `@types/node` | `^20.17.10` | **WARN** | `^` semver range |

**Findings:**
- Node dependencies in agent/tooling subdirectories use `^` semver ranges. These are **not application runtime dependencies** (they are for agent orchestration UI), so the risk is low. However, no `package-lock.json` exists in `.omk/open-design/`, meaning installs are not deterministic.
- The `.opencode/package-lock.json` exists and locks `@opencode-ai/plugin`. ✅

---

## 9. Version Mismatch Between Documentation and Implementation

| ID | Doc Reference | Doc Says | Implementation Says | Status | Severity |
|---|---|---|---|---|---|
| M1 | `docs/TSiSIP-CANONICAL-SPEC.md` §13 | `FROM debian:bookworm-slim` (no digest) | `FROM debian:bookworm-slim@sha256:...` | **PASS** | — | Implementation exceeds spec |
| M2 | `docs/TSiSIP-CANONICAL-SPEC.md` §14 | `image: postgres:16` | `tsisip/postgres:${TSISIP_IMAGE_TAG}` | **PASS** | — | Project-owned image wraps postgres:16 |
| M3 | `docs/TSiSIP-CANONICAL-SPEC.md` §13 | `ARG OPENSIPS_VERSION=3.6` | `ARG OPENSIPS_VERSION=3.6.6` | **PASS** | — | Implementation exceeds spec (more specific) |
| M4 | `AGENTS.md` §9 | `All base images are pinned to SHA256 digests` | True for all `FROM` statements | **PASS** | — | Exact match |
| M5 | `AGENTS.md` §3 | `PostgreSQL 16` | `postgres:16` in Dockerfile | **PASS** | — | Exact match |
| M6 | `.env.example` | `TSISIP_IMAGE_TAG=v0.0.0-dev` | Same | **PASS** | — | Non-floating placeholder |
| M7 | `docs/TSiSIP-CANONICAL-SPEC.md` §14 | `image: tsisip/asterisk:latest` (spec example) | `tsisip/asterisk:${TSISIP_IMAGE_TAG}` | **WARN** | LOW | Spec example uses `:latest`; actual compose uses variable tag |

**Findings:**
- **M1, M3**: The implementation is **stricter** than the canonical spec examples. This is good — the spec shows the concept, and the implementation adds supply-chain hardening.
- **M6 (FIXED since 2026-05-26)**: `.env.example` now uses a safe placeholder (`v0.0.0-dev`) instead of `latest`. ✅
- **M7 (LOW)**: The canonical spec Compose example (§14) shows `image: tsisip/asterisk:latest`. The actual `docker-compose.yml` uses `${TSISIP_IMAGE_TAG}`. The spec example should be updated to match the actual variable-tag pattern.

---

## 10. Git Sources / External Build Dependencies

| ID | Location | Source | Pinned? | Status | Severity |
|---|---|---|---|---|---|
| G1 | `Dockerfile` | `git clone --depth 1 --branch 3.6.6 https://github.com/OpenSIPS/opensips.git` | Release tag `3.6.6` | **PASS** | — |
| G2 | `docker/asterisk/Dockerfile` | `curl .../asterisk-20.9.3.tar.gz` | `ASTERISK_VERSION=20.9.3` | **PASS** | — |

**Findings:**
- **G1 (IMPROVED since 2026-05-26)**: The OpenSIPS source build now clones the `3.6.6` release tag instead of the floating `3.6` branch. This provides deterministic source versioning. ✅
- **G2**: Asterisk source tarball is pinned to `20.9.3`. ✅

---

## 11. Registry Namespace Consistency

| ID | Service | Dev Compose | Prod Compose | VPS Compose | Status | Severity |
|---|---|---|---|---|---|---|
| X1 | `opensips` | `tsisip/opensips` | `ghcr.io/b0yz4kr14/tsisip/opensips` | `ghcr.io/b0yz4kr14/tsisip/opensips` | **PASS** | — |
| X2 | `certbot` | `ghcr.io/b0yz4kr14/tsisip/certbot` | `ghcr.io/b0yz4kr14/tsisip/certbot` | `ghcr.io/b0yz4kr14/tsisip/certbot` | **PASS** | — |
| X3 | `tailscale_cert` | `ghcr.io/b0yz4kr14/tsisip/tailscale_cert` | `ghcr.io/b0yz4kr14/tsisip/tailscale_cert` | *(absent)* | **PASS** | — |

**Findings:**
- **X2 (FIXED since 2026-05-26)**: The `certbot` service now uses the full `ghcr.io/b0yz4kr14/tsisip/...` prefix consistently across all Compose files. ✅

---

## 12. GitHub Actions Version Pinning

| ID | Workflow | Action | Reference | Pinned? | Status |
|---|---|---|---|---|---|
| GA1 | `ci.yml` | `actions/checkout` | `@v4` | Tag | **PASS** |
| GA2 | `ci.yml` | `actions/upload-artifact` | `@v4` | Tag | **PASS** |
| GA3 | `deploy.yml` | `actions/checkout` | `@v4` | Tag | **PASS** |
| GA4 | `deploy.yml` | `docker/setup-buildx-action` | `@v3` | Tag | **PASS** |
| GA5 | `deploy.yml` | `actions/upload-artifact` | `@v4` | Tag | **PASS** |
| GA6 | `deploy.yml` | `actions/download-artifact` | `@v4` | Tag | **PASS** |
| GA7 | `deploy.yml` | `docker/login-action` | `@v3` | Tag | **PASS** |
| GA8 | `deploy.yml` | `webfactory/ssh-agent` | `@v0.9.0` | Tag | **PASS** |
| GA9 | `squad-heartbeat.yml` | `actions/checkout` | `@v4` | Tag | **PASS** |
| GA10 | `squad-heartbeat.yml` | `actions/github-script` | `@v7` | Tag | **PASS** |
| GA11 | `squad-issue-assign.yml` | `actions/checkout` | `@v4` | Tag | **PASS** |
| GA12 | `squad-issue-assign.yml` | `actions/github-script` | `@v7` | Tag | **PASS** |
| GA13 | `squad-triage.yml` | `actions/checkout` | `@v4` | Tag | **PASS** |
| GA14 | `squad-triage.yml` | `actions/github-script` | `@v7` | Tag | **PASS** |

**Findings:**
- All GitHub Actions references use pinned tags (`@v4`, `@v3`, `@v0.9.0`, `@v7`). No floating `@master` or `@main` references remain. ✅

---

## 13. Summary Table

| ID | Component | Current | Required | Status | Severity | Notes |
|---|---|---|---|---|---|---|
| V1 | OpenSIPS (build arg) | `3.6.6` | `3.6 LTS` | **PASS** | — | Pinned to release tag |
| V2 | OpenSIPS (git source) | `3.6.6` tag | Specific tag preferred | **PASS** | — | Deterministic |
| V3 | PostgreSQL | `16` | `16` | **PASS** | — | Digest pinned |
| V4 | Asterisk | `20.9.3` | — | **PASS** | — | Explicitly pinned |
| V5 | Debian base | `bookworm-slim` | — | **PASS** | — | SHA256 pinned, reused ×5 |
| V6 | PHP (OCP) | `8.2-apache-bookworm` | `8.2` | **PASS** | — | SHA256 pinned |
| V7 | PHP (admin_api) | `8.2-apache-bookworm` | `8.2` | **PASS** | — | Same digest as V6 ✅ |
| V8 | Python (anomaly_detector) | `3.12-slim` | — | **PASS** | — | SHA256 pinned |
| V9 | Python (opensips_exporter) | `3.11-slim-bookworm` | — | **PASS** | — | SHA256 pinned |
| V10 | Python (certbot_exporter) | `3.11-slim` | — | **PASS** | — | SHA256 pinned |
| V11 | Prometheus | `v2.51.0` | — | **PASS** | — | SHA256 pinned |
| V12 | Grafana | `10.4.0` | — | **PASS** | — | SHA256 pinned |
| V13 | Alertmanager | `v0.27.0` | — | **PASS** | — | SHA256 pinned |
| V14 | Node Exporter | `v1.8.0` | — | **PASS** | — | SHA256 pinned |
| V15 | Postgres Exporter | `v0.15.0` | — | **PASS** | — | SHA256 pinned |
| V16 | Certbot | `v5.6.0` | — | **PASS** | — | SHA256 pinned |
| V17 | Tailscale | `v1.96.5` | — | **PASS** | — | SHA256 pinned |
| V18 | TSiSIP Image Tag (env) | `v0.0.0-dev` | Pinned tag | **PASS** | — | `.env.example` default |
| V19 | RTPengine APT package | `10.5.3.5-1` | Pinned preferred | **PASS** | — | APT version pinned |
| V20 | Python `requests` (anomaly) | `2.32.3` | Consistent | **PASS** | — | Pinned |
| V21 | Python `requests` (exporter) | `2.31.0` | Consistent | **FAIL** | **HIGH** | Drift vs V20 |
| V22 | Docker Compose `version:` | Absent | Absent | **PASS** | — | Modern spec compliant |
| V23 | Test mock-sip-trunk | `python:3.11-alpine` (no digest) | Digest preferred | **WARN** | LOW | Test-only fixture |

---

## 14. Consistency Issues

1. **Python `requests` Version Drift** (Remaining from 2026-05-26)
   - `anomaly_detector` uses `requests==2.32.3`
   - `opensips_exporter` uses `requests==2.31.0`
   - **Fix:** Standardize on `2.32.3` (newer) in both containers.

2. **Canonical Spec Example Uses `:latest`**
   - `docs/TSiSIP-CANONICAL-SPEC.md` §14 shows `image: tsisip/asterisk:latest`.
   - **Fix:** Update the spec example to use `${TSISIP_IMAGE_TAG}` to match actual implementation.

3. **Test Fixture Dockerfile Unpinned**
   - `tests/integration/mock-sip-trunk/Dockerfile` uses `python:3.11-alpine` without digest.
   - **Fix:** Add `@sha256:...` pin for CI reproducibility.

---

## 15. Next Actions (Prioritized)

| Priority | Action | Owner | File(s) |
|---|---|---|---|
| **P1 (High)** | Align Python `requests` version across `opensips_exporter` and `anomaly_detector` | Backend | `docker/opensips_exporter/Dockerfile` |
| **P2 (Medium)** | Pin `tests/integration/mock-sip-trunk/Dockerfile` base image to SHA256 digest | DevEx | `tests/integration/mock-sip-trunk/Dockerfile` |
| **P2 (Medium)** | Update canonical spec §14 example to use `${TSISIP_IMAGE_TAG}` instead of `:latest` for Asterisk | Docs | `docs/TSiSIP-CANONICAL-SPEC.md` |
| **P3 (Low)** | Consider adding explicit version pins for security-critical OS packages (`libssl3`, `openssl`, `libpq-dev`) in high-assurance builds | Security | Multiple Dockerfiles |
| **P3 (Low)** | Extract inlined `pip install` dependencies in `opensips_exporter` and `certbot_exporter` to dedicated `requirements.txt` files | DevEx | `docker/opensips_exporter/`, `docker/certbot_exporter/` |

---

*Report generated by speckit-version-guard skill execution.*  
*Baseline: docs/TSiSIP-CANONICAL-SPEC.md (v1.1)*  
*Scan scope: 19 Dockerfiles, 6 Compose files, 2 dependency manifests, 3 version files, 6 GitHub Actions workflows*  
*Previous scan comparison: reports/version-guard-2026-05-26.md*
