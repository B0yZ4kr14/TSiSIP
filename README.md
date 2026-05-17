# TSiSIP — Docker-First SIP Edge Proxy Platform

TSiSIP is a Docker-image-first SIP edge-proxy platform built on **OpenSIPS 3.6 LTS**.
It acts as the only public SIP signaling entry point and security boundary for a private, multi-tenant Asterisk PBX backend cluster.

## Features

| Feature | Status | Description |
|---|---|---|
| **001 — OpenSIPS Docker Edge Proxy** | Complete | OpenSIPS 3.6 LTS, RTPengine, PostgreSQL schema, Docker Compose topology |
| **002 — OCP Rebranding & Modernization** | Complete | TSiSIP-branded OpenSIPS Control Panel v9 with D3.js charts, i18n, role-aware density |

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

## Documentation

- [Canonical Architecture Spec](docs/TSiSIP-CANONICAL-SPEC.md)
- [Agent Orchestration Playbook](docs/TSiSIP-AGENT-ORCHESTRATION-PLAYBOOK.md)
- [Operator Runbook](docs/TSiSIP-OPERATOR-RUNBOOK.md)
- [DESIGN.md](DESIGN.md) — Visual design system
- [CHANGELOG.md](CHANGELOG.md)
- [AGENTS.md](AGENTS.md) — Agent onboarding guide

## Technology Stack

| Layer | Technology |
|---|---|
| SIP Proxy | OpenSIPS 3.6 LTS |
| Database | PostgreSQL 16 |
| Media Relay | RTPengine |
| PBX Backend | Asterisk |
| Admin Panel | OCP v9 + TSiSIP Theme (PHP 8.2 / Apache) |
| Packaging | Docker + Docker Compose |

## Security

- SIP Digest authentication with HA1 hashes only (no plaintext passwords)
- Private Docker networks for Asterisk and PostgreSQL (no published ports)
- Runtime secrets injected via Docker secrets or envsubst
- Capability-dropped containers with `no-new-privileges`

## License

Apache-2.0
