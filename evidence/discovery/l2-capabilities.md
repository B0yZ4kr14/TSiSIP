# L2 Sub-Capabilities

## BC-001: SIP Edge Proxy

### BC-001-01: SIP Transport & Protocol Handling
- **Code**: opensips/opensips.cfg.tpl (lines 30-65)
- **Entities**: MANAGES sip_edge network
- **Operations**:
  - UDP listener on 0.0.0.0:5060
  - TCP listener on 0.0.0.0:5060
  - TLS listener on 0.0.0.0:5061
  - WS listener on 0.0.0.0:8080
  - WSS listener on 0.0.0.0:4443
- **External**: N/A

### BC-001-02: SIP Digest Authentication
- **Code**: opensips/opensips.cfg.tpl (lines 120-160)
- **Entities**: READS subscriber, READS credentials
- **Operations**:
  - www_authorize / proxy_authorize against PostgreSQL
  - HA1 hash verification (SHA-256, SHA-512/256)
  - Auth failure tracking via htable (ban_list)
- **External**: PostgreSQL (db_internal network)

### BC-001-03: Tenant-Scoped Routing
- **Code**: opensips/opensips.cfg.tpl (lines 180-220)
- **Entities**: READS tenants, READS header_routing_rules
- **Operations**:
  - Domain lookup for tenant identification
  - Dynamic dispatcher set selection
  - Header-based routing rules
- **External**: PostgreSQL (db_internal network)

### BC-001-04: Topology Hiding
- **Code**: opensips/opensips.cfg.tpl (lines 200-220)
- **Entities**: MANAGES sip_internal network topology
- **Operations**:
  - topology_hiding("C") on outbound messages
  - Strip internal IPs from headers
- **External**: N/A

### BC-001-05: Rate Limiting & DDoS Protection
- **Code**: opensips/opensips.cfg.tpl (lines 100-115)
- **Entities**: MANAGES ban_list htable, MANAGES auth_failures htable
- **Operations**:
  - pike per-IP throttling
  - ratelimit auth throttling
  - Ban list enforcement
- **External**: cachedb_local (in-memory)

### BC-001-06: Media Relay Integration
- **Code**: opensips/opensips.cfg.tpl (lines 240-260)
- **Entities**: CREATES SDP offers/answers
- **Operations**:
  - rtpengine_offer() on INVITE
  - rtpengine_answer() on 200 OK
  - rtpengine_delete() on BYE
- **External**: RTPengine (sip_internal control socket)

### BC-001-07: WebRTC Support
- **Code**: opensips/opensips.cfg.tpl (lines 55-65)
- **Entities**: MANAGES WS/WSS transport
- **Operations**:
  - WebSocket SIP transport
  - WSS secure WebSocket
- **External**: N/A

---

## BC-002: Media Relay

### BC-002-01: RTP Relay
- **Code**: docker/rtpengine/Dockerfile, docker-compose.yml
- **Entities**: MANAGES RTP sessions
- **Operations**:
  - RTP relay on UDP 10000-20000
  - SDP rewriting
  - Kernel bypass mode (containerized)
- **External**: OpenSIPS control via sip_internal:22222

---

## BC-003: PBX Backend

### BC-003-01: Voice Application Server
- **Code**: docker/asterisk/Dockerfile
- **Entities**: MANAGES voice channels
- **Operations**:
  - SIP endpoint for authenticated traffic
  - Voice/video application logic
  - Voicemail, conferencing, IVR
- **External**: OpenSIPS (sip_internal), PostgreSQL (cdr)

---

## BC-004: Tenant & Subscriber Management

### BC-004-01: Subscriber Provisioning
- **Code**: db/init/01-stock-opensips-schema.sql
- **Entities**: OWNS subscriber, OWNS version
- **Operations**:
  - CREATE/READ/UPDATE/DELETE subscriber records
  - HA1 password generation
  - Digest credential storage
- **External**: N/A

### BC-004-02: Tenant Provisioning
- **Code**: db/init/02-tsisip-extensions.sql
- **Entities**: OWNS tenants, OWNS tenant_settings
- **Operations**:
  - Tenant CRUD
  - Domain mapping
  - Tenant-scoped settings
- **External**: N/A

### BC-004-03: Audit Logging
- **Code**: db/init/04-ocp-audit-schema.sql
- **Entities**: OWNS auth_audit_log, OWNS ocp_audit_log
- **Operations**:
  - Auth event logging
  - OCP operation logging
  - Tool usage tracking
- **External**: N/A

---

## BC-005: SIP Trunk Management

### BC-005-01: Trunk Provisioning
- **Code**: db/init/04-trunk-schema.sql
- **Entities**: OWNS sip_trunks, OWNS trunk_endpoints
- **Operations**:
  - Trunk CRUD
  - Endpoint configuration
  - Authentication credentials
- **External**: N/A

### BC-005-02: Rate Limiting
- **Code**: db/init/04-trunk-schema.sql (trunk_rate_limits)
- **Entities**: OWNS trunk_rate_limits
- **Operations**:
  - Per-trunk rate limit configuration
  - Burst and sustained limits
- **External**: N/A

### BC-005-03: Health Probes
- **Code**: db/init/04-trunk-schema.sql (trunk_health_log)
- **Entities**: OWNS trunk_health_log
- **Operations**:
  - Health check logging
  - Failover state tracking
- **External**: N/A

### BC-005-04: IP Whitelisting
- **Code**: db/init/04-trunk-schema.sql (trunk_whitelist)
- **Entities**: OWNS trunk_whitelist
- **Operations**:
  - Trusted IP management
  - NAT handling
- **External**: N/A

---

## BC-006: Anomaly Detection & Security Monitoring

### BC-006-01: Traffic Anomaly Detection
- **Code**: docker/anomaly-detector/
- **Entities**: TRACKS sip_edge traffic patterns
- **Operations**:
  - SIP signaling pattern analysis
  - Anomaly alerting
- **External**: Prometheus metrics, OpenSIPS MI
