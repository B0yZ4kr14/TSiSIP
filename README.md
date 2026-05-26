# TSiSIP — Docker-First SIP Edge Proxy Platform

TSiSIP is a Docker-image-first SIP edge-proxy platform. Its SIP engine is based on **OpenSIPS 3.6 LTS**.
It acts as the only public SIP signaling entry point and security boundary for a private, multi-tenant Asterisk PBX backend cluster.

## Features

| Feature | Status | Description |
|---|---|---|
| **001 — TSiSIP SIP Edge Foundation** | Completed | OpenSIPS 3.6 LTS, RTPengine, PostgreSQL schema, Docker Compose topology |
| **002 — TSiSIP Control Panel Modernization** | Completed | OCP v9 rebrand with D3.js charts, i18n, role-aware density |
| **003 — Prometheus & Grafana Observability** | Completed | Metrics collection, dashboards, alert rules |
| **004 — Health Checks & Autohealing** | Completed | Container health probes, restart policies, dependency ordering |
| **005 — PostgreSQL Backup & Restore** | Completed | WAL archiving, PITR, encryption, RPO/RTO monitoring |
| **006 — Rate Limiting & DDoS Protection** | Completed | `ratelimit`, `userblacklist`, `cachedb_local` modules |
| **007 — TLS & SRTP Encryption** | Completed | TLS v1.2+, SRTP media relay, certificate rotation |
| **008 — DevSecOps Deployment** | Completed | VPS production stack, CI/CD, deterministic image pinning |
| **009 — VPS Deploy Automation** | Completed | Ansible playbooks, bootstrap scripts, nginx unified proxy |
| **010 — OCP Navigation System Links** | Completed | Role-aware navigation, dashboard cards, wiki integration |
| **011 — OCP Forced Password Change** | Completed | First-login password change, HA1 generation, audit logging |
| **012 — OCP Admin Tools Restoration** | Completed | Subscriber, dispatcher, address CRUD with RBAC |
| **013 — Brownfield Follow-up** | Completed | Post-deployment scan remediation, env completeness |
| **015 — Auto TLS Certificate Rotation** | Completed | Certbot automation, Cloudflare Origin CA, expiry alerts |
| **016 — OCP Audit Log Compliance** | Completed | `ocp_audit_log` table, query interface, LGPD retention |
| **017 — SIP Trunk Provider Integration** | Completed | Multi-provider trunk routing, failover, health probes |
| **018 — Global Requirement ID Migration** | Completed | Feature-scoped FR-NNN-XXX IDs across all specs |
| **019 — Spec Kit Memory Hub Integration** | Completed | Memory synthesis, blueprint generation, spec validation |
| **020 — OCP Critical Tool Gap Closure** | Completed | Dialog viewer, MI commands, statistics, dialplan, domains, TLS mgmt |
| **021 — Brownfield Security & Production Hardening** | Completed | Security headers, session hardening, input validation |
| **022 — VPS Go-Live Stabilization** | Completed | Production readiness, port audits, network segmentation tests |
| **023 — Subscriber CRUD Refactor** | Completed | Proxy layer, HA1 delegation, ARCH-PRE-001 resolution |
| **024 — Brownfield Remediation** | Completed | SHA pinning, dynamic IP discovery, Dockerfile healthchecks |

## Quick Start

```bash
# Clone and enter directory
cd TSiSIP

# Create secrets
echo "your-db-password" > secrets/db_password
echo "your-auth-secret-32-chars-long!!" > secrets/auth_secret
echo "your-topology-secret-32-chars!!" > secrets/topology_secret

# Build everything
make build

# Start the stack
make up

# Run tests
make test
```

## Architecture

```
Internet / SIP clients
        |
        | 5060/udp, 5060/tcp
        v
+-----------------------------+
| TSiSIP SIP edge service     |
| OpenSIPS 3.6 engine         |
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

TSiSIP edge
        |
        | internal DB network
        v
+-----------------------------+
| PostgreSQL                  |
| auth + routing metadata     |
+-----------------------------+
```

## Documentation

- [Canonical Architecture Spec](docs/TSiSIP-CANONICAL-SPEC.md)
- [Agent Orchestration Playbook](docs/TSiSIP-AGENT-ORCHESTRATION-PLAYBOOK.md)
- [Operator Runbook](docs/TSiSIP-OPERATOR-RUNBOOK.md)
- [VPS TSiAPP Access Guide](docs/VPS-TSiAPP-ACCESS.md) — SSH, Ansible, and pipeline config
- [DESIGN.md](DESIGN.md) — Visual design system
- [CHANGELOG.md](CHANGELOG.md)
- [AGENTS.md](AGENTS.md) — Agent onboarding guide

## Technology Stack

| Layer | Technology |
|---|---|
| SIP Proxy | TSiSIP SIP edge service (OpenSIPS 3.6 LTS engine) |
| Database | PostgreSQL 16 |
| Media Relay | RTPengine |
| PBX Backend | Asterisk |
| Admin Panel | OCP v9 + TSiSIP Theme (PHP 8.2 / Apache) |
| Packaging | Docker + Docker Compose |

## WebSocket & WebRTC

WebSocket transport is enabled on OpenSIPS port 8080 (WS) and 4443 (WSS).
For production, use the Nginx reverse proxy:

```nginx
location /ws {
    proxy_pass http://opensips:8080;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
}
```

Clients connect via `wss://your-domain/ws` for secure WebRTC signaling.

## Security

- SIP Digest authentication with HA1 hashes only (no plaintext passwords)
- Private Docker networks for Asterisk and PostgreSQL (no published ports)
- Runtime secrets injected via Docker secrets or envsubst
- Capability-dropped containers with `no-new-privileges`
- MI HTTP circuit breaker prevents cascading failures on OpenSIPS overload
- Automatic audit log purge (90-day retention) via backup job

## License

Apache-2.0
