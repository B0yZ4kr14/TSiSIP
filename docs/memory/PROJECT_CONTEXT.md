# Project Context — TSiSIP

## Product Identity

**TSiSIP** is a Docker-image-first SIP edge-proxy platform built on OpenSIPS 3.6 LTS.
It acts as the only public SIP signaling entry point and security boundary for a
private, multi-tenant Asterisk PBX backend cluster.

## Domain Language

| Term | Meaning |
|---|---|
| SIP | Session Initiation Protocol |
| OpenSIPS | Open SIP Server (proxy, registrar, dispatcher) |
| Asterisk | PBX backend for voice/video applications |
| RTPengine | Media relay for RTP/RTCP traffic |
| PostgreSQL | Database for subscriber auth and routing metadata |
| HA1 | Pre-computed MD5 hash for Digest authentication |
| Topology Hiding | Technique to conceal backend IP addresses |
| OCP | Operator Control Panel (web UI for admin) |
| MSL | Memory Safety Level (security classification) |

## Key Constraints

1. OpenSIPS 3.6 LTS is the **only** SIP proxy baseline.
2. PostgreSQL is the **only** database.
3. All runtime components are delivered via **project-owned Docker images**.
4. Asterisk and PostgreSQL have **zero host-published ports**.
5. All SIP authentication uses **pre-computed HA1** (`calculate_ha1 = 0`).
6. Topology hiding is mandatory for all forwarded traffic.
7. No Kamailio-only modules or functions.
8. Development secrets live in `secrets/` (gitignored).

## Project Structure

```
TSiSIP/
├── docs/              # Canonical documentation
├── specs/             # Feature specifications
├── opensips/          # OpenSIPS config templates
├── docker/            # Container support files
├── db/init/           # PostgreSQL initialization
├── web/               # OCP PHP frontend
├── build/             # Asset build tools
├── deploy/            # Deployment automation
└── secrets/           # Runtime secrets (gitignored)
```

## Key Technologies

- OpenSIPS 3.6 LTS (C)
- PostgreSQL 15+ (SQL)
- RTPengine (C/kernel module)
- Asterisk 20+ (C)
- Docker & Docker Compose
- PHP 8.2+ (OCP frontend)
- Node.js (build tooling)
- Python 3.11+ (scripts, probes)
