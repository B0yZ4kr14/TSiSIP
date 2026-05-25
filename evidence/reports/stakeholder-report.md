# Stakeholder Report — TSiSIP

*Generated: 2026-05-25T02:27:41.398251Z*

---

## What This System Does
*Sources: [domain-model.md](../discovery/domain-model.md)*

TSiSIP is a Docker-image-first SIP edge-proxy platform built on OpenSIPS 3.6 LTS. It serves as the **only public entry point** for SIP signaling into a private, multi-tenant Asterisk PBX backend cluster. The platform handles authentication, dynamic routing, topology hiding, media relay, and security monitoring for real-time voice and video communications.

The system is designed for telecommunications service providers who need to expose SIP services to the public internet while keeping backend infrastructure completely isolated. All media traffic is relayed through RTPengine so backend PBX IP addresses never leak externally.

---

## Core Business Capabilities
*Sources: [domain-model.md](../discovery/domain-model.md) · [l1-capabilities.md](../discovery/l1-capabilities.md)*

| Capability | Signal | Rationale |
|---|---|---|
| SIP Edge Proxy | **Strong** | HIGH cohesion, CLEAR boundaries, public-facing auth and routing |
| Media Relay | **Strong** | Independent media plane, well-defined UDP port range |
| PBX Backend | **Strong** | Clean separation, private network isolation |
| Tenant & Subscriber Management | **Needs Attention** | Strong data model but proxy coverage is LOW; no staging env in CI |
| SIP Trunk Management | **Needs Attention** | Feature 006 is active; needs load testing and chaos testing |
| Anomaly Detection | **Needs Attention** | Low test coverage; no accuracy benchmarks |

*Risk signals are preliminary (discovery-only). Run `/assess` before commitment for full security and QA composite scoring.*

---

## System Health Overview

- **Codebase coverage**: Proxy coverage is LOW — tests target Docker containers and configuration, not source modules directly. 23 integration tests cover compose validation, SIP probes, and config checks.
- **Orphan code**: 8% (docs/, reports/, specs/, commands/, plans/) — documentation and design artifacts without clear runtime owners.
- **Dead code**: None detected.
- **E2E coverage**: Minimal — no end-to-end call flow tests against live Asterisk.

*Business risk*: Low test-to-source mapping means regressions in OpenSIPS config or Docker Compose may not be caught until deployment.

---

## Industry Alignment
*Sources: [blueprint-comparison.md](../discovery/blueprint-comparison.md)*

**Framework**: TM Forum (eTOM / SID)

| TSiSIP Capability | Alignment | Notes |
|---|---|---|
| SIP Edge Proxy | Aligned — RM&O Network Resource Management | Core network function |
| Media Relay | Aligned — Media Gateway Control | Standard telco component |
| PBX Backend | Aligned — Service Configuration & Activation | Voice service delivery |
| Tenant & Subscriber Management | Aligned — Customer Information Management | Direct mapping |
| SIP Trunk Management | Aligned — Connectivity Management | Trunk provisioning |
| Anomaly Detection | Aligned — Fault Monitoring | Security surveillance |

**Missing capabilities** (clarification needed):
- Billing & Revenue Management — handled externally?
- Product Catalog Management — tenant plans managed ad-hoc?
- Partner Relationship Management — interconnect partners manual?

---

## Key Findings

### Strengths
- **Strong network segmentation**: Three isolated Docker networks (sip_edge, sip_internal, db_internal) with zero host-published ports on backend services.
- **Defense in depth**: Digest auth + HA1 hashing + TLS + topology hiding + rate limiting + anomaly detection layered.
- **Infrastructure as code**: Ansible + Docker Compose + GitHub Actions for reproducible deployments.
- **Multi-tenant architecture**: Clean tenant-scoped routing with PostgreSQL-backed metadata.

### Concerns
- **Low proxy test coverage**: 0% proxy coverage because tests validate containers, not source modules. Integration tests exist but don't map to capability files.
- **No staging/prod CI**: GitHub Actions only targets dev. Staging and prod environments are declared but absent from CI pipelines.
- **Missing E2E call flow tests**: No automated end-to-end SIP call validation through Asterisk.
- **Anomaly detection untested**: No accuracy benchmarks or false-positive rate measurements.

---

## Modernisation Positioning

| Capability | Position | Rationale |
|---|---|---|
| SIP Edge Proxy | **Retain** | Strong, stable, well-configured. OpenSIPS 3.6 LTS is current. |
| Media Relay | **Retain** | Stable RTPengine. Kernel bypass not needed in containerized baseline. |
| PBX Backend | **Retain** | Asterisk is standard. Two-instance HA is appropriate. |
| Tenant & Subscriber Management | **Extend** | Add automated schema migration tests and HA1 regression suite. |
| SIP Trunk Management | **Extend** | Add load tests and circuit breaker chaos tests. |
| Anomaly Detection | **Evaluate** | Limited visibility into accuracy. Needs stakeholder input on ML vs rule-based approach. |

---

## Proposed Team Ownership
*Sources: [domain-model.md](../discovery/domain-model.md)*

| Squad | Capabilities |
|---|---|
| **Platform / SRE** | BC-001 SIP Edge Proxy, BC-002 Media Relay, BC-006 Anomaly Detection |
| **Voice Engineering** | BC-003 PBX Backend |
| **Operations / DevOps** | BC-004 Tenant & Subscriber Management, BC-005 SIP Trunk Management |

*Note*: BC-004 and BC-005 share PostgreSQL schema and OCP delivery channel. Consider a single "Management Plane" squad.
