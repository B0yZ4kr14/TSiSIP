# Capability Candidates — TSiSIP BrownKit Scan

### C-01: SIP Edge Proxy                           confidence: HIGH

Sources:
  - S1: opensips — SIP proxy configuration and routing logic
  - S2: S2-01-subscriber_auth — subscriber, version, credentials tables
  - S3: S3-sip-signaling — UDP/TCP/TLS/WS/WSS ports
  - S3: S3-mi-http — Management Interface on 8888

Ambiguity flags:
  - [ ] overlaps with C-04 (Subscriber Authentication) — SIP proxy handles auth but auth is a separate concern

Notes: Core capability of TSiSIP. OpenSIPS 3.6 LTS acts as the public SIP signaling entry point.

---

### C-02: Media Relay (RTPengine)                  confidence: HIGH

Sources:
  - S1: docker — Container definitions for all runtime services
  - S3: S3-rtpengine — Docker service definition

Ambiguity flags: none

Notes: RTPengine relays all RTP media. Public UDP ports 10000-20000.

---

### C-03: PBX Backend (Asterisk)                   confidence: HIGH

Sources:
  - S1: docker — Container definitions
  - S3: S3-asterisk-pbx-1 — Docker service
  - S3: S3-asterisk-pbx-2 — Docker service (HA pair)

Ambiguity flags: none

Notes: Two Asterisk instances for high availability. No host-published ports.

---

### C-04: Subscriber Authentication                confidence: HIGH

Sources:
  - S1: db — PostgreSQL schema
  - S2: S2-01-subscriber_auth — subscriber, version, credentials tables
  - S3: S3-sip-signaling — REGISTER/INVITE auth via digest

Ambiguity flags:
  - [ ] overlaps with C-01 (SIP Edge Proxy) — auth is a sub-capability

Notes: PostgreSQL-backed SIP digest auth with HA1 hashes. Supports SHA-256/SHA-512/256.

---

### C-05: Tenant Management                        confidence: HIGH

Sources:
  - S1: db — PostgreSQL schema
  - S2: S2-02-tenant_management — tenants, tenant_settings, domain tables
  - S3: S3-sip-signaling — tenant-scoped routing in OpenSIPS

Ambiguity flags: none

Notes: Multi-tenant metadata drives dynamic routing to correct PBX backend.

---

### C-06: SIP Trunk Management                     confidence: HIGH

Sources:
  - S1: db — PostgreSQL schema
  - S2: S2-03-trunk_management — sip_trunks, trunk_endpoints, trunk_rate_limits
  - S3: S3-sip-signaling — trunk routing logic

Ambiguity flags:
  - [ ] overlaps with C-07 (Routing & Dispatcher) — trunk routing is a specialization

Notes: Feature 006: per-trunk rate limiting, health probes, whitelist, failover.

---

### C-07: Routing & Dispatcher                     confidence: HIGH

Sources:
  - S1: db — PostgreSQL schema
  - S2: S2-02-routing_dispatcher — dispatcher, pbx_backends, header_routing_rules
  - S3: S3-sip-signaling — ds_select_dst usage

Ambiguity flags:
  - [ ] overlaps with C-06 (SIP Trunk Management)

Notes: Dispatcher module with integer algorithm 4 (weighted round-robin) and failover.

---

### C-08: PostgreSQL Database                      confidence: HIGH

Sources:
  - S1: db — PostgreSQL schema
  - S2: S2-01-subscriber_auth, S2-02-tenant_management, S2-03-trunk_management
  - S3: S3-postgres — Docker service

Ambiguity flags: none

Notes: Single source of truth for auth, routing, tenant metadata. No host-published ports.

---

### C-09: Operator Control Panel (OCP)             confidence: MEDIUM

Sources:
  - S1: web — OCP frontend
  - S3: S3-ocp — Docker service (PHP-based)
  - S4: S4-ocp — Operator Control Panel routes

Ambiguity flags: none

Notes: Web UI for operator management. Rebranded TSiSIP theme with i18n (en/es/pt).

---

### C-10: Monitoring & Observability               confidence: MEDIUM

Sources:
  - S1: docker — prometheus, grafana, alertmanager, opensips-exporter
  - S3: S3-prometheus, S3-grafana, S3-alertmanager, S3-opensips-exporter

Ambiguity flags: none

Notes: Prometheus metrics + Grafana dashboards. OpenSIPS exporter on 8080.

---

### C-11: TLS Certificate Management               confidence: MEDIUM

Sources:
  - S1: docker — certbot, tailscale-cert
  - S1: ca-offline — PKI infrastructure
  - S3: S3-certbot, S3-tailscale-cert

Ambiguity flags: none

Notes: Let's Encrypt via certbot + Tailscale cert renewal. Offline CA for internal certs.

---

### C-12: Anomaly Detection                        confidence: LOW

Sources:
  - S1: docker — anomaly-detector container
  - S3: S3-anomaly-detector — Docker service

Ambiguity flags: none

Notes: Python-based anomaly detector. Limited source visibility.

---

### C-13: Backup & Recovery                        confidence: LOW

Sources:
  - S1: docker — backup container
  - S3: S3-backup — Docker service

Ambiguity flags: none

Notes: PostgreSQL backup with encryption. Rclone offload support.

---

### C-14: Deployment Automation                    confidence: MEDIUM

Sources:
  - S1: deploy — Ansible, nginx, VPS scripts
  - S3: S3-script-01 through S3-script-07 — deploy scripts

Ambiguity flags: none

Notes: Ansible playbooks + nginx configs + VPS deployment validation.

---

### C-15: PKI Infrastructure                       confidence: MEDIUM

Sources:
  - S1: ca-offline — PKI certificate authority
  - S1: secrets — runtime secrets
  - S3: S3-certbot, S3-tailscale-cert

Ambiguity flags: none

Notes: Offline CA key, server.crt/server.key, CRL management. Docker secrets for runtime.

---

### C-16: Rate Limiting & DDoS Protection          confidence: MEDIUM

Sources:
  - S1: opensips — pike, ratelimit modules
  - S1: docker — anomaly-detector
  - S3: S3-sip-signaling — pike/ratelimit in opensips.cfg

Ambiguity flags:
  - [ ] overlaps with C-12 (Anomaly Detection) — both handle traffic anomalies

Notes: pike per-IP throttling + ratelimit auth throttling + ban_list htable.

---

### C-17: WebRTC Support                           confidence: LOW

Sources:
  - S1: opensips — proto_ws, proto_wss modules
  - S3: S3-sip-signaling — WS/WSS ports 8080/4443

Ambiguity flags: none

Notes: WebSocket transport for WebRTC SIP clients. Configured but minimal test coverage.

---

### C-18: Admin API                                confidence: LOW

Sources:
  - S1: docker — admin-api container
  - S3: S3-admin-api — Docker service

Ambiguity flags: none

Notes: REST API for administrative operations. Limited visibility into endpoints.

---

### C-19: OCP Audit & Tools                        confidence: LOW

Sources:
  - S1: db — ocp_audit_schema.sql
  - S2: S2-04-audit_ocp — ocp_audit_log, ocp_tools tables
  - S4: S4-ocp — Operator Control Panel

Ambiguity flags: none

Notes: Audit logging for OCP operations and tool usage tracking.

---

### C-20: Health Checks & Autohealing              confidence: LOW

Sources:
  - S1: docker — healthcheck containers
  - S1: scripts — health check scripts
  - S3: S3-script-02, S3-script-05 — health probes

Ambiguity flags: none

Notes: Docker HEALTHCHECKs on all services. SIP OPTIONS probe script.

---

*Total candidates: 20 (target 15–25)*
*Confidence distribution: HIGH 8, MEDIUM 7, LOW 5*
