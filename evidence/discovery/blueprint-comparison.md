# Industry Blueprint Comparison

## Framework: TM Forum (eTOM / SID)

TSiSIP is a telecommunications infrastructure platform. The most relevant reference framework is **TM Forum** (TeleManagement Forum), specifically:
- **eTOM** (Enhanced Telecom Operations Map) for process classification
- **SID** (Shared Information / Data Model) for entity mapping

### Aligned

| TSiSIP Capability | TM Forum eTOM Process | Notes |
|---|---|---|
| BC-001 SIP Edge Proxy | Resource Management & Operations (RM&O) → Network Resource Management | SIP proxy is a network resource controlling signaling |
| BC-002 Media Relay | RM&O → Network Resource Management → Media Gateway Control | RTPengine aligns with media gateway function |
| BC-003 PBX Backend | Fulfillment → Service Configuration & Activation → Voice Service | Asterisk provides voice application services |
| BC-004 Tenant & Subscriber Management | Customer → Customer Information Management → Subscriber Management | Direct alignment with subscriber provisioning |
| BC-005 SIP Trunk Management | Fulfillment → Service Configuration & Activation → Connectivity Management | SIP trunk provisioning aligns with connectivity activation |
| BC-006 Anomaly Detection | Assurance → Service Problem Management → Fault Monitoring | Traffic anomaly detection aligns with fault/surveillance monitoring |

### Org-Specific

| TSiSIP Capability | Rationale |
|---|---|
| OCP Operator Control Panel | Custom operator UI; no standard TM Forum equivalent at this granularity |
| Multi-tenant SIP edge proxy with topology hiding | Specific to TSiSIP's security architecture |
| Docker-image-first delivery | Deployment methodology, not a TM Forum process |

### Missing (Reference processes with no direct capability)

| TM Forum Process | Status | Clarification Needed |
|---|---|---|
| Billing & Revenue Management | Missing | Handled by external billing system? |
| Product Catalog Management | Missing | Tenant plans managed ad-hoc? |
| Workforce Management | Missing | N/A for infrastructure platform |
| Partner Relationship Management | Missing | Interconnect partners managed manually? |
| Customer Experience Management | Missing | No NOC/SOC integration visible |
| Policy Management | Partial | Rate limiting in BC-005 covers some policy; broader QoS policy absent |
