# Domain Model — TSiSIP

## Entity Catalog

| Entity | Owner | Readers | Writers |
|---|---|---|---|
| subscriber | BC-004 | BC-001 | BC-004 |
| version | BC-004 | BC-001 | BC-004 |
| credentials | BC-004 | BC-001 | BC-004 |
| tenants | BC-004 | BC-001, BC-009 | BC-004 |
| tenant_settings | BC-004 | BC-001 | BC-004 |
| domain | BC-004 | BC-001 | BC-004 |
| dispatcher | BC-005 | BC-001 | BC-005 |
| pbx_backends | BC-005 | BC-001 | BC-005 |
| header_routing_rules | BC-005 | BC-001 | BC-005 |
| sip_trunks | BC-005 | BC-001 | BC-005 |
| trunk_endpoints | BC-005 | BC-001 | BC-005 |
| trunk_health_log | BC-005 | BC-006 | BC-005 |
| trunk_rate_limits | BC-005 | BC-001 | BC-005 |
| trunk_whitelist | BC-005 | BC-001 | BC-005 |
| auth_audit_log | BC-004 | BC-009 | BC-001, BC-004 |
| ocp_audit_log | BC-004 | BC-009 | BC-001, BC-004 |
| ocp_tools | BC-004 | BC-009 | BC-004 |
| ocp_tool_usage | BC-004 | BC-009 | BC-004 |

## Ownership Matrix

| Entity | BC-001 | BC-002 | BC-003 | BC-004 | BC-005 | BC-006 |
|---|---|---|---|---|---|---|
| subscriber | R | - | - | O | - | - |
| tenants | R | - | - | O | - | - |
| tenant_settings | R | - | - | O | - | - |
| sip_trunks | R | - | - | - | O | - |
| trunk_endpoints | R | - | - | - | O | - |
| trunk_rate_limits | R | - | - | - | O | - |
| trunk_health_log | - | - | - | - | O | R |
| auth_audit_log | W | - | - | O | - | - |

O = OWNS, R = READS, W = WRITES, C = CREATES, M = MANAGES

## Dependency Graph

```
Internet ──► BC-001 (SIP Edge Proxy)
              │
              ├─► BC-002 (Media Relay) ──► Internet (RTP)
              │
              ├─► BC-003 (PBX Backend)
              │     │
              │     └─► PostgreSQL (infrastructure)
              │
              ├─► BC-004 (Tenant & Subscriber Mgmt)
              │     │
              │     └─► PostgreSQL (infrastructure)
              │
              ├─► BC-005 (SIP Trunk Mgmt)
              │     │
              │     └─► PostgreSQL (infrastructure)
              │
              └─► BC-006 (Anomaly Detection)
                    │
                    └─► Prometheus (infrastructure)
```

## Bounded Context Candidates

1. **Signaling Plane** — BC-001 + BC-002 + BC-006
   - High internal cohesion (all handle real-time traffic)
   - Weak coupling to management plane
   - Team: Platform / SRE

2. **Application Plane** — BC-003
   - Independent voice application server
   - Team: Voice Engineering

3. **Management Plane** — BC-004 + BC-005
   - Subscriber, tenant, and trunk lifecycle management
   - Weak coupling to signaling plane (read-only at runtime)
   - Team: Operations / DevOps

## Infrastructure Classification

| Type | Items |
|---|---|
| Datastore | PostgreSQL (BC-008) |
| Observability | Prometheus, Grafana, Alertmanager, OpenSIPS exporter (C-10) |
| Security | TLS cert management, PKI (C-11, C-15) |
| Resilience | Backup & recovery, health checks (C-13, C-20) |
| Deployment | Ansible, nginx, VPS scripts (C-14) |
| Delivery (Web) | OCP (C-09) |
| Delivery (API) | Admin API (C-18) |
