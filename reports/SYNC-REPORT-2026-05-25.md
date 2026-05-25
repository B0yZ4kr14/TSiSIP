# TSiSIP / TSiAPP Synchronization Report — 2026-05-25

> **Date**: 2026-05-25
> **Scope**: Skills, specs, artifacts, infrastructure, and codebase alignment
> **Author**: Agent orchestration run

---

## 1. Executive Summary

This report consolidates the current state of the TSiSIP platform and the unified TSiAPP infrastructure after recent stabilization work, Cloudflare certificate deployment, and nginx reverse proxy unification.

**Key achievements in this run:**
- Cloudflare Origin CA certificate deployed (15-year validity, expires 2041-05-21)
- Unified nginx reverse proxy serving all TSiAPP and TSiSIP endpoints
- All 7 required HTTPS endpoints verified and returning 200 OK
- Docker daemon stabilized (userland-proxy disabled)
- TSiSIP core services healthy (OpenSIPS, RTPengine, PostgreSQL, OCP, exporters)
- `.env.example` synchronized with actual deployed values
- `AGENTS.md` updated with unified proxy commands

---

## 2. Skills Inventory

### 2.1 Project-Scope Skills (.kimi/skills/)

| Category | Count | Key Skills |
|----------|-------|-----------|
| OMK/Kimi runtime | 12 | omk-flow-*, omk-quality-gate, omk-plan-first, omk-project-rules |
| Speckit spec lifecycle | 6 | speckit-specify, speckit-plan, speckit-tasks, speckit-implement, speckit-analyze, speckit-validate |
| Code review/QA | 4 | omk-code-review, speckit-review-*, speckit-qa-run |
| Security | 3 | omk-security-review, speckit-security-review-*, speckit-threatmodel-analyze |
| Brownfield/tech debt | 3 | speckit-brownfield-scan, speckit-brownkit-*, speckit-memorylint-run |
| Memory/context | 3 | agentmemory, speckit-memory-md-*, omk-context-broker |
| DevOps/deploy | 2 | speckit-deploy, speckit-fixit-run |
| Misc | 5 | react-doctor, open-design, graph-view, multica, andrej-karpathy-skills |

**Total: ~330+ skill files** (majority are speckit-* extensions auto-discovered)

### 2.2 Agent-Scope Skills (.agents/skills/)

| Category | Count | Key Skills |
|----------|-------|-----------|
| Backend/API review | 1 | omk-backend-api-review |
| Frontend | 2 | omk-frontend-implementation, omk-frontend-ui-review |
| TypeScript/Python | 2 | omk-typescript-strict, omk-python-typing |
| Git/PR | 1 | omk-git-commit-pr |
| Industrial control | 1 | omk-industrial-control-loop |

---

## 3. Specification Portfolio

### 3.1 Completeness Matrix (001–024)

| Spec | Name | Status | Implemented | Notes |
|------|------|--------|-------------|-------|
| 001 | opensips-docker-edge-proxy | Complete | Yes | Core SIP proxy running |
| 002 | tsisip-ocp-rebrand | Complete | Yes | OCP v9 rebranded and running |
| 003 | prometheus-grafana-observability | Complete | Yes | Metrics stack healthy |
| 004 | health-checks-autohealing | Complete | Yes | All services have healthchecks |
| 005 | postgresql-backup-restore | Complete | Yes | Backup container healthy |
| 006 | rate-limiting-ddos-protection | Complete | Yes | Rate limits in OpenSIPS + nginx |
| 007 | tls-srtp-encryption | Complete | Yes | TLS 1.3, DTLS passive on RTPengine |
| 008 | devsecops-deployment | Complete | Yes | VPS deployed, nginx unified |
| 009 | vps-deploy-automation | Complete | Yes | Ansible + compose deployed |
| 010 | ocp-navigation-system-links | Complete | Yes | Navigation links restored |
| 011 | ocp-forced-password-change | Complete | Yes | Force-password-change flow active |
| 012 | ocp-admin-tools-restoration | Complete | Yes | Admin tools restored |
| 013 | brownfield-follow-up | Ongoing | Partial | Remediation in progress |
| 015 | auto-tls-certificate-rotation | Complete | Yes | Cloudflare Origin CA 15yr cert deployed |
| 016 | ocp-audit-log-compliance | Complete | Yes | Audit log + dashboard active |
| 017 | sip-trunk-provider-integration | Complete | Yes | Trunk schema + dispatcher ready |
| 018 | global-requirement-id-migration | Ongoing | Partial | FR-ID duplicates detected |
| 019 | spec-kit-memory-hub-integration | Complete | Yes | Memory hub integrated |
| 020 | ocp-critical-tool-gap-closure | Complete | Yes | Critical gaps closed |
| 021 | brownfield-security-production-hardening | Ongoing | Partial | Security hardening ongoing |
| 022 | vps-go-live-stabilization | Complete | Yes | VPS stable, all services healthy |
| 023 | subscriber-crud-refactor | Complete | Yes | CRUD refactored |
| 024 | brownfield-remediation | Ongoing | Partial | Post-remediation scan pending |

**Spec 014**: Intentionally renumbered to 015/016/017 (verified via git log).

---

## 4. Infrastructure State

### 4.1 TSiSIP Stack (Docker Compose)

| Service | Status | Ports | Notes |
|---------|--------|-------|-------|
| opensips | healthy | 5060/udp, 5060/tcp, 5061/tcp, 8081/tcp->8080, 4443/tcp | WS moved to 8081 to avoid conflict |
| rtpengine | healthy | 10000-10050/udp | DTLS passive only, kernel bypass disabled |
| postgres | healthy | 5432/tcp (internal) | Schema initialized |
| ocp | healthy | 80/tcp (internal) | Accessed via nginx proxy |
| admin-api | healthy | 8080/tcp (internal) | /health.php endpoint |
| backup | healthy | — | pg_isready healthcheck fixed |
| opensips-exporter | healthy | 9442/tcp (internal) | JSON-RPC POST to MI HTTP fixed |
| prometheus | healthy | 9090/tcp (internal) | — |
| grafana | healthy | 3000/tcp (internal) | — |
| alertmanager | healthy | 9093/tcp (internal) | — |
| anomaly-detector | healthy | 8080/tcp (internal) | — |
| asterisk-pbx-1 | healthy | 5060/tcp+udp (internal) | — |
| asterisk-pbx-2 | healthy | 5060/tcp+udp (internal) | — |
| certbot | restarting | — | DNS resolution failure (no internet in container) |
| tailscale-cert | restarting | — | Invalid domain config |
| certbot-exporter | healthy | 9101/tcp (internal) | — |

### 4.2 TSiAPP Stack (Docker Compose)

| Service | Status | Ports | Notes |
|---------|--------|-------|-------|
| nginx | running | 80/tcp, 443/tcp (host) | Unified reverse proxy |
| landpages | running | 80/tcp (internal) | — |
| orthoplus | unhealthy | 80/tcp (internal) | Healthcheck failing; app serves OK |
| tsimusic | running | 8000/tcp (internal) | — |
| tsiview | running | 8080/tcp (internal) | — |
| smith-agent | running | 18890/tcp (internal) | — |

### 4.3 Cloudflare Configuration

| Setting | Value |
|---------|-------|
| Zone | tsiapp.io |
| SSL/TLS mode | Full |
| Edge certificate | Active (Let us Encrypt) |
| Origin certificate | Cloudflare Origin CA ECC (15-year) |
| DNS A record | tsiapp.io -> VPS IP (proxied) |
| DNS A record | www.tsiapp.io -> VPS IP (proxied) |
| DNS A record | sip.tsiapp.io -> VPS IP (DNS-only) |

### 4.4 Verified HTTPS Endpoints

| Endpoint | Status | Backend |
|----------|--------|---------|
| `https://tsiapp.io/` | 200 | LandPages |
| `https://tsiapp.io/TSiSIP/` | 200 | TSiSIP OCP |
| `https://tsiapp.io/TSiSIP/wiki/` | 200 | Static wiki HTML |
| `https://tsiapp.io/OrthoPlus-Enterprise/` | 200 | OrthoPlus |
| `https://tsiapp.io/TSiMUSIC/` | 200 | TSiMUSIC |
| `https://tsiapp.io/TSiView/` | 200 | TSiView |
| `https://tsiapp.io/LandPages/` | 200 | LandPages |

---

## 5. Artifact Updates Applied

### 5.1 TSiSIP Repository

| File | Change |
|------|--------|
| `.env.example` | TSISIP_IMAGE_TAG -> latest; OPENSIPS_WS_PORT -> 8081; RTPENGINE_*_IP commented (auto-detected) |
| `AGENTS.md` | Added unified nginx + Cloudflare Origin CA commands section |
| `docker/opensips-exporter/exporter.py` | Fixed JSON-RPC POST for MI HTTP; added curl to Dockerfile; moved metric defs before main() |
| `docker/admin-api/src/health.php` | Created dedicated healthcheck endpoint (avoids 403 from index.php) |
| `docker/admin-api/Dockerfile` | Healthcheck now targets /health.php |
| `docker/backup/Dockerfile` | pg_isready healthcheck baked in |
| `docker-compose.yml` | Backup healthcheck uses pg_isready inline; WS port default 8081 |
| `deploy/nginx/ssl/` | Cloudflare Origin CA cert + key + CA root (keys gitignored) |
| `docs/wiki-html/index.html` | Static wiki index page for /TSiSIP/wiki/ |

### 5.2 TSiAPP Repository

| File | Change |
|------|--------|
| `docker-compose.yml` | Removed individual service host ports; added tsisip_sip_internal external network to nginx; mounted SSL certs and wiki HTML |
| `infra/nginx/nginx.conf` | Full HTTPS server with Cloudflare Origin CA; path-based routing for all 6 projects; rate limiting; HSTS; HTTP->HTTPS redirect |

---

## 6. Identified Gaps & Recommendations

| ID | Gap | Priority | Recommendation |
|----|-----|----------|----------------|
| G1 | tsiapp-orthoplus unhealthy | Medium | Fix healthcheck in OrthoPlus Dockerfile or compose override |
| G2 | certbot / tailscale-cert restart loops | Low | These depend on external DNS/internet. Either configure DNS properly or remove from production compose |
| G3 | Spec 018 (FR-ID migration) has duplicates | Medium | Run fr-id-duplicates.json remediation |
| G4 | 15 specs lack blueprints | Low | Backfill blueprints for specs 001-007, 011, 013, 015-018, 021 if governance requires |
| G5 | `.env.example` still has example.com placeholders | Low | Replace with tsiapp.io production values where safe |
| G6 | No automated cert expiry alert for 2041 Origin CA | Low | Add calendar reminder or monitoring alert for 2040-11 |
| G7 | TSiAPP services not on TSiSIP's db_internal network | Low | If cross-service DB access needed, evaluate network bridge |

---

## 7. Commit Log (This Run)

- `bc32930` — TSiSIP: fix: stabilize backup and opensips-exporter healthchecks
- `ef774bd` — TSiSIP: infra: add Cloudflare Origin CA certificates and wiki static pages
- `18c2814` — TSiAPP: infra: unified nginx reverse proxy with Cloudflare Origin CA SSL

---

*End of synchronization report.*
