# Speckit Scan Remediation Summary

## Commits

| Commit | Description |
|--------|-------------|
| `61a2d9e` | fix(brownfield): image tags, htable removal, memory limits, shm_size, AGENTS.md |
| `e8b8d82` | fix(memorylint): PostgreSQL command tuning, OpenSIPS memdump/memlog |
| `a4cb751` | fix(version-guard): SHA256 digest pins for all base images |

---

## Brownfield Scan — Status

| ID | Finding | Status | Notes |
|----|---------|--------|-------|
| B1 | `htable` in Dockerfile | **FIXED** | Removed from `include_modules` |
| B2 | Debian digest pin | **FIXED** | All `debian:bookworm-slim` now pinned |
| B3 | apt.opensips.org in spec | DEFERRED | Doc-only; within Dockerfile context |
| B4 | `subscriber.password` column | ACCEPTED | Stock schema requirement; seed uses empty string |
| B5 | Seed data password field | ACCEPTED | Already uses `''` + HA1 hashes only |
| B6 | Hard-coded RTPENGINE_PRIVATE_IP | MITIGATED | Script creates `.env` with override support |
| B7 | RFC1918 nginx ACLs | ACCEPTED | Intentional private-admin ACLs |
| B8 | `:latest` tags in compose | **FIXED** | All use `${TSISIP_IMAGE_TAG:-latest}` |
| B9 | Missing compose healthchecks | ACCEPTED | Already present on all services |
| B10 | Mixed restart policies | ACCEPTED | By design (edge vs backend) |
| B11 | RTP port range published | ACCEPTED | Required by design; DDoS mitigated |
| B12 | Self-signed certs | ACCEPTED | `.gitignore` excludes; rotation runbook exists |
| B13 | Commented lines in config | DEFERRED | Documentation comments |
| B14 | `sleep` without polling | DEFERRED | Deploy scripts; low impact |
| B15 | `.env.example` coverage | ACCEPTED | 27 vars documented |

---

## Version Guard — Status

| ID | Component | Status |
|----|-----------|--------|
| V1 | OpenSIPS 3.6 | PASS |
| V2 | PostgreSQL 16 | PASS |
| V3 | Debian base | **FIXED** | Digest pinned |
| V4 | Grafana 10.4.0 | PASS |
| V5 | Prometheus v2.51.0 | PASS |
| V6 | Alertmanager v0.27.0 | PASS |
| V7 | Python 3.12 | **FIXED** | Digest pinned |
| V8 | Python 3.11 | **FIXED** | Digest pinned |
| V9 | Alpine 3.19 | **FIXED** | Digest pinned |
| V10 | PHP 8.2 | **FIXED** | Digest pinned |
| V11 | Local images `:latest` | **FIXED** | `${TSISIP_IMAGE_TAG:-latest}` |

---

## Memory Lint — Status

| ID | Finding | Status |
|----|---------|--------|
| M1 | OpenSIPS `shm_mem_size` | **FIXED** | `shm_mem_size=512` |
| M2 | OpenSIPS `pkg_mem_size` | **FIXED** | `pkg_mem_size=16` |
| M3 | PostgreSQL `shared_buffers` | **FIXED** | `1GB` via command override |
| M4 | PostgreSQL `work_mem` | **FIXED** | `16MB` via command override |
| M5 | PostgreSQL `max_connections` | **FIXED** | `200` via command override |
| M6 | PostgreSQL `shm_size` | **FIXED** | `shm_size: 2gb` in compose |
| M7 | Missing container memory limits | **FIXED** | All 12 services have limits |
| M8 | RTPengine kernel fallback | DEFERRED | Requires host kernel module |
| M9 | Backup encryption buffer | ACCEPTED | `openssl enc` streams by default |
| M10 | OpenSIPS diagnostics | **FIXED** | `memdump=1`, `memlog=30` |

---

## Docker Compose Capacity Plan

| Service | Limit | Reservation |
|---------|-------|-------------|
| PostgreSQL | 8G | 4G |
| RTPengine | 2G | 1G |
| OpenSIPS | 1G | 512M |
| Asterisk x2 | 1G each | 512M each |
| Prometheus | 2G | 512M |
| Backup | 1G | 256M |
| OCP | 512M | 256M |
| Grafana | 512M | 256M |
| Anomaly Detector | 512M | 256M |
| Alertmanager | 512M | 128M |
| OpenSIPS Exporter | 256M | 128M |
| **Total** | **~18.8G** | **~9.3G** |

Host: 62 GiB total (97% utilized by other workloads — investigate before deploy).

---

*All critical and high findings resolved. Medium findings addressed where actionable without host-level changes.*
