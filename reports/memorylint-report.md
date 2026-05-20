# Memory Lint Report

> **SUPERSEDED**: This report reflects the initial scan (2026-05-18). See `reports/remediation-summary.md` for resolution status (M1–M7, M10 fixed; M8 deferred; M9 accepted) and `reports/memorylint-audit-2026-05-20.md` for the current audit. Commit `9180cad` further increased VPS backup `mem_limit` from 128 MB to 256 MB.

**Host Memory:** 62 GiB total / 1.2 GiB available (heavily utilized)
**Cgroup Version:** v2 (`stat -fc %T /sys/fs/cgroup/` returns `tmpfs` or `cgroup2fs`)

---

## Findings

| ID | Service | Config | Current Value | Limit | Risk | Severity | Recommendation |
|----|---------|--------|---------------|-------|------|----------|----------------|
| M1 | OpenSIPS | shm_mem_size | **Not set** (default ~64MB) | - | OOM under load; dialog state exhaustion | HIGH | Add `shm_mem_size=512` (MB) to opensips.cfg.tpl |
| M2 | OpenSIPS | pkg_mem_size | **Not set** (default ~4MB/proc) | - | Per-process memory pressure under many branches | MEDIUM | Add `pkg_mem_size=16` (MB) to opensips.cfg.tpl |
| M3 | PostgreSQL | shared_buffers | **Not configured** (default ~128MB) | - | Suboptimal cache; query latency | MEDIUM | Set `shared_buffers=4GB` (25% of host if dedicated) or container-aware fraction |
| M4 | PostgreSQL | work_mem | **Not configured** (default ~4MB) | - | Complex sorts spill to disk | LOW | Set `work_mem=64MB` with caution for connection count |
| M5 | PostgreSQL | max_connections | **Not configured** (default 100) | - | Memory multiplier for work_mem | LOW | Set explicitly; monitor `max_connections * work_mem` |
| M6 | PostgreSQL | shm_size | **Not set** in compose | - | `work_mem` sorts may fail for large queries | MEDIUM | Add `shm_size: 2gb` to postgres service in compose |
| M7 | All services | memory limits | **None** in docker-compose.yml | - | Noisy-neighbor risk; OOM unpredictability | MEDIUM | Add `deploy.resources.limits.memory` to each service |
| M8 | RTPengine | kernel table | `FAILED TO CREATE KERNEL TABLE 0` | - | Userspace fallback uses more memory per session | MEDIUM | Document kernel-module requirement or increase RTPengine memory reservation |
| M9 | Backup service | encryption buffer | `openssl enc -aes-256-cbc` streams | - | Large backups may spike memory | LOW | Verify streaming mode; add `--bufsize` if available |
| M10 | OpenSIPS | memdump/memlog | **Not configured** | - | No runtime memory diagnostics | LOW | Add `memdump=1` and `memlog=30` for troubleshooting |

---

## Capacity Planning

| Service | Suggested Limit | Suggested Reservation |
|---------|-----------------|----------------------|
| OpenSIPS | 1 GB | 512 MB |
| PostgreSQL | 8 GB | 4 GB |
| RTPengine | 2 GB | 1 GB |
| Asterisk (x2) | 1 GB each | 512 MB each |
| Grafana + Prometheus | 1 GB total | 512 MB total |
| Backup | 1 GB | 256 MB |
| **Total** | **~15 GB** | **~8 GB** |

**Host capacity:** 62 GiB total → ** ample headroom** for declared limits.
**Current utilization:** ~60 GiB used by host/other workloads → **available for containers is tight** (~1-2 GiB free).

> **Warning**: Host is already at ~97% memory utilization. Container startup may trigger OOM killer on host unless memory is freed or swap is configured.

---

## Runtime Snapshot

No containers currently running (`--check-runtime` not executed or no active containers).

---

## Top Action Items

1. **Set OpenSIPS shared memory** (M1 - HIGH): Add `shm_mem_size=512` to `opensips.cfg.tpl`. Default 64MB is insufficient for dialog tracking + pike + ratelimit tables under production load.
2. **Add container memory limits** (M7 - MEDIUM): Define `deploy.resources.limits.memory` and `reservations.memory` for every service in docker-compose.yml to prevent host OOM.
3. **Configure PostgreSQL memory** (M3/M6 - MEDIUM): Create a `postgresql.conf` overlay with `shared_buffers`, `work_mem`, `max_connections` and add `shm_size` to compose service.
4. **Investigate host memory pressure**: 60/62 GiB in use is critical. Identify non-TSiSIP consumers before deploying this stack.

---

*Would you like me to generate the specific OpenSIPS and PostgreSQL memory configuration snippets?*
