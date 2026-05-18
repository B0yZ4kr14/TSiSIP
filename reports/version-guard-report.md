# Version Guard Report

**Baseline:** docs/TSiSIP-CANONICAL-SPEC.md + AGENTS.md
**Strict Mode:** off

---

## Component Versions

| ID | Component | Current | Required | Status | Severity | Notes |
|----|-----------|---------|----------|--------|----------|-------|
| V1 | OpenSIPS | 3.6 (branch) | >=3.6 LTS | PASS | - | Git clone uses `OPENSIPS_VERSION` build-arg, defaults to `3.6` |
| V2 | PostgreSQL | 16 | >=15 | PASS | - | Consistent across `docker/postgres/Dockerfile` and `docker/backup/Dockerfile` |
| V3 | Debian base | `bookworm-slim` | stable | WARN | MEDIUM | No digest pin; tag rolls with security updates |
| V4 | Grafana | `10.4.0` | - | PASS | - | Pinned semver |
| V5 | Prometheus | `v2.51.0` | - | PASS | - | Pinned semver |
| V6 | Alertmanager | `v0.27.0` | - | PASS | - | Pinned semver |
| V7 | Python (anomaly-detector) | `3.12-slim` | - | WARN | LOW | Minor-only tag; consider `3.12.x-slim` or digest |
| V8 | Python (opensips-exporter) | `3.11-slim-bookworm` | - | WARN | LOW | Minor-only tag; consider `3.11.x-slim-bookworm` or digest |
| V9 | Alpine (ca-tool/prometheus-builder) | `3.19` | - | WARN | LOW | Minor-only tag; consider `3.19.x` or digest |
| V10 | PHP (OCP) | `8.2-apache-bookworm` | - | PASS | - | Minor pinned |
| V11 | Local images (compose) | `:latest` | pinned | FAIL | CRITICAL | All `tsisip/*` images use `:latest`; no traceability |

---

## Consistency Issues

| Issue | Files | Details |
|-------|-------|---------|
| OpenSIPS version sourcing | Dockerfile, docs/TSiSIP-CANONICAL-SPEC.md | Dockerfile uses `OPENSIPS_VERSION` arg (default `3.6`); spec references `3.6.x` modules — aligned |
| PostgreSQL version | docker-compose.yml, docker/postgres/Dockerfile, docker/backup/Dockerfile | All use `16` except compose which uses `tsisip/postgres:latest` — indirect but consistent via build |
| Debian version | Dockerfile, docker/asterisk/Dockerfile, docker/rtpengine/Dockerfile | All use `debian:bookworm-slim` — consistent |
| Python version drift | docker/anomaly-detector/Dockerfile (`3.12`), docker/opensips-exporter/Dockerfile (`3.11`) | Two different Python minors — acceptable if dependencies require, but document why |

---

## Outdated Components (Informational)

- **Debian bookworm**: Current stable; no action needed.
- **PostgreSQL 16**: Current major; PG17 available but 16 is supported.
- **Grafana 10.4.0**: 11.x is available; upgrade path should be tested before bumping.
- **Prometheus v2.51.0**: Current stable; no action needed.

---

## Summary

- Passed: 6
- Failed (Critical): 1
- Failed (High): 0
- Warnings (Medium): 1
- Warnings (Low): 3

---

## Next Actions

1. **Pin local image tags** (V11): Update docker-compose.yml to use `${TSISIP_IMAGE_TAG:-latest}` with a build-time default of the git short-SHA.
2. **Pin base image digests** (V3): For the main `Dockerfile`, pin `debian:bookworm-slim@sha256:...` and update via Dependabot/ Renovate.
3. **Standardize Python minor** (V7/V8): Decide on single Python version (3.12 recommended) for all Python services unless dependency conflicts prevent it.
4. **Add lockfiles** where missing: Python services should include `requirements.txt` with hashed pins or `poetry.lock`.

---

*Would you like me to generate the specific docker-compose.yml edits to pin image tags?*
