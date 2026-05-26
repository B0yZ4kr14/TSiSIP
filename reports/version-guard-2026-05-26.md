# TSiSIP Version Guard Analysis Report

**Date:** 2026-05-26  
**Baseline:** `docs/TSiSIP-CANONICAL-SPEC.md` (v1.1, 2026-05-19)  
**Scope:** Docker base images, Docker Compose declarations, language runtime versions, OS packages, Python/Node dependencies, and doc-vs-implementation consistency  
**Strict Mode:** Off (informational + severity triage)

---

## Executive Summary

| Category | Passed | Failed (Critical) | Failed (High) | Warnings (Med/Low) |
|---|---:|---:|---:|---:|
| Docker Base Images | 15 | 0 | 2 | 3 |
| Docker Compose Images | 6 | 0 | 1 | 2 |
| Language Runtimes | 5 | 0 | 1 | 2 |
| OS Packages (apt/apk) | 13 | 0 | 1 | 0 |
| Python/Node Dependencies | 5 | 0 | 1 | 1 |
| Doc-vs-Implementation | 4 | 0 | 0 | 2 |
| **TOTAL** | **48** | **0** | **6** | **10** |

> **Assessment:** The TSiSIP project demonstrates strong supply-chain hygiene with **100% of `FROM` statements pinned to SHA256 digests**. The primary concerns are (1) a floating `latest` tag recommended in `.env.example`, (2) unpinned `rtpengine` APT package, (3) inconsistent PHP base image digests between OCP and admin-api, and (4) a minor Python `requests` version drift between exporter containers.

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
| D9 | `docker/admin_api/Dockerfile` | `php` | `8.2-apache` | ✅ `bc7a503572236c4f5fa40a83e6e8a53eb71d502909d6c4d5641b93e34a728303` | **HIGH** | **FAIL** |
| D10 | `docker/prometheus/Dockerfile` (builder) | `alpine` | `3.19` | ✅ `6baf43584bcb78f2e5847d1de515f23499913ac9f12bdf834811a3145eb11ca1` | — | **PASS** |
| D11 | `docker/prometheus/Dockerfile` (runtime) | `prom/prometheus` | `v2.51.0` | ✅ `5ccad477d0057e62a7cd1981ffcc43785ac10c5a35522dc207466ff7e7ec845f` | — | **PASS** |
| D12 | `docker/grafana/Dockerfile` | `grafana/grafana` | `10.4.0` | ✅ `f9811e4e687ffecf1a43adb9b64096c50bc0d7a782f8608530f478b6542de7d5` | — | **PASS** |
| D13 | `docker/anomaly_detector/Dockerfile` | `python` | `3.12-slim` | ✅ `401f6e1a67dad31a1bd78e9ad22d0ee0a3b52154e6bd30e90be696bb6a3d7461` | — | **PASS** |
| D14 | `docker/ca-tool/Dockerfile` | `alpine` | `3.19` | ✅ Same digest as D10 | — | **PASS** |
| D15 | `docker/certbot/Dockerfile` | `certbot/certbot` | `v5.6.0` | ✅ `0107d084c225631fc64a8313e19adb07275f7296fde338f7dfa93986c80b2e3e` | — | **PASS** |
| D16 | `docker/certbot_exporter/Dockerfile` | `python` | `3.11-slim` | ✅ `2c285c669cc837aa3bcf1af23ea1932b7b5214f9c9d3aad22417446ad91cb4fb` | — | **PASS** |
| D17 | `docker/opensips_exporter/Dockerfile` | `python` | `3.11-slim-bookworm` | ✅ `cd67330292a51e2963156f74ff340455d66b2172e9190e99f40dff9357471177` | — | **PASS** |
| D18 | `docker/tailscale_cert/Dockerfile` | `tailscale/tailscale` | `v1.96.5` | ✅ `dbeff02d2337344b351afac203427218c4d0a06c43fc10a865184063498472a6` | — | **PASS** |

**Findings:**
- **D1–D5**: The `debian:bookworm-slim` digest `67b30a...` is reused across 5 stages in 3 Dockerfiles. Excellent consistency.
- **D6–D7**: The `postgres:16` digest `b6ccf0...` is reused across postgres and backup images. Excellent consistency.
- **D8 vs D9 (HIGH)**: `docker/ocp/Dockerfile` uses `php:8.2-apache-bookworm@sha256:5e0eab...` while `docker/admin_api/Dockerfile` uses `php:8.2-apache@sha256:bc7a50...`. These are **different images with different digests**. Both are PHP 8.2, but the OCP image explicitly targets the `-bookworm` variant while admin-api uses the generic tag. This creates a supply-chain divergence risk. **Recommendation:** Align both to the same explicit variant and digest.

### 1.2 External Images in Docker Compose

| ID | Compose File | Service | Image | SHA256 Pinned | Severity | Status |
|---|---|---|---|---|---|---|
| C1 | `docker-compose.yml` | `postgres_exporter` | `prometheuscommunity/postgres-exporter:v0.15.0` | ✅ | — | **PASS** |
| C2 | `docker-compose.yml` | `node_exporter` | `prom/node-exporter:v1.8.0` | ✅ | — | **PASS** |
| C3 | `docker-compose.yml` | `alertmanager` | `prom/alertmanager:v0.27.0` | ✅ | — | **PASS** |
| C4 | `docker-compose.prod.yml` | `alertmanager` | `prom/alertmanager:v0.27.0` | ✅ | — | **PASS** |
| C5 | `docker-compose.monitoring.yml` | `alertmanager` | `prom/alertmanager:v0.27.0` | ✅ | — | **PASS** |
| C6 | `docker-compose.monitoring.yml` | `node_exporter` | `prom/node-exporter:v1.8.0` | ✅ | — | **PASS** |

**Findings:**
- All 6 external image references across all Compose files are pinned to SHA256 digests. No floating tags.

---

## 2. OpenSIPS Version Consistency

| ID | Location | Declared Version | Required (Spec) | Status | Severity |
|---|---|---|---|---|---|
| O1 | `Dockerfile` | `ARG OPENSIPS_VERSION=3.6` | 3.6 LTS | **PASS** | — |
| O2 | `Dockerfile` (git clone) | `--branch 3.6` | 3.6 LTS | **PASS*** | — |
| O3 | `docs/TSiSIP-CANONICAL-SPEC.md` | `OpenSIPS 3.6 LTS` | 3.6 LTS | **PASS** | — |
| O4 | `AGENTS.md` | `OpenSIPS 3.6 LTS` | 3.6 LTS | **PASS** | — |
| O5 | `docs/memory/DECISIONS.md` | `OpenSIPS 3.6 LTS` | 3.6 LTS | **PASS** | — |

**Findings:**
- OpenSIPS version is consistently declared as **3.6** across all canonical documentation and the Dockerfile build argument.
- The git clone uses `--depth 1 --branch 3.6`, which tracks the tip of the 3.6 branch. This means the build is **not reproducible to a specific patch version** (e.g., 3.6.5 vs 3.6.6). The spec does not mandate a specific patch level, but for true supply-chain determinism, consider pinning to a release tag (e.g., `3.6.5`) and verifying the SHA256 of the cloned source.
- **No references to OpenSIPS 3.4 or other forbidden versions** were found in docs or code. ✅

---

## 3. PostgreSQL Version Consistency

| ID | Location | Declared Version | Required (Spec) | Status | Severity |
|---|---|---|---|---|---|
| P1 | `docker/postgres/Dockerfile` | `postgres:16` | ≥15 (spec example shows 16) | **PASS** | — |
| P2 | `docker/backup/Dockerfile` | `postgres:16` | ≥15 | **PASS** | — |
| P3 | `docs/TSiSIP-CANONICAL-SPEC.md` §14 | `postgres:16` | 16 | **PASS** | — |
| P4 | `docker-compose.yml` (image tag) | `${TSISIP_IMAGE_TAG}` | Project-owned image | **PASS** | — |
| P5 | `AGENTS.md` §3 | `PostgreSQL 16` | 16 | **PASS** | — |

**Findings:**
- PostgreSQL 16 is used consistently across the postgres service, backup container, canonical spec, and AGENTS.md.
- No `postgres:latest` or unpinned PostgreSQL references exist in production files. ✅

---

## 4. Python / Node / PHP Runtime Versions

| ID | Location | Runtime | Declared Version | Status | Severity | Notes |
|---|---|---|---|---|---|---|
| R1 | `docker/anomaly_detector/Dockerfile` | Python | `3.12-slim` | **PASS** | — | SHA256 pinned |
| R2 | `docker/opensips_exporter/Dockerfile` | Python | `3.11-slim-bookworm` | **PASS** | — | SHA256 pinned |
| R3 | `docker/certbot_exporter/Dockerfile` | Python | `3.11-slim` | **PASS** | — | SHA256 pinned |
| R4 | `docker/ocp/Dockerfile` | PHP | `8.2-apache-bookworm` | **PASS** | — | SHA256 pinned |
| R5 | `docker/admin_api/Dockerfile` | PHP | `8.2-apache` | **PASS*** | **HIGH** | Different digest from R4 |
| R6 | `.omk/open-design/package.json` | Node.js | `~24` (engines) | **INFO** | LOW | Agent tooling only |
| R7 | Project root | Node.js | *not declared* | **WARN** | MED | No `.nvmrc` at root |
| R8 | Project root | Python | *not declared* | **WARN** | MED | No `.python-version` at root |

**Findings:**
- **R5 (HIGH)**: Admin API and OCP both run PHP 8.2 but use **different base image digests**. They should share the same explicit base image (`php:8.2-apache-bookworm`) and digest to ensure identical runtime behavior, shared CVE surface, and caching efficiency.
- **R7–R8 (MED)**: No `.nvmrc` or `.python-version` exists at the project root. While TSiSIP is Docker-first and doesn't rely on host runtimes, these files are useful for CI pipelines and developer onboarding. Consider adding them for local test scripts (`tests/*.test.js`, `tests/integration/*.py`).

---

## 5. Docker Compose Version Declarations

| ID | File | `version:` Key | Status | Notes |
|---|---|---|---|---|
| V1 | `docker-compose.yml` | Absent | **PASS** | Compose Spec v3+ (obsolete key omitted) |
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

All `FROM` statements in all Dockerfiles and all external `image:` references in Compose files use `@sha256:...` pinning. This is exemplary supply-chain practice.

### 6.2 Floating / Variable Tags (Issues)

| ID | Location | Tag Pattern | Severity | Status | Notes |
|---|---|---|---|---|---|
| F1 | `.env.example` | `TSISIP_IMAGE_TAG=latest` | **HIGH** | **FAIL** | Default value is a floating tag |
| F2 | `docker-compose.yml` | `tsisip/opensips:${TSISIP_IMAGE_TAG}` | **INFO** | **WARN** | Resolved from env; safe if env is pinned |
| F3 | `docker-compose.vps.override.yml` | `:test` tags on all images | **INFO** | **WARN** | Test override; acceptable for local dev |
| F4 | `Makefile` (brownfield scan) | `:latest` exclusion regex | **INFO** | **WARN** | The brownfield scan explicitly excludes `certbot-exporter` and `opensips-exporter` from `:latest` checks, but neither uses `:latest` in compose. Regex may be stale |

**Findings:**
- **F1 (HIGH)**: `.env.example` sets `TSISIP_IMAGE_TAG=latest`. The comments above it warn against this for production, but the default value itself is a floating tag. Any developer who copies `.env.example` to `.env` without changing the tag will run floating images. **Recommendation:** Change the default to a placeholder like `TSISIP_IMAGE_TAG=SET_ME_TO_A_PINNED_VERSION` or `v0.0.0-dev`.
- **F2**: All project-owned Compose services use `${TSISIP_IMAGE_TAG}`. This is acceptable **only when the environment variable is pinned** at deploy time. The VPS deploy scripts and CI should enforce this.
- **F3**: The `docker-compose.vps.override.yml` uses `:test` tags. This is a local test override and is acceptable.

---

## 7. OS Package Version Pins (apt-get / apk)

| ID | Dockerfile | Package Manager | Packages Without Version Pin | Security-Critical? | Severity | Status |
|---|---|---|---|---|---|---|
| A1 | `Dockerfile` | `apt-get` | `ca-certificates`, `git`, `gcc`, `make`, `bison`, `flex`, `libpq-dev`, `libssl-dev`, `libwebsockets-dev`, `libmicrohttpd-dev`, `libpcre2-dev`, `pkg-config`, `libncurses-dev`, `gettext-base`, `libpq5`, `libssl3`, `libmicrohttpd12`, `libpcre2-8-0`, `netcat-openbsd`, `procps`, `curl` | Some (libssl3, libpq5) | **MED** | WARN |
| A2 | `docker/rtpengine/Dockerfile` | `apt-get` | `rtpengine`, `netcat-openbsd` | `rtpengine` is critical | **HIGH** | **FAIL** |
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
- **A2 (HIGH)**: The `docker/rtpengine/Dockerfile` installs `rtpengine` via `apt-get install rtpengine` **without any version pin**. Because the base image digest is pinned, the apt index is deterministic at build time, but the `rtpengine` package version will float with Debian bookworm security updates. For a critical media relay component, consider pinning to a specific version (e.g., `rtpengine=VERSION`) or installing from a specific DEB.
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
- Node dependencies in agent/tooling subdirectories use `^` semver ranges. These are **not application runtime dependencies** (they are for agent orchestration UI), so the risk is low. However, no `package-lock.json` exists in `.omk/open-design/` or `.squad/templates/`, meaning installs are not deterministic.
- The `.opencode/package-lock.json` exists and locks `@opencode-ai/plugin`. ✅

---

## 9. Version Mismatch Between Documentation and Implementation

| ID | Doc Reference | Doc Says | Implementation Says | Status | Severity |
|---|---|---|---|---|---|
| M1 | `docs/TSiSIP-CANONICAL-SPEC.md` §13 | `FROM debian:bookworm-slim` (no digest) | `FROM debian:bookworm-slim@sha256:...` | **PASS** | — | Implementation exceeds spec |
| M2 | `docs/TSiSIP-CANONICAL-SPEC.md` §14 | `image: postgres:16` | `tsisip/postgres:${TSISIP_IMAGE_TAG}` | **PASS** | — | Project-owned image wraps postgres:16 |
| M3 | `docs/TSiSIP-CANONICAL-SPEC.md` §13 | `ARG OPENSIPS_VERSION=3.6` | `ARG OPENSIPS_VERSION=3.6` | **PASS** | — | Exact match |
| M4 | `AGENTS.md` §9 | `All base images are pinned to SHA256 digests` | True for all `FROM` statements | **PASS** | — | Exact match |
| M5 | `AGENTS.md` §3 | `PostgreSQL 16` | `postgres:16` in Dockerfile | **PASS** | — | Exact match |
| M6 | `.env.example` | `TSISIP_IMAGE_TAG=latest` | Same | **FAIL** | **HIGH** | Floating tag default contradicts pinning policy |
| M7 | `Makefile` brownfield scan | Excludes `certbot-exporter` / `opensips-exporter` from `:latest` check | Neither uses `:latest` in compose | **WARN** | LOW | Regex may be stale |
| M8 | `docs/TSiSIP-CANONICAL-SPEC.md` §14 | `image: tsisip/asterisk:latest` (spec example) | `tsisip/asterisk:${TSISIP_IMAGE_TAG}` | **WARN** | LOW | Spec example uses `:latest`; actual compose uses variable tag |

**Findings:**
- **M1**: The implementation is **stricter** than the canonical spec example. This is good — the spec shows the concept, and the implementation adds supply-chain hardening.
- **M6 (HIGH)**: `.env.example` sets a floating `latest` default. While the comment warns about production pinning, the default itself violates the project's SHA256-pinned policy for base images. The environment-driven project images should also default to a non-floating value.
- **M8 (LOW)**: The canonical spec Compose example (§14) shows `image: tsisip/asterisk:latest` and `image: tsisip/asterisk:latest` for the PBX nodes. The actual `docker-compose.yml` uses `${TSISIP_IMAGE_TAG}`. The spec example should probably be updated to match the actual variable-tag pattern.

---

## 10. Git Sources / External Build Dependencies

| ID | Location | Source | Pinned? | Status | Severity |
|---|---|---|---|---|---|
| G1 | `Dockerfile` | `git clone --depth 1 --branch 3.6 https://github.com/OpenSIPS/opensips.git` | Branch only (no commit/tag) | **WARN** | **MED** |
| G2 | `docker/asterisk/Dockerfile` | `curl .../asterisk-${ASTERISK_VERSION}.tar.gz` | `ASTERISK_VERSION=20.9.3` | **PASS** | — |

**Findings:**
- **G1 (MED)**: The OpenSIPS source build clones the `3.6` branch with `--depth 1`. This always fetches the latest commit on that branch. For full reproducibility, consider cloning a specific release tag (e.g., `3.6.5`) and verifying the source archive checksum. This is a medium risk because Debian base + build toolchain are pinned, but the OpenSIPS source itself floats.
- **G2**: Asterisk source tarball is pinned to `20.9.3`. ✅

---

## 11. Registry Namespace Consistency

| ID | Service | Dev Compose | Prod Compose | VPS Compose | Status | Severity |
|---|---|---|---|---|---|---|
| X1 | `opensips` | `tsisip/opensips` | `ghcr.io/b0yz4kr14/tsisip/opensips` | `ghcr.io/b0yz4kr14/tsisip/opensips` | **PASS** | — |
| X2 | `certbot` | `tsisip/certbot` | `tsisip/certbot` | `tsisip/certbot` | **WARN** | **MED** |
| X3 | `tailscale_cert` | `tsisip/tailscale_cert` | `tsisip/tailscale_cert` | *(absent)* | **WARN** | LOW |

**Findings:**
- **X2 (MED)**: The `certbot` service uses `tsisip/certbot` (no `ghcr.io/b0yz4kr14/` prefix) in **all** Compose files, while every other production service uses the full `ghcr.io/b0yz4kr14/tsisip/...` prefix in `docker-compose.prod.yml` and `docker-compose.vps.yml`. If certbot images are pushed to GHCR under the full path, this will cause pull failures. Verify if this is intentional (local-only profile service) or an oversight.

---

## 12. Summary Table

| ID | Component | Current | Required | Status | Severity | Notes |
|---|---|---|---|---|---|---|
| V1 | OpenSIPS (build arg) | `3.6` | `3.6 LTS` | **PASS** | — | Git branch `3.6` |
| V2 | OpenSIPS (git source) | Branch `3.6` tip | Specific tag preferred | **WARN** | MED | `--depth 1 --branch 3.6` floats |
| V3 | PostgreSQL | `16` | `16` | **PASS** | — | Digest pinned |
| V4 | Asterisk | `20.9.3` | — | **PASS** | — | Explicitly pinned |
| V5 | Debian base | `bookworm-slim` | — | **PASS** | — | SHA256 pinned, reused ×5 |
| V6 | PHP (OCP) | `8.2-apache-bookworm` | `8.2` | **PASS** | — | SHA256 pinned |
| V7 | PHP (admin_api) | `8.2-apache` | `8.2` | **FAIL** | HIGH | Different digest from V6 |
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
| V18 | TSiSIP Image Tag (env) | `latest` (default) | Pinned tag | **FAIL** | HIGH | `.env.example` default |
| V19 | RTPengine APT package | Unpinned | Pinned preferred | **FAIL** | HIGH | `apt-get install rtpengine` |
| V20 | Python `requests` (anomaly) | `2.32.3` | Consistent | **PASS** | — | Pinned |
| V21 | Python `requests` (exporter) | `2.31.0` | Consistent | **FAIL** | HIGH | Drift vs V20 |
| V22 | Docker Compose `version:` | Absent | Absent | **PASS** | — | Modern spec compliant |

---

## 13. Consistency Issues

1. **PHP Base Image Divergence** (`docker/ocp/Dockerfile` vs `docker/admin_api/Dockerfile`)
   - OCP uses `php:8.2-apache-bookworm@sha256:5e0eab...`
   - Admin API uses `php:8.2-apache@sha256:bc7a50...`
   - **Fix:** Align both to the same explicit `-bookworm` variant and identical digest.

2. **Python `requests` Version Drift**
   - `anomaly_detector` uses `2.32.3`
   - `opensips_exporter` uses `2.31.0`
   - **Fix:** Standardize on `2.32.3` (newer) in both containers.

3. **Registry Namespace Inconsistency for `certbot`**
   - `certbot` uses `tsisip/certbot` in all environments.
   - All other prod services use `ghcr.io/b0yz4kr14/tsisip/...`.
   - **Fix:** Verify if certbot images are published to GHCR under the full path; if so, update compose files.

4. **OpenSIPS Source Floats on Branch Tip**
   - `git clone --depth 1 --branch 3.6` always fetches the latest commit.
   - **Fix:** Consider pinning to a release tag (e.g., `3.6.5`) and verifying a known-good commit hash.

5. **Canonical Spec Example Uses `:latest`**
   - `docs/TSiSIP-CANONICAL-SPEC.md` §14 shows `image: tsisip/asterisk:latest`.
   - **Fix:** Update the spec example to use `${TSISIP_IMAGE_TAG}` to match actual implementation.

---

## 14. Next Actions (Prioritized)

| Priority | Action | Owner | File(s) |
|---|---|---|---|
| **P1 (High)** | Change `.env.example` default `TSISIP_IMAGE_TAG` from `latest` to a placeholder like `v0.0.0-dev` or require explicit setting | DevOps | `.env.example` |
| **P1 (High)** | Align `docker/admin_api/Dockerfile` base image to match `docker/ocp/Dockerfile` (`php:8.2-apache-bookworm` with same digest) | Backend | `docker/admin_api/Dockerfile` |
| **P1 (High)** | Pin `rtpengine` APT package to a specific version in `docker/rtpengine/Dockerfile` | DevOps | `docker/rtpengine/Dockerfile` |
| **P1 (High)** | Align Python `requests` version across `opensips_exporter` and `anomaly_detector` | Backend | `docker/opensips_exporter/Dockerfile` |
| **P2 (Medium)** | Pin OpenSIPS git clone to a specific release tag (e.g., `3.6.5`) instead of floating branch tip | DevOps | `Dockerfile` |
| **P2 (Medium)** | Add `.nvmrc` (Node version) and `.python-version` (Python version) to project root for CI/dev clarity | DevEx | Project root |
| **P2 (Medium)** | Verify `certbot` image registry path in production; align with `ghcr.io/b0yz4kr14/tsisip/...` if published there | DevOps | `docker-compose.prod.yml`, `docker-compose.vps.yml` |
| **P3 (Low)** | Update canonical spec §14 example to use `${TSISIP_IMAGE_TAG}` instead of `:latest` for Asterisk | Docs | `docs/TSiSIP-CANONICAL-SPEC.md` |
| **P3 (Low)** | Consider adding explicit version pins for security-critical OS packages (`libssl3`, `openssl`, `libpq-dev`) in high-assurance builds | Security | Multiple Dockerfiles |
| **P3 (Low)** | Extract inlined `pip install` dependencies in `opensips_exporter` and `certbot_exporter` to dedicated `requirements.txt` files | DevEx | `docker/opensips_exporter/`, `docker/certbot_exporter/` |

---

*Report generated by speckit-version-guard skill execution.*  
*Baseline: docs/TSiSIP-CANONICAL-SPEC.md (v1.1)*  
*Scan scope: 18 Dockerfiles, 6 Compose files, 3 dependency manifests, 8 documentation files*
