# Memory Lint Report — TSiSIP

**Date**: 2026-05-20
**Host Memory**: 62.25 GiB
**Cgroup Version**: v2
**Scope**: all

---

## Compose Resource Limits Summary

| Profile | Total Limits | Services with Limits | Services without Limits |
|---|---|---|---|
| `docker-compose.yml` (dev) | 18.0 GB | 11/11 | 0 |
| `docker-compose.prod.yml` (prod) | 22.5 GB | 11/11 | 0 |
| `docker-compose.vps.yml` (VPS-lite) | 2.9 GB | 7/7 | 0 |

✅ **All services in all compose files have memory limits.**

---

## Findings

| ID | Service | Config | Current Value | Container Limit | Risk | Severity | Recommendation |
|---|---|---|---|---|---|---|---|
| M1 | OpenSIPS | `pkg_mem_size` (`-M`) | 16 MB | 1-2 GB | Per-process memory may be tight under high connection load | LOW | Consider increasing `-M` to 32 or 64 MB if connection count grows |
| M2 | PostgreSQL | `work_mem` × `max_connections` | 16 MB × 200 = 3.2 GB | 8 GB | Worst-case memory approaches reservation (4 GB) | LOW | Monitor; consider lowering `work_mem` to 8 MB if many complex queries run concurrently |
| M3 | VPS Backup | `mem_limit` | 128 MB | 128 MB | pg_dump + gzip + encryption may OOM on large databases | MEDIUM | Increase to 256 MB or add `memswap_limit` equal to `mem_limit` |
| M4 | Prometheus | `memory` limit | 2 GB (prod) | 2 GB | Time-series data growth unbounded without retention policy | LOW | Document retention policy (default 15d) and storage limits |
| M5 | OpenSIPS | `shm_mem_size` (`-m`) | 512 MB | 1-2 GB | Not explicitly set in `.cfg.tpl` | LOW | Add explicit `shm_mem_size = 512` to `opensips.cfg.tpl` for clarity |

---

## Capacity Planning

### Dev Profile (`docker-compose.yml`)
- Total declared limits: **18.0 GB**
- Available host memory: **62.25 GB**
- Headroom: **44.25 GB** (71% available)
- Status: ✅ Safe

### Prod Profile (`docker-compose.prod.yml`)
- Total declared limits: **22.5 GB**
- Available host memory: **62.25 GB**
- Headroom: **39.75 GB** (64% available)
- Status: ✅ Safe

### VPS-Lite Profile (`docker-compose.vps.yml`)
- Total declared limits: **2.9 GB**
- Target host memory: ~4 GB
- Headroom: **~1.1 GB** (27% available)
- Status: ⚠️ Tight but within design constraints

---

## PostgreSQL Memory Calculation

```
shared_buffers:        1,024 MB
work_mem × 200 conn:   3,200 MB
maintenance_work_mem:    256 MB
---------------------------------
Worst-case estimated:    4,480 MB = 4.4 GB
Container limit:         8,000 MB = 8.0 GB
Container reservation:   4,000 MB = 4.0 GB
Headroom:                3,520 MB = 3.6 GB
```

✅ **No memory exhaustion risk.** Worst-case estimate fits comfortably within limits.

---

## Application Code Memory Risk

| Pattern | Found? | Detail |
|---|---|---|
| Unbounded SQL result sets | ❌ No | All queries use `LIMIT :limit OFFSET :offset` |
| Large file reads | ❌ No | `file_get_contents` only on small files (< 1 KB manifests, secrets) |
| Missing connection pool | ⚠️ N/A | PHP uses per-request PDO connections (standard for PHP-FPM/Apache) |
| Unbounded caches | ❌ No | No application-level caches found |
| Recursive functions without depth limit | ❌ No | None found |

---

## Runtime Snapshot

| Container | Memory Usage | Memory Limit | Usage % | Status |
|---|---|---|---|---|
| `tsisip-postgres-1` | 68.52 MiB | 8 GiB | 0.84% | ✅ Healthy |
| `tsisip-ocp-1` | 23.69 MiB | 512 MiB | 4.63% | ✅ Healthy |
| Other containers | Various | Various | < 1% | ✅ Healthy |

**OOM History**: No OOM kills detected (`dmesg` clean).

---

## Top Action Items

1. **M3 (MEDIUM)**: Increase VPS backup `mem_limit` from 128 MB to 256 MB to prevent OOM during large database dumps.
2. **M1 (LOW)**: Consider increasing OpenSIPS `-M` (pkg_mem_size) from 16 MB to 32 MB if connection load increases.
3. **M5 (LOW)**: Add explicit `shm_mem_size = 512` to `opensips.cfg.tpl` for documentation clarity.

---

*Audit completed in read-only mode. No files were modified.*
