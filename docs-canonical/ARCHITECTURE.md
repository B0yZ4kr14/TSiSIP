# Architecture

<!-- docguard:version 0.2.0 -->
<!-- docguard:status active -->
<!-- docguard:last-reviewed 2026-05-26 -->
<!-- docguard:generated false -->
<!-- docguard:standards arc42, C4 -->

> **Canonical Architecture Document for TSiSIP.**
> Follows [arc42](https://arc42.org) structure and [C4 Model](https://c4model.com) diagrams.

| Metadata | Value |
|----------|-------|
| **Status** | ![Status](https://img.shields.io/badge/status-active-brightgreen) |
| **Version** | `0.2.0` |
| **Last Updated** | 2026-05-26 |
| **Project Size** | 83 files, ~12K lines |

---

## System Overview

TSiSIP is a Docker-first SIP edge-proxy platform built on OpenSIPS 3.6 LTS. It acts as the sole public SIP signaling entry point and security boundary for a private, multi-tenant Asterisk PBX backend cluster. All SIP traffic (REGISTER, INVITE, etc.) is authenticated against PostgreSQL-backed subscriber credentials and dynamically routed to the correct Asterisk backend using tenant-scoped metadata. RTPengine relays all media so backend PBX IP addresses never leak to the public internet.

## Component Map

| Directory / Component | Responsibility | Docs Reference |
|-----------|---------|----------------|
| `opensips/` | OpenSIPS 3.6 LTS config templates & routing logic | `docs/TSiSIP-CANONICAL-SPEC.md` §8 |
| `db/init/` | PostgreSQL schema, extensions, triggers & seed data | `docs/TSiSIP-CANONICAL-SPEC.md` §12 |
| `docker/` | Container images (OpenSIPS, RTPengine, Asterisk, OCP) & entrypoints | `Dockerfile`, `docker-compose.vps.yml` |
| `web/` | OCP admin panel (PHP 8.2, D3.js, jQuery, RBAC) | `docs/OCP-CROSS-ANALYSIS.md` |
| `tests/` | Integration tests, VPS stabilization tests, JS audits, performance | `tests/integration/`, `tests/vps-stabilization/` |
| `scripts/` | Build & deployment automation | `scripts/build-ocp-theme.sh`, `scripts/ci-scan.sh` |
| `deploy/` | Ansible playbooks, nginx config, VPS deployment | `deploy/ansible/`, `deploy/nginx/` |
| `reports/` | Brownfield scans, version guard, memorylint, validation | `reports/brownfield-scan-report-*.md` |
| `secrets/` | Runtime secrets (gitignored — never committed) | `.env.example` |
| `specs/` | Feature specifications (001–024) | `docs/aide/roadmap.md` |
| `evidence/` | Brownkit remediation & verification evidence | `evidence/remediation/` |
| `design/` | Logo, palette, typography tokens | `design/palette.md`, `design/typography.md` |
| `ca-offline/` | Offline CA key material (sensitive, restricted) | — |
| `build/` | OCP theme CSS variable generator & asset manifest | `build/generate-css-variables.js` |
| `commands/` | Project-local slash commands | `.github/agents/` |
| `graphify-out/` | Knowledge graph output (graphify tool) | `graphify-out/GRAPH_REPORT.md` |
| `plans/` | Implementation planning artifacts | `plans/` |

---

## 1. Introduction & Goals
<!-- arc42: §1 — Introduction and Goals -->

TSiSIP is a Docker-first SIP edge-proxy platform that acts as the only public SIP signaling entry point and security boundary for a private, multi-tenant Asterisk PBX backend cluster.

### Quality Goals

| Priority | Quality Goal | Scenario |
|----------|-------------|----------|
| 1 | Security | All non-OPTIONS SIP requests authenticated; backend IPs never exposed |
| 2 | Availability | Dispatcher failover with health probes; 99.9% uptime target |
| 3 | Maintainability | Feature specs in `specs/`; canonical docs drive implementation |
| 4 | Observability | Prometheus metrics + Grafana dashboards for all services |
| 5 | Compliance | LGPD-ready audit logging; TLS/SRTP encryption enforced |

## 2. Constraints
<!-- arc42: §2 — Constraints -->

| Type | Constraint | Background |
|------|-----------|------------|
| Technical | OpenSIPS 3.6 LTS only | Canonical baseline; changing requires ADR |
| Technical | PostgreSQL only | MySQL / MariaDB explicitly rejected |
| Technical | Docker image + Compose | Bare-metal / VM-first runtime rejected |
| Infrastructure | Docker on Ubuntu 24.04 | VPS `tsiapp.io` (179.190.15.116) |
| Network | Three-network isolation | `sip_edge` (public), `sip_internal` (private), `db_internal` (private) |
| Security | No host-published ports for Asterisk/PostgreSQL | Topology hiding requirement |
| Security | HA1 precomputed only (`calculate_ha1 = 0`) | Plaintext passwords forbidden |

## 3. Context & Scope
<!-- arc42: §3 — Context and Scope (C4 Level 1: System Context) -->

\`\`\`mermaid
graph TD
    U[SIP Clients / IP Phones] -->|5060/udp, 5060/tcp| O[OpenSIPS]
    O -->|SIP control| A[Asterisk PBX backends]
    O -->|DB queries| P[(PostgreSQL)]
    R[RTP Clients] -->|10000-20000/udp| RT[RTPengine]
    RT -->|Media relay| A
    W[Web Admins] -->|HTTPS 443| OC[OCP Admin Panel]
    OC --> P
\`\`\`

## 4. Solution Strategy
<!-- arc42: §4 — Solution Strategy -->

See `docs-canonical/ADR.md` for architecture decision records.

Key strategies:
- **Docker-first delivery**: Every service runs in a project-owned container image.
- **PostgreSQL-only metadata**: All auth, routing, and tenant data lives in PostgreSQL.
- **Topology hiding**: OpenSIPS strips internal network details; RTPengine handles media.
- **Defense in depth**: Rate limiting (pike, ratelimit), IP ACLs, TLS/SRTP, RBAC on OCP.

## 5. Building Block View
<!-- arc42: §5 — Building Block View (C4 Level 2: Container) -->

| Component | Responsibility | Location | Tests |
|-----------|---------------|----------|-------|
| OpenSIPS | SIP signaling, digest auth, routing, topology hiding | `docker/` | `tests/vps-stabilization/test-vps-sip.sh` |
| RTPengine | Media relay (RTP/RTCP), SDP rewriting | `docker/rtpengine/` | `tests/integration/test-sip-call-flow.sh` |
| PostgreSQL | Subscriber auth, tenant metadata, routing rules, CDR | `db/init/` | `tests/vps-stabilization/test-feature-017.sh` |
| Asterisk | PBX backend (private network only) | `docker/asterisk/` | — |
| OCP | Admin panel (PHP 8.2, D3.js, RBAC) | `web/` | `tests/accessibility-audit.test.js` |
| Prometheus | Metrics collection | `docker/prometheus/` | `tests/integration/test_monitoring.py` |
| Grafana | Visualization & alerting | `docker/grafana/` | — |

\`\`\`mermaid
graph TD
    Client[SIP Client] --> OpenSIPS[OpenSIPS 5060/udp]
    OpenSIPS --> RTPengine[RTPengine 10000-20000/udp]
    OpenSIPS --> PostgreSQL[(PostgreSQL)]
    OpenSIPS --> Asterisk[Asterisk sip_internal]
    RTPengine --> Asterisk
    Admin[Web Admin] --> OCP[OCP PHP Panel 443]
    OCP --> PostgreSQL
    Prometheus --> OpenSIPS
    Prometheus --> RTPengine
    Grafana --> Prometheus
\`\`\`

## 6. Runtime View
<!-- arc42: §6 — Runtime View -->

\`\`\`mermaid
sequenceDiagram
    participant C as SIP Client
    participant O as OpenSIPS
    participant P as PostgreSQL
    participant A as Asterisk
    participant R as RTPengine
    C->>O: REGISTER / INVITE
    O->>P: auth_check (subscriber HA1)
    P-->>O: auth result + tenant metadata
    O->>O: ds_select_dst(setid, 4, "f")
    O->>R: rtpengine_offer()
    O->>A: relay (topology_hiding "C")
    A-->>O: 200 OK
    O->>R: rtpengine_answer()
    O-->>C: 200 OK (public IP only)
\`\`\`

## 7. Deployment View
<!-- arc42: §7 — Deployment View -->

See `docs-canonical/DEPLOYMENT.md` for details.

| Environment | Infrastructure | URL |
|-------------|---------------|-----|
| Development | localhost Docker | http://localhost:8080 (OCP) |
| Staging | VPS `tsiapp.io` | https://staging.tsiapp.io |
| Production | VPS `tsiapp.io` | https://tsiapp.io |

## 8. Crosscutting Concepts
<!-- arc42: §8 — Crosscutting Concepts -->

### Tech Stack

| Category | Technology | Version | License |
|----------|-----------|---------|---------|
| SIP Proxy | OpenSIPS | 3.6.6 LTS | GPL-2.0+ |
| Database | PostgreSQL | 16 | PostgreSQL License |
| Media Relay | RTPengine | latest (source build) | GPL-3.0 |
| PBX Backend | Asterisk | 20 LTS | GPL-2.0 |
| Admin Panel | PHP | 8.2 | PHP License |
| Frontend | D3.js, jQuery, Tailwind-inspired CSS | — | MIT |
| Monitoring | Prometheus, Grafana | — | Apache-2.0 |
| Orchestration | Docker, Docker Compose | 29.5.2 | Apache-2.0 |
| CI/CD | GitHub Actions | — | — |

### Documentation Tools

| Tool | Config | Status |
|------|--------|--------|
| DocGuard | `.docguard.json` (default) | Active |
| Speckit | `.specify/` | Active |

### Layer Boundaries

| Layer | Can Import From | Cannot Import From |
|-------|----------------|-------------------|
| `web/` (OCP) | `db/init/` (schema knowledge), `opensips/` (MI HTTP) | `secrets/`, `ca-offline/` |
| `opensips/` | `db/init/` (schema), `docker/` (envsubst) | `web/`, `ca-offline/` |
| `docker/` | `opensips/`, `db/init/` | `web/`, `secrets/` (runtime mount only) |
| `tests/` | Any layer (read-only) | `secrets/` (except in CI mounts), `ca-offline/` |
| `deploy/` | `docker/`, `opensips/`, `db/init/` | `web/`, `secrets/` |

## 9. Architecture Decisions
<!-- arc42: §9 — Architecture Decisions -->

See `docs-canonical/ADR.md` for the full decision log.

Key decisions (from canonical spec):
- ADR-001: OpenSIPS 3.6 LTS as sole SIP proxy baseline
- ADR-002: PostgreSQL as sole database (reject MySQL/MariaDB)
- ADR-003: Docker image + Compose as canonical runtime
- ADR-004: Precomputed HA1 (`calculate_ha1 = 0`)
- ADR-005: `topology_hiding("C")` as baseline
- ADR-006: Explicit `rtpengine_offer/answer/delete` (not `rtpengine_manage`)
- ADR-007: Three-network Docker isolation (`sip_edge`, `sip_internal`, `db_internal`)

## 10. Quality Requirements
<!-- arc42: §10 — Quality Requirements -->

See `docs-canonical/TEST-SPEC.md` for test requirements and coverage targets.

## 11. Risks & Technical Debt
<!-- arc42: §11 — Risk Assessment and Technical Debt -->

See `DRIFT-LOG.md` for documented deviations from canonical specs.
See `docs-canonical/KNOWN-GOTCHAS.md` for known issues.

Active risks (from latest brownfield scan):
- M2: `sip_trunk_did_mappings.tenant_id` still UUID (should be VARCHAR(36)) — in queue
- L3: VEX/SBOM generation pending — in queue
- Floating `:latest` tags in some Docker images — scheduled for SHA256 pinning (Stage 3)
- Unbounded audit log growth — retention policy exists (`OCP_AUDIT_RETENTION_DAYS=90`) but needs enforcement verification

## 12. Glossary
<!-- arc42: §12 — Glossary -->

| Term | Definition |
|------|-----------|
| CDD | Canonical-Driven Development — documentation as the source of truth |
| Canonical Doc | A specification document that defines system behavior |
| Drift | Conscious deviation from canonical documentation |
| HA1 | MD5(username:realm:password) hash used for SIP Digest authentication |
| MI | Management Interface (OpenSIPS HTTP 8888) |
| OCP | Operator Control Panel — PHP-based admin UI |
| RBAC | Role-Based Access Control (readonly, user, devops, admin) |

---

## Revision History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 0.1.0 | 2026-05-24 | DocGuard Generate | Auto-generated (arc42 + C4 aligned) |
| 0.2.0 | 2026-05-26 | Kimi AI | Filled all placeholders; added System Overview, Component Map, Layer Boundaries, real tech stack, deployment URLs, runtime sequence |

---

## Standards Reference

> **Aligned with**: arc42 Template + C4 Model
>
> **Sections covered**: §1 Introduction, §2 Constraints, §3 Context, §4 Solution Strategy, §5 Building Blocks, §6 Runtime, §7 Deployment, §8 Crosscutting, §9 ADRs, §10 Quality, §11 Risks, §12 Glossary
>
> **Reference**: Starke, G. & Brown, S. "arc42 — Architecture communication template." https://arc42.org | Brown, S. "The C4 Model for visualising software architecture." https://c4model.com
>
> *Standards alignment inspired by RAG-grounded generation (Lopez et al., AITPG, IEEE TSE 2026).*
