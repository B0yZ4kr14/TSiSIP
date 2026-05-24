# TSiSIP — Project Status

> **Updated:** 2026-05-24
> **Architect:** DevSecOps Autonomous
> **VPS TSiAPP:** Production vps-lite+PBX stack running; SIP public 5060/5061 blocked upstream of host
> **GitHub:** `main` at `8cdcdd7`

---

## Executive Summary

TSiSIP is **deployed on VPS TSiAPP** in the expanded vps-lite profile for SIP host-ready (**10 services**).
All **23 features (001–023)** have implemented and documented artifacts. Note: there is no spec `014` in the filesystem; numbering jumps from `013` to `015`.
Feature 022 (VPS Go-Live Stabilization) is **complete** — all 10 ACs verified on VPS with evidence bundle in `.sisyphus/evidence/` and `docs/security/evidence/022-vps-go-live/`.
Feature 005 (PostgreSQL Backup & Restore) was audited and validated on the local/manual path; PITR live and offsite replication remain pending environment.
GitNexus was updated locally on 2026-05-19 and reported the TSiSIP index as `up-to-date`.
On 2026-05-24, the TSiSIP SIP edge was stabilized on the VPS after OpenSIPS 3.6 engine compatibility fixes and `userblacklist` schema corrections.
On 2026-05-24, the backup pipeline was revalidated on the VPS: **WAL archiving active**, backup/validate/purge working manually, and metrics exporter accessible internally via Docker network `metrics_host` at `backup:9101` (host loopback mapping removed due to `userland-proxy=false`).
On 2026-05-24, Cloudflare DNS was configured: `tsiapp.io` proxied for HTTPS; `sip.tsiapp.io` non-proxied → `179.190.15.116` for SIP signaling.

---

## Implemented Features

| Feature | Status | Validation |
|---------|--------|------------|
| 001 — TSiSIP SIP Edge | ✅ | GHCR image with OpenSIPS 3.6 engine and tls_openssl.so |
| 002 — TSiSIP Control Panel | ✅ | GHCR image; public access via Nginx/TLS. Local port 127.0.0.1:8084 when userland-proxy=true; VPS uses container bridge IP via nginx |
| 003 — Prometheus/Grafana | Artifacts ready | Prometheus/Grafana/Alertmanager disabled on VPS-lite; backup exporter active on loopback |
| 004 — Health Checks | ✅ | Scripts in all containers |
| 005 — PostgreSQL Backup | **Live** | Backup container stable; WAL archiving active; metrics exporter operational; cron schedules active; offsite/PITR pending environment |
| 006 — Rate Limiting | Partial live | TSiSIP SIP edge uses pike + Nginx limit_req; anomaly detector/dashboard and external flood pending |
| 007 — TLS/SRTP | Partial live | tls_openssl.so compiled; **Let's Encrypt certificates active** for `tsiapp.io` (valid until 2026-08-19); auto-renewal via certbot with `tls_reload` deploy hook; mTLS trunk/SRTP external pending |
| 008 — DevSecOps Deploy | Partial live | Ansible + GHCR + bootstrap; supply chain hardening and formal SSL Labs pending |
| 009 — VPS Deploy Automation | ✅ | Orchestrated pipeline implemented; all 13 tasks complete (T1.1–T4.2) |
| 010 — OCP Navigation System Links | ✅ | Implemented |
| 011 — OCP Forced Password Change | ✅ | Implemented |
| 012 — OCP Admin Tools Restoration | ✅ | Implemented |
| 013 — Brownfield Follow-up | ✅ | Implemented |
| 015 — Auto TLS Certificate Rotation | ✅ | Implemented |
| 016 — OCP Audit Log Compliance | ✅ | Implemented |
| 017 — SIP Trunk Provider Integration | ✅ | Implemented |
| 018 — Global Requirement ID Migration | ✅ | Implemented |
| 019 — Spec-Kit Memory Hub Integration | ✅ | Implemented |
| 020 — OCP Critical Tool Gap Closure | ✅ | Implemented |
| 021 — Brownfield Security Production Hardening | ✅ | Implemented |
| 022 — VPS Go-Live Stabilization | **Complete** — All 10 ACs verified (AC1-AC10) | 2026-05-24 |
| 023 — Subscriber CRUD Refactor | **Complete** — All 10 ACs verified; proxy layer operational; OCP zero direct writes | 2026-05-24 |

---

## VPS-Lite Production Stack (10 Services)

| Service | GHCR Image | Memory | Ports |
|---------|------------|--------|-------|
| postgres | `tsisip/postgres:latest` | 2G | — |
| rtpengine | `tsisip/rtpengine:latest` | 1G | 10000-20000/udp |
| opensips | `tsisip/opensips:latest` | 1G | 5060/udp+tcp, 5061/tcp |
| asterisk-pbx-1 | `tsisip/asterisk:latest` | 1G | internal |
| asterisk-pbx-2 | `tsisip/asterisk:latest` | 1G | internal |
| ocp | `tsisip/ocp:latest` | 512M | container bridge IP via nginx (userland-proxy=false) |
| backup | `tsisip/backup:latest` | 1G | internal (`metrics_host` network) |
| admin-api | `tsisip/admin-api:latest` | 256M | internal |
| certbot | `tsisip/certbot:latest` | 128M | — |
| certbot-exporter | `tsisip/certbot-exporter:latest` | 64M | — |

**Total allocated RAM:** ~7.5GB
**Disabled services:** prometheus, grafana, alertmanager, opensips-exporter, anomaly-detector

---

## Deploy Artifacts Ready

| File | Purpose |
|------|---------|
| `docker-compose.vps.yml` | Lightweight stack with mem_limits and shm_size |
| `docker-compose.prod.yml` | Full stack (16 services) |
| `docker-compose.yml` | Development stack (16 services) |
| `deploy/scripts/vps-bootstrap.sh` | Automatic VPS initialization |
| `deploy/scripts/vps-deploy.sh` | Deploy in 3 waves with health checks |
| `deploy/scripts/vps-nginx-setup.sh` | Existing Nginx integration (dynamically detects OCP container IP) |
| `deploy/scripts/test-vps-local.sh` | Local vps-lite profile test |
| `deploy/VPS-DEPLOY-READINESS.md` | Complete deploy checklist |
| `deploy/README-VPS-DEPLOY.md` | Quick deploy guide |

---

## Active Issues

| Issue | Severity | Status |
|-------|----------|--------|
| VPS OOM crash | Critical | **Resolved by hardware upgrade** |
| OpenSIPS tls_openssl.so | Medium | **Resolved** — rebuild with TLS module |
| Auth credential length 32 bytes | Medium | **Resolved** — fixed to 32 bytes |
| RTPengine bad fd | Medium | **Resolved** — .env with correct IP |
| Dispatcher DB schema | Medium | **Resolved** — SQL scripts applied |
| OpenSIPS 3.6 config API | High | **Resolved on VPS** — `sl_send_reply`, `mf_process_maxfwd_header`, `check_source_address`, `sql_query`, `ds_select_dst` and `www_challenge` adjusted |
| userblacklist schema runtime | High | **Resolved on VPS** — table created and `version.userblacklist=2` applied |
| Dispatcher without real destinations | High | **Resolved** — set 1 points to `asterisk-pbx-1` and `asterisk-pbx-2`; both state=0 |
| Asterisk config path | High | **Resolved** — configs mounted at `/etc/asterisk` and `/usr/local/etc/asterisk`; PJSIP UDP/TCP loaded |
| SIP 5060/5061 external | High | **Upstream block** — `tcpdump` on VPS captured 0 SYN during external scan; filter occurs before host |
| UFW 5061/tcp | Medium | **Resolved** — 5061/tcp released IPv4/IPv6 |
| Ansible docker.io conflict | Low | **Resolved** — skip install if Docker present |
| Docker userland-proxy=false | Low | **Mitigated** — nginx proxy uses OCP container bridge IP; documented in 21 files |
| Certbot restart loop (`setpgid: EPERM`) | Medium | **Resolved** — Replaced cron daemon with sleep loop in entrypoint; image rebuilt and healthy (2026-05-24) |
| Certbot-exporter restart loop (`UnboundLocalError`) | Medium | **Resolved** — Added `global _last_failures`; image rebuilt and healthy (2026-05-24) |
| Cloudflare DNS configuration | Medium | **Resolved** — `tsiapp.io` proxied for HTTPS (Cloudflare anycast); `sip.tsiapp.io` non-proxied → `179.190.15.116` for SIP signaling; legacy wildcard `*.tsiapp.io` removed |
| Backup container restart loop | Medium | **Resolved** — Added `CHOWN` + `DAC_OVERRIDE` capabilities to compose; runtime `chown` for tsisip-backup dirs in entrypoint; OpenBSD netcat syntax fix in metrics exporter; image rebuilt and healthy (2026-05-24) |

## Live VPS Validation (2026-05-24)

| Check | Result |
|-------|--------|
| SSH TSiAPP | OK via alias, public and Tailscale |
| VPS Resources | Ubuntu 24.04, Docker 29.5.2, Compose v5.1.4, ~62GB free |
| Docker Stack | 10 services UP/healthy: `postgres`, `rtpengine`, `opensips`, `ocp`, `backup`, `admin-api`, `asterisk-pbx-1`, `asterisk-pbx-2`, `certbot`, `certbot-exporter` |
| Backup container | `healthy`; metrics exporter serves Prometheus text format on container port 9101; cron jobs active |
| TSiSIP SIP edge healthcheck | `OK: OpenSIPS is healthy` |
| Asterisk healthcheck | Both return `OK: Asterisk is healthy` |
| OCP loopback | `https://127.0.0.1/TSiSIP/login.php` responds HTTP 200 via nginx (use `-k` for self-signed cert) |
| OCP public | `https://tsiapp.io/TSiSIP` accessible via Cloudflare proxy (TLS origin-pull active) |
| SIP subdomain | `sip.tsiapp.io` resolves to VPS IP `179.190.15.116` (non-proxied for SIP signaling) |
| Local ports on VPS | 5060/tcp, 5061/tcp, 5060/udp and RTP 10000-20000/udp listening |
| External ports | 5060/tcp and 5061/tcp appear `filtered` from outside; `tcpdump` confirms packets do not reach host |
| Dispatcher DB | 2 real active destinations: `sip:asterisk-pbx-1:5060`, `sip:asterisk-pbx-2:5060` |
| SIP OPTIONS | UDP and TCP return `SIP/2.0 200 OK` internally |
| SIP INVITE without auth | UDP and TCP return `SIP/2.0 407 Proxy Authentication Required` |
| Backup + WAL | Encrypted backup created at `/backup/daily`, validate manual OK, purge manual OK, WAL `.gz` generated at `/backup/wal` |
| Backup metrics | `curl http://backup:9101/metrics` (via Docker `metrics_host` network) returns RPO/RTO/status and `backup_current_wal_info`; host port mapping removed due to `userland-proxy=false` |
| Certbot metrics | `curl http://<certbot-exporter-ip>:9101/metrics` returns `certbot_days_until_expiry`, `certbot_renewal_failure_total`, `certbot_last_success_timestamp`; port on `metrics_host` (internal) |

---

## Quality Gates

Consolidated quality report available at:
- [`reports/CONSOLIDATED-QUALITY-GATE-2026-05-19.md`](reports/CONSOLIDATED-QUALITY-GATE-2026-05-19.md)

| Gate | Status |
|------|--------|
| Brownfield Scan | PASS |
| Memory & Resource Audit | PASS |
| Version Guard | PASS |
| Remediation Progress | PASS |
| VPS Production Validation | PASS |
| **Overall** | PASS WITH WARNINGS |

No critical blockers identified. Recommended actions prioritized in consolidated report.
Updated: 2026-05-24.

---

## Real Pending Items

| Area | Status | Next Action |
|------|--------|-------------|
| VPS live deploy | Completed on host | Production vps-lite+PBX stack healthy; SIP public exposure depends on upstream ACL outside host |
| Feature 002 governance | Closed (N/A) | Approval gate by 3 representatives not executable in automation; keep as manual reopening if needed |
| Feature 001 infra-quality | Closed | Checklist revised on 2026-05-19 as PASS with scoped deferrals |
| RTPengine kernel table | Deferred | Decide between kernel module on host or extra memory for userspace fallback |
| Deploy scripts polling | Deferred | Replace fixed sleeps with polling when deploy live stabilizes |
| TLS/rclone real | Pending environment | Replace dummy certificates and configure real rclone/MinIO on deploy |
| Supply chain deterministic | Pending | Replace `:latest` with versioned tags/digests and release/rollback manifest |
| Backup cron jobs | Pending window | Observe first automatic execution 02:00/03:00/04:00 UTC after deploy |

> Note: Feature 001 T4.4/T4.5/T4.7 was reconciled on 2026-05-19; `spec.md`, `plan.md` and `tasks.md` now agree that OPTIONS 200 OK, INVITE 401 and authenticated INVITE to Asterisk were validated.

---

## Next Steps

1. **Release SIP external outside host:** open 5060/tcp, 5060/udp and 5061/tcp at provider/NAT/Tailscale ACL; VPS already listens and UFW already allows.
2. **Monitor scheduled jobs:** backup (02:00 UTC), purge (03:00 UTC) and validate (04:00 UTC) are installed; missing first automatic window observation.
3. **Phase 2:** add monitoring (Prometheus/Grafana) when it does not conflict with OrthoPlus and there is port policy.
4. **Feature 023:** complete Subscriber CRUD Refactor (proxy client integration).

---

## Recent Commits

```
279ae33 docs(vps): document Docker userland-proxy=false impact on OCP port 8084
496de30 fix(security): resolve socratic-popperian audit findings — docs, code, and config alignment
```

---

## Architecture Decisions

- **ADR-001:** vps-lite profile adopted for VPS (updated: ~7.5GB RAM, 10 services)
- **Docker-first:** Zero build sections in docker-compose.prod.yml
- **PostgreSQL-only:** db_postgres, no MySQL/MariaDB
- **TSiSIP SIP engine:** OpenSIPS 3.6 LTS with tls_openssl.so compiled, no sanity module
- **LGPD:** Compliance framework for retention and encryption
- **VPS OCP access:** nginx proxy to container bridge IP due to userland-proxy=false (RTPengine performance)

---

## OMK Registry

- **Goal Feature 005:** `tsisip-feature-005-postgresql-backup-res-2026-05-17T20-59-49-039Z`
- **Status:** ✅ CLOSED (PASS 8/8)
- **Speckit-analyze:** 7 issues detected, 3 fixed (I1 HIGH, I2 MEDIUM, I3 MEDIUM)
- **Speckit-clarify:** 5 questions answered and integrated

---

*Production stack active on VPS; remaining pending item is upstream release of public SIP ports.*
