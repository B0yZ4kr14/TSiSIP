# D1 — Deep Candidate Analysis & D2 — Action Determination

## C-01: SIP Edge Proxy
- **Cohesion**: HIGH — single purpose: public SIP signaling entry point
- **Coupling**: MEDIUM — depends on BC-004 (tenant metadata), BC-002 (RTPengine), BC-003 (Asterisk)
- **Boundary**: CLEAR — UDP/TCP/TLS/WS ports 5060/5061/8080/4443
- **Action**: CONFIRM → BC-001
- **Rationale**: Core capability with well-defined protocol boundaries.

## C-02: Media Relay (RTPengine)
- **Cohesion**: HIGH — single purpose: RTP media relay and SDP rewriting
- **Coupling**: LOW — controlled by BC-001 via RTPengine control socket
- **Boundary**: CLEAR — UDP ports 10000-20000, internal control on sip_internal
- **Action**: CONFIRM → BC-002
- **Rationale**: Independent media plane capability.

## C-03: PBX Backend (Asterisk)
- **Cohesion**: HIGH — voice/video application server
- **Coupling**: LOW — receives traffic from BC-001 via sip_internal
- **Boundary**: CLEAR — private Docker network only, no host ports
- **Action**: CONFIRM → BC-003
- **Rationale**: Distinct application-layer capability, though externally sourced (Asterisk).

## C-04: Subscriber Authentication
- **Cohesion**: HIGH — digest auth against PostgreSQL
- **Coupling**: HIGH — embedded in BC-001 routing logic
- **Boundary**: PARTIAL — auth is a cross-cutting concern within SIP proxy
- **Action**: MERGE into BC-001
- **Rationale**: Auth is inseparable from SIP proxy operation; no standalone interface.

## C-05: Tenant Management
- **Cohesion**: HIGH — tenant CRUD, domain mapping, settings
- **Coupling**: MEDIUM — read by BC-001 for routing, read by OCP for UI
- **Boundary**: CLEAR — PostgreSQL tables with well-defined schema
- **Action**: CONFIRM → BC-004
- **Rationale**: Distinct business capability with its own data model.

## C-06: SIP Trunk Management
- **Cohesion**: HIGH — trunk provisioning, rate limits, health probes, whitelist
- **Coupling**: MEDIUM — used by BC-001 for outbound routing
- **Boundary**: CLEAR — dedicated schema + OpenSIPS dispatcher integration
- **Action**: CONFIRM → BC-005
- **Rationale**: Distinct operational capability with dedicated Feature 006 spec.

## C-07: Routing & Dispatcher
- **Cohesion**: HIGH — destination selection, failover, load balancing
- **Coupling**: HIGH — embedded in BC-001; uses BC-004/BC-005 data
- **Boundary**: PARTIAL — logic lives inside OpenSIPS config
- **Action**: MERGE into BC-001
- **Rationale**: Routing is an internal mechanism of the SIP proxy, not a standalone capability.

## C-08: PostgreSQL Database
- **Cohesion**: LOW — stores data for multiple capabilities
- **Coupling**: HIGH — shared by BC-001, BC-004, BC-005, BC-009
- **Boundary**: CLEAR — network-isolated container
- **Action**: DE-SCOPE
- **Classification**: infrastructure.datastore
- **Rationale**: Data persistence is cross-cutting infrastructure, not a business capability.

## C-09: Operator Control Panel (OCP)
- **Cohesion**: MEDIUM — UI for tenant/trunk mgmt + audit + tools
- **Coupling**: MEDIUM — depends on BC-004, BC-005 for data
- **Boundary**: CLEAR — HTTP/PHP web interface
- **Action**: DE-SCOPE
- **Classification**: delivery_channel.web
- **Rationale**: OCP is a delivery channel for operator-facing operations. The underlying capabilities (tenant mgmt, trunk mgmt) are the actual business capabilities.

## C-10: Monitoring & Observability
- **Cohesion**: MEDIUM — metrics, dashboards, alerting
- **Coupling**: LOW — reads from BC-001 exporter and system metrics
- **Boundary**: CLEAR — Prometheus/Grafana on internal network
- **Action**: DE-SCOPE
- **Classification**: infrastructure.observability
- **Rationale**: Cross-cutting operational infrastructure.

## C-11: TLS Certificate Management
- **Cohesion**: HIGH — cert renewal, deployment
- **Coupling**: LOW — provides certs to BC-001 and BC-002
- **Boundary**: CLEAR — certbot + tailscale-cert containers
- **Action**: DE-SCOPE
- **Classification**: infrastructure.security
- **Rationale**: Security infrastructure, not a business capability.

## C-12: Anomaly Detection
- **Cohesion**: HIGH — traffic anomaly detection
- **Coupling**: LOW — reads SIP metrics, may trigger BC-001 config changes
- **Boundary**: CLEAR — dedicated Python container
- **Action**: CONFIRM → BC-006
- **Rationale**: Distinct security assurance capability.

## C-13: Backup & Recovery
- **Cohesion**: HIGH — DB backup, encryption, offload
- **Coupling**: LOW — operates on BC-008 data
- **Boundary**: CLEAR — backup container with cron schedule
- **Action**: DE-SCOPE
- **Classification**: infrastructure.resilience
- **Rationale**: Operational resilience infrastructure.

## C-14: Deployment Automation
- **Cohesion**: MEDIUM — Ansible playbooks, nginx configs, VPS scripts
- **Coupling**: LOW — deploys all capabilities
- **Boundary**: PARTIAL — spans multiple environments
- **Action**: DE-SCOPE
- **Classification**: infrastructure.deployment
- **Rationale**: Delivery infrastructure, not runtime capability.

## C-15: PKI Infrastructure
- **Cohesion**: HIGH — CA key, cert generation, CRL
- **Coupling**: LOW — provides trust material
- **Boundary**: CLEAR — ca-offline directory + Docker secrets
- **Action**: DE-SCOPE
- **Classification**: infrastructure.security
- **Rationale**: Security infrastructure.

## C-16: Rate Limiting & DDoS Protection
- **Cohesion**: HIGH — pike, ratelimit, ban_list
- **Coupling**: HIGH — embedded in BC-001 traffic handling
- **Boundary**: PARTIAL — config inside OpenSIPS, no external API
- **Action**: MERGE into BC-001
- **Rationale**: Security sub-mechanism of SIP proxy.

## C-17: WebRTC Support
- **Cohesion**: HIGH — WS/WSS transport for WebRTC clients
- **Coupling**: MEDIUM — extends BC-001 with WebSocket transport
- **Boundary**: CLEAR — ports 8080/4443
- **Action**: MERGE into BC-001
- **Rationale**: Transport extension of SIP proxy, not a distinct capability.

## C-18: Admin API
- **Cohesion**: MEDIUM — REST API for admin operations
- **Coupling**: MEDIUM — wraps BC-004 and BC-005
- **Boundary**: PARTIAL — limited documentation
- **Action**: DE-SCOPE
- **Classification**: delivery_channel.api
- **Rationale**: API delivery channel for operator operations.

## C-19: OCP Audit & Tools
- **Cohesion**: MEDIUM — audit logging, tool usage tracking
- **Coupling**: MEDIUM — part of OCP delivery channel
- **Boundary**: PARTIAL — shared with BC-004 data model
- **Action**: MERGE into BC-004
- **Rationale**: Audit is a facet of tenant/subscriber management.

## C-20: Health Checks & Autohealing
- **Cohesion**: MEDIUM — container health probes, SIP OPTIONS tests
- **Coupling**: LOW — monitors all containers
- **Boundary**: CLEAR — Docker HEALTHCHECK + test scripts
- **Action**: DE-SCOPE
- **Classification**: infrastructure.resilience
- **Rationale**: Operational resilience infrastructure.

---

## Tally
| Action | Count |
|---|---|
| CONFIRM | 6 |
| MERGE | 6 |
| DE-SCOPE | 8 |
| SPLIT | 0 |
| FLAG | 0 |
