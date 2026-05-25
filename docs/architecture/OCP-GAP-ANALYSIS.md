# OCP Gap Analysis: TSiSIP vs Official OpenSIPS Control Panel v9

> **Date**: 2026-05-25
> **Reference**: https://controlpanel.opensips.org/documentation.php
> **OCP Version Class**: 9 (9.3.2 – 9.3.6)

---

## Executive Summary

The official OpenSIPS Control Panel (OCP) v9 provides approximately 28 modules/tools for provisioning, monitoring, and managing OpenSIPS. The TSiSIP OCP currently implements approximately 15 modules with full TSiSIP branding, role-based access control, and audit logging. This document catalogs the gap for roadmap planning.

---

## Implemented Modules

| Module | File | Status | Notes |
|--------|------|--------|-------|
| Dashboard | dashboard.php | Done | D3.js charts, widget framework |
| SIP Subscribers | subscribers.php | Done | CRUD + HA1 generation |
| Dispatcher | dispatcher.php | Done | Load-based routing, health probes |
| Dialplan | dialplan.php | Done | Full rule management |
| Domains | domains.php | Done | Domain module provisioning |
| CDR Viewer | cdr-viewer.php | Done | CDR search and filtering |
| Dialog | dialog.php | Done | Active call monitoring |
| RTPEngine | rtpengine.php | Done | Instance management |
| TLS Management | tls-management.php | Done | Certificate handling |
| MI Commands | mi-commands.php | Done | OpenSIPS MI interface |
| Statistics | statistics.php | Done | D3.js real-time charts |
| Addresses | address.php | Done | Permissions module (IP-based) |
| Userblacklist | userblacklist.php | Done | Per-user blacklist |
| Audit Log | audit-log.php | Done | SHA-256 chained audit trail |
| Tenants | tenants.php | Done | Multi-tenant management |
| Header Routing | header-routing.php | Done | Feature 002 routing rules |
| Trunk Providers | trunk-providers.php | Done | Outbound trunk management |
| Trunk DIDs | trunk-dids.php | Done | Inbound DID mapping |
| Trunk Status | trunk-status.php | Done | Health monitoring |
| Wiki | wiki/index.php | Done | Markdown-based documentation |
| Admin Users | users.php | Done | OCP user management |

---

## Missing Modules (Gap)

| Module | OCP Doc | Priority | TSiSIP Relevance |
|--------|---------|----------|------------------|
| Aliases | alias_management.html | Medium | SIP alias provisioning |
| Groups | group_management.html | Medium | Group-based ACL for subscribers |
| Call Center | callcenter.html | Low | Call queue management |
| Clusterer | clusterer.html | Medium | High-availability clustering |
| Config Table | config.html | High | Runtime config via DB (9.3.5+) |
| Dynamic Routing | drouting.html | High | LCR / carrier routing |
| Load Balancer | loadbalancer.html | Medium | Alternative to dispatcher |
| Keepalived | keepalived.html | Low | HA failover daemon |
| Monit | monit.html | Low | External monitoring integration |
| RTPProxy | rtpproxy.html | Low | Legacy RTP proxy |
| SIPtrace | siptrace.html | Medium | SIP packet capture viewer |
| Sockets Management | sockets_mgm.html | High | Dynamic socket provisioning (9.3.6+) |
| Status Report | status_report.html | Medium | OpenSIPS status identifiers (9.3.3+) |
| SMPP Gateway | smpp.html | Low | SMS gateway provisioning |
| TViewer | tviewer.html | High | Generic table provisioning framework |
| UAC Registrant | uac_registant.html | Medium | Client registrations (partial: trunk-providers) |

---

## Architectural Differences

| Aspect | Official OCP v9 | TSiSIP OCP |
|--------|-----------------|------------|
| Branding | Generic OpenSIPS | TSiSIP premium cold-tone metallic blue |
| Multi-box | Yes (boxes/systems) | Single-box (containerized) |
| Auth | Simple admin table | Role hierarchy + bcrypt + audit logging |
| i18n | English base | EN / ES / PT |
| Charts | Basic | D3.js interactive with SRTP/Trunk analytics |
| Wiki | None (external docs) | Embedded markdown wiki |

---

## Roadmap Recommendations

1. **Phase 1 (Immediate)**: Config Table, Dynamic Routing, Sockets Management — core routing capabilities
2. **Phase 2 (Short-term)**: Aliases, Groups, UAC Registrant — subscriber management completeness
3. **Phase 3 (Medium-term)**: Clusterer, SIPtrace, Status Report — operations and observability
4. **Phase 4 (Long-term)**: Call Center, Load Balancer, SMPP — advanced features based on demand
