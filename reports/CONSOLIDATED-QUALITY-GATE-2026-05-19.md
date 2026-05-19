# Consolidated Quality Gate Report

**Date**: 2026-05-19
**Project**: TSiSIP

## Executive Summary

The TSiSIP project has undergone five concurrent speckit audits: brownfield scan, memory and resource audit, version guard, remediation tracking, and live VPS production validation. All critical and high-severity findings have been resolved. The brownfield scan reduced from 1 Critical / 1 High / 6 Medium / 7 Low down to zero open critical/high items. Memory lint configured OpenSIPS shared memory (512 MB) and package memory (16 MB), added PostgreSQL tuning parameters, and placed memory limits on all 12 compose services. Version guard moved every rolling base image tag to SHA256 digest pins and replaced bare `:latest` local image tags with a `${TSISIP_IMAGE_TAG:-latest}` variable. The VPS is running seven healthy services with validated SIP signaling, functional backup/WAL archiving, and confirmed upstream filtering of public SIP ports.

The remaining concerns are operational rather than architectural: the host exhibits ~97% memory utilization from non-TSiSIP workloads, upstream ACL still blocks external SIP access to the VPS, and some deferred low-priority items (deploy script polling, spec doc references, RTPengine kernel module) remain in the backlog. No blockers prevent continued production operation on the current vps-lite profile.

## 1. Brownfield Scan

**Status**: PASS

**Top 3 Issues**:
1. **`:latest` local image tags in docker-compose.yml** (B8 — CRITICAL) — **FIXED**. All `tsisip/*` images now use `${TSISIP_IMAGE_TAG:-latest}` for traceability and rollback.
2. **`htable` module in Dockerfile** (B1 — HIGH) — **FIXED**. Removed from `include_modules`; `ratelimit` + `userblacklist` + `cachedb_local` are the canonical rate-limiting stack.
3. **Missing compose-level healthchecks** (B9 — MEDIUM) — **ACCEPTED**. Healthchecks are present in all Dockerfiles; compose-level blocks were verified as already implemented.

## 2. Memory & Resource Audit

**Status**: PASS

**Key Findings**:
- OpenSIPS `shm_mem_size` was unset (~64 MB default), risking OOM under dialog + pike + ratelimit load — now fixed at 512 MB.
- No container memory limits existed in docker-compose.yml — now fixed with limits and reservations on all 12 services.
- Host memory utilization is ~97% (60/62 GiB consumed by non-TSiSIP workloads), leaving only ~1–2 GiB free for containers.

**Recommendations**:
- Investigate and reduce host memory pressure before deploying the full 13-service production stack.
- Document the kernel-module requirement for RTPengine or increase its memory reservation to compensate for userspace fallback overhead.

## 3. Version Guard

**Status**: PASS

**Dependency Status**:
- All base images (Debian, Python, Alpine, PHP) are now pinned to SHA256 digests; previously rolling tags have been eliminated.
- Local compose images use `${TSISIP_IMAGE_TAG:-latest}` instead of hardcoded `:latest`, enabling git-SHA and semver traceability.
- OpenSIPS 3.6, PostgreSQL 16, Grafana 10.4.0, Prometheus v2.51.0, and Alertmanager v0.27.0 are aligned with the canonical spec.

**Update Recommendations**:
- Standardize on a single Python minor (3.12 recommended) across the anomaly-detector and opensips-exporter services.
- Add hashed lockfiles (`requirements.txt` with hashes or `poetry.lock`) to all Python services.

## 4. Remediation Progress

**Status**: PASS

**Fixed**:
- `:latest` image tags → `${TSISIP_IMAGE_TAG:-latest}` (B8, V11)
- `htable` removal from Dockerfile `include_modules` (B1)
- Debian digest pin (B2, V3)
- OpenSIPS memory: `shm_mem_size=512`, `pkg_mem_size=16` (M1, M2)
- PostgreSQL memory: `shared_buffers=1GB`, `work_mem=16MB`, `max_connections=200`, `shm_size: 2gb` (M3–M6)
- Container memory limits and reservations added to all 12 services (M7)
- OpenSIPS diagnostics: `memdump=1`, `memlog=30` (M10)
- Python, Alpine, and PHP base image digest pins (V7–V10)

**Still Open**:
- `apt.opensips.org` reference in canonical spec (B3) — deferred, doc-only
- Commented lines in `opensips.cfg.tpl` (B13) — deferred, documentation comments
- `sleep` statements without retry loops in deploy scripts (B14) — deferred, low impact
- RTPengine kernel table fallback (M8) — deferred, requires host kernel module installation
- Real TLS certificates and rclone/MinIO offsite replication — pending environment-dependent credentials

## 5. VPS Production Validation

**Status**: PASS

**Current Posture**:
- **Service health**: All 7 services healthy (`postgres`, `rtpengine`, `opensips`, `ocp`, `backup`, `asterisk-pbx-1`, `asterisk-pbx-2`).
- **SIP validation**: OPTIONS returns `200 OK` over UDP and TCP; unauthenticated INVITE returns `401 Unauthorized`; authenticated INVITE reaches Asterisk endpoint `1000@from-opensips`.
- **Network exposure**: VPS firewall (UFW) allows required ports; external scan shows 5060/5061 `filtered` because packets do not reach the host — remaining exposure work is upstream.
- **Backup status**: WAL archiving active; encrypted daily backups created in `/backup/daily` with `.hmac`; validate and purge scripts functional; backup metrics exporter available only on loopback (`127.0.0.1:9101`).
- **Pending gates**: First automatic cron windows (backup 02:00, purge 03:00, validate 04:00 UTC) not yet observed; PITR live restore and offsite replication not yet proven.

## Overall Quality Gate

**Result**: PASS_WITH_WARNINGS

**Blockers**: None

**Recommended Actions** (priority ordered):
1. Open upstream provider/NAT/Tailscale ACL for SIP ports 5060/tcp, 5060/udp, and 5061/tcp.
2. Investigate host memory pressure (~97% utilization) before deploying the full 13-service production stack.
3. Observe the first automatic backup/purge/validate cron window.
4. Replace self-signed certificates with real TLS certs and configure rclone/MinIO offsite replication.
5. Standardize Python services on a single minor version (3.12) and add hashed lockfiles.
6. Replace fixed `sleep` statements in deploy scripts with health-check polling (`wait-for-it` or similar).
7. Decide on RTPengine kernel module deployment vs. increased userspace memory reservation.
