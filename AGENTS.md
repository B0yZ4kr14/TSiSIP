# TSiSIP — Agent Onboarding Guide

> Read this first. This file is the single source of context for AI coding agents working on the TSiSIP repository.
> **Note:** For project-specific architectural rules, design patterns, coding standards, and constitution enforcement gates, refer to `.specify/memory/constitution.md`.

---

## 1. Project Overview

**TSiSIP** is a Docker-image-first SIP edge-proxy platform built on **OpenSIPS 3.6 LTS**. Its sole purpose is to act as the only public SIP signaling entry point and security boundary for a private, multi-tenant Asterisk PBX backend cluster.

In plain terms:
- OpenSIPS sits on the public internet and handles all SIP traffic (REGISTER, INVITE, etc.).
- It authenticates every request against PostgreSQL-backed subscriber credentials.
- It dynamically routes authenticated traffic to the correct Asterisk backend using tenant-scoped metadata.
- RTPengine relays all media (voice/video RTP) so backend PBX IP addresses never leak to the public internet.
- Asterisk and PostgreSQL live on isolated Docker networks with zero host-published ports.
- An OCP (OpenSIPS Control Panel) v9 web interface provides operational dashboards, role-based wiki, and tenant management.

The project follows **Spec-Driven Development (SDD)** via Speckit, with 24 tracked feature specifications (001–024).

---

## 2. Repository State

- **Documentation-first greenfield with active commits**: Dockerfile, Docker Compose, OpenSIPS config template, PostgreSQL schema, container entrypoints, CI workflows, integration tests, operator runbooks, and deploy pipelines are committed.
- Git is initialized and has **active commits** on `master` branch.
- The repository currently contains:
  - Canonical architecture specification (`docs/TSiSIP-CANONICAL-SPEC.md`)
  - Agent orchestration configuration (`.claude/`, `.claude-flow/`, `.swarm/`, `.sisyphus/`, `.omk/`, `.kimi/`)
  - GitHub-level guidance (`.github/copilot-instructions.md`) and CI/CD workflows (`.github/workflows/`)
  - MCP configuration (`.mcp.json`, `.omk/mcp.json`, `.kimi/mcp.json`)
  - Docker builds: `Dockerfile`, `docker-compose.yml`, `docker-compose.prod.yml`, `docker-compose.vps.yml`, `.dockerignore`
  - OpenSIPS config: `opensips/opensips.cfg.tpl`
  - Container entrypoints: `docker/entrypoint.sh`, `docker/ocp/entrypoint.sh`
  - PostgreSQL schema: `db/init/01-stock-opensips-schema.sql`, `db/init/02-tsisip-extensions.sql`, `db/init/03-seed-data.sql`, `db/init/04-ocp-tools-schema.sql`, `db/init/04-ocp-audit-schema.sql`, `db/init/04-trunk-schema.sql`, `db/init/05-seed-trunk-data.sql`
  - OCP web application: `web/` (PHP 8.2 + Apache)
  - OCP theme build pipeline: `build/`, `scripts/build-ocp-theme.sh`
  - Integration tests: `tests/integration/` (pytest + Python sockets)
  - Frontend tests: `tests/` (Node.js — accessibility + D3.js/jQuery coexistence)
  - Deploy automation: `deploy/scripts/orchestrate-deploy.sh`, `deploy/ansible/`
  - Secrets directory: `secrets/` (`.gitignore` protected)
  - Environment template: `.env.example`
  - This file (`AGENTS.md`)

### Git Workflow

| Convention | Rule |
|---|---|
| Canonical branch | `master` |
| Feature branches | Branch from `master`; name should align with the spec directory (e.g., `specs/020-ocp-critical-tool-gap-closure/` → `feature/020-ocp-critical-tool-gap-closure`) |
| Commit messages | Descriptive; reference feature ID when applicable (e.g., `Feature 020: add TLS management tool`) |
| Git mutations | Require explicit user confirmation; never auto-commit |

**Safety:** `secrets/` and `.env*` are gitignored. Never stage or commit them.

---

## 3. Technology Stack

| Layer | Technology | Role |
|---|---|---|
| SIP Proxy | OpenSIPS 3.6 LTS | Public signaling edge; auth, routing, topology hiding |
| Database | PostgreSQL 16 | Subscriber auth, tenant metadata, routing rules, dispatcher state, CDR |
| Media Relay | RTPengine | Public RTP relay; SDP rewriting; SRTP/DTLS support |
| PBX Backend | Asterisk | Private voice/video application servers |
| Admin Panel | OCP v9 + TSiSIP Theme | PHP 8.2 / Apache; dashboards, wiki, role-based navigation |
| Observability | Prometheus + Grafana + Alertmanager + custom exporter | Metrics, alerting, anomaly detection |
| Backup | Custom cron-based container | Encrypted PostgreSQL backups, WAL archiving, offsite replication |
| Packaging | Docker image + Docker Compose | Canonical runtime delivery |
| Build Tools | Node.js, GNU gettext (`msgfmt`), Bash | OCP asset pipeline, i18n compilation |
| Test Framework | pytest (Python), Node.js (frontend) | Integration and frontend validation |
| Deploy | Ansible + GitHub Actions + shell orchestrator | VPS bootstrap, image push, gated deploy |

**Public Ports:**
- OpenSIPS: `5060/udp`, `5060/tcp`, `5061/tcp` (TLS)
- RTPengine: `10000-20000/udp` (or `10000-10999/udp` on VPS-lite)
- OCP (internal/Nginx-proxied): `8084/tcp` on loopback (VPS) or `80/tcp` (container)

**Non-negotiable rules:**
> These baseline constraints are formally enforced by the Architecture Constitution (see `.specify/memory/constitution.md`).

- OpenSIPS 3.6 LTS is the **only** SIP proxy baseline. Changing it requires a documented architecture decision.
- PostgreSQL is the **only** database. Do not introduce MySQL, MariaDB, or `db_mysql` variants.
- OpenSIPS must be delivered through a **project-owned Docker image**, never bare-metal or VM-first install instructions.
- Asterisk and PostgreSQL must have **zero host-published ports**.

---

## 4. System Architecture

```text
Internet / SIP clients
        |
        | 5060/udp, 5060/tcp, 5061/tcp (TLS)
        v
+-----------------------------+
| OpenSIPS Docker image       |
| TSiSIP edge proxy           |
| - auth                      |
| - header routing            |
| - topology hiding           |
| - dispatcher failover       |
| - rate limiting (pike)      |
| - WebSocket/WebRTC          |
+-------------+---------------+
              |
              | internal SIP control
              v
+-----------------------------+
| Asterisk PBX backends       |
| private Docker network only |
+-----------------------------+

Internet / RTP clients
        |
        | 10000-20000/udp
        v
+-----------------------------+
| RTPengine media relay       |
| public RTP, internal control|
| SRTP/DTLS enabled           |
+-----------------------------+

OpenSIPS
        |
        | internal DB network
        v
+-----------------------------+
| PostgreSQL                  |
| auth + routing metadata     |
| CDR + audit logs            |
+-----------------------------+

OCP (Operator Control Panel)
        |
        | internal DB / SIP networks
        v
+-----------------------------+
| PHP 8.2 + Apache            |
| Dashboards, Wiki, Auth      |
| Role-based navigation       |
+-----------------------------+
```

### Docker Network Model

| Network | Members | External Access | Purpose |
|---|---|---:|---|
| `sip_edge` | OpenSIPS, RTPengine | Yes | Public SIP and RTP ingress |
| `sip_internal` | OpenSIPS, RTPengine, Asterisk | No | Internal SIP forwarding and RTPengine control |
| `db_internal` | OpenSIPS, PostgreSQL, OCP, Prometheus, Grafana, Backup, Exporter | No | Database access and observability |
| `metrics_host` | (VPS-lite only) OCP, Backup, Postgres | Partial | Loopback-exposed metrics and OCP proxy |

**Published ports:**
- OpenSIPS: `5060/udp`, `5060/tcp`, `5061/tcp`
- RTPengine: `10000-20000/udp`

**Forbidden published ports:**
- Asterisk: any
- PostgreSQL: any
- RTPengine control socket (`--listen-ng`): any

---

## 5. Directory Structure

```
TSiSIP/
├── docs/                               # Canonical documentation
│   ├── TSiSIP-CANONICAL-SPEC.md        # Architecture & tech baseline
│   ├── TSiSIP-AGENT-ORCHESTRATION-PLAYBOOK.md
│   ├── TSiSIP-OPERATOR-RUNBOOK.md
│   ├── architecture/                   # ADRs
│   ├── features/                       # Feature proposals
│   └── wiki/                           # Embedded wiki markdown sources
├── .github/
│   ├── workflows/                      # CI/CD (ci.yml, deploy.yml)
│   ├── agents/                         # Speckit agent definitions
│   ├── prompts/                        # Speckit prompt templates
│   └── copilot-instructions.md
├── .claude/                            # Claude Code agent definitions & helpers
├── .claude-flow/                       # Ruflo (Claude Flow) V3 orchestration
├── .omk/                               # oh-my-kimi project config, hooks, memory
├── .kimi/                              # Kimi-specific agent rules & skills
├── .swarm/
│   └── state.json                      # Swarm runtime state
├── .sisyphus/
│   └── run-continuation/               # Run continuation metadata
├── opensips/                           # OpenSIPS configuration template
│   └── opensips.cfg.tpl
├── docker/                             # Container support files
│   ├── entrypoint.sh                   # OpenSIPS runtime config renderer
│   ├── ocp/
│   │   ├── Dockerfile                  # OCP v9 PHP/Apache image
│   │   ├── entrypoint.sh               # OCP secret-permission fixer
│   │   └── php-session-security.ini    # PHP hardening
│   ├── rtpengine/
│   │   ├── Dockerfile
│   │   └── healthcheck.sh
│   ├── asterisk/
│   │   ├── Dockerfile
│   │   ├── healthcheck.sh
│   │   ├── pjsip.conf
│   │   └── extensions.conf
│   ├── postgres/
│   │   ├── Dockerfile
│   │   └── healthcheck.sh
│   ├── prometheus/
│   │   ├── Dockerfile
│   │   ├── alert-rules.yml
│   │   ├── alertmanager.yml.tpl
│   │   ├── prometheus.yml.tpl
│   │   └── entrypoint.sh
│   ├── grafana/
│   │   └── Dockerfile
│   ├── opensips_exporter/
│   │   ├── Dockerfile
│   │   └── exporter.py
│   ├── anomaly_detector/
│   │   ├── Dockerfile
│   │   ├── detector.py
│   │   └── baseline.py
│   ├── backup/
│   │   ├── Dockerfile
│   │   ├── backup.sh
│   │   ├── encrypt.sh
│   │   ├── entrypoint.sh
│   │   ├── metrics-exporter.sh
│   │   ├── pitr-restore.sh
│   │   ├── purge.sh
│   │   ├── quota-check.sh
│   │   ├── rclone.conf.tpl
│   │   ├── replicate.sh
│   │   ├── rotate-key.sh
│   │   ├── rpo-monitor.sh
│   │   ├── validate.sh
│   │   └── wal-archive.sh
│   ├── ca-tool/
│   │   ├── Dockerfile
│   │   ├── ca-init.sh
│   │   ├── cert-gen.sh
│   │   └── cert-rotate.sh
│   ├── admin_api/
│   │   └── Dockerfile                  # Admin API proxy container
│   ├── certbot/
│   │   ├── Dockerfile
│   │   ├── deploy-hook.sh
│   │   └── healthcheck.sh
│   ├── certbot_exporter/
│   │   ├── Dockerfile
│   │   └── exporter.py
│   ├── tailscale_cert/
│   │   └── Dockerfile
│   └── healthcheck/                    # Shared healthcheck scripts
├── db/init/                            # PostgreSQL initialization scripts
│   ├── 01-stock-opensips-schema.sql
│   ├── 02-tsisip-extensions.sql
│   ├── 03-seed-data.sql
│   ├── 04-ocp-tools-schema.sql
│   ├── 04-ocp-audit-schema.sql
│   ├── 04-trunk-schema.sql
│   └── 05-seed-trunk-data.sql
├── web/                                # OCP v9 PHP application
│   ├── common/
│   │   ├── config.php                  # DB auth, PDO, role hierarchy
│   │   ├── header.php                  # Asset manifest loader
│   │   ├── footer.php
│   │   ├── role-nav.php                # Role-based sidebar navigation
│   │   ├── csrf.php                    # CSRF token generation/validation
│   │   ├── pagination.php              # Reusable LIMIT/OFFSET pagination
│   │   └── ha1-generator.php           # HA1 hash generators for SIP auth
│   ├── tsisip/                         # TSiSIP branded assets
│   │   ├── css/
│   │   ├── js/
│   │   ├── assets/
│   │   ├── locale/                     # .po/.mo i18n files
│   │   └── asset-manifest.json
│   ├── login.php
│   ├── dashboard.php
│   ├── subscribers.php                 # SIP subscriber CRUD with HA1 generation
│   ├── cdr-viewer.php                  # Read-only CDR viewer with filters
│   ├── dispatcher.php                  # Dispatcher target CRUD (replaces stub)
│   ├── rtpengine.php
│   ├── wiki.php
│   ├── change-password.php
│   ├── index.php
│   ├── logout.php
│   └── css/main.css
├── build/                              # OCP theme build pipeline
│   ├── generate-css-variables.js
│   ├── generate-manifest.js
│   ├── tsisip-theme.src.css
│   ├── theme.json
│   └── Makefile
├── tests/
│   ├── integration/                    # pytest + Python socket tests
│   ├── accessibility-audit.test.js     # Node.js WCAG 2.1 AA checks
│   ├── d3-jquery-coexistence.test.js   # Node.js frontend scope tests
│   ├── performance/
│   └── visual-regression/
├── scripts/
│   ├── build-ocp-theme.sh
│   ├── rollback-ocp-theme.sh
│   ├── ci-scan.sh
│   ├── sip-auth-probe.py
│   ├── cert-expiry-monitor.sh
│   └── tls-reload.sh
├── deploy/
│   ├── ansible/
│   │   ├── inventory.yml
│   │   ├── playbook-deploy.yml
│   │   └── playbook-hardening.yml
│   ├── scripts/
│   │   ├── orchestrate-deploy.sh       # Feature 009 deploy pipeline
│   │   ├── vps-bootstrap.sh
│   │   ├── vps-deploy.sh
│   │   ├── vps-nginx-setup.sh
│   │   ├── test-vps-local.sh
│   │   └── safe-recovery.sh
│   ├── nginx/
│   ├── audit/
│   ├── validate.sh
│   ├── README.md
│   ├── README-VPS-DEPLOY.md
│   └── VPS-DEPLOY-READINESS.md
├── specs/                              # Speckit SDD artifacts (001–024)
│   ├── 001-opensips-docker-edge-proxy/
│   ├── 002-tsisip-ocp-rebrand/
│   ├── 003-prometheus-grafana-observability/
│   ├── 004-health-checks-autohealing/
│   ├── 005-postgresql-backup-restore/
│   ├── 006-rate-limiting-ddos-protection/
│   ├── 007-tls-srtp-encryption/
│   ├── 008-devsecops-deployment/
│   ├── 009-vps-deploy-automation/
│   ├── 010-ocp-navigation-system-links/
│   ├── 011-ocp-forced-password-change/
│   ├── 012-ocp-admin-tools-restoration/
│   ├── 013-brownfield-follow-up/
│   ├── 014-auto-tls-certificate-rotation/
│   ├── 015-ocp-audit-log-compliance/
│   ├── 016-sip-trunk-provider-integration/
│   ├── 017-global-requirement-id-migration/
│   ├── 018-spec-kit-memory-hub-integration/
│   ├── 019-ocp-critical-tool-gap-closure/
│   ├── 020-brownfield-security-production-hardening/
│   ├── 021-vps-go-live-stabilization/
│   └── 022-subscriber-crud-refactor/
├── reports/                            # Quality gate & scan reports
├── secrets/                            # Runtime secrets (gitignored)
├── design/
│   ├── palette.md
│   └── typography.md
├── .env.example
├── .dockerignore
├── Dockerfile                          # OpenSIPS 3.6 LTS source-build image
├── docker-compose.yml                  # Full development stack (16 services)
├── docker-compose.prod.yml             # Production stack (GHCR images)
├── docker-compose.vps.yml              # VPS-lite profile (~7.5GB RAM, 10 services)
├── Makefile
├── CHANGELOG.md
├── STATUS.md
├── SECURITY.md
├── ROADMAP.md
├── DESIGN.md
├── README.md
├── CLAUDE.md
├── KIMI.md
├── GEMINI.md
└── AGENTS.md                           # This file
```

> **Note:** `.claude/`, `.claude-flow/`, `.swarm/`, `.sisyphus/`, `.omk/`, and `.specify/` are **agent orchestration state/config**, not TSiSIP application tooling. Do not confuse them with application source or deployment artifacts.

---

## 6. Build and Test Commands

**Canonical build and validation commands (committed):**

```bash
# Build the OpenSIPS image from source
docker build -t tsisip/opensips:latest .

# Build the RTPengine image from source
docker build -t tsisip/rtpengine:latest -f docker/rtpengine/Dockerfile .

# Build the OCP image with theme assets
./scripts/build-ocp-theme.sh

# Validate rendered Compose configuration
docker compose config

# Validate OpenSIPS config syntax inside the built image
docker run --rm \
  -e DB_HOST=postgres -e DB_NAME=opensips -e DB_USER=opensips \
  -e HOST_PUBLIC_IP=127.0.0.1 -e OPENSIPS_LISTEN_IP=0.0.0.0 \
  -e RTPENGINE_HOST=rtpengine \
  -v $(pwd)/secrets/db_password:/run/secrets/db_password:ro \
  -v $(pwd)/secrets/auth_secret:/run/secrets/auth_secret:ro \
  -v $(pwd)/secrets/topology_secret:/run/secrets/topology_secret:ro \
  tsisip/opensips:latest \
  /entrypoint.sh /usr/local/sbin/opensips -c -f /etc/opensips/opensips.cfg

# Start the database and verify schema initialization
docker compose up -d postgres
docker compose exec postgres psql -U opensips -d opensips -c "\dt"

# Build all services
docker compose build

# Start the full stack
docker compose up -d

# Runtime SIP validation (OPTIONS 200 OK)
docker run --rm --network tsisip_sip_edge alpine \
  sh -c "apk add --no-cache sipsak >/dev/null 2>&1 && \
         sipsak -s sip:opensips:5060 -vv"
# Expected: SIP/2.0 200 OK with Server: OpenSIPS (3.6.5 ...)

# Runtime SIP validation (INVITE 401 Unauthorized)
python3 -c "
import socket
msg = b'INVITE sip:test@opensips:5060 SIP/2.0\r\n' \
      b'Via: SIP/2.0/UDP 172.22.0.1:5061;branch=z9hG4bK-invite123\r\n' \
      b'From: <sip:test@172.22.0.1>;tag=invitetag\r\n' \
      b'To: <sip:test@opensips:5060>\r\n' \
      b'Call-ID: test-invite-001@172.22.0.1\r\n' \
      b'CSeq: 1 INVITE\r\nMax-Forwards: 70\r\n' \
      b'Contact: <sip:test@172.22.0.1:5061>\r\n' \
      b'Content-Length: 0\r\n\r\n'
sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
sock.settimeout(5)
sock.sendto(msg, ('127.0.0.1', 5060))
data, _ = sock.recvfrom(4096)
print(data.decode())
"
# Expected: SIP/2.0 401 Unauthorized with WWW-Authenticate headers
```

**OCP Rebranding build and validation commands (Feature 002):**

```bash
# Full orchestrated build (assets + i18n + tests + Docker image)
./scripts/build-ocp-theme.sh

# Individual steps:
# Generate CSS variables from design tokens
node build/generate-css-variables.js

# Generate hashed asset manifest
node build/generate-manifest.js

# Compile i18n locale files
msgfmt web/tsisip/locale/tsisip-en.po -o web/tsisip/locale/en_US/LC_MESSAGES/tsisip.mo
msgfmt web/tsisip/locale/tsisip-es.po -o web/tsisip/locale/es_ES/LC_MESSAGES/tsisip.mo
msgfmt web/tsisip/locale/tsisip-pt.po -o web/tsisip/locale/pt_BR/LC_MESSAGES/tsisip.mo

# Run D3.js + jQuery coexistence test
node tests/d3-jquery-coexistence.test.js

# Run accessibility audit
node tests/accessibility-audit.test.js

# CSS specificity audit
grep -c '!important' web/tsisip/css/tsisip-theme.css

# Build OCP Docker image
docker build -t tsisip/ocp:latest -f docker/ocp/Dockerfile .

# Validate OCP container health (run after compose up)
docker compose exec ocp bash -c "curl -fsSL http://localhost/login.php | grep -q 'TSiSIP'"

# Validate audit healthcheck endpoint
docker compose exec ocp bash -c "curl -fsSL http://localhost/healthcheck-audit.php | grep -q 'status.*ok'"

# Validate wiki rendering
curl -fsSL http://localhost:8084/wiki.php | grep -q "TSiSIP Professional Wiki"
```

**Audit Log & Compliance Dashboard tests (Feature 016 Wave 6):**

```bash
# Run end-to-end audit pipeline tests (requires running compose stack)
OCP_SEED_ADMIN_PASS=$(grep -oP "crypt\\('\\K[^']+" db/init/03-seed-data.sql | head -n1) \
    bash tests/integration/test-ocp-audit.sh

# Run dashboard UI/filter tests (requires running compose stack)
OCP_SEED_ADMIN_PASS=$(grep -oP "crypt\\('\\K[^']+" db/init/03-seed-data.sql | head -n1) \
    bash tests/integration/test-audit-dashboard.sh

# Validate PHP syntax for all audit-related files
for f in web/common/audit.php web/audit-log.php web/audit-export.php \
         web/cli/purge-audit-log.php web/healthcheck-audit.php; do
    php -l "$f"
done
```

**Unified Nginx Reverse Proxy + Cloudflare Origin CA (VPS Production):**

```bash
# The unified nginx container runs in TSiAPP project and proxies to both
# TSiAPP services and TSiSIP OCP. It uses Cloudflare Origin CA certificates
# with 15-year validity (expires 2041-05-21).
#
# Certificate files (in TSiSIP/deploy/nginx/ssl/):
#   tsiapp.io.crt      — Cloudflare Origin CA ECC certificate
#   tsiapp.io.key      — private key (gitignored)
#   cloudflare_origin_ca_rsa_root.pem — CA root for verification
#
# Cloudflare SSL/TLS mode: Full (encrypts origin traffic)
# DNS records (proxied): tsiapp.io → 179.190.15.116
#
# Endpoints served:
#   https://tsiapp.io/              → LandPages
#   https://tsiapp.io/TSiSIP/       → TSiSIP OCP
#   https://tsiapp.io/TSiSIP/wiki/  → Static wiki index
#   https://tsiapp.io/OrthoPlus-Enterprise/ → OrthoPlus
#   https://tsiapp.io/TSiMUSIC/     → TSiMUSIC
#   https://tsiapp.io/TSiView/      → TSiView

# Validate nginx config syntax
docker exec tsiapp-nginx nginx -t

# Test all HTTPS endpoints locally (skip cert verification)
for path in / /TSiSIP/ /TSiSIP/wiki/ /OrthoPlus-Enterprise/ /TSiMUSIC/ /TSiView/; do
  curl -sk -o /dev/null -w "%{http_code}" "https://localhost$path"
done
```

**Automated TLS Certificate Rotation tests (Feature 015 Wave 5):**

```bash
# Validate TLS rotation integration tests (works without running stack)
bash tests/integration/test-tls-rotation.sh

# Validate OpenSIPS config with TLS enabled (requires built image)
docker run --rm --entrypoint /usr/local/sbin/opensips \
  -v $(pwd)/opensips/opensips.cfg.tpl:/etc/opensips/opensips.cfg.tpl:ro \
  tsisip/opensips:latest \
  -c -f /etc/opensips/opensips.cfg

# Validate certbot image build and --dry-run support
docker build -t tsisip/certbot:latest -f docker/certbot/Dockerfile docker/certbot/
docker run --rm --entrypoint /usr/local/bin/certbot tsisip/certbot:latest --help | grep -q dry-run

# Validate deploy-hook.sh syntax and healthcheck
bash -n docker/certbot/deploy-hook.sh
bash -n docker/certbot/healthcheck.sh

# Manually trigger certificate rotation (staging mode)
./scripts/cert-rotate.sh --staging

# Reload OpenSIPS TLS certificates without downtime
./scripts/tls-reload.sh
```

**SIP Trunk Provider Integration tests (Feature 017 Wave 7):**

```bash
# Validate OpenSIPS config with trunk modules (requires built image)
docker run --rm \
  -e DB_HOST=postgres -e DB_NAME=opensips -e DB_USER=opensips \
  -e HOST_PUBLIC_IP=127.0.0.1 -e OPENSIPS_LISTEN_IP=0.0.0.0 \
  -e RTPENGINE_HOST=rtpengine -e TRUNK_CRED_KEY=dummy \
  -v $(pwd)/secrets/db_password:/run/secrets/db_password:ro \
  -v $(pwd)/secrets/auth_secret:/run/secrets/auth_secret:ro \
  -v $(pwd)/secrets/topology_secret:/run/secrets/topology_secret:ro \
  -v $(pwd)/secrets/server.crt:/run/secrets/server.crt:ro \
  -v $(pwd)/secrets/server.key:/run/secrets/server.key:ro \
  -v $(pwd)/secrets/ca.crt:/run/secrets/ca.crt:ro \
  tsisip-opensips:latest \
  /entrypoint.sh /usr/local/sbin/opensips -c -f /etc/opensips/opensips.cfg

# Run Python integration tests (requires running compose stack)
python3 tests/integration/test_sip_trunk_outbound.py
python3 tests/integration/test_sip_trunk_failover.py
python3 tests/integration/test_sip_trunk_inbound.py
python3 tests/integration/test_sip_trunk_rate_limit.py
python3 tests/integration/test_sip_trunk_health_probe.py

# Validate trunk schema in PostgreSQL
docker compose exec postgres psql -U opensips -d opensips -c "\dt sip_trunk_*"
```

**Runbook Automation tests (Feature 025):**

```bash
# Scale a new Asterisk backend
./scripts/runbook/scale-asterisk.sh 192.0.2.99 1 "new-pbx-1"

# Verify evidence artifact was produced
ls evidence/runbook/*_scale-*/evidence.json

# Run integration test
python3 -m pytest tests/integration/test_runbook_scale.py -v
```

**Point-in-Time Recovery tests (Feature 005/Stage 8):**

```bash
# Verify PITR dry-run (lists backup + WAL segments to be replayed)
docker compose exec backup /usr/local/bin/pitr-restore.sh --target $(date -u +%Y-%m-%dT%H:%M:%SZ) --verify-only

# Run PITR integration tests
python3 -m pytest tests/integration/test_backup_pitr.py -v
```

**Makefile targets:**

```bash
make build          # Build Docker images and OCP theme assets
make test           # Run all automated tests (Node.js frontend tests)
make up             # Start the full Docker Compose stack
make down           # Stop the Docker Compose stack
make lint           # Validate Docker Compose and OpenSIPS config syntax
make health-checks  # Validate healthcheck stanzas across all compose files
make runbook-scale  # Example: scale a new Asterisk backend (set IP=... SETID=...)
make pitr-verify    # Verify PITR restore to a temp database
make ocp-build      # Build OCP theme assets only
make ocp-rollback   # Rollback OCP theme to original OCP v9
make clean          # Remove generated artifacts and Docker volumes
make help           # Show available targets
```

**Additional repository checks:**

```bash
# List all tracked files (excluding .git and node_modules)
rg --files -uuu -g '!**/.git/**' -g '!**/node_modules/**'

# Search for canonical keywords across documentation
rg -n "OpenSIPS|PostgreSQL|RTPengine|Asterisk|db_postgres|sanity" docs .github AGENTS.md CLAUDE.md .mcp.json

# Brownfield scan (spec drift, tech debt, anti-patterns)
# Skill: speckit-brownfield-scan

# Version guard (dependency pinning, version consistency)
# Skill: speckit-version-guard

# Memory lint (resource limits, memory misconfiguration)
# Skill: speckit-memorylint
```

> `CLAUDE.md` contains a generic `npm run build && npm test` example. **Ignore it for TSiSIP** until a `package.json` or equivalent manifest is committed at the project root.

---

## 7. Code Style and Naming Conventions

> These conventions define the project's coding standards and domain-specific constraints. High-level architecture gates and enforcement policies are defined in `.specify/memory/constitution.md`.

### Names to Preserve Exactly
- `TSiSIP` (capitalization)
- `OpenSIPS 3.6 LTS`
- `PostgreSQL`
- `RTPengine`
- `Asterisk`
- `OCP` (OpenSIPS Control Panel)

### Database & Service Naming
- Use **lowercase snake_case** for:
  - Database identifiers (tables, columns, indexes)
  - Docker service names
  - Docker network names
- Examples: `sip_edge`, `sip_internal`, `db_internal`, `header_routing_rules`, `pbx_backends`, `auth_audit_log`, `ocp_users`, `ocp_login_log`

### OpenSIPS Config Conventions
- Use integer algorithm arguments for dispatcher: `ds_select_dst($var(setid), 4, "f")`
- Use `topology_hiding("C")` as the canonical baseline
- Use explicit `rtpengine_offer()`, `rtpengine_answer()`, and `rtpengine_delete()` — not `rtpengine_manage()` as baseline
- Use `mf_process_maxfwd_header(70)` (RFC 3261 default)

### Module References
- Only reference modules documented for **OpenSIPS 3.6 LTS**.
- `sanity` is **forbidden** — it is not in the OpenSIPS 3.6 module documentation.
- Do not add Kamailio-only modules or functions.

### PHP Conventions
- OCP uses **PHP 8.2** with PDO for PostgreSQL.
- Use prepared statements for all database queries.
- Passwords are stored with `password_hash()` (bcrypt) for OCP users; SIP auth uses HA1 hashes only.
- Session security: `session.cookie_secure` enabled when HTTPS is detected via `X-Forwarded-Proto`.
- Role hierarchy (lowest to highest): `readonly` -> `user` -> `assistant` -> `dentist` -> `devops` -> `admin`.

### Shell Conventions
- Entrypoint and deploy scripts use `set -euo pipefail` where bash is available; `set -eu` for POSIX `sh`.
- Secrets are read with `awk` (not `tr`) to avoid busybox `tr` quirks: `awk 'BEGIN{RS=""; ORS=""} {print}'`.

---

## 8. Documentation Workflow

For **any** request that produces or modifies documentation, specifications, architecture, DevOps guidance, OpenSIPS config guidance, PostgreSQL schemas, or canonical project decisions, you **must** follow the playbook in:

```
docs/TSiSIP-AGENT-ORCHESTRATION-PLAYBOOK.md
```

The playbook requires a multi-agent validation swarm:

| Agent Role | Mission |
|---|---|
| `doc-forensics` | Detect ambiguity, drift, contradictions |
| `opensips-rfc-validator` | Validate OpenSIPS/RFC claims against canonical sources |
| `solution-architecture` | Verify topology, Docker-first, PostgreSQL-only, isolation |
| `devops-docs` | Validate Dockerfile, Compose, networks, ports, secrets |
| `data-specs` | Validate PostgreSQL DDL, auth schemas, indexes |
| `implementation-specs` | Convert architecture into implementable route logic |
| `socratic-popper-reviewer` | Challenge assumptions, force falsifiable claims |

Completion gate:
- Source validation matrix
- Falsification checklist
- Concrete documentation patch or explicit no-change finding
- Final conformance statement against Docker-first and PostgreSQL-only rules
- **Zero** unresolved blocking claims

---

## 9. Security Considerations

### Authentication
- SIP Digest authentication backed by PostgreSQL.
- Store credentials as **HA1 hashes only** (`ha1`, `ha1_sha256`, `ha1_sha512t256`).
- **Never** store plaintext passwords.
- OpenSIPS must read precomputed HA1 columns (`calculate_ha1 = 0`).
- OCP users use bcrypt `password_hash()` with account lockout after 5 failed attempts.

### Header Sanitization
- Remove untrusted inbound headers before using routing metadata:
  - `P-Asserted-Identity`
  - `P-Preferred-Identity`
  - `X-Tenant-ID`
  - `X-Backend-ID`
  - `X-Route-Override`
- Strip credentials before forwarding:
  - `Authorization`
  - `Proxy-Authorization`
- Remove client-supplied `X-Routing-Key` after lookup.

### Topology Hiding
- Use `topology_hiding` so backend PBX IPs are never exposed externally.
- Asterisk and PostgreSQL must have **no host-published ports**.
- RTPengine control socket (`--listen-ng`) must bind only to the internal `sip_internal` address, never `0.0.0.0`.

### Secrets Management
- Keep runtime secrets, private keys, generated credentials, `.env*` (except `.env.example`), and the `secrets/` directory **out of commits**.
- `.gitignore` already excludes them.
- Inject secrets at runtime via Docker secrets or environment-templated config files.
- OCP copies secrets to `/tmp` with `www-data`-readable permissions at startup.

### Docker Runtime Hardening
- Drop all capabilities except those required:
  - OpenSIPS: `NET_BIND_SERVICE`, `SETUID`, `SETGID`
  - RTPengine: `NET_BIND_SERVICE`, `NET_ADMIN`
- Use `security_opt: ["no-new-privileges:true"]`.
- All base images are pinned to SHA256 digests.

### LGPD / Compliance
- The project maintains a compliance framework for data retention and encryption.
- Backup encryption uses AES-256-CBC + PBKDF2 + HMAC-SHA256 via `openssl enc` (Feature 005 implementation).
- CDR and audit logs include tenant isolation for multi-tenancy.

---

## 10. Rejected Patterns

> The following patterns are explicitly forbidden in TSiSIP implementation. These prohibitions are backed by Architecture Constitution gates; see `.specify/memory/constitution.md`.

The following are **explicitly rejected** in TSiSIP documentation, configs, and implementation:

| Rejected Pattern | Canonical Replacement |
|---|---|
| OpenSIPS 3.4 baseline | OpenSIPS 3.6 LTS only |
| `db_mysql`, MySQL, MariaDB | `db_postgres`, PostgreSQL DSNs, PostgreSQL DDL |
| Bare-metal / VM-first runtime | Project-owned Docker images + Compose |
| Host-level package installation of OpenSIPS | Package installation inside Dockerfiles only |
| `calculate_ha1 = 1` | `calculate_ha1 = 0` (precomputed HA1) |
| `password_column = "password"` | `password_column = "ha1"` |
| Plaintext password population in seed data | Populate HA1 hash columns only |
| Kamailio `auth_check()` / `auth_challenge()` | OpenSIPS `www_authorize()` / `www_challenge()` / `proxy_authorize()` / `proxy_challenge()` |
| Auth limited to REGISTER and INVITE only | Authenticate **all** non-OPTIONS untrusted requests |
| Hard-coded `ds_select_dst(1, ...)` | Derive dispatcher set from authenticated tenant-scoped PostgreSQL metadata |
| Custom `CREATE TABLE subscriber` replacing stock schema | Generate stock OpenSIPS 3.6 schema first, then `ALTER TABLE` |
| Custom `CREATE TABLE dispatcher` with `flags` column | Use stock dispatcher schema; column is `state`, not `flags` |
| `topology_hiding("U")` as baseline | `topology_hiding("C")` |
| `rtpengine_manage()` as baseline | Explicit `rtpengine_offer()` / `rtpengine_answer()` / `rtpengine_delete()` |
| RTPengine `listen-ng=127.0.0.1` in multi-container runtime | Bind to `${RTPENGINE_INTERNAL_IP}:22222` on `sip_internal` |
| RTPengine kernel DKMS as baseline | Containerized RTPengine baseline |

---

## 11. Testing Strategy

### Frontend Tests (Node.js)

| Test | File | Purpose |
|---|---|---|
| D3.js + jQuery coexistence | `tests/d3-jquery-coexistence.test.js` | Verifies `tsisip-charts.js` uses isolated ES module scope and does not pollute global `$` |
| Accessibility audit | `tests/accessibility-audit.test.js` | WCAG 2.1 AA checks: color contrast, ARIA labels, focus indicators, font-size, touch targets |

### Integration Tests (Python + pytest)

All tests live in `tests/integration/` and use raw Python sockets or `pytest` with `psycopg2-binary`:

| Test | Validates |
|---|---|
| `test_end_to_end_call.py` | REGISTER -> 401 -> REGISTER(auth) -> 200 -> INVITE -> route -> Asterisk |
| `test_multi_tenant_routing.py` | Tenant-scoped header routing and dispatcher set selection |
| `test_webrtc_support.py` | WebSocket/WSS transport and SRTP/DTLS negotiation |
| `test_tls_srtp.py` | TLS transport, certificate rotation, mTLS trunk validation |
| `test_ddos_protection.py` | Rate limiting, pike module, circuit breaker behavior |
| `test_rate_limiting.py` | Per-tenant and per-IP rate limit enforcement |
| `test_backup_restore.py` | Encrypted backup creation, validation, purge, PITR restore |
| `test_backup_pitr.py` | Point-in-Time Recovery: backup → temp DB restore → schema verification |
| `test_backup_rclone.py` | Offsite replication via rclone |
| `test_runbook_scale.py` | Runbook automation: scale-asterisk.sh evidence and dispatcher insertion |
| `test_certificate_rotation.py` | CA-tool cert generation and rotation |
| `test_cdr_billing.py` | Call Detail Record generation and billing attribution |
| `test_monitoring.py` | Prometheus metrics scraping and Grafana datasource health |
| `test_observability.py` | Alertmanager alert routing and anomaly detection |
| `test_anomaly_detection.py` | Z-score anomaly detection and Alertmanager integration |
| `test_graceful_degradation.py` | Failover behavior when backends are unavailable |
| `test_restart_policy.py` | Container restart policy compliance |
| `test_circuit_breaker.py` | Circuit breaker state transitions |

### Running Tests

```bash
# Frontend tests
node tests/d3-jquery-coexistence.test.js
node tests/accessibility-audit.test.js

# Integration tests (requires running stack)
pip install pytest psycopg2-binary
pytest tests/integration/ -v --tb=short

# Feature-specific integration tests
pytest tests/integration/test_multi_tenant_routing.py -v
pytest tests/integration/test_webrtc_support.py -v
pytest tests/integration/test_cdr_billing.py -v
pytest tests/integration/test_ddos_protection.py -v
pytest tests/integration/test_anomaly_detection.py -v
```

### CI Test Execution

GitHub Actions `.github/workflows/ci.yml` runs:
1. `validate` — Docker Compose syntax, OpenSIPS config structure, committed-secrets scan, Ansible syntax-check, Nginx config validation
2. `build-opensips` — Docker image build
3. `build-ocp` — OCP image build + smoke test
4. `build-supporting` — Matrix build of Prometheus, Grafana, exporter, backup, CA-tool, anomaly_detector
5. `test-integration` — Stack startup, health checks, config validation, pytest suite
6. `speckit-scan` — Brownfield + version-guard + memorylint
7. `security-scan` — Trivy vulnerability scanner

---

## 12. Deployment Process

TSiSIP uses the **Feature 009 VPS Deploy Automation Pipeline** for all production deploys. The pipeline is implemented in `deploy/scripts/orchestrate-deploy.sh` and is triggerable both locally and via GitHub Actions (`workflow_dispatch`).

### Pipeline Stages (Gated)

| Gate | Stage | Description | Halt on Failure |
|---|---|---|---|
| 0 | Pre-flight | Disk space, registry reachability, OpenSIPS config syntax, committed-secrets scan, Docker Compose syntax | Yes |
| 1 | Impact Analysis | Git diff against `origin/master`; static heuristic for HIGH risk on core configs (`opensips.cfg.tpl`, compose files, `entrypoint.sh`) | Yes (override with `FORCE_DEPLOY=1`) |
| 2 | Build | Builder agent detects changed Dockerfiles via `git diff`, builds only modified images | Yes |
| 3 | Push | Pusher agent tags and pushes to GHCR; falls back to build-on-target if credentials missing | No (warn + fallback flag) |
| 4 | Deploy | Deployer agent captures pre-deploy image digests, SSH syncs code, `docker compose pull && up` | Yes |
| 5 | Verify | Verifier agent checks container health, OCP HTTP 200, SIP OPTIONS 200 OK, backup metrics | Yes -> automatic rollback |

### Rollback Behavior

Before deploy (Gate 4), the pipeline captures current running image digests on the target host into `.deploy-rollback/<run-id>-digests.txt`. If Gate 5 fails, the pipeline automatically re-tags the previous digests and restarts containers.

### CLI Flags

- `./orchestrate-deploy.sh` — full pipeline
- `./orchestrate-deploy.sh --dry-run` — validates all gates without mutating state
- `./orchestrate-deploy.sh --live-test` — runs post-deploy verification only

### GitHub Actions Deploy Workflow

`.github/workflows/deploy.yml` provides a `workflow_dispatch` trigger with:
- `deploy_target`: production / staging
- `dry_run`: boolean — validates gates without mutations
- `force_deploy`: boolean — bypasses HIGH risk impact gate

Jobs: `preflight` -> `impact` -> `build` -> `push` -> `deploy` -> `verify`

### Ansible Playbooks

- `deploy/ansible/playbook-deploy.yml` — Docker, Compose, and stack deployment on target host
- `deploy/ansible/playbook-hardening.yml` — OS-level hardening (UFW, fail2ban, sysctl)

### Constraints

- Docker-first: only container images are deployed; no bare-metal package installation on target
- PostgreSQL-only: no MySQL/MariaDB paths in deploy logic
- OpenSIPS 3.6 LTS: config syntax gate rejects forbidden modules (e.g., `sanity`)
- Secrets: no secrets committed; `.env` and `secrets/` are gitignored
- Git mutations: pipeline does not auto-commit; SHA is recorded for audit only

---

## 13. Agent Orchestration Notes

This repository uses an extensive agent orchestration setup. As an AI coding agent, you should be aware of the following:

- **Ruflo (Claude Flow) V3** is configured via `.mcp.json` and `.claude-flow/config.yaml`.
  - Topology: `hierarchical-mesh`
  - Max agents: `15`
  - Memory backend: `hybrid` (HNSW + knowledge graph)
  - Auto-start: `false`
- **Claude Code hooks** are defined in `.claude/settings.json`:
  - Pre/post edit hooks
  - Pre/post bash hooks
  - Session start/end hooks
  - Subagent start/stop hooks
  - These invoke `.claude/helpers/hook-handler.cjs` and related scripts.
- **Agent definitions** live in `.claude/agents/` (60+ role-specific markdown files).
- **Slash commands** live in `.claude/commands/`.
- **Helpers/scripts** live in `.claude/helpers/`.

When editing files in this repo, the hooks may trigger automatically. If you encounter unexpected behavior, check `.claude/settings.json` for the relevant hook mappings.

---

## 14. Useful Quick References

### Canonical Spec Sections

| Topic | File | Section |
|---|---|---|
| Architecture rules | `docs/TSiSIP-CANONICAL-SPEC.md` | Sections 2, 4, 5 |
| OpenSIPS modules | `docs/TSiSIP-CANONICAL-SPEC.md` | Section 6 |
| OpenSIPS init params | `docs/TSiSIP-CANONICAL-SPEC.md` | Section 7 |
| Routing logic skeleton | `docs/TSiSIP-CANONICAL-SPEC.md` | Section 8 |
| Auth contract | `docs/TSiSIP-CANONICAL-SPEC.md` | Section 9 |
| Header routing contract | `docs/TSiSIP-CANONICAL-SPEC.md` | Section 10 |
| RTP relay contract | `docs/TSiSIP-CANONICAL-SPEC.md` | Section 11 |
| PostgreSQL schema | `docs/TSiSIP-CANONICAL-SPEC.md` | Section 12 |
| Dockerfile baseline | `docs/TSiSIP-CANONICAL-SPEC.md` | Section 13 |
| Compose contract | `docs/TSiSIP-CANONICAL-SPEC.md` | Section 14 |
| Security model | `docs/TSiSIP-CANONICAL-SPEC.md` | Section 17 |
| Rejected patterns | `docs/TSiSIP-CANONICAL-SPEC.md` | Section 18.1 |
| Acceptance criteria | `docs/TSiSIP-CANONICAL-SPEC.md` | Section 21 |

### Official OpenSIPS Validation Sources

- `https://www.opensips.org/Documentation/Manuals`
- `https://www.opensips.org/Documentation/Manual-3-6`
- `https://www.opensips.org/Documentation/Modules-3-6`
- `https://opensips.org/docs/modules/3.6.x/<module>.html`

### Relevant RFCs

| RFC | Role |
|---|---|
| RFC 3261 | SIP core, proxy behavior, transactions, dialogs, Digest |
| RFC 3263 | SIP server location |
| RFC 8760 | SIP Digest SHA-256 and SHA-512/256 |
| RFC 3264 | SDP offer/answer |
| RFC 8866 | SDP |
| RFC 3550 | RTP/RTCP |
| RFC 3711 | SRTP |

---

*Last updated: 2026-05-24. This file must be updated whenever new build tooling, manifests, or canonical architecture decisions are committed.*

<!-- SPECKIT START -->
For additional context about technologies to be used, project structure,
shell commands, and other important information, read the current plan
<!-- SPECKIT END -->

---

## Brownfield Remediation Status

Last scan: 2026-05-20. Reports: `reports/brownfield-scan-2026-05-20.md`, `reports/brownfield-scan-2026-05-20-post-remediation.md`.

| Cycle | Findings | Status | Commit |
|---|---|---|---|
| Ciclo 1 | B1 (CRITICAL), B2 (HIGH), B3 (HIGH), B4 (HIGH) | **COMPLETE** | `da0964e` |
| Ciclo 2 | B5 (MEDIUM), B6 (MEDIUM), B7 (MEDIUM) | **COMPLETE** | `7b91a1a` |
| Ciclo 3 | B8 (MEDIUM), B9 (MEDIUM) | **COMPLETE** | `4147c14` |
| Ciclo 4 | B10 (LOW), B11 (LOW) | **COMPLETE** | `b7296be` |
| Ciclo 5 | B12 (LOW), B13 (LOW) | **COMPLETE** | `96cfadd` |

### Resolution Summary

| ID | Severity | Resolution | Evidence |
|---|---|---|---|
| B1 | CRITICAL | Removed plaintext password comment from seed data; retained bcrypt hash with `force_password_change` | `evidence/remediation/ciclo-1/` |
| B2 | HIGH | RTPengine `--listen-ng` no longer falls back to `0.0.0.0`; binds only to `${RTPENGINE_INTERNAL_IP}:22222` | `evidence/remediation/ciclo-1/` |
| B3 | HIGH | **FALSE POSITIVE** — all 4 env vars present in `.env.example` | `evidence/remediation/ciclo-1/` |
| B4 | HIGH | **FALSE POSITIVE** — auth contract already uses `proxy_authorize` for non-REGISTER | `evidence/remediation/ciclo-1/` |
| B5 | MEDIUM | **ACCEPTABLE** — `sed` stub in `orchestrate-deploy.sh` is syntax-check only, not runtime | `evidence/remediation/ciclo-2/b5-acceptable.txt` |
| B6 | MEDIUM | E2E test host parameterized via `TARGET_HOST` env var | `evidence/remediation/ciclo-2/` |
| B7 | MEDIUM | Documented OCP manual container workaround in OPERATOR-RUNBOOK | `evidence/remediation/ciclo-2/` |
| B8 | MEDIUM | Removed `ALLOW_UNENCRYPTED_BACKUPS` opt-out; encryption now mandatory | `evidence/remediation/ciclo-3/` |
| B9 | MEDIUM | **FALSE POSITIVE** — observability services are active in prod/dev; intentionally absent in VPS-lite | `evidence/remediation/ciclo-3/` |
| B10 | LOW | Added explicit `OPENSIPS_HOST: 127.0.0.1` to all production compose files | `evidence/remediation/ciclo-4/` |
| B11 | LOW | Changed cert-gen example IP from RFC1918 to TEST-NET-1 (`192.0.2.1`) | `evidence/remediation/ciclo-4/` |
| B12 | LOW | Rephrased "sanity check" to "validation check" in comment | `evidence/remediation/ciclo-5/` |
| B13 | LOW | Removed `latest` fallback from production compose files; `.env.example` now documents pinning | `evidence/remediation/ciclo-5/` |
| B14 | MEDIUM | Fixed residual `ALLOW_UNENCRYPTED_BACKUPS` reference in `docker/backup/backup.sh` (Feature 013) | `evidence/remediation/feature-013/` |
| B15 | MEDIUM | Added healthchecks to `backup` and `anomaly_detector` services across all compose profiles | `evidence/remediation/feature-013/` |
| B16 | LOW | Documented CI `:latest` tag policy in deploy workflow; tagged releases preferred | `evidence/remediation/feature-013/` |

> **16/16 findings addressed**. Zero outstanding brownfield items.

## Memorylint Status

Last scan: 2026-05-20. Report: `reports/memorylint-audit-2026-05-20.md`.

| ID | Service | Finding | Status | Commit |
|---|---|---|---|---|
| M1 | OpenSIPS | `pkg_mem_size` may be tight under high load | LOW — monitor | — |
| M2 | PostgreSQL | `work_mem` × `max_connections` approaches reservation | LOW — monitor | — |
| M3 | VPS Backup | `mem_limit` insufficient for large dumps | **FIXED** | `9180cad` |
| M4 | Prometheus | Retention policy unbounded growth risk | LOW — document | — |
| M5 | OpenSIPS | `shm_mem_size` not explicit in `.cfg.tpl` | LOW — add for clarity | — |

> All critical and high memorylint findings resolved. See `reports/remediation-summary.md` for historical M1-M10 resolution.

## 15. Spec Kit Memory Hub

TSiSIP uses the **Spec Kit Memory Hub** (`memory-md` extension v0.8.5) to maintain durable, queryable agent memory across sessions.

### Memory Files

| File | Purpose | Access Level |
|---|---|---|
| `docs/memory/INDEX.md` | Navigation hub for all memory files | Public |
| `docs/memory/PROJECT_CONTEXT.md` | Product identity, domain language, constraints | Public |
| `docs/memory/ARCHITECTURE.md` | System shape, module boundaries, integrations | Public |
| `docs/memory/DECISIONS.md` | Architecture Decision Records (ADRs) | Public |
| `docs/memory/BUGS.md` | Known bugs, root causes, resolutions | Public |
| `docs/memory/WORKLOG.md` | Session history and completed work | Public |
| `docs/memory/memory-synthesis.md` | Condensed context for agent planning | Public |

### Commands

```bash
# Index all durable memory files into SQLite cache
node .specify/extensions/memory-md/dist/bin/speckit-memory.js index-memory

# Search indexed memory
node .specify/extensions/memory-md/dist/bin/speckit-memory.js search-memory "<query>"

# Generate synthesis for a feature
node .specify/extensions/memory-md/dist/bin/speckit-memory.js synthesize --feature specs/<feature-dir>

# Audit cache integrity
node .specify/extensions/memory-md/dist/bin/speckit-memory.js audit-memory

# Compare token usage
node .specify/extensions/memory-md/dist/bin/speckit-memory.js token-report --feature specs/<feature-dir>
```

### Security Rules

- **Never** commit secrets, API keys, or PII to memory files.
- `secrets/`, `.env*`, and `db/init/03-seed-data.sql` are excluded from indexing.
- All memory captures require explicit human approval.
- See `docs/security/019-agent-memory-governance.md` for full governance framework.

### Configuration

- Config: `.specify/extensions/memory-md/config.yml`
- Optimizer: Disabled (local-only, no remote embedding API)
- SQLite cache: `.spec-kit-memory/memory.sqlite` (gitignored)

## 16. OCP Administrative Tools

The TSiSIP Operator Control Panel (OCP) provides web-based administrative tools for OpenSIPS runtime management. Feature 020 closed six critical tool gaps identified in `docs/OCP-CROSS-ANALYSIS.md`.

### Available Tools

| Tool | File | Role | Operations |
|---|---|---|---|
| Dialog Viewer | `web/dialog.php` | devops+ | Read-only view of active SIP dialogs (call-id, from/to, duration, state) |
| MI Commands | `web/mi-commands.php` | devops+ / admin | Execute whitelisted MI commands via OpenSIPS MI HTTP interface |
| Statistics Monitor | `web/statistics.php` | devops+ | D3.js dashboard with 6+ key metrics; 30-second auto-refresh |
| Dialplan Manager | `web/dialplan.php` | devops+ | Full CRUD on PostgreSQL `dialplan` table |
| Domains Manager | `web/domains.php` | devops+ | Full CRUD on PostgreSQL `domain` table |
| TLS Management | `web/tls-management.php` | devops view / admin reload | View TLS certificates; trigger `tls_reload` (admin only) |

### Security Patterns

All OCP tools follow the same security baseline:
- `requireAuth()` → `checkPasswordChange()` → `requireRole('devops')` minimum
- CSRF token validation on every mutating POST (`validateCsrfToken()`)
- PDO prepared statements for all database queries
- `htmlspecialchars()` on all rendered output
- Audit logging via `logAuditEvent()` to `auth_audit_log` table
- MI command whitelist hardcoded in PHP — non-whitelisted commands rejected with HTTP 403

### References

- Security assessment: `docs/security/020-ocp-gap-closure-security-assessment.md`
- Threat model: `docs/security/020-ocp-gap-closure-threat-model.md`
- Database schema: `db/init/04-ocp-tools-schema.sql`
- Feature specification: `specs/020-ocp-critical-tool-gap-closure/spec.md`

---

## Recent Changes

- 2026-05-24 — Feature 020: OCP Critical Tool Gap Closure — Added 6 admin tools (Dialog, MI Commands, Statistics, Dialplan, Domains, TLS Management), MI HTTP integration, i18n for 16 modules, security headers, session hardening
- 2026-05-24 — Feature 023: Subscriber CRUD Refactor — ARCH-PRE-001 resolved (subscriber mutations routed through admin_api microservice)
- 2026-05-24 — OpenSIPS canon-drift remediation — reply route renamed to REPLY_MANAGE, failure route renamed to FAILURE_MANAGE, ICE=remove added to all rtpengine_offer() calls, persistent_state=1 added to dispatcher
- 2026-05-24 — CI scan passes — test resilience improved, audit accuracy fixed
- 2026-05-19 — Production deploy to VPS tsiapp.io — full stack operational

## Known Issues and Gotchas

### ⚠️ D3.js CDN Dependency in Air-Gapped Environments
**Issue:** statistics.php loads D3.js from CDN; air-gapped deployments will show graceful degradation message but no charts.
**Root Cause:** No local D3.js copy bundled in OCP image.
**Prevention Rule:** For air-gapped deployments, vendor D3.js into web/tsisip/js/ and update script src.

### ⚠️ MI HTTP Timeout Cascading
**Issue:** If the MI HTTP endpoint becomes unreachable, statistics.php and mi-commands.php both enter error states simultaneously. No circuit breaker exists.
**Root Cause:** Each page independently polls MI HTTP with timeout but no shared circuit breaker state.
**Prevention Rule:** Consider adding a circuit breaker or shared health state for MI HTTP-dependent pages.

### ⚠️ Audit Log Table Growth
**Issue:** All 6 new tools plus remediation logging increase auth_audit_log write volume. No retention or partitioning strategy is documented for high-volume audit scenarios.
**Root Cause:** Audit logging was added without retention policy.
**Prevention Rule:** Define audit log retention (e.g., 90 days) and implement automated purge or partitioning.

### ⚠️ Statistics Auto-Refresh Load
**Issue:** statistics.php 30-second auto-refresh generates continuous MI HTTP load (2 req/min per active tab).
**Root Cause:** Fixed-interval polling without backoff.
**Prevention Rule:** Monitor MI HTTP endpoint load; consider increasing interval or adding user-toggle for refresh.

---

<!-- gitnexus:start -->
# GitNexus — Code Intelligence

This project is indexed by GitNexus as **TSiSIP** (9930 symbols, 10912 relationships, 19 execution flows). Use the GitNexus MCP tools to understand code, assess impact, and navigate safely.

> If any GitNexus tool warns the index is stale, run `npx gitnexus analyze` in terminal first.

## Always Do

- **MUST run impact analysis before editing any symbol.** Before modifying a function, class, or method, run `gitnexus_impact({target: "symbolName", direction: "upstream"})` and report the blast radius (direct callers, affected processes, risk level) to the user.
- **MUST run `gitnexus_detect_changes()` before committing** to verify your changes only affect expected symbols and execution flows.
- **MUST warn the user** if impact analysis returns HIGH or CRITICAL risk before proceeding with edits.
- When exploring unfamiliar code, use `gitnexus_query({query: "concept"})` to find execution flows instead of grepping. It returns process-grouped results ranked by relevance.
- When you need full context on a specific symbol — callers, callees, which execution flows it participates in — use `gitnexus_context({name: "symbolName"})`.

## Never Do

- NEVER edit a function, class, or method without first running `gitnexus_impact` on it.
- NEVER ignore HIGH or CRITICAL risk warnings from impact analysis.
- NEVER rename symbols with find-and-replace — use `gitnexus_rename` which understands the call graph.
- NEVER commit changes without running `gitnexus_detect_changes()` to check affected scope.

## Resources

| Resource | Use for |
|----------|---------|
| `gitnexus://repo/TSiSIP/context` | Codebase overview, check index freshness |
| `gitnexus://repo/TSiSIP/clusters` | All functional areas |
| `gitnexus://repo/TSiSIP/processes` | All execution flows |
| `gitnexus://repo/TSiSIP/process/{name}` | Step-by-step execution trace |

## CLI

| Task | Read this skill file |
|------|---------------------|
| Understand architecture / "How does X work?" | `.claude/skills/gitnexus/gitnexus-exploring/SKILL.md` |
| Blast radius / "What breaks if I change X?" | `.claude/skills/gitnexus/gitnexus-impact-analysis/SKILL.md` |
| Trace bugs / "Why is X failing?" | `.claude/skills/gitnexus/gitnexus-debugging/SKILL.md` |
| Rename / extract / split / refactor | `.claude/skills/gitnexus/gitnexus-refactoring/SKILL.md` |
| Tools, resources, schema reference | `.claude/skills/gitnexus/gitnexus-guide/SKILL.md` |
| Index, status, clean, wiki CLI commands | `.claude/skills/gitnexus/gitnexus-cli/SKILL.md` |

<!-- gitnexus:end -->
