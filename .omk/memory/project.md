# Project Memory

Project-local graph memory is the source of truth; this file is a human-readable mirror.

## Runtime Surfaces

- Follow AGENTS.md and .kimi/AGENTS.md for active agent policy.
- Use chat-agent-harness.json when present for MCP/skills/hooks inventory, worker limits, authority boundaries, and gates.
- Keep .omk/memory mirrors free of secrets and private user data.

## Canonical Facts

| Fact | Value | Confidence | Source | Refresh Trigger |
|------|-------|------------|--------|-----------------|
| SIP Engine | OpenSIPS 3.6 LTS | high | docs/TSiSIP-CANONICAL-SPEC.md section 2 | ADR required to change |
| Database | PostgreSQL only | high | docs/TSiSIP-CANONICAL-SPEC.md section 2 | ADR required to change |
| Media Relay | RTPengine | high | docs/TSiSIP-CANONICAL-SPEC.md section 3 | ADR required to change |
| PBX Backend | Asterisk (isolated) | high | docs/TSiSIP-CANONICAL-SPEC.md section 3 | ADR required to change |
| Packaging | Docker image + Compose | high | docs/TSiSIP-CANONICAL-SPEC.md section 3 | Never (foundational) |
| Public SIP Ports | 5060/udp, 5060/tcp | high | docs/TSiSIP-CANONICAL-SPEC.md section 3 | Network redesign |
| RTP Port Range | 10000-20000/udp | high | docs/TSiSIP-CANONICAL-SPEC.md section 3 | Media capacity change |
| Forbidden Modules | sanity, db_mysql | high | docs/TSiSIP-CANONICAL-SPEC.md sections 6, 18.1 | Spec version bump |
| Auth Hash | HA1 only (calculate_ha1=0) | high | docs/TSiSIP-CANONICAL-SPEC.md sections 7, 9 | Security audit |
| Topology Hiding | topology_hiding("C") | high | docs/TSiSIP-CANONICAL-SPEC.md section 8 | Spec version bump |
| Dispatcher Algo | Integer 4 with "f" | high | docs/TSiSIP-CANONICAL-SPEC.md section 8 | Routing redesign |
| Max-Forwards | 70 (RFC 3261) | high | docs/TSiSIP-CANONICAL-SPEC.md sections 7, 8 | Spec version bump |
| TLS Client Certs | require_cert=1 (mandatory) | high | docs/TSiSIP-CANONICAL-SPEC.md section 17 | Security audit |
| TLS Ciphers | ECDHE+AESGCM:ECDHE+CHACHA20:!aNULL:!MD5:!DSS | high | docs/TSiSIP-CANONICAL-SPEC.md section 17 | Security audit |
| TLS Methods | TLSv1.2:TLSv1.3 | high | docs/TSiSIP-CANONICAL-SPEC.md section 17 | Security audit |
| Graceful Degradation | 488 (RTPengine), 480 (PostgreSQL/dispatcher) | high | docs/TSiSIP-CANONICAL-SPEC.md section 8 | Spec version bump |
| Network sip_edge | OpenSIPS, RTPengine | high | docs/TSiSIP-CANONICAL-SPEC.md section 5 | Network redesign |
| Network sip_internal | OpenSIPS, RTPengine, Asterisk | high | docs/TSiSIP-CANONICAL-SPEC.md section 5 | Network redesign |
| Network db_internal | OpenSIPS, PostgreSQL | high | docs/TSiSIP-CANONICAL-SPEC.md section 5 | Network redesign |
| No host ports | Asterisk, PostgreSQL | high | docs/TSiSIP-CANONICAL-SPEC.md section 5 | Security incident |
| RTPengine control | Bind to sip_internal only | high | docs/TSiSIP-CANONICAL-SPEC.md section 5 | Security incident |

## Module Baseline

Required: db_postgres, sqlops, sl, tm, rr, maxfwd, sipmsgops, signaling, auth, auth_db, dialog, dispatcher, rtpengine, topology_hiding, permissions.

Optional: drouting (only when LCR needed).

Forbidden: sanity (not in 3.6 docs), rtpproxy (non-canonical unless ADR).

## Authentication Contract

- calculate_ha1 = 0 (precomputed HA1)
- password_column = "ha1"
- Hash columns: ha1, ha1_sha256, ha1_sha512t256
- Load credentials into AVPs: tenant_id, route_setid
- Authenticate ALL non-OPTIONS untrusted requests

## Header Sanitization Contract

Remove before routing: P-Asserted-Identity, P-Preferred-Identity, X-Tenant-ID, X-Backend-ID, X-Route-Override, X-Routing-Key.

Strip before forwarding: Authorization, Proxy-Authorization.

## Docker Hardening

- Drop all capabilities except NET_BIND_SERVICE, SETUID, SETGID
- security_opt: ["no-new-privileges:true"]
- Secrets injected via Docker secrets or env-templated configs

## Wiki System

- Renderer: web/wiki.php (regex-based markdown, no external deps)
- Source: docs/wiki/ (10 pages: README, system-overview, devops-sip, administrators, operators-users, developers, security-compliance, runbooks-troubleshooting, dentists, assistants)
- Role-based navigation: admin, devops, dentist, assistant, user, readonly
- Dashboard: web/dashboard.php (role-aware landing)
- Deployed via Docker image with docs/wiki/ copied to /var/www/docs/wiki/
- Added: 2026-05-19

## Feature Spec Status (2026-05-19)

| Feature | Status | Complete | Pending |
|---------|--------|----------|---------|
| 001-opensips-docker-edge-proxy | Completed | 19 | 0 |
| 002-tsisip-ocp-rebrand | Implemented | 30 | 0 |
| 003-prometheus-grafana-observability | Partial | 15 | 0 |
| 004-health-checks-autohealing | Partial | 17 | 0 |
| 005-postgresql-backup-restore | Implemented | 18 | 2 |
| 006-rate-limiting-ddos-protection | Partial | 7 | 9 |
| 007-tls-srtp-encryption | Partial | 14 | 5 |
| 008-devsecops-deployment | Live/Pending | 13 | 0 |

## Quality Gates (2026-05-19)

- Consolidated report: reports/CONSOLIDATED-QUALITY-GATE-2026-05-19.md
- Validation report: reports/VALIDATION-REPORT-2026-05-19.md
- Quality gates report: reports/QUALITY-GATES-2026-05-19.md
- Overall: PASS
- Critical blockers: 0
- Warnings: 0
- Resolved: spec drift 004 (488/480 routes), spec drift 007 (TLS certs/ciphers/method), unpinned base images (6 SHA256 pins), compose GHCR `:latest` tags replaced with `${TSISIP_IMAGE_TAG:-latest}`, memory limits on docker-compose.prod.yml (12 services)

## Canonical Spec Version

- Current: 1.1 (updated 2026-05-19)
- Sections added: 19 (Wiki System)
- Contradictions fixed: 9
- Cross-references added: 6
