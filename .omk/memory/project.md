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

## Feature Spec Status (2026-05-27)

| Feature | Status | Complete | Pending |
|---------|--------|----------|---------|
| 001-opensips-docker-edge-proxy | Completed | 19 | 0 |
| 002-tsisip-ocp-rebrand | Completed | 30 | 0 |
| 003-prometheus-grafana-observability | Completed | 15 | 0 |
| 004-health-checks-autohealing | Completed | 17 | 0 |
| 005-postgresql-backup-restore | Completed | 18 | 2 |
| 006-rate-limiting-ddos-protection | Completed | 16 | 0 |
| 007-tls-srtp-encryption | Completed | 19 | 0 |
| 008-devsecops-deployment | Completed | 13 | 0 |
| 009-vps-deploy-automation | Completed | 18 | 0 |
| 010-ocp-navigation-system-links | Completed | 12 | 0 |
| 011-ocp-forced-password-change | Completed | 8 | 0 |
| 012-ocp-admin-tools-restoration | Completed | 14 | 0 |
| 013-brownfield-follow-up | Completed | 10 | 0 |
| 014-reserved | — | — | — |
| 015-auto-tls-certificate-rotation | Completed | 11 | 0 |
| 016-ocp-audit-log-compliance | Completed | 16 | 0 |
| 017-sip-trunk-provider-integration | Completed | 13 | 0 |
| 018-global-requirement-id-migration | Completed | 9 | 0 |
| 019-spec-kit-memory-hub-integration | Completed | 14 | 0 |
| 020-ocp-critical-tool-gap-closure | Completed | 61 | 0 |
| 021-brownfield-security-production-hardening | Completed | 18 | 0 |
| 022-vps-go-live-stabilization | Completed | 22 | 0 |
| 023-subscriber-crud-refactor | Completed | 15 | 0 |
| 024-brownfield-remediation | Completed | 20 | 0 |
| 025-ocp-dark-mode | Completed | 12 | 0 |
| 026-websocket-realtime | Completed | 10 | 0 |
| 027-mobile-responsive | Completed | 8 | 0 |
| 028-custom-dashboard | Completed | 14 | 0 |
| 029-frontend-refactor | Completed | 18 | 0 |
| 030-ocp-user-management-rbac | Completed | 22 | 0 |
| 031-ocp-rest-api | Completed | 16 | 0 |
| **Total** | **31/31 Complete** | **448** | **2** |

## VPS TSiAPP Canonical Parameters (2026-05-19)

| Parameter | Value | File |
|-----------|-------|------|
| Hostname | `TSiAPP` | docs/VPS-TSiAPP-ACCESS.md |
| Public IP | `179.190.15.116` | docs/VPS-TSiAPP-ACCESS.md |
| Tailscale IP | `100.111.74.69` | docs/VPS-TSiAPP-ACCESS.md |
| SSH Port | `22` | docs/VPS-TSiAPP-ACCESS.md |
| Default User | `tsi` | docs/VPS-TSiAPP-ACCESS.md |
| Root User | `root` | docs/VPS-TSiAPP-ACCESS.md |
| SSH Key | `TSiHomeLab` (Ed25519) | deploy/ssh/TSiAPP-config |
| Deploy Dir | `/opt/tsisip` | deploy/ansible/inventory.yml |
| Registry | `ghcr.io/b0yz4kr14/tsisip/*` | deploy/scripts/orchestrate-deploy.sh |

### SSH Aliases
- `tsia-root` — root@179.190.15.116
- `tsia-tsi` — tsi@179.190.15.116
- `tsia-root-tail` — root@100.111.74.69
- `tsia-tsi-tail` — tsi@100.111.74.69

### Bootstrap Security
- Password auth disabled after key install
- Root login: `prohibit-password`
- Bootstrap passwords stored in operator vault (`~/.tsi-vault`), never in repo

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

## Audit Consolidation (2026-05-26)

**Commit:** `523c5ef`
**Tests:** 44/44 PASS | **DocGuard:** 235/235 A+

### New Reports
- `reports/brownfield-scan-2026-05-26.md` — 0 CRITICAL, 1 HIGH, 5 MEDIUM, 6 LOW
- `reports/version-guard-2026-05-26.md` — 48 passes, 6 HIGH failures
- `reports/memorylint-2026-05-26.md` — 3 CRITICAL, 4 HIGH, 5 MEDIUM, 2 LOW
- `reports/gitnexus-analysis-2026-05-26.md` — 647 files, 7,539 symbols, 15 flows
- `reports/CONSOLIDATED-AUDIT-2026-05-26.md` — master action plan

### Critical Pre-Production Blockers

| ID | Finding | Fix |
|---|---|---|
| M1 | OpenSIPS memory exceeds container limits in all profiles | Set children=8, recalc -m/-M |
| M2 | PostgreSQL prod theoretical max exceeds 8 GB limit | Reduce work_mem to 16 MB or raise limit |
| M3 | PostgreSQL prod shm_size insufficient | Increase to 3gb |

### High-Priority Actions
- B17: Update stale `.github/copilot-instructions.md`
- F1: Fix `.env.example` floating `latest` default
- D9/V7: Align admin_api PHP base image with OCP
- A2/V19: Pin rtpengine APT package
- PY8/V21: Align Python requests version across containers
- M5-M6: Paginate unbounded PHP queries (LGPD export, audit integrity)
- M4: Rebalance dev PostgreSQL memory reservation

### Blocked External Dependencies
- Stage 6: SIP Public Exposure — awaiting firewall/Tailscale ACL
- Stage 8.1: S3 Backup — awaiting operator config in secrets dir

### TSi-Vault Notes Created
- `audit/2026-05-26-consolidated.md`
- `audit/memory-critical-oom.md`
- `audit/brownfield-findings.md`
- `audit/version-guard-failures.md`
- `architecture/gitnexus-insights.md`
