# Speckit Consolidated Scan Report — 2026-05-27

**Date**: 2026-05-27
**Commit**: 6353526
**Branch**: main
**Files Scanned**: 10,837
**Host Memory**: 62Gi
**Cgroup**: v2

---

## 1. Brownfield Scan Findings

### Spec Drift

| ID | Category | Severity | File | Finding | Recommendation |
|----|----------|----------|------|---------|----------------|
| B1 | Spec Drift | LOW | `docker/healthcheck/opensips-health.sh` | Uses `127.0.0.1` default for `OPENSIPS_HOST` | Acceptable for container-local healthcheck; document why localhost is safe here |
| B2 | Spec Drift | LOW | `docker/healthcheck/rtpengine-health.sh` | Uses `127.0.0.1` default for `RTPENGINE_HOST` | Same as B1; container-local healthcheck only |
| B3 | Spec Drift | LOW | `web/rtpproxy.php` | Hard-coded placeholder IP `10.0.0.1:7722` in HTML input | Use configuration-driven value or remove placeholder |

### Technical Debt

| ID | Category | Severity | File | Finding | Recommendation |
|----|----------|----------|------|---------|----------------|
| D1 | Debt | LOW | `scripts/lint.sh` | Checks for TODOs but doesn't fail build | Consider making TODO presence a warning in CI |
| D2 | Debt | LOW | Multiple test scripts | Use of `mktemp` without `trap` cleanup | Add `trap` for temp file cleanup in test scripts |

### Anti-Patterns

| ID | Category | Severity | File | Finding | Recommendation |
|----|----------|----------|------|---------|----------------|
| A1 | Anti-Pattern | MEDIUM | `web/change-password.php` | Hard-coded fallback IP `'0.0.0.0'` on audit log failure | Use `$_SERVER['REMOTE_ADDR'] ?? 'unknown'` consistently |
| A2 | Anti-Pattern | LOW | `web/cli/purge-cdr.php` | Hard-coded `'0.0.0.0'::INET` as placeholder | Use NULL or configuration value |

### Configuration Rot

| ID | Category | Severity | File | Finding | Recommendation |
|----|----------|----------|------|---------|----------------|
| C1 | Config Rot | MEDIUM | `docker-compose.build.yml` | No `restart` policies, no `healthcheck`, no memory limits | Add `deploy.resources.limits.memory` and `restart` for build overlay parity |
| C2 | Config Rot | MEDIUM | `docker-compose.vps.override.yml` | No `restart` policies, no memory limits | Add resource constraints for VPS test profile |
| C3 | Config Rot | HIGH | `docker-compose.prod.yml` | `postgres` service has **no memory limit** | Add `deploy.resources.limits.memory` (recommend 8G based on dev profile) |

### Security Surface

| ID | Category | Severity | File | Finding | Recommendation |
|----|----------|----------|------|---------|----------------|
| S1 | Security | LOW | `tests/integration/test-ocp-audit.sh` | Uses literal `'HACKED'` as test mutation value | Use a less alarming test string like `'TEST_MUTATION'` |

---

## 2. Memory Lint Findings

| ID | Service | Config | Current Value | Limit | Risk | Severity | Recommendation |
|----|---------|--------|---------------|-------|------|----------|----------------|
| M1 | postgres (prod) | deploy.resources.limits.memory | NONE | — | Unbounded growth possible | HIGH | Add `deploy.resources.limits.memory: 8G` to match dev profile |
| M2 | postgres (prod) | shared_buffers + work_mem * max_connections | 2GB + (8MB * 300) = 4.4GB | NONE | Could exhaust container memory | MEDIUM | Add memory limit or reduce max_connections to 200 |
| M3 | postgres (prod) | shm_size | 3gb | — | Adequate for current config | PASS | No action needed |
| M4 | opensips (prod) | command line args | `-m 1024 -M 96` | 2G container limit | shm=1GB + pkg ~768MB = ~1.8GB; headroom OK | PASS | No action needed |
| M5 | rtpengine (prod) | memory limit | 2G | — | No explicit config; userspace fallback | MEDIUM | Verify kernel forwarding is enabled in prod; if userspace-only, monitor memory |
| M6 | backup | memory limit | 1G | — | Adequate for pg_dump | PASS | No action needed |
| M7 | pgbouncer | memory limit | 128M | — | Low connection overhead | PASS | No action needed |

**Capacity Planning:**
- Total declared limits (docker-compose.yml): ~21.5GB
- Total declared limits (docker-compose.prod.yml): ~15.6GB (excluding postgres)
- Available host memory: 62Gi
- Headroom: ~46GB — adequate for multi-container deployment

---

## 3. Version Guard Findings

| ID | Component | Current | Required | Status | Severity | Notes |
|----|-----------|---------|----------|--------|----------|-------|
| V1 | OpenSIPS | 3.6.6 (git branch) | >=3.6 LTS | PASS | — | Dockerfile uses `ARG OPENSIPS_VERSION=3.6.6` |
| V2 | Debian base | `bookworm-slim@sha256:67b30a...` | stable | PASS | — | Pinned digest across all Dockerfiles |
| V3 | PostgreSQL | `postgres:16@sha256:b6ccf...` | >=15 | PASS | — | Pinned digest |
| V4 | PHP OCP | `php:8.2-apache-bookworm@sha256:5e0ea...` | 8.2 | PASS | — | Consistent across OCP and admin_api |
| V5 | Python (anomaly_detector) | `python:3.12-slim@sha256:401f6...` | 3.12 | PASS | — | Pinned digest |
| V6 | Grafana | `grafana:10.4.0@sha256:f9811...` | 10.x | PASS | — | Pinned digest |
| V7 | Prometheus | `prom/prometheus:v2.51.0@sha256:5ccad...` | 2.x | PASS | — | Pinned digest |
| V8 | Alertmanager | `prom/alertmanager:v0.27.0@sha256:e13b6...` | 0.27 | PASS | — | Pinned digest |
| V9 | Certbot | `certbot/certbot:v5.6.0@sha256:0107d...` | 5.x | PASS | — | Pinned digest |
| V10 | Tailscale | `tailscale:v1.96.5@sha256:dbeff...` | 1.x | PASS | — | Pinned digest |
| V11 | cAdvisor | `gcr.io/cadvisor/cadvisor:v0.49.1@sha256:3cde6...` | 0.49 | PASS | — | Pinned digest |
| V12 | pgbouncer | `pgbouncer/pgbouncer@sha256:aa8a38...` | latest | PASS | — | Pinned digest (no version tag available) |
| V13 | **rtpengine_exporter** | `python:3.12-alpine` | — | **FAIL** | **HIGH** | **Unpinned base image — floating tag** |
| V14 | Compose images | `${TSISIP_IMAGE_TAG:?must be set}` | — | PASS | — | Enforced at runtime; no floating `latest` |
| V15 | Node Exporter | `node-exporter:v1.8.0@sha256:8a57a...` | 1.8 | PASS | — | Pinned digest |
| V16 | Postgres Exporter | `postgres-exporter:v0.15.0@sha256:386b1...` | 0.15 | PASS | — | Pinned digest |

**Consistency Issues:**
- `rtpengine_exporter/Dockerfile` is the **only** image without a pinned digest or specific version tag.
- All other 20+ images use SHA-256 digests.

---

## Summary by Severity

| Severity | Brownfield | Memory | Version | Total |
|----------|------------|--------|---------|-------|
| CRITICAL | 0 | 0 | 0 | 0 |
| HIGH | 0 | 1 | 1 | 2 |
| MEDIUM | 3 | 2 | 0 | 5 |
| LOW | 5 | 0 | 0 | 5 |
| PASS | — | 4 | 16 | 20 |

---

## Top 5 Action Items (Operator-Independent)

1. **[HIGH] V13**: Pin `rtpengine_exporter/Dockerfile` base image to `python:3.12-alpine@sha256:...` digest
2. **[HIGH] C3/M1**: Add `deploy.resources.limits.memory: 8G` to `postgres` service in `docker-compose.prod.yml`
3. **[MEDIUM] C1**: Add `restart` and `deploy.resources.limits.memory` to `docker-compose.build.yml` services
4. **[MEDIUM] C2**: Add `restart` and memory limits to `docker-compose.vps.override.yml` services
5. **[MEDIUM] A1**: Replace hard-coded `'0.0.0.0'` fallback in `web/change-password.php` with `'unknown'`

## Deferred / Operator-Dependent

- DNS A record for `tsiapp.io` → 179.190.15.116
- Firewall/Tailscale ACL for SIP 5060/udp+tcp
- S3-compatible backup credentials
- Host swap tuning (130GB)
