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
| B14 (initial) | `sleep` without polling | DEFERRED | Deploy scripts; low impact |
| B15 (initial) | `.env.example` coverage | ACCEPTED | 27 vars documented |
| B14 (residual) | `ALLOW_UNENCRYPTED_BACKUPS` reference in `backup.sh` | **FIXED** | Feature 013; commit `d09c385` |
| B15 (residual) | Missing healthchecks on `backup`/`anomaly-detector` | **FIXED** | Feature 013; commit `d09c385` |
| B16 (residual) | CI pipeline publishes `:latest` tag | **FIXED** | Feature 013; commit `d09c385` |

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

> Historical M1–M10 scan: see `memorylint-report.md`. Updated audit in `memorylint-audit-2026-05-20.md` reflects current state. Commit `9180cad` increased VPS backup `mem_limit` from 128 MB to 256 MB (M3).

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

---

## Brownfield Security Findings — 2026-05-28 Remediation

### CRITICAL Findings

| ID | Finding | Status | Fix |
|----|---------|--------|-----|
| C1 | OpenSIPS WebSocket ports published on host (8081/tcp, 4443/tcp) | **FIXED** | Removed port mappings from `docker-compose.yml`; WS/WSS internal-only per spec §5 |
| C2 | `node_exporter` mounts host `/proc`, `/sys`, `/` with `pid: host` + `sip_internal` access | **FIXED** | Removed `sip_internal` network; moved to isolated `monitoring` network |
| C3 | OpenSIPS `sql_query` uses pseudo-variables (`$fd`, `$rd`, `$rU`, `$si`) directly in SQL without escaping | **FIXED** | Applied `$(pv{s.escape.common})` transformation to all pseudo-variables before SQL interpolation in 6 queries |
| C4 | MI HTTP bound to `0.0.0.0` via `${OPENSIPS_LISTEN_IP}` — reachable on `sip_edge` (public) | **FIXED** | Introduced `MI_HTTP_IP` env var (defaults to `127.0.0.1`); forced loopback when `OPENSIPS_LISTEN_IP=0.0.0.0`; updated `entrypoint.sh` envsubst |

### HIGH Findings

| ID | Finding | Status | Fix |
|----|---------|--------|-----|
| H1 | RTPengine `--listen-http=0.0.0.0:2225` exposes HTTP management on all interfaces | **FIXED** | Changed to `--listen-http=${RTPENGINE_INTERNAL_IP}:2225` in compose files |
| H2 | `anomaly_detector` publishes host loopback port without documented auth | **FIXED** | Added `X-API-Key` header auth to `/api/v1/event` and `/api/v1/status` endpoints; `ANOMALY_API_KEY` required in prod |
| H3 | `HEADER_ROUTING` hard-codes dispatcher set `1` fallback instead of tenant-scoped lookup | **FIXED** | Replaced hardcoded `1` with `sql_query_one` lookup of `tenants.default_dispatcher_setid`; falls back to `1` only if tenant has no default |
| H4 | `postgres_exporter` uses `sslmode=disable` | **FIXED** | Changed to `sslmode=prefer` |
| H5 | `opensips` mounts `tls_certs:/certs/live:rw` (should be `:ro`) | **FIXED** | Changed to `:ro` in `docker-compose.yml` and `docker-compose.prod.yml` |
| H6 | `OPENSIPS_HOST: 127.0.0.1` hardcoded across all compose profiles | **FIXED** | Parameterized to `${OPENSIPS_HOST:-127.0.0.1}` |
| H7 | `TRUNK_ROUTING` uses unvalidated `$rd` in SQL | **FIXED** | Added SIP domain format regex validation (`^[a-zA-Z0-9][-a-zA-Z0-9]*(\.[a-zA-Z0-9][-a-zA-Z0-9]*)*$`) before SQL; escape applied |
| H8 | `INBOUND_DID_ROUTING` uses unvalidated `$rU` (DID number) in SQL | **FIXED** | Added E.164 format regex validation (`^[+]?[0-9]{3,15}$`) before SQL; escape applied |

---

*All CRITICAL and HIGH brownfield findings resolved as of commit `802862a`.*
