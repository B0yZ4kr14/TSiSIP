# L1 Capabilities

## BC-001: SIP Edge Proxy
- **Cohesion**: HIGH
- **Coupling**: MEDIUM
- **Boundary**: CLEAR
- **Source action**: CONFIRM
- **Description**: Public-facing SIP signaling edge proxy built on OpenSIPS 3.6 LTS. Handles all SIP traffic (REGISTER, INVITE, OPTIONS), authenticates requests against PostgreSQL, routes to correct Asterisk backend using tenant-scoped metadata, performs topology hiding, and manages media relay integration.
- **Evidence**: opensips/opensips.cfg.tpl, docker/Dockerfile, docker/entrypoint.sh, docker-compose.yml (ports 5060/udp, 5060/tcp, 5061/tcp)

## BC-002: Media Relay
- **Cohesion**: HIGH
- **Coupling**: LOW
- **Boundary**: CLEAR
- **Source action**: CONFIRM
- **Description**: RTP media relay using RTPengine. Rewrites SDP to hide backend PBX IP addresses. Handles public RTP on UDP ports 10000-20000.
- **Evidence**: docker/rtpengine/Dockerfile, docker-compose.yml (ports 10000-20000/udp)

## BC-003: PBX Backend
- **Cohesion**: HIGH
- **Coupling**: LOW
- **Boundary**: CLEAR
- **Source action**: CONFIRM
- **Description**: Asterisk voice/video application servers. Two instances for high availability. Receive authenticated SIP traffic via sip_internal network only — no host-published ports.
- **Evidence**: docker/asterisk/Dockerfile, docker-compose.yml (asterisk-pbx-1, asterisk-pbx-2)

## BC-004: Tenant & Subscriber Management
- **Cohesion**: HIGH
- **Coupling**: MEDIUM
- **Boundary**: CLEAR
- **Source action**: CONFIRM (from C-05, merged C-19)
- **Description**: Multi-tenant subscriber provisioning, domain mapping, tenant settings, and audit logging. PostgreSQL-backed with HA1 password hashing. Supports SHA-256 and SHA-512/256 digest algorithms.
- **Evidence**: db/init/01-*.sql, db/init/02-*.sql, db/init/04-ocp-*.sql

## BC-005: SIP Trunk Management
- **Cohesion**: HIGH
- **Coupling**: MEDIUM
- **Boundary**: CLEAR
- **Source action**: CONFIRM
- **Description**: SIP trunk provisioning, per-trunk rate limiting, health probes, IP whitelisting, and failover configuration. Feature 006 implementation.
- **Evidence**: db/init/04-trunk-*.sql, db/init/05-*.sql, specs/006-rate-limiting-ddos-protection/

## BC-006: Anomaly Detection & Security Monitoring
- **Cohesion**: HIGH
- **Coupling**: LOW
- **Boundary**: CLEAR
- **Source action**: CONFIRM
- **Description**: Traffic anomaly detection for SIP-layer attacks. Monitors signaling patterns and may trigger protective actions.
- **Evidence**: docker/anomaly-detector/, docker-compose.yml

---

## De-scoped Items

### Infrastructure
- **Datastore**: PostgreSQL (BC-008)
- **Observability**: Prometheus + Grafana + Alertmanager + OpenSIPS exporter (C-10)
- **Security**: TLS cert management, PKI (C-11, C-15)
- **Resilience**: Backup & recovery, health checks (C-13, C-20)
- **Deployment**: Ansible, nginx, VPS scripts (C-14)

### Delivery Channels
- **Web**: OCP Operator Control Panel (C-09)
- **API**: Admin API (C-18)

---

## Flagged Items
None.
