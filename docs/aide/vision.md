# TSiSIP — Project Vision

## Purpose

TSiSIP is a Docker-image-first SIP edge-proxy platform built on OpenSIPS 3.6 LTS. Its sole purpose is to act as the only public SIP signaling entry point and security boundary for a private, multi-tenant Asterisk PBX backend cluster.

## Goals

1. **Secure SIP Edge**: OpenSIPS handles all public SIP traffic with digest authentication against PostgreSQL-backed subscriber credentials.
2. **Multi-Tenant Routing**: Dynamically route authenticated traffic to the correct Asterisk backend using tenant-scoped metadata from PostgreSQL.
3. **Topology Hiding**: Backend PBX IP addresses must never leak to the public internet.
4. **Media Relay**: RTPengine relays all media so backend IPs remain private.
5. **Operational Visibility**: OCP provides web-based admin tools for dialog monitoring, MI commands, statistics, dialplan management, domains, and TLS management.
6. **SIP Trunk Integration**: Connect to external SIP trunk providers with automatic health monitoring, DID routing, and UAC registration.

## Target Users

- **DevOps/SRE**: Deploy, monitor, and operate the SIP edge platform.
- **Voice Engineers**: Configure routing, trunks, and dialplan rules.
- **Tenants/Resellers**: Manage subscribers and DID mappings via OCP.

## Key Capabilities

| Capability | Status | Feature |
|---|---|---|
| OpenSIPS 3.6 LTS Docker image | Complete | 001 |
| PostgreSQL schema + auth | Complete | 001 |
| Docker Compose 3-network topology | Complete | 001 |
| RTPengine media relay | Complete | 001 |
| Prometheus/Grafana observability | Complete | 003 |
| Health checks + autohealing | Complete | 004 |
| PostgreSQL backup/restore | Complete | 005 |
| Rate limiting + DDoS protection | Complete | 006 |
| TLS/SRTP encryption | Complete | 007 |
| DevSecOps deployment pipeline | Complete | 008 |
| OCP rebranding (TSiSIP theme) | Complete | 002 |
| OCP audit log compliance | Complete | 016 |
| SIP trunk provider integration | Complete | 017 |
| OCP critical tool gap closure | Complete | 020 |
| VPS go-live stabilization | Complete | 022 |
| Subscriber proxy API (ARCH-PRE-001) | Complete | 023 |
| Global FR-NNN-XXX ID migration | Complete | 018 |

## Architecture Principles

- Docker-image-first: All runtime components delivered as project-owned Docker images.
- PostgreSQL-only: No MySQL/MariaDB.
- Precomputed HA1: calculate_ha1 = 0.
- Explicit RTP management: rtpengine_offer / rtpengine_answer / rtpengine_delete.
- Security boundary: OpenSIPS only public entry point; Asterisk and PostgreSQL have zero host-published ports.

## Non-Goals

- Full OCP v9.3.6 parity (28 remaining tools out of scope).
- Real-time WebSocket updates (polling-based acceptable).
- Multi-box OpenSIPS management (single Docker Compose stack only).
- Call recording or media playback.
- Bare-metal or VM-first runtime paths.

## Success Criteria

- OPTIONS probe returns 200 OK on 5060/udp.
- INVITE without auth returns 407 Proxy Authentication Required.
- Authenticated INVITE routes to correct Asterisk backend.
- RTP media flows through RTPengine without leaking backend IPs.
- OCP admin tools enforce RBAC (devops/admin roles) and CSRF protection.
- All schema changes auto-sync to dispatcher and UAC registrant.
- Zero forbidden modules (db_mysql, sanity, Kamailio-only).
