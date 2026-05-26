# TSiSIP Memory Lint Audit Report

> **Audit Date:** 2026-05-26  
> **Skill:** speckit-memorylint  
> **Scope:** Docker Compose (dev/prod/vps/monitoring), OpenSIPS, PostgreSQL, RTPengine, Application Code, Host Runtime  
> **Auditor:** Kimi Code CLI (subagent)  
> **Project:** /home/b0yz4kr14/Projects/TSiSIP

---

## 1. Executive Summary

This audit scanned the TSiSIP project for memory-related misconfigurations across Docker Compose profiles, OpenSIPS shared/private memory, PostgreSQL memory parameters, RTPengine runtime, and application code patterns. **3 CRITICAL and 4 HIGH severity findings were identified**, all of which represent calculable or likely OOM (Out-of-Memory) risks under production load.

### Risk Distribution

| Severity | Count | Categories |
|----------|-------|------------|
| CRITICAL | 3 | OpenSIPS over-allocation, PostgreSQL memory exhaustion |
| HIGH | 4 | PostgreSQL shm/reservation, unbounded PHP queries |
| MEDIUM | 5 | Missing pooler, missing observability, swap masking |
| LOW | 2 | Missing OOM tuning, missing cAdvisor |

---

## 2. Host Runtime Context

| Parameter | Value |
|-----------|-------|
| **Host Total Memory** | 62.25 GiB |
| **Host Available Memory** | ~42 GiB |
| **Swap Total** | 130 GiB |
| **Swap Used** | 1.9 GiB |
| **Cgroup Version** | v2 |
| **Docker Daemon Memory** | 62.25 GiB |
| **Recent OOM Kills** | None detected (`dmesg` clean) |

### Observations
- The host has **extremely large swap (130 GiB, 2× physical RAM)**. This can mask memory pressure, causing performance degradation (swap thrashing) rather than fast failure, making root-cause diagnosis harder.
- No OOM kills in recent history, but this is a greenfield/development host; production load patterns have not been exercised.

---

## 3. Docker Compose Capacity Planning

### 3.1 Profile Summary

| Profile | Services | Total Limits | Total Reservations | Stated Target Host |
|---------|----------|--------------|--------------------|--------------------|
| `docker-compose.yml` (dev) | 18 | ~18.5 GB | ~8.4 GB | N/A |
| `docker-compose.prod.yml` | 15 | ~23.2 GB | ~11.3 GB | N/A |
| `docker-compose.vps.yml` | 10 | ~7.5 GB | ~3.8 GB | **~4 GB RAM** |
| `docker-compose.monitoring.yml` | 7 | ~4.0 GB | ~1.8 GB | Overlay |

### 3.2 Headroom Analysis

| Profile | Limits vs Host (62G) | Reservations vs Host | Verdict |
|---------|----------------------|----------------------|---------|
| Dev | 30% utilization | 14% utilization | ✅ Adequate |
| Prod | 37% utilization | 18% utilization | ✅ Adequate |
| VPS | **12% of host, but 188% of stated 4G target** | 62% of 4G target | ⚠️ **Misaligned** |
| Monitoring | 6% utilization | 3% utilization | ✅ Adequate |

**Finding:** The VPS profile declares a target of `~4GB RAM` yet sums to **7.5 GB in declared limits**. While Docker limits are per-container upper bounds (not reservations), simultaneous memory pressure across multiple services on a 4 GB host will trigger kernel-level OOM kills regardless of cgroup limits.

---

## 4. Detailed Findings

### Findings Table

| ID | Service / Component | Config Source | Current Value | Container Limit | Risk | Severity | Recommendation |
|----|---------------------|---------------|---------------|-----------------|------|----------|----------------|
| **M1** | OpenSIPS (all profiles) | `Dockerfile` + `docker-compose*.yml` | See per-profile breakdown below | See below | **Startup OOM or runtime OOM-kill** | **CRITICAL** | Reduce `-m`/`-M` or raise container limits; set explicit `children` |
| **M2** | PostgreSQL (prod) | `docker-compose.prod.yml` | `shared_buffers=2GB` + `work_mem=32MB` × 300 + `maintenance_work_mem=512MB` = **~12.1 GB theoretical max** | 8 GB | **OOM under parallel query load** | **CRITICAL** | Reduce `work_mem` to 16 MB or `max_connections` to 150; or raise limit to 16 GB |
| **M3** | PostgreSQL (prod) | `docker-compose.prod.yml` | `shm_size=2gb` with `shared_buffers=2GB` | 8 GB | **Shared memory allocation failure on startup or under WAL pressure** | **HIGH** | Increase `shm_size` to `3gb` minimum |
| M4 | PostgreSQL (dev) | `docker-compose.yml` | `shared_buffers=1GB` + `work_mem=16MB` × 200 + `maintenance_work_mem=256MB` = **~4.5 GB** | 8 GB (reservation 4 GB) | Memory pressure exceeds reservation; cgroup throttling | HIGH | Reduce `work_mem` to 8 MB or raise reservation to 6 GB |
| M5 | OCP / LGPD Export | `web/cli/export-audit-lgpd.php` | `fetchAll()` on unbounded `SELECT … ORDER BY` (no `LIMIT`) | 512 MB (OCP container) | PHP memory exhaustion for subscribers with large history | HIGH | Add `LIMIT` clauses and paginate; stream JSON output |
| M6 | OCP / Audit Integrity | `web/common/audit.php:181` | `SELECT * FROM ocp_audit_log ORDER BY id ASC` (no `LIMIT`) | 512 MB (OCP container) | Memory exhaustion as audit log grows | HIGH | Paginate integrity verification in chunks |
| M7 | OpenSIPS | `opensips/opensips.cfg.tpl` | No `children` parameter set | N/A | Process count auto-scales with CPU cores, amplifying pkg memory usage | MEDIUM | Add explicit `children=8` (or appropriate for container limit) |
| M8 | PostgreSQL | All profiles | No connection pooler configured | N/A | Per-request connection overhead; risk of `max_connections` exhaustion | MEDIUM | Deploy PgBouncer in transaction pool mode |
| M9 | Prometheus Alerts | `docker/prometheus/alert-rules.yml` | `HostMemoryCritical` only (`node_memory_MemAvailable_bytes`) | N/A | No per-container memory utilization alerting | MEDIUM | Add `container_memory_working_set_bytes / container_spec_memory_limit_bytes` alerts |
| M10 | Host Swap | Host OS | `130 GiB` swap on `62 GiB` RAM | N/A | Swap thrashing masks memory pressure; slows failure detection | MEDIUM | Reduce swap to `8-16 GiB` or set `vm.swappiness=10` |
| M11 | RTPengine | `docker/rtpengine/Dockerfile` + compose | Userspace fallback (no kernel module); 10,000 ports; no runtime memory flags | 2 GB (1 GB VPS) | Userspace mode uses ~50-100 KB per media leg; 5,000 concurrent calls ≈ 500 MB-1 GB | MEDIUM | Add memory-based alerting; consider `--table=0` documentation is correct but monitor |
| M12 | Critical Services | All compose files | No `oom_score_adj` set | N/A | OOM killer may terminate postgres/opensips before tailscale/certbot | LOW | Set `oom_score_adj: -500` on postgres, opensips, rtpengine |
| M13 | Container Metrics | Monitoring stack | No cAdvisor or docker daemon metrics exposed | N/A | Prometheus cannot scrape per-container memory usage | LOW | Add cAdvisor sidecar or enable Docker metrics endpoint |

---

### M1 — OpenSIPS Memory Over-Allocation (CRITICAL)

OpenSIPS memory is configured via `-m` (shared memory, MB) and `-M` (private/pkg memory per process, MB). Total approximate allocation = `(children + 1) × M + m`. The default `children` in OpenSIPS 3.6 is **8** (or auto-detected from CPU cores, which on this host may be higher).

#### Dev Profile (`docker-compose.yml`)

| Parameter | Value |
|-----------|-------|
| `-m` (shm) | 512 MB (from `Dockerfile` CMD) |
| `-M` (pkg) | 64 MB (from `Dockerfile` CMD) |
| Container limit | 1 GB |
| **Calculated min** | `(8+1) × 64 + 512 = **1,088 MB**` |
| **Overage** | **+64 MB (+6.3%)** |

#### Production Profile (`docker-compose.prod.yml`)

| Parameter | Value |
|-----------|-------|
| `-m` (shm) | 1,024 MB (explicit `command` override) |
| `-M` (pkg) | 128 MB (explicit `command` override) |
| Container limit | 2 GB |
| **Calculated min** | `(8+1) × 128 + 1,024 = **2,176 MB**` |
| **Overage** | **+128 MB (+6.3%)** |

#### VPS Profile (`docker-compose.vps.yml`)

| Parameter | Value |
|-----------|-------|
| `-m` (shm) | 640 MB (explicit `command` override) |
| `-M` (pkg) | 80 MB (explicit `command` override) |
| Container limit | 1 GB |
| **Calculated min** | `(8+1) × 80 + 640 = **1,360 MB**` |
| **Overage** | **+336 MB (+32.8%)** |

#### Impact
OpenSIPS will either:
1. **Fail to start** if mmap allocations exceed the cgroup limit at startup, or
2. **Be OOM-killed immediately after startup** when the kernel accounts RSS + page tables.

On high-core hosts (e.g., 16+ cores), if OpenSIPS auto-detects more children, the overage becomes dramatically worse.

#### Recommendation
1. **Set explicit `children=8`** in `opensips/opensips.cfg.tpl` (or `children=4` for VPS).
2. **Recalculate and adjust**:
   - **Dev**: `-m 512 -M 48` → `(9×48)+512 = 944 MB` (fits in 1 GB with OS overhead)  
     *OR* raise container limit to **1.5 GB**.
   - **Prod**: `-m 1024 -M 96` → `(9×96)+1024 = 1,888 MB` (fits in 2 GB)  
     *OR* raise container limit to **2.5 GB**.
   - **VPS**: `-m 512 -M 48` → `(9×48)+512 = 944 MB` (fits in 1 GB)  
     *OR* raise container limit to **1.5 GB**.

---

### M2 — PostgreSQL Theoretical Max Memory Exceeds Container Limit (CRITICAL)

**Profile:** `docker-compose.prod.yml`

| Parameter | Value |
|-----------|-------|
| `shared_buffers` | 2 GB |
| `work_mem` | 32 MB |
| `max_connections` | 300 |
| `maintenance_work_mem` | 512 MB |
| Container limit | 8 GB |

**Conservative worst-case calculation:**
```
shared_buffers + (work_mem × max_connections) + maintenance_work_mem
= 2 GB + (32 MB × 300) + 512 MB
= 2 GB + 9.6 GB + 0.5 GB
= ~12.1 GB
```

This **exceeds the 8 GB container limit by ~50%**. While not every connection performs a sort/hash simultaneously, under load spikes (e.g., trunk failover causing many parallel CDR inserts + auth queries), PostgreSQL workers can collectively allocate enough memory to trigger an OOM kill.

#### Recommendation
Choose **one** of the following:
1. **Raise container limit to 16 GB** and reservation to 8 GB (aligns with the memory model).
2. **Reduce `work_mem` to 16 MB** → theoretical max = `2 + 4.8 + 0.5 = 7.3 GB` (fits in 8 GB).
3. **Reduce `max_connections` to 150** and add PgBouncer (see M8).

---

### M3 — PostgreSQL shm_size Insufficient for shared_buffers (HIGH)

**Profile:** `docker-compose.prod.yml`

| Parameter | Value |
|-----------|-------|
| `shm_size` | 2 GB |
| `shared_buffers` | 2 GB |

PostgreSQL places `shared_buffers`, WAL buffers, lock space, and other shared structures in POSIX shared memory (`/dev/shm`). The `shm_size` must be **strictly greater** than `shared_buffers` to accommodate these additional structures. With `shm_size=2gb` and `shared_buffers=2GB`, there is **zero headroom**.

#### Recommendation
Increase `shm_size` to **3 GB** in `docker-compose.prod.yml`:
```yaml
shm_size: 3gb
```

---

### M4 — PostgreSQL Memory Exceeds Reservation in Dev (HIGH)

**Profile:** `docker-compose.yml`

| Parameter | Value |
|-----------|-------|
| `shared_buffers` | 1 GB |
| `work_mem` | 16 MB |
| `max_connections` | 200 |
| `maintenance_work_mem` | 256 MB |
| Container limit | 8 GB |
| Container reservation | 4 GB |

**Calculation:**
```
1 GB + (16 MB × 200) + 256 MB = ~4.5 GB
```

This exceeds the **4 GB reservation**. While it fits within the 8 GB limit, Docker/Kubernetes will throttle or penalize the container when it exceeds its reservation on a constrained host, causing performance degradation.

#### Recommendation
- Reduce `work_mem` to **8 MB** → max = `1 + 1.6 + 0.256 = ~2.9 GB` (comfortably under 4 GB reservation).
- Or raise reservation to **6 GB**.

---

### M5 — Unbounded SQL Queries in LGPD Export CLI (HIGH)

**File:** `web/cli/export-audit-lgpd.php`

The script executes three unbounded queries:

```php
$cdrStmt = $pdo->prepare(
    "SELECT … FROM cdr WHERE from_user = :subscriber OR to_user = :subscriber ORDER BY call_start DESC"
); // No LIMIT

$ocpAuditStmt = $pdo->prepare(
    "SELECT … FROM ocp_audit_log WHERE username = :subscriber … ORDER BY event_time DESC"
); // No LIMIT

$authAuditStmt = $pdo->prepare(
    "SELECT … FROM auth_audit_log WHERE username = :subscriber ORDER BY event_time DESC"
); // No LIMIT
```

All three use `fetchAll(PDO::FETCH_ASSOC)`, loading the entire result set into PHP memory, then `json_encode()` builds a single in-memory string, and `file_put_contents()` writes it. For a high-volume subscriber (e.g., a trunk provider or enterprise PBX with years of CDRs), this can exhaust the **256 MB PHP `memory_limit`** or the **512 MB OCP container limit**.

#### Recommendation
1. Add `LIMIT` clauses (e.g., `LIMIT 10000`) with pagination loops.
2. Stream JSON output using a generator or `fopen()` + `fwrite()` instead of `json_encode()` on the full array.
3. Consider exporting to a temporary file on disk and streaming to stdout.

---

### M6 — Unbounded Audit Log Integrity Check (HIGH)

**File:** `web/common/audit.php:181`

```php
$rows = $pdo->query('SELECT * FROM ocp_audit_log ORDER BY id ASC')
             ->fetchAll(PDO::FETCH_ASSOC);
```

This integrity verification loads **the entire audit log** into memory. As the project targets LGPD compliance with 90-day retention, this table will grow continuously. Eventually this will OOM the OCP container.

#### Recommendation
Paginate the integrity check in chunks (e.g., `LIMIT 1000` with `id > last_id` cursor):
```php
$lastId = 0;
while (true) {
    $stmt = $pdo->prepare('SELECT * FROM ocp_audit_log WHERE id > ? ORDER BY id ASC LIMIT 1000');
    $stmt->execute([$lastId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rows)) break;
    // verify chunk
    $lastId = end($rows)['id'];
}
```

---

### M7 — Missing Explicit OpenSIPS children Count (MEDIUM)

**File:** `opensips/opensips.cfg.tpl`

There is no `children` parameter in the OpenSIPS configuration. OpenSIPS 3.6 may default to 8 or auto-detect the number of CPU cores. On a high-core host, this could spawn 16, 32, or more child processes, each consuming `-M` megabytes of pkg memory. This amplifies the M1 over-allocation risk.

#### Recommendation
Add to `opensips/opensips.cfg.tpl`:
```
# Fixed children count for predictable memory sizing inside containers
children = 8
```

For the VPS profile, consider `children = 4` to further reduce memory footprint.

---

### M8 — Missing Connection Pooler (MEDIUM)

**Profile:** All

PostgreSQL is configured with `max_connections = 200` (dev) / `300` (prod). The OCP, admin_api, backup, and OpenSIPS services each open direct connections. There is no PgBouncer or similar pooler. Under load:
- Each connection consumes backend memory (~5-10 MB for backends + work_mem).
- Connection setup/teardown overhead adds latency.
- Risk of `FATAL: sorry, too many clients already`.

#### Recommendation
Deploy **PgBouncer** in transaction pool mode as a sidecar or shared service:
- Target `max_connections` on PostgreSQL: **100-150**.
- PgBouncer `default_pool_size`: **50**.
- PgBouncer `max_client_conn`: **1000**.

---

### M9 — Missing Container Memory Alerts (MEDIUM)

**File:** `docker/prometheus/alert-rules.yml`

The only memory alert is `HostMemoryCritical` (host-level `< 5%` available). There are **no per-container memory utilization alerts**. Without cAdvisor (see M13), these metrics are unavailable anyway.

#### Recommendation
Once cAdvisor is deployed, add:
```yaml
- alert: ContainerMemoryHigh
  expr: (container_memory_working_set_bytes / container_spec_memory_limit_bytes) > 0.85
  for: 5m
  labels:
    severity: warning
  annotations:
    summary: "Container {{ $labels.name }} memory usage above 85%"
```

---

### M10 — Excessive Host Swap (MEDIUM)

**Host:** 130 GiB swap on 62 GiB RAM

With swap larger than physical RAM, memory pressure will cause pages to be swapped out rather than triggering fast OOM kills. This leads to:
- **Performance degradation** (swap thrashing) before failure.
- **Silent latency spikes** that are hard to correlate with memory pressure.
- **False confidence** that the system is healthy while it is thrashing.

#### Recommendation
1. Reduce swap to **8–16 GiB** (12–25% of physical RAM).
2. Or set `vm.swappiness=10` to strongly prefer OOM over swap under pressure.

---

### M11 — RTPengine Userspace Mode Memory Risk (MEDIUM)

**Profile:** All

The RTPengine container runs in userspace mode (no kernel forwarding module installed). Userspace mode allocates memory per media session (call leg) in userland. With:
- **Dev/Prod:** 10,001 UDP ports (10000–20000) → ~5,000 concurrent bidirectional calls.
- **VPS:** 1,000 UDP ports (10000–10999) → ~500 concurrent bidirectional calls.

Estimated memory at full capacity: **~50–100 KB per leg × 2 legs × 5,000 calls = 500 MB–1 GB**.

The container limits are:
- Dev/Prod: **2 GB** → adequate but tight under full load + SRTP/DTLS overhead.
- VPS: **1 GB** → could exhaust under moderate load.

#### Recommendation
1. Monitor `rtpengine_ports_used` and add a memory-based alert if container metrics become available.
2. For VPS, consider reducing port range to **5,000 ports** if the tenant base is small.

---

### M12 — Missing OOM Score Adjustments (LOW)

**Profile:** All compose files

No service has `oom_score_adj` set. In a memory-pressure scenario, the Linux OOM killer uses heuristics (memory usage, runtime, niceness) to select a victim. This could lead to **PostgreSQL or OpenSIPS being killed before tailscale_cert or certbot**, which are far less critical.

#### Recommendation
Add to critical services:
```yaml
# postgres, opensips, rtpengine
deploy:
  resources:
    limits:
      memory: ...
# Not directly in compose, but via runtime or systemd unit:
# oom_score_adj: -500
```

*Note: Docker Compose does not natively expose `oom_score_adj`. Use systemd drop-ins or Docker run `--oom-score-adj` on the host.*

---

### M13 — Missing cAdvisor for Container Metrics (LOW)

**Profile:** Monitoring stack

The Prometheus stack scrapes `node_exporter` (host metrics) and application exporters, but there is no **cAdvisor** or **Docker metrics endpoint** enabled. Consequently, per-container memory usage (`container_memory_working_set_bytes`) is unavailable, making M9 impossible to implement.

#### Recommendation
Add cAdvisor as a service in `docker-compose.monitoring.yml`:
```yaml
cadvisor:
  image: gcr.io/cadvisor/cadvisor:v0.49.1
  volumes:
    - /:/rootfs:ro
    - /var/run:/var/run:ro
    - /sys:/sys:ro
    - /var/lib/docker/:/var/lib/docker:ro
  ports:
    - "127.0.0.1:8080:8080"
```

---

## 5. Capacity Planning Matrices

### 5.1 Dev Profile (`docker-compose.yml`)

| Service | Limit | Reservation | Current Config | Fit |
|---------|-------|-------------|----------------|-----|
| postgres | 8 GB | 4 GB | 4.5 GB theoretical max | ⚠️ Tight |
| opensips | 1 GB | 512 MB | 1.09 GB calculated min | ❌ **Over** |
| rtpengine | 2 GB | 1 GB | Up to ~1 GB at capacity | ✅ OK |
| asterisk_pbx_1 | 1 GB | 512 MB | — | ✅ OK |
| asterisk_pbx_2 | 1 GB | 512 MB | — | ✅ OK |
| ocp | 512 MB | 256 MB | PHP limit 256 MB | ⚠️ Tight |
| admin_api | 256 MB | 128 MB | — | ✅ OK |
| prometheus | 2 GB | 512 MB | TSDB bounded to 10 GB disk | ✅ OK |
| alertmanager | 512 MB | 128 MB | — | ✅ OK |
| anomaly_detector | 512 MB | 256 MB | — | ✅ OK |
| grafana | 512 MB | 256 MB | — | ✅ OK |
| opensips_exporter | 256 MB | 128 MB | — | ✅ OK |
| postgres_exporter | 128 MB | 64 MB | — | ✅ OK |
| node_exporter | 128 MB | 64 MB | — | ✅ OK |
| certbot | 256 MB | 128 MB | — | ✅ OK |
| tailscale_cert | 128 MB | 64 MB | — | ✅ OK |
| certbot_exporter | 64 MB | 32 MB | — | ✅ OK |
| backup | 1 GB | 256 MB | pg_dump + encryption | ✅ OK |

### 5.2 Production Profile (`docker-compose.prod.yml`)

| Service | Limit | Reservation | Current Config | Fit |
|---------|-------|-------------|----------------|-----|
| postgres | 8 GB | 4 GB | 12.1 GB theoretical max | ❌ **Over** |
| opensips | 2 GB | 1 GB | 2.18 GB calculated min | ❌ **Over** |
| rtpengine | 2 GB | 1 GB | Up to ~1 GB at capacity | ✅ OK |
| asterisk_pbx_1 | 2 GB | 1 GB | — | ✅ OK |
| asterisk_pbx_2 | 2 GB | 1 GB | — | ✅ OK |
| ocp | 1 GB | 512 MB | PHP limit 256 MB | ✅ OK |
| admin_api | 256 MB | 128 MB | — | ✅ OK |
| prometheus | 2 GB | 1 GB | TSDB bounded | ✅ OK |
| alertmanager | 512 MB | 256 MB | — | ✅ OK |
| anomaly_detector | 1 GB | 512 MB | — | ✅ OK |
| grafana | 1 GB | 512 MB | — | ✅ OK |
| opensips_exporter | 512 MB | 256 MB | — | ✅ OK |
| certbot | 256 MB | — | — | ✅ OK |
| tailscale_cert | 128 MB | — | — | ✅ OK |
| certbot_exporter | 64 MB | — | — | ✅ OK |
| backup | 1 GB | 512 MB | — | ✅ OK |

### 5.3 VPS Profile (`docker-compose.vps.yml`)

| Service | Limit | Reservation | Current Config | Fit |
|---------|-------|-------------|----------------|-----|
| postgres | 2 GB | 1 GB | 1.43 GB theoretical max | ✅ OK |
| opensips | 1 GB | 512 MB | 1.36 GB calculated min | ❌ **Over** |
| rtpengine | 1 GB | 512 MB | Up to ~200 MB at capacity | ✅ OK |
| asterisk_pbx_1 | 1 GB | 512 MB | — | ✅ OK |
| asterisk_pbx_2 | 1 GB | 512 MB | — | ✅ OK |
| ocp | 512 MB | 256 MB | PHP limit 256 MB | ⚠️ Tight |
| admin_api | 256 MB | 128 MB | — | ✅ OK |
| certbot | 128 MB | 64 MB | — | ✅ OK |
| certbot_exporter | 64 MB | 32 MB | — | ✅ OK |
| backup | 1 GB | 256 MB | — | ✅ OK |

**Profile total limits = 7.5 GB on a stated 4 GB host.** This is a deployment hazard.

---

## 6. Top Action Items (Prioritized)

1. **[CRITICAL] M1 — Fix OpenSIPS memory in all profiles**
   - Set `children=8` in `opensips.cfg.tpl`.
   - Recalculate `-m` and `-M` so that `(children+1)×M + m < container_limit×0.85`.
   - Dev: `-m 512 -M 48` or raise limit to 1.5 GB.
   - Prod: `-m 1024 -M 96` or raise limit to 2.5 GB.
   - VPS: `-m 512 -M 48` or raise limit to 1.5 GB.

2. **[CRITICAL] M2 — Fix PostgreSQL production memory**
   - Option A: Raise postgres limit to **16 GB** and reservation to **8 GB**.
   - Option B: Reduce `work_mem` to **16 MB** (or `max_connections` to 150 + add PgBouncer).

3. **[HIGH] M3 — Increase PostgreSQL `shm_size` in production**
   - Change `shm_size: 2gb` → `shm_size: 3gb` in `docker-compose.prod.yml`.

4. **[HIGH] M5 — Add LIMIT pagination to LGPD export**
   - Paginate CDR, OCP audit, and auth audit queries.
   - Stream JSON to disk instead of `fetchAll()` + `json_encode()`.

5. **[HIGH] M6 — Paginate audit integrity check**
   - Chunk `verifyAuditLogIntegrity()` into `LIMIT 1000` cursor-based pages.

6. **[MEDIUM] M4 — Rebalance dev PostgreSQL**
   - Reduce `work_mem` to 8 MB or raise reservation to 6 GB.

7. **[MEDIUM] M8 — Deploy PgBouncer**
   - Reduces connection overhead and allows lowering `max_connections`.

8. **[MEDIUM] M10 — Reduce host swap or swappiness**
   - Prevent swap thrashing from masking memory pressure.

9. **[LOW] M9 + M13 — Add cAdvisor and container memory alerts**
   - Enables observability of per-container memory pressure.

---

## 7. Conformance Statement

- **Docker-first rule:** ✅ All services run in containers with explicit resource limits.
- **PostgreSQL-only rule:** ✅ No MySQL/MariaDB present.
- **Missing resource constraints:** ⚠️ All services have limits, but OpenSIPS and PostgreSQL limits are **miscalculated** and do not protect against OOM.
- **Memory observability:** ⚠️ Host-level memory is monitored; container-level memory is not.

---

*Report generated by speckit-memorylint skill. This is a read-only audit; no files were modified.*
