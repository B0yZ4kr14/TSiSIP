# Feature Specification: TSiSIP Control Panel — Full OCP v9.3.6 Parity

## Overview

**Feature**: TSiSIP Control Panel — Full OCP v9.3.6 Parity  
**Short name**: tsisip-ocp-full-parity  
**Created**: 2026-05-17  
**Status**: In Progress  
**Last Updated**: 2026-05-25  

### Context

The official OpenSIPS Control Panel (OCP) v9.3.6 provides **32 modules/tools** for provisioning, operating, and monitoring OpenSIPS servers. The TSiSIP Control Panel currently implements approximately **21 modules** with TSiSIP premium branding, role-based access control, audit logging, and multi-tenant support.

This feature delivers **100% functional parity** with the official OCP v9.3.6 module set while preserving all TSiSIP differentiators: premium metallic-blue branding, multi-tenant architecture, SHA-256 chained audit logging, and the embedded markdown wiki.

### Objective

Implement all missing OCP v9.3.6 modules so that TSiSIP reflects **every configuration capability** present in the upstream Control Panel, with zero functional gaps. The implementation must:

- Maintain the existing TSiSIP corporate identity (logo, palette, typography, responsive behavior)
- Preserve the existing role hierarchy (`readonly` < `user` < `assistant` < `dentist` < `devops` < `admin`)
- Preserve the existing audit log (SHA-256 hash-chained, append-only)
- Preserve the existing multi-tenant architecture
- Preserve the embedded markdown wiki at `/TSiSIP/wiki/`
- Follow the existing PHP-native server-rendered architecture (no SPA conversion)
- Align all database schemas with the official OCP v9.3.6 PostgreSQL baseline

---

## Gap Analysis: TSiSIP vs Official OCP v9.3.6

### Implemented Modules (21 / 32)

| # | Module | File | Status | OCP Doc |
|---|--------|------|--------|---------|
| 1 | **Dashboard** | `dashboard.php` | Done | `dashboard.html` |
| 2 | **SIP Subscribers** | `subscribers.php` | Done | `user_management.html` |
| 3 | **Dispatcher** | `dispatcher.php` | Done | `dispatcher.html` |
| 4 | **Dialplan** | `dialplan.php` | Done | `dialplan.html` |
| 5 | **Domains** | `domains.php` | Done | `domains.html` |
| 6 | **CDR Viewer** | `cdr-viewer.php` | Done | `cdrviewer.html` |
| 7 | **Dialog** | `dialog.php` | Done | `dialog.html` |
| 8 | **RTPEngine** | `rtpengine.php` | Done | `rtpengine.html` |
| 9 | **TLS Management** | `tls-management.php` | Done | `tls_management.html` |
| 10 | **MI Commands** | `mi-commands.php` | Done | `mi.html` |
| 11 | **Statistics Monitor** | `statistics.php` | Done | `smonitor.html` |
| 12 | **Addresses** | `address.php` | Done | `addresses.html` |
| 13 | **Userblacklist** | `userblacklist.php` | Done | — |
| 14 | **Audit Log** | `audit-log.php` | Done | — |
| 15 | **Tenants** | `tenants.php` | Done | — |
| 16 | **Header Routing** | `header-routing.php` | Done | — |
| 17 | **Trunk Providers** | `trunk-providers.php` | Done | — |
| 18 | **Trunk DIDs** | `trunk-dids.php` | Done | — |
| 19 | **Trunk Status** | `trunk-status.php` | Done | — |
| 20 | **Wiki** | `wiki/index.php` | Done | — |
| 21 | **Admin Users** | `users.php` | Done | `admins.html` |

### Missing Modules (11 / 32)

| # | Module | Priority | OCP Doc | Description |
|---|--------|----------|---------|-------------|
| 22 | **Aliases** | High | `alias_management.html` | SIP alias provisioning for subscribers |
| 23 | **Groups** | High | `group_management.html` | Group-based ACL for SIP subscribers |
| 24 | **Call Center** | Medium | `callcenter.html` | Call queue, agent, and flow management |
| 25 | **Clusterer** | Medium | `clusterer.html` | OpenSIPS built-in clustering provisioning |
| 26 | **Config Table** | High | `config.html` | Runtime config via DB table (9.3.5+) |
| 27 | **Dynamic Routing** | High | `drouting.html` | LCR / carrier routing with gateways and rules |
| 28 | **Load Balancer** | Medium | `loadbalancer.html` | Alternative load balancing to dispatcher |
| 29 | **Keepalived** | Low | `keepalived.html` | HA failover daemon monitoring (9.3.3+) |
| 30 | **Monit** | Low | `monit.html` | External Monit monitoring service integration |
| 31 | **RTPProxy** | Low | `rtpproxy.html` | Legacy RTP proxy instance management |
| 32 | **SIPtrace** | Medium | `siptrace.html` | SIP packet capture viewer |
| 33 | **Sockets Management** | High | `sockets_mgm.html` | Dynamic socket provisioning via DB (9.3.6+) |
| 34 | **Status Report** | Medium | `status_report.html` | OpenSIPS status identifiers (9.3.3+) |
| 35 | **SMPP Gateway** | Low | `smpp.html` | SMS gateway / SMSC provisioning |
| 36 | **TViewer** | High | `tviewer.html` | Generic table provisioning framework |
| 37 | **UAC Registrant** | High | `uac_registant.html` | Client registration provisioning |

> **Note**: "Priority" is derived from TSiSIP operational relevance. Config Table, Dynamic Routing, Sockets Management, and TViewer are **core routing/infrastructure** capabilities. Aliases, Groups, and UAC Registrant complete the **subscriber management** story. Clusterer, SIPtrace, and Status Report are **operations/observability** enablers.

---

## Functional Requirements

### FR-PARITY-001: Aliases Management
**Description**: Provide full CRUD for SIP aliases (db aliases table) linked to subscriber accounts.  
**Acceptance Criteria**:
- List aliases with pagination, search by username/alias
- Create alias linked to an existing subscriber
- Edit alias destination
- Delete alias with audit logging
- Role-aware: `readonly` sees list only; `user`+ can CRUD

### FR-PARITY-002: Groups Management
**Description**: Manage group-based permissions for SIP subscribers (db group table).  
**Acceptance Criteria**:
- List groups with member count
- Create/edit/delete groups
- Add/remove subscribers from groups
- Role-aware: `admin` and `devops` only for group mutations

### FR-PARITY-003: Call Center
**Description**: Provision and manage Call Center module flows, agents, and calls.  
**Acceptance Criteria**:
- CRUD for call flows (CC flows table)
- CRUD for agents (CC agents table)
- Live call queue monitor (read-only)
- Role-aware: `assistant`+ can configure; all roles can monitor

### FR-PARITY-004: Clusterer
**Description**: Provision OpenSIPS built-in Clusterer module for high-availability.  
**Acceptance Criteria**:
- CRUD for cluster nodes (clusterer table)
- Visual topology view
- Health status per node
- Role-aware: `devops` and `admin` only

### FR-PARITY-005: Config Table
**Description**: Runtime configuration via the `config` table (introduced in OCP 9.3.5 / OpenSIPS 3.6).  
**Acceptance Criteria**:
- CRUD for config key/value pairs with type validation
- Grouping by config category
- Immediate effect indicator (requires OpenSIPS reload)
- Role-aware: `devops` and `admin` only

### FR-PARITY-006: Dynamic Routing
**Description**: LCR / carrier routing via routing rules, carriers, and gateways.  
**Acceptance Criteria**:
- CRUD for gateways (dr_gateways table)
- CRUD for carriers (dr_carriers table)
- CRUD for routing rules (dr_rules table)
- Rule priority ordering with drag/drop or numeric priority
- Role-aware: `devops` and `admin` for mutations; all roles can view

### FR-PARITY-007: Load Balancer
**Description**: Alternative to dispatcher for load balancing destinations.  
**Acceptance Criteria**:
- CRUD for load balancer destinations (load_balancer table)
- Enable/disable destinations
- Real-time utilization view
- Role-aware: `devops` and `admin` for mutations

### FR-PARITY-008: Keepalived
**Description**: Interface for monitoring and switching Keepalived daemon (9.3.3+).  
**Acceptance Criteria**:
- Status overview of keepalived instances
- Manual failover trigger (admin only)
- Config view (read-only)
- Role-aware: `devops` and `admin`

### FR-PARITY-009: Monit
**Description**: Integration with Monit monitoring service.  
**Acceptance Criteria**:
- Display Monit service status via API/polling
- Alert history view
- Role-aware: all roles can view; `devops`+ can acknowledge alerts

### FR-PARITY-010: RTPProxy
**Description**: Manage RTPProxy instances used by OpenSIPS.  
**Acceptance Criteria**:
- CRUD for RTPProxy instances (rtpproxy_sockets table)
- Enable/disable instances
- Role-aware: `devops` and `admin`

### FR-PARITY-011: SIPtrace
**Description**: Viewer for SIP data captured via the siptrace module.  
**Acceptance Criteria**:
- Search/filter SIP traces by call-id, from, to, method
- Expand trace to view full message flow
- Export traces to PCAP or text
- Role-aware: all roles can view; `admin` can purge old traces

### FR-PARITY-012: Sockets Management
**Description**: Provision OpenSIPS dynamic sockets through database (9.3.6+).  
**Acceptance Criteria**:
- CRUD for socket definitions (sockets table)
- Validation of socket address format (proto:ip:port)
- Active/inactive toggle
- Role-aware: `devops` and `admin`

### FR-PARITY-013: Status Report
**Description**: Access reports provided by OpenSIPS Status-Report identifiers (9.3.3+).  
**Acceptance Criteria**:
- List all status report identifiers
- View detailed report per identifier
- Filter by severity (error, warning, info)
- Role-aware: all roles can view

### FR-PARITY-014: SMPP Gateway
**Description**: Provisioning of SMS Centers for OpenSIPS SMPP Gateway.  
**Acceptance Criteria**:
- CRUD for SMSC definitions (smpp table)
- Enable/disable gateways
- Message count statistics
- Role-aware: `devops` and `admin`

### FR-PARITY-015: TViewer
**Description**: Generic framework to provision arbitrary database tables.  
**Acceptance Criteria**:
- Config-driven table viewer (define table, columns, types via config)
- Full CRUD for configured tables
- Search and pagination
- Role-aware: configurable per table

### FR-PARITY-016: UAC Registrant
**Description**: Client registration provisioning for OpenSIPS UAC module.  
**Acceptance Criteria**:
- CRUD for UAC registrations (uacreg table)
- Enable/disable registrations
- Registration status view (last register, expiry)
- Role-aware: `devops` and `admin`

---

## Navigation & Information Architecture

The OCP sidebar navigation must be updated to include all 32 modules in a logically grouped structure:

```
Dashboard
├── Overview

SIP Users
├── Subscribers
├── Aliases        [NEW]
├── Groups         [NEW]

System
├── Addresses
├── Call Center    [NEW]
├── CDR Viewer
├── Clusterer      [NEW]
├── Config Table   [NEW]
├── Dialog
├── Dialplan
├── Dispatcher
├── Domains
├── Dynamic Routing [NEW]
├── Keepalived     [NEW]
├── Load Balancer  [NEW]
├── MI Commands
├── Monit          [NEW]
├── RTPEngine
├── RTPProxy       [NEW]
├── SIPtrace       [NEW]
├── Sockets Mgmt   [NEW]
├── Statistics
├── Status Report  [NEW]
├── TLS Management
├── UAC Registrant [NEW]

Trunking (TSiSIP-specific)
├── Providers
├── DIDs
├── Status

Administration
├── Tenants
├── Header Routing
├── Admin Users
├── Audit Log
├── Wiki
```

---

## Database Schema Requirements

Each missing module requires PostgreSQL tables aligned with the official OpenSIPS 3.6 LTS module schemas. Key tables to create:

| Module | Table(s) | OpenSIPS Module |
|--------|----------|-----------------|
| Aliases | `aliases` | `alias_db` |
| Groups | `grp` | `group` |
| Call Center | `cc_flows`, `cc_agents`, `cc_calls` | `call_center` |
| Clusterer | `clusterer` | `clusterer` |
| Config Table | `config` | `cfgutils` / `config` |
| Dynamic Routing | `dr_gateways`, `dr_rules`, `dr_carriers`, `dr_groups` | `drouting` |
| Load Balancer | `load_balancer` | `load_balancer` |
| RTPProxy | `rtpproxy_sockets` | `rtpproxy` |
| SIPtrace | `sip_trace` | `siptrace` |
| Sockets Mgmt | `sockets` | `proto_*` / `core` |
| Status Report | `status_report` | `status_report` |
| SMPP Gateway | `smpp` | `smpp` |
| TViewer | Config-driven | N/A |
| UAC Registrant | `uacreg` | `uac_registrant` |

All tables must use lowercase snake_case identifiers, proper indexing, and be initialized via `db/init/` migration scripts.

---

## Success Criteria

| ID | Criterion | Measurement | Target |
|---|---|---|---|
| SC-PARITY-001 | Module completeness | Percentage of OCP v9.3.6 modules implemented | 100% (32/32) |
| SC-PARITY-002 | Brand consistency | Modules displaying TSiSIP branding | 100% of new modules |
| SC-PARITY-003 | Role-aware access | Modules respecting role hierarchy | 100% of new modules |
| SC-PARITY-004 | Audit coverage | Audit log entries for mutations | 100% of write operations |
| SC-PARITY-005 | Schema alignment | Tables matching OpenSIPS 3.6 stock schema | 100% of new tables |
| SC-PARITY-006 | Navigation completeness | All modules accessible from sidebar | 100% |
| SC-PARITY-007 | Wiki documentation | Each new module documented in wiki | 100% |
| SC-PARITY-008 | External routing | `https://tsiapp.io/TSiSIP/*` serves all modules | Pass |
| SC-PARITY-009 | Wiki routing | `https://tsiapp.io/TSiSIP/wiki/*` serves documentation | Pass |

---

## Dependencies

- OpenSIPS 3.6 LTS with all relevant modules compiled (`alias_db`, `group`, `call_center`, `clusterer`, `cfgutils`, `drouting`, `load_balancer`, `rtpproxy`, `siptrace`, `status_report`, `smpp`, `uac_registrant`)
- PostgreSQL 16 with tsisip-extensions schema baseline
- OCP v9 PHP-native view architecture (existing)
- TSiSIP theme layer (CSS, SVG, i18n) — existing

---

## Risks

| Risk | Impact | Likelihood | Mitigation |
|---|---|---|---|
| Database schema drift from OpenSIPS 3.6 stock | High | Medium | Generate stock schema first, then ALTER TABLE for TSiSIP extensions; validate against `opensipsdbctl create` output |
| Module interdependencies (e.g., Dynamic Routing depends on Domains) | Medium | High | Implement in dependency order: Domains → Dynamic Routing; Subscribers → Aliases → Groups |
| Role hierarchy complexity with 6 roles | Medium | Medium | Use existing `role-nav.php` pattern; add role checks to each new module controller |
| Audit log volume with full CRUD parity | Medium | Medium | Existing SHA-256 chain handles volume; monitor table size and implement retention policy |
| Performance of generic TViewer with large tables | High | Low | Implement server-side pagination (100 rows/page); add indexes on all filterable columns |

---

## Notes

### Upstream Reference

All module specifications are derived from the official OCP v9 documentation and screenshots:
- https://controlpanel.opensips.org/documentation.php
- https://controlpanel.opensips.org/screenshots.php

### TSiSIP Brand Preservation

Every new module MUST:
1. Include `require_once __DIR__ . '/common/config.php';`
2. Include `require_once __DIR__ . '/common/header.php';` (or wiki-header for wiki pages)
3. Use existing `tsisip_asset()` helper for cache-busted assets
4. Apply `data-tsisip-role` attribute for CSS role-aware density
5. Log mutations via existing audit proxy

### Reverse Proxy Configuration

External access is via `https://tsiapp.io/TSiSIP/` (panel) and `https://tsiapp.io/TSiSIP/wiki/` (wiki).  
Nginx upstream: `tsisip_ocp` → `172.18.0.5:80` (container IP on VPS).  
`proxy_redirect` rewrites backend redirects to preserve the `/TSiSIP/` prefix and force HTTPS.
