# Project Profile: TSiSIP

> **Scan Date**: 2026-05-25
> **Scanner**: speckit-brownfield-scan
> **Repository**: B0yZ4kr14/TSiSIP (main branch)

---

## 1. Tech Stack

| Category | Detected |
|----------|----------|
| **Primary languages** | Python (~49%), YAML (~15%), Markdown (~10%), TypeScript (~4%), JavaScript (~3%), PHP (~2%), Shell (~1%), OpenSIPS CFG (~1%) |
| **SIP Proxy** | OpenSIPS 3.6 LTS (source-build in Docker) |
| **Database** | PostgreSQL 16 (subscriber auth, routing metadata, audit logs) |
| **Media Relay** | RTPengine 10.5 (Debian bookworm) |
| **PBX Backend** | Asterisk (2x instances for HA) |
| **Web Control Panel (OCP)** | PHP 8.2 + Apache (rebranded OCP v9) |
| **Frontend Build** | Node.js (theme asset generation: CSS variables, manifest, i18n) |
| **Monitoring** | Prometheus 2.51 + Grafana 10.4 + Alertmanager 0.27 |
| **Testing** | pytest (integration tests), Node.js (frontend coexistence + a11y) |
| **CI/CD** | GitHub Actions (ci.yml, deploy.yml, squad-* workflows) |
| **Container Platform** | Docker + Docker Compose (3-network topology) |
| **Certificate Management** | Cloudflare Origin CA (15-year ECC cert) + certbot (Let's Encrypt fallback) |
| **Reverse Proxy** | nginx:alpine (unified TSiAPP proxy with path-based routing) |

### Base Images (Docker)

| Image | Count | Purpose |
|-------|-------|---------|
| `debian:bookworm-slim` | 3 | OpenSIPS build, RTPengine, CA tool |
| `postgres:16` | 2 | Primary DB, backup container |
| `python:3.11/3.12-slim` | 3 | Anomaly detector, certbot-exporter, admin-api helper |
| `php:8.2-apache` | 2 | OCP v9, admin-api |
| `alpine:3.19` | 2 | Certbot, healthcheck builder |
| `prom/prometheus:v2.51.0` | 1 | Metrics collection |
| `grafana/grafana:10.4.0` | 1 | Visualization dashboards |
| `certbot/certbot` | 1 | ACME certificate automation |
| `tailscale/tailscale` | 1 | Mesh VPN certificate access |

---

## 2. Architecture

### 2.1 High-Level Pattern
**Docker-First Multi-Service Platform** with three isolated networks:

```
Internet → Cloudflare → VPS nginx:443 → Docker Networks
                                              │
                    ┌─────────────────────────┼─────────────────────────┐
                    │                         │                         │
              sip_edge                   sip_internal               db_internal
                    │                         │                         │
               OpenSIPS                   RTPengine                PostgreSQL
               (5060/udp/tcp)            (10000-10050/udp)         (5432/tcp)
               (8081/tcp WS)             (22222/udp ctrl)          |
               (4443/tcp WSS)                                      |
                    │                         │                    |
                    └────────── Asterisk PBX ─┘                    |
                               (x2 instances)                      |
```

### 2.2 Service Topology

| Service | Network Membership | Published Ports | Internal Ports |
|---------|-------------------|-----------------|----------------|
| `opensips` | sip_edge, sip_internal, db_internal | 5060/udp, 5060/tcp, 5061/tcp, 8081/tcp, 4443/tcp | 8888/tcp (MI HTTP) |
| `rtpengine` | sip_edge, sip_internal | 10000-10050/udp | 22222/udp (NG ctrl) |
| `postgres` | db_internal | — | 5432/tcp |
| `asterisk-pbx-{1,2}` | sip_internal | — | 5060/tcp+udp |
| `ocp` | sip_internal, db_internal | — | 80/tcp |
| `admin-api` | sip_internal, db_internal | — | 8080/tcp |
| `prometheus` | sip_internal | — | 9090/tcp |
| `grafana` | sip_internal | — | 3000/tcp |
| `alertmanager` | sip_internal | — | 9093/tcp |
| `anomaly-detector` | sip_internal | — | 8080/tcp |
| `opensips-exporter` | sip_internal | — | 9442/tcp |
| `backup` | db_internal | — | 9101/tcp (metrics) |
| `certbot` / `tailscale-cert` | — | — | — (restart loops) |

### 2.3 Module Structure

| Module | Path | Purpose | Language |
|--------|------|---------|----------|
| **SIP Proxy Core** | `opensips/` | OpenSIPS 3.6 config template (TLS, auth, routing, topology hiding) | OpenSIPS CFG |
| **Web Control Panel** | `web/` | OCP v9 PHP application (27 endpoints) | PHP 8.2 |
| **OCP Theme Assets** | `web/tsisip/` | Rebranded CSS, JS, fonts, icons, i18n locales | CSS/JS/JSON |
| **Database Schema** | `db/init/` | PostgreSQL initialization scripts (stock + extensions + seed) | SQL |
| **Container Images** | `docker/*/` | 15+ service-specific Dockerfiles with healthchecks | Dockerfile |
| **Integration Tests** | `tests/integration/` | 20+ pytest-based integration tests | Python |
| **Frontend Tests** | `tests/` | D3/jQuery coexistence, accessibility audit | Node.js |
| **Build Scripts** | `build/` | Theme variable generator, asset manifest builder | Node.js |
| **Deploy Scripts** | `scripts/` | Build, rollback, cert rotation, TLS reload, security audit | Shell/Bash |
| **Deploy Config** | `deploy/` | Ansible playbooks, nginx configs, SSH hardening, audit scripts | YAML/Shell |
| **Specifications** | `specs/` | 24 feature specs with plan, tasks, memory, blueprint | Markdown |
| **Documentation** | `docs/` | Canonical spec, operator runbook, agent orchestration playbook | Markdown |
| **Reports** | `reports/` | Brownfield scans, memorylint, version-guard, validation reports | Markdown |
| **Evidence** | `evidence/` | Phase-gated implementation evidence (discovery → QA → remediation) | Mixed |
| **Agent Config** | `.squad/`, `.agents/`, `.kimi/`, `.omk/` | Multi-agent orchestration (Claude, Kimi, OMK, Swarm) | YAML/JSON/Markdown |

---

## 3. Conventions

### 3.1 File Naming
- **Shell scripts**: `kebab-case.sh` (e.g., `build-ocp-theme.sh`, `tls-reload.sh`)
- **Python scripts**: `snake_case.py` (e.g., `sip-auth-probe.py`, `migrate-fr-ids.py`)
- **PHP files**: `kebab-case.php` (e.g., `audit-log.php`, `healthcheck-audit.php`)
- **SQL migrations**: `NN-descriptive-name.sql` (e.g., `04-trunk-schema.sql`)
- **Docker services**: `lowercase-hyphenated` (e.g., `opensips-exporter`, `asterisk-pbx-1`)
- **Network names**: `lowercase_underscore` (e.g., `sip_edge`, `db_internal`)

### 3.2 Commit Style
**Conventional Commits** with scope tags:
```
feat(tls): enable TLS 1.3 support
fix(docker): remove invalid rtpengine DTLS args
docs(specs): mark 002, 009, 010 as Completed
infra: add Cloudflare Origin CA certificates
```

### 3.3 Branch Pattern
- `main` — primary branch (ahead of origin by 17+ commits)
- `master` — legacy alias
- Feature work appears to happen directly on `main` or via spec-kit governed workflows

### 3.4 Testing Conventions
- **Integration tests**: `tests/integration/test_*.py` using pytest
- **Frontend tests**: `tests/*.test.js` using Node.js assertions
- **Healthchecks**: Per-container `HEALTHCHECK` in Dockerfiles + compose overrides
- **SIP validation**: Manual `sipsak` and Python socket scripts

### 3.5 Documentation Conventions
- **Specs**: `specs/NNN-feature-name/spec.md` + `plan.md` + `tasks.md`
- **Memory**: `specs/NNN-feature-name/memory.md` + `memory-synthesis.md`
- **Evidence**: `evidence/phase{1-5}/`, `evidence/qa/`, `evidence/remediation/`
- **Agent instructions**: `AGENTS.md` (project-level), `.kimi/AGENTS.md` (Kimi-specific)

---

## 4. Existing Governance

| Artifact | Status | Notes |
|----------|--------|-------|
| `AGENTS.md` | ✅ Present | 50KB+ comprehensive agent onboarding guide |
| `CLAUDE.md` | ✅ Present | Generic Claude Code config |
| `.github/copilot-instructions.md` | ✅ Present | Repo-specific constraints for GitHub Copilot |
| `docs/TSiSIP-CANONICAL-SPEC.md` | ✅ Present | 40KB architecture & tech baseline |
| `docs/TSiSIP-OPERATOR-RUNBOOK.md` | ✅ Present | 52KB operational procedures |
| `docs/TSiSIP-AGENT-ORCHESTRATION-PLAYBOOK.md` | ✅ Present | Multi-agent validation workflow |
| `Makefile` | ✅ Present | 7 standard targets |
| `.specify/` | ✅ Present | spec-kit extensions, catalogs, workflows |
| `.squad/` | ✅ Present | Agent squad orchestration (8 roles defined) |
| `.github/workflows/` | ✅ Present | CI, deploy, squad heartbeat/triage/assignment |
| `.editorconfig` | ❌ Missing | Not detected |
| `CONTRIBUTING.md` | ❌ Missing | Not detected |
| `ARCHITECTURE.md` | ❌ Missing | Canonical spec serves this role |
| `ADR/` | ❌ Missing | Architecture decisions embedded in specs/memory |

---

## 5. Notable Characteristics

### 5.1 Security-First Design
- All secrets in `secrets/` directory (gitignored)
- Docker secrets mounted via `:ro` read-only
- `cap_drop: [ALL]` + explicit `cap_add` on containers
- `security_opt: [no-new-privileges:true]`
- TLS 1.3 for SIP, DTLS passive for RTP
- Topology hiding via OpenSIPS (backend IPs never exposed)
- Auth rate limiting (3 failures → ban via cache)
- HA1-only password storage (no plaintext)

### 5.2 Production Readiness
- Healthchecks on all critical services
- Prometheus metrics + Grafana dashboards
- Alertmanager for alerting
- Anomaly detection container
- Backup container with rclone + encryption
- Circuit breaker pattern in dispatcher
- Auto-healing restart policies

### 5.3 Agent Orchestration Heavy
- **4 agent frameworks** coexisting: Claude Code, Kimi (OMK), Swarm, Sisyphus
- **~330+ skills** loaded (majority speckit extensions)
- **24 specs** with full governance trail (memory, evidence, blueprints)
- **8 squad roles** defined (sip-engineer, database-engineer, frontend-engineer, devops-engineer, security-engineer, qa-engineer, scribe, ralph)

### 5.4 Certificate Strategy
- **Primary**: Cloudflare Origin CA ECC certificate (15-year, expires 2041)
- **Edge**: Cloudflare-managed Let's Encrypt (auto-renewed)
- **Fallback**: certbot Let's Encrypt (currently failing due to no DNS)
- **Internal**: Self-generated TLS certs for SIP/TLS

---

## 6. Recommendations

1. **Add `.editorconfig`** to enforce consistent formatting across PHP/Python/JS/SQL files
2. **Create `CONTRIBUTING.md`** summarizing commit conventions, spec workflow, and agent orchestration rules
3. **Resolve `certbot` / `tailscale-cert` restart loops** — either fix DNS/internet access or remove from production compose
4. **Fix `tsiapp-orthoplus` healthcheck** in the unified nginx stack
5. **Backfill blueprints** for early specs (001–007) if architectural governance requires it
6. **Add certificate expiry monitoring** for the 2041 Origin CA cert (calendar alert for ~2040-11)
7. **ConsiderADR directory** for major architectural decisions currently scattered in specs/memory files

---

*End of brownfield scan report.*
