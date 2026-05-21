# MemoryLint Audit Report

**Date**: 2026-05-21  
**Scope**: Docker Compose runtime, PostgreSQL, OpenSIPS, PHP/OCP, Backup  
**Auditor**: speckit.memorylint.audit equivalent

---

## Executive Summary

| Category | Status | Score |
|---|---|---|
| Container Memory Limits | Strong | 15/15 services configured |
| PostgreSQL Tuning | Good | shared_buffers aligned with container limit |
| OpenSIPS Memory | Adequate | shm_mem_size defined, pkg_mem_size implicit |
| Swap Control | Present | memswap_limit on VPS profile |
| Memory Reservations | Weak | 3 services lack reservations |
| PHP/OCP Memory | Not Configured | No explicit memory_limit or opcache settings |
| Backup Memory Impact | Uncontrolled | gzip compression without memory bounds |

**Overall Rating**: 7.5/10 — Good baseline with gaps in reservations and PHP tuning.

---

## Detailed Findings

### F1: Missing Memory Reservations (MEDIUM)
- **Services**: certbot, tailscale-cert, certbot-exporter
- **Issue**: These services have `deploy.resources.limits.memory` but no `reservations.memory`.
- **Risk**: Under memory pressure, Docker may not reserve guaranteed RAM, leading to OOM kills during certificate renewal or export.
- **Recommendation**: Add `reservations.memory` equal to 50% of limit for each service.

### F2: PHP Memory Unbounded (MEDIUM)
- **Location**: docker/ocp/
- **Issue**: No `php.ini` or Dockerfile `php_admin_value[memory_limit]` found.
- **Risk**: Default PHP memory_limit (128M) may be insufficient for large subscriber exports or insufficiently restrictive for denial-of-memory attacks.
- **Recommendation**: Create `docker/ocp/php.ini` with `memory_limit = 256M`, `max_execution_time = 30`, `opcache.memory_consumption = 64`.

### F3: Backup Compression Memory Unbounded (LOW)
- **Location**: docker/backup/backup.sh
- **Issue**: `gzip -c` runs without memory or CPU limits. Large databases (multi-GB) can spike memory during compression.
- **Risk**: OOM kill of backup container mid-dump, leaving partial/corrupt backup.
- **Recommendation**: Add `nice -n 10` and consider `pigz` (parallel gzip) with thread limits, or stream directly to S3 without local compression.

### F4: OpenSIPS pkg_mem_size Implicit (LOW)
- **Location**: opensips/opensips.cfg.tpl
- **Issue**: Only `shm_mem_size` (512MB) is documented. `pkg_mem_size` per process is left at default.
- **Risk**: Under high concurrency, per-process package memory may exhaust container limit (1GB) before shared memory is consumed.
- **Recommendation**: Document `pkg_mem_size` calculation: `(container_limit - shm_mem_size) / child_processes`.

### F5: PostgreSQL work_mem Stack Risk (LOW)
- **Location**: docker/postgres/00-tsisip.conf
- **Issue**: `work_mem = 16MB` × `max_connections = 200` = 3.2GB theoretical max. Container limit is 4GB, but shared_buffers = 1GB leaves only 3GB effective.
- **Risk**: Multiple complex queries running concurrently could approach the 4GB container limit.
- **Mitigation**: Current settings are conservative; monitor with `pg_stat_activity` and adjust if OOM observed.

---

## Positive Findings

- ✅ All 15 Compose services have explicit memory limits.
- ✅ PostgreSQL shared_buffers (1GB) is correctly set to ~25% of container reservation.
- ✅ VPS profile uses `memswap_limit` to prevent swap thrashing.
- ✅ OpenSIPS shm_mem_size is explicitly configured (512MB).

---

## Remediation Tasks

| ID | Task | Priority | File |
|---|---|---|---|
| ML-001 | Add memory reservations for certbot, tailscale-cert, certbot-exporter | MEDIUM | docker-compose.yml |
| ML-002 | Create php.ini with memory_limit and opcache settings | MEDIUM | docker/ocp/php.ini |
| ML-003 | Add nice/pigz limits to backup.sh | LOW | docker/backup/backup.sh |
| ML-004 | Document pkg_mem_size calculation in opensips.cfg.tpl | LOW | opensips/opensips.cfg.tpl |

---

## References
- `docker-compose.yml`
- `docker-compose.vps.yml`
- `docker/postgres/00-tsisip.conf`
- `opensips/opensips.cfg.tpl`
- `docker/backup/backup.sh`
