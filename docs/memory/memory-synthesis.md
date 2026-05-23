# Memory Synthesis — TSiSIP

> Condensed context for agent planning. Updated after each feature completion.
> Full memory: see INDEX.md for navigation.

## Project Identity
TSiSIP is a Docker-first SIP edge-proxy on OpenSIPS 3.6 LTS, routing authenticated traffic to private Asterisk backends via PostgreSQL-backed subscriber credentials. RTPengine relays all media. OCP provides web admin tools.

## Architecture Constraints
- 3 Docker networks: sip_edge (public), sip_internal (private), db_internal (private)
- OpenSIPS publishes 5060/udp+tcp; RTPengine publishes 10000-20000/udp
- Asterisk and PostgreSQL have ZERO host-published ports
- All auth uses pre-computed HA1 (calculate_ha1=0)
- Topology hiding mandatory (topology_hiding("C"))

## Key Decisions
1. OpenSIPS 3.6 LTS only — no Kamailio modules
2. PostgreSQL only — no MySQL/MariaDB
3. Docker images only — no bare-metal
4. Pre-computed HA1 — no plaintext passwords
5. Explicit rtpengine_offer/answer/delete — not manage()
6. OCP admin tools use PDO + CSRF + RBAC (CUP-012-01 amends Constitution V1)

## Known Bugs
1. systemd-resolved conflicts with Docker DNS → use --network host for certbot
2. certbot-exporter has UnboundLocalError → use manual certbot
3. backup-1 unhealthy until first .enc backup → low priority
4. validate-input.php orphaned → refactor subscribers.php and dispatcher.php to use it

## Rejected Patterns
- db_mysql, calculate_ha1=1, plaintext passwords, Kamailio auth_check(), hard-coded dispatcher sets, custom subscriber tables, topology_hiding("U") as baseline, rtpengine_manage() as baseline, RTPengine kernel DKMS as baseline

## Current Open Items
- CUP-012-01: Ratify Constitution amendment for OCP subscriber/dispatcher writes
- SEC-ACTION-005: Create formal threat model document (LOW, pending)
- Feature 019: Complete memory hub bootstrap and validation
- Feature 020: Remediation cycle R1-R5 complete (R6 P3 optional, R7 passed)
  - FINDING-SEC-020-001: RESOLVED — CRUD failure audit gap fixed
  - FINDING-SEC-020-002: RESOLVED — Security headers added
  - FINDING-SEC-020-003: RESOLVED — Session hardening applied

## Tech Stack
OpenSIPS 3.6 LTS | PostgreSQL 15+ | RTPengine | Asterisk 20+ | Docker | PHP 8.2+ | Node.js | Python 3.11+
