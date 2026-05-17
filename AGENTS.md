# TSiSIP — Agent Onboarding Guide

> Read this first. This file is the single source of context for AI coding agents working on the TSiSIP repository.

---

## 1. Project Overview

**TSiSIP** is a Docker-image-first SIP edge-proxy platform built on **OpenSIPS 3.6 LTS**. Its sole purpose is to act as the only public SIP signaling entry point and security boundary for a private, multi-tenant Asterisk PBX backend cluster.

In plain terms:
- OpenSIPS sits on the public internet and handles all SIP traffic (REGISTER, INVITE, etc.).
- It authenticates every request against PostgreSQL-backed subscriber credentials.
- It dynamically routes authenticated traffic to the correct Asterisk backend using tenant-scoped metadata.
- RTPengine relays all media (voice/video RTP) so backend PBX IP addresses never leak to the public internet.
- Asterisk and PostgreSQL live on isolated Docker networks with zero host-published ports.

---

## 2. Repository State

- **Documentation-first greenfield with foundation committed**: Dockerfile, Docker Compose, OpenSIPS config template, PostgreSQL schema, and container entrypoint are committed. No application source code, language manifest, CI workflow, or test runner exists yet.
- Git is initialized but has **no commits**.
- The repository currently contains:
  - Canonical architecture specification (`docs/`)
  - Agent orchestration configuration (`.claude/`, `.claude-flow/`, `.swarm/`, `.sisyphus/`)
  - GitHub-level guidance (`.github/copilot-instructions.md`)
  - MCP configuration (`.mcp.json`)
  - Docker build: `Dockerfile`, `docker-compose.yml`, `.dockerignore`
  - OpenSIPS config: `opensips/opensips.cfg.tpl`
  - Container entrypoint: `docker/entrypoint.sh`
  - PostgreSQL schema: `db/init/01-stock-opensips-schema.sql`, `db/init/02-tsisip-extensions.sql`, `db/init/03-seed-data.sql`
  - Secrets directory: `secrets/` (`.gitignore` protected)
  - Environment template: `.env.example`
  - This file (`AGENTS.md`)

---

## 3. Technology Stack

| Layer | Technology | Role |
|---|---|---|
| SIP Proxy | OpenSIPS 3.6 LTS | Public signaling edge; auth, routing, topology hiding |
| Database | PostgreSQL | Subscriber auth, tenant metadata, routing rules, dispatcher state |
| Media Relay | RTPengine | Public RTP relay; SDP rewriting |
| PBX Backend | Asterisk | Private voice/video application servers |
| Packaging | Docker image + Docker Compose | Canonical runtime delivery |
| Public Ports | `5060/udp`, `5060/tcp` | SIP signaling (OpenSIPS only) |
| Public Ports | `10000-20000/udp` | RTP media (RTPengine only) |

**Non-negotiable rules:**
- OpenSIPS 3.6 LTS is the **only** SIP proxy baseline. Changing it requires a documented architecture decision.
- PostgreSQL is the **only** database. Do not introduce MySQL, MariaDB, or `db_mysql` variants.
- OpenSIPS must be delivered through a **project-owned Docker image**, never bare-metal or VM-first install instructions.

---

## 4. System Architecture

```text
Internet / SIP clients
        |
        | 5060/udp, 5060/tcp
        v
+-----------------------------+
| OpenSIPS Docker image       |
| TSiSIP edge proxy           |
| - auth                      |
| - header routing            |
| - topology hiding           |
| - dispatcher failover       |
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
+-----------------------------+

OpenSIPS
        |
        | internal DB network
        v
+-----------------------------+
| PostgreSQL                  |
| auth + routing metadata     |
+-----------------------------+
```

### Docker Network Model

| Network | Members | External Access | Purpose |
|---|---|---:|---|
| `sip_edge` | OpenSIPS, RTPengine | Yes | Public SIP and RTP ingress |
| `sip_internal` | OpenSIPS, RTPengine, Asterisk | No | Internal SIP forwarding and RTPengine control |
| `db_internal` | OpenSIPS, PostgreSQL | No | Database access only |

**Published ports:**
- OpenSIPS: `5060/udp`, `5060/tcp`
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
│   └── TSiSIP-AGENT-ORCHESTRATION-PLAYBOOK.md
│                                       # Mandatory multi-agent doc workflow
├── .github/
│   └── copilot-instructions.md         # Repo-specific implementation constraints
├── .claude/                            # Claude Code agent definitions & helpers
│   ├── agents/                         # 60+ role-specific agent prompts
│   ├── commands/                       # Slash-command definitions
│   ├── helpers/                        # Shell/JS hooks (pre-commit, post-edit, etc.)
│   ├── skills/                         # Project-local skill definitions
│   └── settings.json                   # Claude Code hooks, permissions, env
├── .claude-flow/                       # Ruflo (Claude Flow) V3 orchestration
│   ├── config.yaml                     # Runtime config (topology, memory, neural)
│   ├── CAPABILITIES.md                 # Full capabilities reference
│   ├── data/                           # Memory storage
│   ├── logs/                           # Operation logs
│   ├── metrics/                        # Codebase maps, performance, security audits
│   ├── sessions/                       # Session persistence
│   └── swarm/                          # Swarm state
├── .swarm/
│   └── state.json                      # Swarm runtime state
├── .sisyphus/
│   └── run-continuation/               # Run continuation metadata
├── opensips/                           # OpenSIPS configuration template
│   └── opensips.cfg.tpl
├── docker/                             # Container support files
│   ├── entrypoint.sh                   # Runtime config renderer (envsubst + secrets)
│   ├── rtpengine/
│   │   └── Dockerfile                  # RTPengine container image
│   └── asterisk/
│       └── Dockerfile                  # Asterisk PBX container image
├── db/init/                            # PostgreSQL initialization scripts
│   ├── 01-stock-opensips-schema.sql    # Baseline subscriber + dispatcher + version
│   ├── 02-tsisip-extensions.sql        # Tenants, routing rules, audit log
│   └── 03-seed-data.sql                # Dev tenant, dispatcher pool, HA1 subscriber
├── secrets/                            # Runtime secrets (gitignored)
├── .mcp.json                           # MCP server config (Ruflo)
├── .env.example                        # Environment variable template
├── .dockerignore                       # Docker build context exclusions
├── Dockerfile                          # OpenSIPS 3.6 LTS source-build image
├── docker-compose.yml                  # Canonical three-network topology
├── CLAUDE.md                           # Generic Claude Code config (Ruflo rules)
├── AGENTS.md                           # This file
└── .gitignore                          # Excludes secrets/, .env, .env.*, *.log, *.pid
```

> **Note:** `.claude/`, `.claude-flow/`, `.swarm/`, and `.sisyphus/` are **agent orchestration state/config**, not TSiSIP application tooling. Do not confuse them with application source or deployment artifacts.

---

## 6. Build and Test Commands

**Canonical build and validation commands (committed):**

```bash
# Build the OpenSIPS image from source
docker build -t tsisip-opensips:latest .

# Build the RTPengine image from source
docker build -t tsisip/rtpengine:latest -f docker/rtpengine/Dockerfile .

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
  tsisip-opensips:latest \
  /entrypoint.sh /usr/local/sbin/opensips -c -f /etc/opensips/opensips.cfg

# Start the database and verify schema initialization
docker compose up -d postgres
docker compose exec postgres psql -U opensips -d opensips -c "\dt"

# Build all services
docker compose build

# Start the full stack
docker compose up -d

# Runtime SIP validation (T4.4 — OPTIONS 200 OK)
docker run --rm --network tsisip_sip_edge alpine \
  sh -c "apk add --no-cache sipsak >/dev/null 2>&1 && \
         sipsak -s sip:opensips:5060 -vv"
# Expected: SIP/2.0 200 OK with Server: OpenSIPS (3.6.5 ...)

# Runtime SIP validation (T4.5 — INVITE 407 Proxy-Authenticate)
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
# Expected: SIP/2.0 407 Proxy Authentication Required with Proxy-Authenticate headers
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
```

**Additional repository checks:**

```bash
# List all tracked files (excluding .git and node_modules)
rg --files -uuu -g '!**/.git/**' -g '!**/node_modules/**'

# Search for canonical keywords across documentation
rg -n "OpenSIPS|PostgreSQL|RTPengine|Asterisk|db_postgres|sanity" docs .github AGENTS.md CLAUDE.md .mcp.json
```

> `CLAUDE.md` contains a generic `npm run build && npm test` example. **Ignore it for TSiSIP** until a `package.json` or equivalent manifest is committed.

---

## 7. Code Style and Naming Conventions

### Names to Preserve Exactly
- `TSiSIP` (capitalization)
- `OpenSIPS 3.6 LTS`
- `PostgreSQL`
- `RTPengine`
- `Asterisk`

### Database & Service Naming
- Use **lowercase snake_case** for:
  - Database identifiers (tables, columns, indexes)
  - Docker service names
  - Docker network names
- Examples: `sip_edge`, `sip_internal`, `db_internal`, `header_routing_rules`, `pbx_backends`, `auth_audit_log`

### OpenSIPS Config Conventions
- Use integer algorithm arguments for dispatcher: `ds_select_dst($var(setid), 4, "f")`
- Use `topology_hiding("C")` as the canonical baseline
- Use explicit `rtpengine_offer()`, `rtpengine_answer()`, and `rtpengine_delete()` — not `rtpengine_manage()` as baseline
- Use `mf_process_maxfwd_header(70)` (RFC 3261 default)

### Module References
- Only reference modules documented for **OpenSIPS 3.6 LTS**.
- `sanity` is **forbidden** — it is not in the OpenSIPS 3.6 module documentation.
- Do not add Kamailio-only modules or functions.

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

### Docker Runtime Hardening
- Drop all capabilities except those required (`NET_BIND_SERVICE`, `SETUID`, `SETGID`).
- Use `security_opt: ["no-new-privileges:true"]`.

---

## 10. Rejected Patterns

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

## 11. Agent Orchestration Notes

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

## 12. Useful Quick References

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

*Last updated: 2026-05-17. This file must be updated whenever new build tooling, manifests, or canonical architecture decisions are committed.*
