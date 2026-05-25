# TSiSIP Memory Lint Report

**Generated:** 2026-05-19
**Scope:** `all`
**Runtime Check:** Enabled
**Host Memory:** 62.25 GiB total / ~33 GiB available
**Cgroup Version:** v2 (`cgroup2fs`)
**Docker Daemon Memory:** 62.25 GiB

---

## 1. Compose Resource Limits Summary

### docker-compose.yml (Development)

| Service | Memory Limit | Reservation | shm_size |
|---------|-------------:|------------:|---------:|
| postgres | 8G | 4G | 2gb |
| rtpengine | 2G | 1G | — |
| opensips | 1G | 512M | — |
| asterisk-pbx-1 | 1G | 512M | — |
| asterisk-pbx-2 | 1G | 512M | — |
| ocp | 512M | 256M | — |
| admin-api | 256M | 128M | — |
| prometheus | 2G | 512M | — |
| alertmanager | 512M | 128M | — |
| anomaly-detector | 512M | 256M | — |
| grafana | 512M | 256M | — |
| opensips-exporter | 256M | 128M | — |
| certbot | 256M | 128M | — |
| tailscale-cert | 128M | 64M | — |
| certbot-exporter | 64M | 32M | — |
| backup | 1G | 256M | — |
| postgres-exporter | 128M | 64M | — |
| node-exporter | 128M | 64M | — |

**Total declared limits:** ~19.5 GB
**Status:** All services have memory limits and reservations.

### docker-compose.prod.yml (Production)

| Service | Memory Limit | Reservation | shm_size |
|---------|-------------:|------------:|---------:|
| postgres | 8G | 4G | **Missing** |
| rtpengine | 2G | 1G | — |
| opensips | 2G | 1G | — |
| asterisk-pbx-1 | 2G | 1G | — |
| asterisk-pbx-2 | 2G | 1G | — |
| ocp | 1G | 512M | — |
| admin-api | 256M | 128M | — |
| prometheus | 2G | 1G | — |
| alertmanager | 512M | 256M | — |
| anomaly-detector | 1G | 512M | — |
| grafana | 1G | 512M | — |
| opensips-exporter | 512M | 256M | — |
| certbot | 256M | — | — |
| tailscale-cert | 128M | — | — |
| certbot-exporter | 64M | — | — |
| backup | 1G | 512M | — |

**Total declared limits:** ~23.7 GB
**Status:** `shm_size` missing on PostgreSQL; some services lack memory reservations.

### docker-compose.vps.yml (VPS-Lite)

| Service | Memory Limit | Reservation | shm_size |
|---------|-------------:|------------:|---------:|
| postgres | 2G | 1G | 1gb |
| rtpengine | 1G | 512M | — |
| opensips | 1G | 512M | — |
| asterisk-pbx-1 | 1G | 512M | — |
| asterisk-pbx-2 | 1G | 512M | — |
| ocp | 512M | 256M | — |
| admin-api | 256M | 128M | — |
| certbot | 128M | 64M | — |
| certbot-exporter | 64M | 32M | — |
| backup | 1G | 256M | — |

**Total declared limits:** ~7.9 GB
**Status:** All services have memory limits and reservations; `shm_size` appropriately scaled for 2GB container.

---

## 2. Detailed Findings

| ID | Service | Config | Current Value | Limit | Risk | Severity | Recommendation |
|----|---------|--------|---------------|-------|------|----------|----------------|
| M1 | OpenSIPS | `pkg_mem_size` (`-M`) | 32 MB | 1–2G | Per-child pkg memory is half the documented recommendation; under load with TLS + WebSocket + dialog state, children may exhaust pkg memory and crash | **HIGH** | Increase `-M` to `64` or `128` in `Dockerfile` CMD to align with config comment `(1G - 512M) / 8 children = ~64MB` |
| M2 | PostgreSQL (prod) | `shm_size` | *default 64MB* | 8G | Production compose omits `shm_size`; default 64MB may be insufficient if `shared_buffers` is tuned >64MB, causing PostgreSQL startup failure or performance degradation | **HIGH** | Add `shm_size: 2gb` to `docker-compose.prod.yml` postgres service to match dev profile |
| M3 | RTPengine (prod) | Port range | 10000–20000 (10,001 ports) | 2G | Kernel forwarding unavailable in container; userspace fallback consumes more memory per session. 10K ports x per-session tracking = potentially hundreds of MB under peak load | **MEDIUM** | Add explicit `--table=0` to force userspace mode and avoid kernel fallback attempts; monitor RTPengine memory under load tests |
| M4 | Prometheus (all) | TSDB retention size | `--storage.tsdb.retention.size=10GB` | 2G | TSDB disk retention is 10GB but memory limit is 2GB. While TSDB uses mmap, high cardinality or long retention can cause memory pressure | **MEDIUM** | Ensure Prometheus memory limit scales with TSDB retention; consider `--storage.tsdb.retention.size` <= memory limit or add memory-based alerting |
| M5 | Certbot (prod) | Memory reservation | *none* | 256M | No memory reservation means scheduler cannot guarantee memory availability during cert rotation | **LOW** | Add `reservations: {memory: 128M}` to certbot service in `docker-compose.prod.yml` |
| M6 | Tailscale-cert (prod) | Memory reservation | *none* | 128M | Same as M5 — no reservation for critical TLS path | **LOW** | Add `reservations: {memory: 64M}` to tailscale-cert service |
| M7 | Certbot-exporter (prod) | Memory reservation | *none* | 64M | Same as M5 — no reservation | **LOW** | Add `reservations: {memory: 32M}` to certbot-exporter service |
| M8 | All containers | OOM score / graceful handler | *none configured* | — | No `oom_score_adj`, `restart: always` (only `unless-stopped`/`on-failure`), or in-app OOM handlers | **LOW** | Consider `restart: always` for critical path services (OpenSIPS, PostgreSQL, RTPengine); add memory-pressure alerting |
| M9 | OpenSIPS-exporter | Cache TTL | 10 seconds | 256M | Cache `_cache` dict grows unbounded with unique `(cmd, params)` combinations; while currently low cardinality, long uptimes with dynamic MI commands could accumulate entries | **LOW** | Add cache entry expiration or periodic `_cache` pruning beyond TTL sweep |

---

## 3. OpenSIPS Memory Configuration

**Source:** `Dockerfile` (line 51)

```dockerfile
CMD ["/usr/local/sbin/opensips", "-F", "-f", "/etc/opensips/opensips.cfg", "-m", "512", "-M", "32"]
```

| Parameter | Value | Assessment |
|-----------|-------|------------|
| `shm_mem_size` (`-m`) | 512 MB | Adequate for dialog state, pike, ratelimit tables, and cachedb_local |
| `pkg_mem_size` (`-M`) | 32 MB | **LOW** — Config comment recommends ~64MB per child for 1GB container limit |
| `children` (default) | 8 | OpenSIPS default; not overridden |
| **Calculated pkg total** | 8 x 32 = 256 MB | |
| **OpenSIPS total memory** | 512 + 256 = 768 MB | |
| **Dev/VPS container limit** | 1 GB | Headroom: ~256 MB (tight under TLS/WebSocket load) |
| **Prod container limit** | 2 GB | Headroom: ~1.2 GB (acceptable) |

**Config Comment (opensips.cfg.tpl:28–31):**
> "pkg_mem_size per process should be ~((container_limit - shm_mem_size) / children). Example: (1GB - 512MB) / 8 children = ~64MB per child."

**Discrepancy:** Dockerfile uses `-M 32` instead of the documented `-M 64`.

---

## 4. PostgreSQL Memory Parameters

### Development (`docker-compose.yml`)

| Parameter | Value | Notes |
|-----------|-------|-------|
| `shared_buffers` | 1 GB | ~12.5% of 8GB container limit |
| `effective_cache_size` | 3 GB | Advisory only |
| `work_mem` | 16 MB | |
| `maintenance_work_mem` | 256 MB | |
| `max_connections` | 200 | |
| `wal_buffers` | 16 MB | |
| `shm_size` | 2 GB | Sufficient for shared_buffers |

**Capacity check:**
```
shared_buffers + (work_mem x max_connections) = 1GB + (16MB x 200) = 1GB + 3.2GB = 4.2GB
Container limit: 8GB
Headroom: ~3.8GB
```

### Production (`docker-compose.prod.yml`)

| Parameter | Value | Notes |
|-----------|-------|-------|
| `shared_buffers` | *default (~128MB)* | Not explicitly set in command |
| `work_mem` | *default (~4MB)* | Not explicitly set |
| `max_connections` | *default (100)* | Not explicitly set |
| `shm_size` | **Missing** | Docker default = 64MB |

**Capacity check:**
```
shared_buffers + (work_mem x max_connections) ~= 128MB + (4MB x 100) = 128MB + 400MB = 528MB
Container limit: 8GB
Headroom: ~7.5GB
```

**Risk:** Even though memory headroom is enormous, missing `shm_size` can cause PostgreSQL to fail if it attempts to allocate shared memory larger than `/dev/shm`. The production profile should either explicitly set `shm_size` or document why it is omitted.

### VPS (`docker-compose.vps.yml`)

| Parameter | Value | Notes |
|-----------|-------|-------|
| `shared_buffers` | 512 MB | ~25% of 2GB container limit |
| `effective_cache_size` | 1536 MB | |
| `work_mem` | 8 MB | |
| `maintenance_work_mem` | 128 MB | |
| `max_connections` | 100 | |
| `wal_buffers` | 16 MB | |
| `shm_size` | 1 GB | Sufficient |

**Capacity check:**
```
shared_buffers + (work_mem x max_connections) = 512MB + (8MB x 100) = 512MB + 800MB = 1.312GB
Container limit: 2GB
Headroom: ~688MB (tight but workable for VPS)
```

---

## 5. RTPengine Configuration

| Profile | Port Range | Memory Limit | `listen-ng` | Kernel Forwarding |
|---------|-----------:|-------------:|-------------|-------------------|
| Dev | 10000–10050 (51 ports) | 2G | `0.0.0.0:22222` | Not explicitly disabled |
| Prod | 10000–20000 (10,001 ports) | 2G | `${RTPENGINE_INTERNAL_IP}:22222` | Not explicitly disabled |
| VPS | 10000–10999 (1,000 ports) | 1G | `${RTPENGINE_INTERNAL_IP}:22222` | Not explicitly disabled |

**Note:** RTPengine containers lack the kernel DKMS module; they will fallback to userspace forwarding. The `--table=0` flag is not passed, so RTPengine may attempt kernel table initialization before falling back. Userspace mode consumes more CPU and memory per session than kernel mode.

**Estimation (userspace fallback):**
- 10,001 ports in prod ~= max ~2,500–5,000 concurrent SRTP calls
- Per-call userspace overhead: ~50–100 KB
- Peak memory estimate: ~500 MB–1 GB
- Container limit: 2G (acceptable headroom)

---

## 6. Application Code Patterns

### PHP (OCP Web Application)

**Source:** `web/**/*.php`, `docker/ocp/php.ini`

| Pattern | Status | Evidence |
|---------|--------|----------|
| SQL queries with `LIMIT` | Pass | All list endpoints use `LIMIT ? OFFSET ?` or `:limit` / `:offset` bindings |
| Pagination | Pass | `common/pagination.php` reused across CRUD pages |
| PHP `memory_limit` | Pass | 256 MB in `docker/ocp/php.ini` |
| PHP `max_execution_time` | Pass | 30 seconds |
| PHP `max_input_vars` | Pass | 1,000 |
| Unbounded `fetchAll()` | Pass | None found; all queries paginated |
| Large file reads | Pass | No `file_get_contents` on untrusted/large inputs detected |
| Recursive functions | Pass | None detected |

### Python (Anomaly Detector)

**Source:** `docker/anomaly-detector/detector.py`, `baseline.py`

| Pattern | Status | Evidence |
|---------|--------|----------|
| Unbounded buffer | Pass | `event_buffer` and `ip_tracker` cleared every `WINDOW_SECONDS` (60s) |
| Unbounded cache | Pass | `TrafficBaseline` uses `deque(maxlen=1440)` |
| Unbounded `banned_ips` | Pass | `set()` defined but **never populated** in current code — always empty |
| Thread safety | Pass | `threading.Lock()` around shared state |

### Python (OpenSIPS Exporter)

**Source:** `docker/opensips-exporter/exporter.py`

| Pattern | Status | Evidence |
|---------|--------|----------|
| Cache with TTL | Pass | `CACHE_TTL_SECONDS = 10`; global `_cache_timestamp` |
| Cache eviction | Warning | `_cache` dict grows with unique `(cmd, params)` keys; no LRU or size limit |

---

## 7. Runtime Snapshot

`docker stats --no-stream` captured at scan time:

| Container | Memory Usage | Limit | % Used | Status |
|-----------|-------------:|------:|-------:|--------|
| tsisip-postgres-1 | 97.42 MB | 8 GB | 1.19% | Healthy |
| tsisip-rtpengine-1 | 28.44 MB | 2 GB | 1.39% | Healthy |
| tsisip-opensips-1 | 187.2 MB | 1 GB | 18.28% | Healthy |
| tsisip-asterisk-pbx-1-1 | 106.9 MB | 1 GB | 10.44% | Healthy |
| tsisip-asterisk-pbx-2-1 | 75.05 MB | 1 GB | 7.33% | Healthy |
| tsisip-ocp-1 | 38.19 MB | 512 MB | 7.46% | Healthy |
| tsisip-admin-api-1 | 18.77 MB | 256 MB | 7.33% | Healthy |
| tsisip-prometheus-1 | 79.98 MB | 2 GB | 3.91% | Healthy |
| tsisip-alertmanager-1 | 22 MB | 512 MB | 4.30% | Healthy |
| tsisip-anomaly-detector-1 | 32.34 MB | 512 MB | 6.32% | Healthy |
| tsisip-grafana-1 | 54.88 MB | 512 MB | 10.72% | Healthy |
| tsisip-opensips-exporter-1 | 16.33 MB | 256 MB | 6.38% | Healthy |
| tsisip-certbot-exporter-1 | 15.45 MB | 64 MB | 24.15% | Healthy (highest %) |
| tsisip-backup-1 | 56.43 MB | 1 GB | 5.51% | Healthy |
| tsisip-postgres-exporter-1 | 15.02 MB | 128 MB | 11.73% | Healthy |
| tsisip-node-exporter-1 | 17.43 MB | 128 MB | 13.62% | Healthy |
| tsisip-certbot-1 | 0 B | — | 0.00% | Stopped |
| tsisip-tailscale-cert-1 | 0 B | — | 0.00% | Stopped |

**OOM Kills:** `dmesg` not accessible (permission denied); no OOM kills detected via runtime stats.

**Alert Threshold:** No containers exceed 80% memory utilization. Highest is certbot-exporter at 24.15%.

---

## 8. Capacity Planning

### Per-Profile Totals

| Profile | Total Limits | Host Available | Headroom | Fit |
|---------|-------------:|---------------:|----------|-----|
| Development | ~19.5 GB | ~33 GB | ~13.5 GB | Fits |
| Production | ~23.7 GB | ~33 GB | ~9.3 GB | Fits |
| VPS-Lite | ~7.9 GB | ~33 GB | ~25.1 GB | Fits |

**Note:** Headroom calculations assume all containers simultaneously hit their memory limits, which is unrealistic. Actual observed TSiSIP runtime usage is ~860 MB.

---

## 9. Top Action Items

1. **HIGH — Fix OpenSIPS pkg memory:** Update `Dockerfile` line 51 to increase `-M 32` to `-M 64` (or `-M 128` for production headroom) to match the documented per-child memory calculation in `opensips.cfg.tpl`.

2. **HIGH — Add PostgreSQL `shm_size` to production:** Add `shm_size: 2gb` (or at least `1gb`) to the `postgres` service in `docker-compose.prod.yml` to prevent shared memory allocation failures.

3. **MEDIUM — Document RTPengine userspace mode:** Add `--table=0` to RTPengine `command` in all compose files to explicitly disable kernel forwarding attempts and avoid unnecessary startup overhead in containers.

4. **MEDIUM — Align Prometheus memory with TSDB retention:** Either reduce `--storage.tsdb.retention.size` or increase Prometheus memory limit; add a runbook note that 10GB TSDB disk retention + 2GB memory limit is a known ratio.

5. **LOW — Add missing memory reservations in production:** `certbot`, `tailscale-cert`, and `certbot-exporter` in `docker-compose.prod.yml` lack `reservations.memory`.

6. **LOW — Add cache size guard to OpenSIPS exporter:** Implement a max size or LRU eviction on the `_cache` dict to prevent theoretical unbounded growth if MI command variety increases.

7. **LOW — Add memory-based alerting:** Ensure Prometheus rules alert when any TSiSIP container exceeds 80% of its memory limit for sustained periods.

---

*Report generated by speckit-memorylint. This is a read-only audit; no files were modified.*
