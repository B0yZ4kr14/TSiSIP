# Architect Report — TSiSIP

*Generated: 2026-05-25T02:27:41.398251Z*

---

## System Overview
*Sources: [context.json](../context.json)*

- **Primary languages**: C (OpenSIPS), Python (scripts/tests), PHP (OCP), Shell, JavaScript (build)
- **Architecture**: Microservices (Docker Compose — 15+ services)
- **Build systems**: Docker, Docker Compose, Make, Node.js (OCP theme)
- **CI**: GitHub Actions
- **DB**: PostgreSQL (db_internal network)
- **Code volume**: ~732 SQL lines, ~5000+ Python lines (including venv), ~1000+ shell/C config

---

## Capability Topology
*Sources: [l1-capabilities.md](../discovery/l1-capabilities.md) · [analysis.md](../discovery/analysis.md)*

| ID | Capability | Cohesion | Coupling | Boundary | LOC (est) |
|---|---|---|---|---|---|
| BC-001 | SIP Edge Proxy | HIGH | MEDIUM | CLEAR | ~400 (cfg + Dockerfile) |
| BC-002 | Media Relay | HIGH | LOW | CLEAR | ~100 (Dockerfile) |
| BC-003 | PBX Backend | HIGH | LOW | CLEAR | ~100 (Dockerfile) |
| BC-004 | Tenant & Subscriber Management | HIGH | MEDIUM | CLEAR | ~400 (SQL schema) |
| BC-005 | SIP Trunk Management | HIGH | MEDIUM | CLEAR | ~300 (SQL schema) |
| BC-006 | Anomaly Detection | HIGH | LOW | CLEAR | ~200 (Python) |

---

## Coupling Analysis

### BC-001 (SIP Edge Proxy) — MEDIUM coupling
- Reads from BC-004 (tenants, subscribers) via PostgreSQL
- Controls BC-002 (RTPengine) via internal control socket
- Forwards to BC-003 (Asterisk) via sip_internal
- **Mitigation**: Clean network segmentation reduces coupling surface.

### BC-004 (Tenant & Subscriber Management) — MEDIUM coupling
- Read by BC-001 at runtime (every SIP request)
- Read by OCP delivery channel for operator UI
- **Risk**: High read frequency on db_internal; no read replica configured.

### BC-005 (SIP Trunk Management) — MEDIUM coupling
- Read by BC-001 for outbound routing
- Health logs read by BC-006 for anomaly detection
- **Risk**: Shared dispatcher table with BC-001 routing logic.

---

## Bounded Context Analysis
*Sources: [domain-model.md](../discovery/domain-model.md)*

| Context | Capabilities | Internal Cohesion | External Coupling |
|---|---|---|---|
| Signaling Plane | BC-001, BC-002, BC-006 | HIGH | LOW (read-only to Management) |
| Application Plane | BC-003 | HIGH | LOW (receives from Signaling) |
| Management Plane | BC-004, BC-005 | HIGH | MEDIUM (read by Signaling) |

**Recommendation**: Keep current context boundaries. Do not merge Management Plane into Signaling Plane — the read-only runtime dependency is healthy separation.

---

## Decomposition Options

1. **Extract BC-003 first** (lowest shared entity count = 0)
   - Asterisk is already a separate container with no shared DB tables.
   - Feasibility: HIGH

2. **Extract BC-002 second** (shared only control socket with BC-001)
   - RTPengine is stateless media relay.
   - Feasibility: HIGH

3. **Extract BC-006 third** (reads only metrics, no shared entities)
   - Anomaly detector is independent.
   - Feasibility: HIGH

4. **Split Management Plane** (BC-004 vs BC-005)
   - Both share PostgreSQL but have distinct schemas.
   - Feasibility: MEDIUM — would require DB-per-service or schema isolation.

---

## Modernisation Positioning
*Metric-level rationale*

| Capability | Position | Drivers |
|---|---|---|
| BC-001 | Retain | Coupling MEDIUM but boundaries CLEAR; config-driven, not code-heavy |
| BC-002 | Retain | LOW coupling, CLEAR boundary; standard component |
| BC-003 | Retain | LOW coupling; external sourced (Asterisk) |
| BC-004 | Extend | MEDIUM coupling + LOW coverage → add migration tests |
| BC-005 | Extend | MEDIUM coupling + HIGH change velocity → add load tests |
| BC-006 | Evaluate | LOW coupling but untested accuracy; needs ML vs rules decision |

---

## Industry Blueprint Gaps
*Sources: [blueprint-comparison.md](../discovery/blueprint-comparison.md)*

| Gap | Type | Recommendation |
|---|---|---|
| Billing & Revenue Management | Externalized? | Verify if external billing system handles this |
| Product Catalog Management | Missing | Consider formalizing tenant plans as products |
| Policy Management | Partial | Expand rate limiting into broader QoS policy framework |

---

## Code Coverage & Orphan Zones

- **Mapped files**: 57 / 62 significant files (92%)
- **Orphans**: docs/, reports/, specs/, commands/, plans/ (8%)
- **Architectural risk**: LOW — orphans are documentation and design artifacts, not runtime code.

---

## Security Risk Overlay
*Pending `/assess` — no security composite or unified ranking available.*

---

## QA Risk Overlay
*Sources: [qa-context.json](../qa/qa-context.json)*

| Capability | Coverage | Automation | Testability | Posture |
|---|---|---|---|---|
| BC-001 | proxy LOW | partial | good | needs-work |
| BC-002 | proxy LOW | none | good | high-risk |
| BC-003 | proxy LOW | none | good | high-risk |
| BC-004 | proxy LOW | partial | good | needs-work |
| BC-005 | proxy LOW | partial | good | needs-work |
| BC-006 | proxy LOW | none | good | high-risk |

**Release readiness**: Not production-ready for automated release gating. Manual validation required for each deployment.

---

## Unified Risk Map
*Pending `/assess` — no unified ranking available.*
