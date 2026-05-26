# TSiSIP Consolidated Audit — 2026-05-26

**Date:** 2026-05-26  
**Commit:** `523c5ef` — fix(tests): end-to-end call — unique static IPs per test container  
**Status:** 44/44 integration tests PASS, DocGuard 235/235 A+, 0 CRITICAL brownfield findings  
**Audits Run:** Brownfield Scan, Version Guard, Memory Lint, GitNexus Analysis

---

## Executive Dashboard

| Audit | Pass | Warn | Fail / Critical | Grade |
|---|---|---|---|---|
| Brownfield Scan | 11/11 spec checks | 6 LOW + 5 MEDIUM | 1 HIGH (B17) | A |
| Version Guard | 48/54 | 10 MED/LOW | 6 HIGH | B+ |
| Memory Lint | — | 5 MEDIUM + 4 HIGH + 2 LOW | 3 CRITICAL | C |
| GitNexus Analysis | 647 files, 7,539 symbols | PHP scope extraction failed | No blocking | A |

**Overall Health:** Good. No CRITICAL brownfield or version issues. Memory lint reveals 3 CRITICAL OOM risks that need immediate attention before production load.

---

## Critical Actions (CRITICAL — Pre-Production Blockers)

| ID | Finding | File(s) | Fix | Effort |
|---|---|---|---|---|
| **M1** | OpenSIPS memory exceeds container limits in ALL profiles. Dev: +6.3%, Prod: +6.3%, VPS: +32.8% | `opensips/opensips.cfg.tpl`, `docker-compose*.yml` | Set `children=8`, recalc `-m`/`-M` so calculated total < limit * 0.85 | 30 min |
| **M2** | PostgreSQL prod theoretical max (~12.1 GB) exceeds 8 GB container limit | `docker-compose.prod.yml` | Reduce `work_mem` to 16 MB OR raise limit to 16 GB | 15 min |
| **M3** | PostgreSQL prod `shm_size=2gb` insufficient for `shared_buffers=2GB` | `docker-compose.prod.yml` | Change to `shm_size: 3gb` | 5 min |

**M1 Recommended Values:**
- **Dev:** `-m 512 -M 48` -> 944 MB (fits in 1 GB) OR raise limit to 1.5 GB
- **Prod:** `-m 1024 -M 96` -> 1,888 MB (fits in 2 GB) OR raise limit to 2.5 GB
- **VPS:** `-m 512 -M 48` -> 944 MB (fits in 1 GB) OR raise limit to 1.5 GB

---

## High-Priority Actions (HIGH — Merge/Deploy Blockers)

| ID | Audit | Finding | File(s) | Fix | Effort |
|---|---|---|---|---|---|
| **B17** | Brownfield | Copilot instructions falsely claim repo is "greenfield" | `.github/copilot-instructions.md` | Rewrite to reflect current state (Docker, Compose, tests, CI) | 20 min |
| **F1** | Version Guard | `.env.example` defaults image tag to floating latest | `.env.example` | Change to placeholder like `v0.0.0-dev` | 5 min |
| **D9/V7** | Version Guard | Admin API PHP base image diverges from OCP | `docker/admin_api/Dockerfile` | Align to same explicit variant and digest as OCP | 10 min |
| **A2/V19** | Version Guard | `rtpengine` APT package unpinned | `docker/rtpengine/Dockerfile` | Pin to specific version | 15 min |
| **PY8/V21** | Version Guard | Python `requests` version drift between containers | `docker/opensips_exporter/Dockerfile` | Align to same version as anomaly detector | 5 min |
| **M5** | Memory Lint | Unbounded `fetchAll()` in LGPD export CLI | `web/cli/export-audit-lgpd.php` | Add `LIMIT` + pagination; stream JSON to disk | 1 hr |
| **M6** | Memory Lint | Unbounded audit log integrity check | `web/common/audit.php:181` | Paginate with `LIMIT 1000` cursor | 30 min |
| **M4** | Memory Lint | PostgreSQL dev exceeds 4 GB reservation | `docker-compose.yml` | Reduce `work_mem` to 8 MB OR raise reservation to 6 GB | 10 min |

---

## Medium-Priority Actions (MEDIUM — Quality/Security)

| ID | Audit | Finding | File(s) | Fix | Effort |
|---|---|---|---|---|---|
| **B18** | Brownfield | Default OCP admin credential embedded as plaintext in seed SQL | `db/init/03-seed-data.sql` | Replace with comment/placeholder; generate via Docker secrets | 15 min |
| **B19** | Brownfield | Schema drift: `tenant_id` VARCHAR(36) vs UUID spec | `db/init/02-tsisip-extensions.sql` | Document deviation in `docs/memory/DECISIONS.md` OR migrate to UUID | 30 min |
| **B20** | Brownfield | OCP parity tables may store plaintext credentials | `db/init/04-ocp-parity-schema.sql` | Audit usage; rename column to hash suffix; enforce bcrypt | 1 hr |
| **B21** | Brownfield | Missing `set -euo pipefail` in entrypoints | 5 shell scripts | Add `set -euo pipefail` to bash entrypoints | 20 min |
| **B22** | Brownfield | Healthcheck script lacks `set` directive | `docker/backup/healthcheck.sh` | Add `set -eo pipefail` | 5 min |
| **G1/V2** | Version Guard | OpenSIPS git clone floats on `3.6` branch tip | `Dockerfile` | Pin to release tag + verify checksum | 20 min |
| **X2** | Version Guard | `certbot` image registry prefix inconsistent | Compose prod/vps files | Verify registry path; align with GHCR if published there | 10 min |
| **M7** | Memory Lint | Missing explicit `children` in OpenSIPS config | `opensips/opensips.cfg.tpl` | Add `children=8` (dev/prod) or `children=4` (VPS) | 5 min |
| **M8** | Memory Lint | No PostgreSQL connection pooler | All compose files | Deploy PgBouncer in transaction pool mode | 2 hr |
| **M9** | Memory Lint | No per-container memory alerts | `docker/prometheus/alert-rules.yml` | Add `ContainerMemoryHigh` alert once cAdvisor deployed | 30 min |
| **M10** | Memory Lint | Host swap 130 GiB masks memory pressure | Host OS | Reduce swap to 8–16 GiB OR set `vm.swappiness=10` | 10 min |
| **M11** | Memory Lint | RTPengine userspace mode memory unmonitored | All compose files | Add ports-used alert; consider reducing VPS port range | 20 min |

---

## Low-Priority Actions (LOW — Polish/Documentation)

| ID | Audit | Finding | File(s) | Fix | Effort |
|---|---|---|---|---|---|
| **B23** | Brownfield | Hard-coded test credential in VPS test script | `tests/vps-stabilization/test-vps-sip.sh` | Parameterize via env var or secrets file | 10 min |
| **B24** | Brownfield | `.env.example` floating tag needs stronger warning | `.env.example` | Add explicit WARNING comment | 5 min |
| **B25** | Brownfield | `EXPOSE 22222/udp` in RTPengine Dockerfile | `docker/rtpengine/Dockerfile` | Remove or comment as internal-only | 5 min |
| **B26** | Brownfield | `EXPOSE 5038/tcp` in Asterisk Dockerfile | `docker/asterisk/Dockerfile` | Remove or comment as internal-only | 5 min |
| **B27** | Brownfield | RTPengine `--interface` falls back to loopback | `docker-compose.vps.yml` | Remove fallback; fail-fast in entrypoint | 10 min |
| **B28** | Brownfield | Agent tooling TODOs noise scan results | `.specify/`, `.omk/`, etc. | Exclude from `scripts/ci-scan.sh` | 10 min |
| **R7–R8** | Version Guard | Missing `.nvmrc` / `.python-version` | Project root | Add for CI/dev clarity | 10 min |
| **M12** | Memory Lint | No `oom_score_adj` for critical services | All compose files | Use systemd drop-ins or docker runtime flag | 20 min |
| **M13** | Memory Lint | No cAdvisor for container metrics | `docker-compose.monitoring.yml` | Add cAdvisor service | 30 min |

---

## Blocked Items (External Dependencies)

| ID | Stage | Blocker | Status |
|---|---|---|---|
| Stage 6 | SIP Public Exposure | Awaiting firewall/Tailscale ACL to expose 5060/udp+tcp | Blocked |
| Stage 8.1 | S3 Backup | Awaiting operator inserting real S3 config into secrets dir | Blocked |

---

## GitNexus Architecture Insights

- **647 files**, **7,539 symbols**, **8,260 edges**, **15 execution flows**
- **Integration tests dominate** (~25 of 29 major communities) — strong testing posture
- **Core logic clusters:** Admin API (subscriber CRUD), OCP web (auth), Observability exporters, Anomaly detector
- **Auth flows** well-isolated; no circular dependencies
- **Limitations:** PHP scope extraction failed (40+ files); only 4 HTTP routes detected (all Python Flask); OCP/Admin API PHP routes invisible
- **Impact analysis:** LOW risk for `opensips.cfg.tpl`, `docker-compose.vps.yml`, `Dockerfile` (all runtime config with no downstream static deps)

---

## Brownfield Spec Compliance Matrix

| Rule | Status | Evidence |
|---|---|---|
| OpenSIPS 3.6 LTS baseline | PASS | `Dockerfile` ARG 3.6; config references 3.6 modules |
| `db_postgres` only | PASS | Zero `db_mysql`/`db_sqlite` references |
| Docker-first delivery | PASS | All services build from committed Dockerfiles |
| `calculate_ha1 = 0` | PASS | `opensips.cfg.tpl:95` |
| `password_column = "ha1"` | PASS | `opensips.cfg.tpl:97` |
| `topology_hiding("C")` | PASS | Routes 575, 718 |
| Explicit rtpengine_offer/answer/delete | PASS | No `rtpengine_manage()` in config |
| No hard-coded `ds_select_dst(1, ...)` | PASS | Dynamic `$var(ds_set)` |
| `mf_process_maxfwd_header(70)` | PASS | `opensips.cfg.tpl:291` |
| No `sanity` module | PASS | CI rejects it; zero references |
| Network names snake_case | PASS | `sip_edge`, `sip_internal`, `db_internal` |
| SHA256 digest pinning (all FROM) | PASS | 18 Dockerfiles, 6 compose images |

---

## Memory Capacity Planning

| Profile | Services | Total Limits | Total Reservations | Stated Target Host |
|---|---|---|---|---|
| Dev | 18 | ~18.5 GB | ~8.4 GB | N/A |
| Prod | 15 | ~23.2 GB | ~11.3 GB | N/A |
| VPS | 10 | ~7.5 GB | ~3.8 GB | **~4 GB** — Misaligned |
| Monitoring | 7 | ~4.0 GB | ~1.8 GB | Overlay |

**VPS Hazard:** 7.5 GB limits on a 4 GB target host will cause kernel OOM kills under simultaneous pressure.

---

*Generated by Kimi Code CLI — Consolidation of 4 parallel audit reports.*  
*Reports: `brownfield-scan-2026-05-26.md`, `version-guard-2026-05-26.md`, `memorylint-2026-05-26.md`, `gitnexus-analysis-2026-05-26.md`*
