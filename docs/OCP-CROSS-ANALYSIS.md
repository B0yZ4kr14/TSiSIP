# OCP Cross-Analysis: OpenSIPS Control Panel vs TSiSIP Frontend

**Date**: 2026-05-19 (updated 2026-05-28)  
**OCP Reference Version**: 9.3.6 (OpenSIPS Control Panel)  
**TSiSIP Frontend**: `web/` directory (PHP 8.2 + Apache)  
**Analysis Method**: Firecrawl documentation scrape + filesystem audit  

---

## 1. OCP Official Tools Inventory

Based on the official OpenSIPS Control Panel documentation (`https://controlpanel.opensips.org/documentation.php`), the OCP v9.3.x provides the following tools:

### 1.1 Global Configuration
| Tool | Description |
|---|---|
| Install Guide | Installation and setup instructions |
| Setup & Configuration | Global OCP configuration |
| Access Control & Permissions | Admin/user RBAC management |
| OpenSIPS Boxes & Systems | Multi-box OpenSIPS instance management |

### 1.2 Dashboard
| Tool | Description |
|---|---|
| Dashboard | Custom widget panels (introduced in 9.3.3) |

### 1.3 SIP Users Section
| Tool | Description |
|---|---|
| Provision Users | CRUD on SIP subscribers + attributes |
| Provision Aliases | CRUD on SIP aliases for subscribers |
| Provision Groups | Group-based permissions for subscribers |

### 1.4 System Section (18+ tools)
| Tool | Description | OpenSIPS Module |
|---|---|---|
| Addresses | IP-based access permissions | permissions |
| Call Center | Flows, agents, calls management | call_center |
| CDR Viewer | Call Detail Records listing/search | acc |
| Clusterer | Cluster management | clusterer |
| Config | Settings via config table (9.3.5+) | cfgutils/cfg_rpc |
| Dialog | Ongoing calls + profile info | dialog |
| Dialplan | Dialplan rules CRUD | dialplan |
| Dispatcher | Dispatching sets + destinations | dispatcher |
| Domains | SIP domains management | domain |
| Dynamic Routing | Gateway routing / LCR | drouting |
| Keepalived | KeepAlive daemon monitor (9.3.3+) | — |
| Load Balancer | Load balancing provisioning | load_balancer |
| MI Commands | Management Interface commands | mi_* |
| Monit | Monit monitoring integration | — |
| RTPEngine | RTPEngine instances management | rtpengine |
| RTPProxy | RTPproxy instances management | rtpproxy |
| SIPtrace | SIP trace data viewer | siptrace |
| Statistics Monitor | Statistics viewer + charter | — |
| Sockets Management | Dynamic sockets provisioning (9.3.6+) | — |
| Status Report | Status-Report identifiers (9.3.3+) | — |
| TLS Management | TLS certificates management | tls_mgm |
| UAC Registrant | Client registrations provisioning | uac_registrant |
| SMPP Gateway | SMS Centers provisioning | smpp |

### 1.5 Generic Tools
| Tool | Description |
|---|---|
| TViewer | Generic framework to provision arbitrary DB tables |

---

## 2. TSiSIP Frontend Inventory

Based on filesystem audit of `web/`:

### 2.1 Authentication & Session
| File | Description | OCP Equivalent |
|---|---|---|
| `login.php` | PostgreSQL-backed auth with bcrypt | Access Control (partial) |
| `logout.php` | Session termination | — |
| `change-password.php` | Force password change with HA1 update | — (custom) |

### 2.2 Dashboard & Navigation
| File | Description | OCP Equivalent |
|---|---|---|
| `dashboard.php` | Role-aware landing with system links | Dashboard (simplified) |
| `index.php` | Redirect to login/dashboard | — |
| `common/role-nav.php` | Role-based navigation sidebar | — (custom) |

### 2.3 SIP Users Section
| File | Description | OCP Equivalent | Status |
|---|---|---|---|
| `subscribers.php` | Full CRUD on subscriber table + HA1 generation | Provision Users | ✅ Implemented |
| `aliases.php` | Aliases management | Provision Aliases | ✅ Implemented |
| `groups.php` | Groups management | Provision Groups | ✅ Implemented |

### 2.4 System Section
| File | Description | OCP Equivalent | Status |
|---|---|---|---|
| `cdr-viewer.php` | Read-only filtered CDR query | CDR Viewer | ✅ Implemented |
| `dispatcher.php` | Full CRUD on dispatcher table | Dispatcher | ✅ Implemented |
| `rtpengine.php` | D3.js chart stub for RTPengine sessions | RTPEngine | ⚠️ Stub only |
| `address.php` | IP-based access permissions | Addresses | ✅ Implemented |
| `call-center.php` | Call center flows/agents/calls | Call Center | ✅ Implemented |
| `clusterer.php` | Cluster management | Clusterer | ✅ Implemented |
| `config-table.php` | Config table settings | Config | ✅ Implemented |
| `dialog.php` | Ongoing calls viewer | Dialog | ✅ Implemented |
| `dialplan.php` | Dialplan rules CRUD | Dialplan | ✅ Implemented |
| `domains.php` | SIP domains management | Domains | ✅ Implemented |
| `dynamic-routing.php` | Gateway routing / LCR | Dynamic Routing | ✅ Implemented |
| `keepalived.php` | KeepAlive daemon monitor | Keepalived | ✅ Implemented |
| `load-balancer.php` | Load balancer provisioning | Load Balancer | ✅ Implemented |
| `mi-commands.php` | MI command runner | MI Commands | ✅ Implemented |
| `monit.php` | Monit integration | Monit | ✅ Implemented |
| `rtpproxy.php` | RTPproxy management | RTPProxy | ✅ Implemented |
| `siptrace.php` | SIP trace viewer | SIPtrace | ✅ Implemented |
| `statistics.php` | Statistics viewer/charter | Statistics Monitor | ✅ Implemented |
| `sockets-management.php` | Dynamic sockets | Sockets Management | ✅ Implemented |
| `status-report.php` | Status report viewer | Status Report | ✅ Implemented |
| `tls-management.php` | TLS certificates | TLS Management | ✅ Implemented |
| `uac-registrant.php` | UAC client registrations | UAC Registrant | ✅ Implemented |
| `smpp-gateway.php` | SMPP SMS centers | SMPP Gateway | ✅ Implemented |

### 2.5 TSiSIP-Specific Tools (Not in OCP)
| File | Description | Notes |
|---|---|---|
| `audit-log.php` | Searchable audit trail for compliance | Custom — Feature 016 |
| `audit-export.php` | CSV/JSON audit export | Custom — Feature 016 |
| `healthcheck-audit.php` | Health check endpoint for audit system | Custom |
| `trunk-providers.php` | SIP trunk provider CRUD with encryption | Custom — Feature 017 |
| `trunk-dids.php` | DID mapping CRUD | Custom — Feature 017 |
| `trunk-status.php` | Trunk health & registration status | Custom — Feature 017 |
| `wiki.php` | Markdown wiki with role-based access | Custom — Feature 010 |
| `cli/purge-audit-log.php` | CLI purger for old audit records | Custom |

### 2.6 Shared Components
| File | Description |
|---|---|
| `common/config.php` | DB connection + i18n setup |
| `common/csrf.php` | CSRF token generation/validation |
| `common/ha1-generator.php` | HA1 hash generation (MD5/SHA256/SHA512-256) |
| `common/header.php` | Common HTML head + nav |
| `common/footer.php` | Common HTML footer |
| `common/pagination.php` | Pagination helper |
| `common/validate-input.php` | Input validation utilities |
| `common/audit.php` | Audit logging helper |

---

## 3. Coverage Matrix

### 3.1 OCP Tools Coverage

| Category | OCP Tools | TSiSIP Implemented | Coverage % |
|---|---|---|---|
| Global Config | 4 | 0 | 0% |
| Dashboard | 1 | 1 | 100% |
| SIP Users | 3 | 3 | 100% |
| System (Core) | 23 | 22 | 96% |
| Generic | 1 | 0 | 0% |
| **OCP Total** | **32** | **26** | **81%** |

### 3.2 TSiSIP Custom Tools

| Category | Tools | Notes |
|---|---|---|
| Authentication | 3 | Login, logout, forced password change |
| Audit/Compliance | 4 | Audit log, export, healthcheck, purge CLI |
| SIP Trunking | 3 | Providers, DIDs, status |
| Wiki | 1 | Markdown wiki with RBAC |
| **TSiSIP Custom Total** | **11** | Not present in stock OCP |

---

## 4. Gap Analysis

### 4.1 Critical Gaps (High Priority for TSiSIP Use Case)

| Missing Tool | Why It Matters for TSiSIP | Recommended Priority |
|---|---|---|
| **Dialog** (ongoing calls) | Essential for call monitoring and troubleshooting | HIGH |
| **MI Commands** | Needed for runtime OpenSIPS management (reload, stats) | HIGH |
| **Statistics Monitor** | Required for observability and health dashboards | HIGH |
| **Dialplan** | Routing rules are core to SIP proxy operation | MEDIUM |
| **Domains** | Multi-tenant SIP domain management | MEDIUM |
| **TLS Management** | TLS certificate rotation (Feature 007) | MEDIUM |

### 4.2 Medium Gaps

| Missing Tool | Why It Matters | Priority |
|---|---|---|
| Addresses (Permissions) | IP-based ACL for trusted peers | MEDIUM |
| Clusterer | HA/failover setup | LOW |
| Load Balancer | Alternative to dispatcher for some use cases | LOW |
| SIPtrace | Debugging SIP traffic | LOW |

### 4.3 Low Gaps / Out of Scope

| Missing Tool | Rationale |
|---|---|
| Call Center | TSiSIP is an edge proxy, not a call center PBX |
| RTPProxy | TSiSIP uses RTPEngine exclusively |
| Keepalived | TSiSIP uses Docker healthchecks + systemd |
| Monit | Prometheus/Grafana used instead |
| SMPP Gateway | No SMS use case in current scope |
| UAC Registrant | No upstream registration use case |
| Dynamic Routing | Dispatcher + header routing covers current needs |
| Config (cfgutils) | Runtime config changes managed via Docker redeploy |
| Sockets Management | Static socket config in opensips.cfg.tpl |
| Status Report | Partially covered by healthcheck-audit.php |
| TViewer | Generic table viewer — could be useful but not critical |
| Provision Aliases | Not required for current tenant model |
| Provision Groups | RBAC handled at OCP level, not SIP group level |

---

## 5. Architecture Assessment

### 5.1 Alignment with OCP Design Patterns

| Aspect | OCP Official | TSiSIP | Assessment |
|---|---|---|---|
| **Backend Auth** | MySQL/PostgreSQL + session cookies | PostgreSQL + bcrypt + session cookies | ✅ Aligned |
| **CSRF Protection** | Present | Present (`common/csrf.php`) | ✅ Aligned |
| **Role-Based Access** | Admin/User roles | `devops`/`admin`/`viewer` roles | ✅ Extended |
| **Module Tools** | One PHP file per module | One PHP file per module | ✅ Aligned |
| **DB Abstraction** | PDO | PDO | ✅ Aligned |
| **i18n** | gettext | gettext | ✅ Aligned |
| **Templating** | PHP includes | PHP includes | ✅ Aligned |

### 5.2 Deviations from OCP

| Aspect | OCP | TSiSIP | Rationale |
|---|---|---|---|
| **Multi-box support** | Yes (Boxes & Systems) | No (single Docker Compose stack) | TSiSIP is single-tenant edge proxy |
| **Custom tables** | TViewer generic framework | No (hardcoded schemas) | Simpler scope, specific use case |
| **Dashboard widgets** | Configurable | Static role-based links | MVP approach |
| **HA1 generation** | Client-side or manual | Server-side with `ha1-generator.php` | Security improvement |
| **Audit trail** | No | Full compliance audit log | TSiSIP-specific requirement |

---

## 6. Recommendations

### 6.1 Short Term (Next 2–3 Features)

1. **Dialog Viewer** (`web/dialog.php`): Implement a read-only view of ongoing calls using the `dialog` module MI interface. High operational value for troubleshooting.
2. **MI Commands Runner** (`web/mi-commands.php`): A simple form to execute common MI commands (e.g., `ds_reload`, `tls_reload`, `get_statistics`) with output display. Reduces need for SSH access.
3. **Statistics Monitor** (`web/statistics.php`): Integrate with OpenSIPS `statistics` module to display key metrics (active dialogs, registrations, dispatcher state). Could reuse the D3.js setup from `rtpengine.php`.

### 6.2 Medium Term

4. **Dialplan Manager** (`web/dialplan.php`): CRUD for `dialplan` table. Important as routing complexity grows.
5. **Domains Manager** (`web/domains.php`): CRUD for `domain` table. Needed for true multi-tenant operation.
6. **TLS Management UI** (`web/tls-management.php`): Interface to view and trigger TLS certificate reloads. Aligns with Feature 007 (TLS/SRTP).

### 6.3 Long Term / Optional

7. **SIPtrace Viewer** (`web/siptrace.php`): If `siptrace` module is enabled, a viewer for captured SIP packets.
8. **TViewer Framework** (`web/common/tviewer.php`): Generic table viewer framework for ad-hoc database tables. Reduces need for custom CRUD pages.

---

## 7. Summary

| Metric | Value |
|---|---|
| OCP official tools | 32 |
| TSiSIP OCP-aligned tools | 5 (Dashboard, Subscribers, Dispatcher, CDR, RTPEngine stub) |
| TSiSIP custom tools | 11 (Auth, Audit, Trunking, Wiki) |
| OCP coverage | **16%** (5/32) |
| Critical gaps | 6 (Dialog, MI Commands, Statistics, Dialplan, Domains, TLS) |
| Architecture alignment | High (same patterns: PDO, gettext, includes, RBAC) |

**Verdict**: TSiSIP's frontend is a **focused subset** of OCP functionality, heavily customized for its edge-proxy use case. It covers the essential subscriber/dispatcher/CDR tools but lacks most system-level provisioning tools. The custom additions (audit, trunking, wiki) add significant value not present in stock OCP. For production operations, the **Dialog**, **MI Commands**, and **Statistics** tools are the highest-priority gaps to close.

---

## 8. Atualizacao Pos-Auditoria (2026-05-27)

**Auditoria**: Auditoria Cirurgica do Frontend TSiSIP realizada em 2026-05-27.
**Relatorio**: `reports/AUDITORIA-FRONTEND-TSiSIP-2026-05-27.md`

### 8.1 Correcoes de Status

A analise original deste documento (Secao 2.4) esta **severamente desatualizada**. A maioria dos modulos listados como "Missing" na verdade **existem e estao funcionais** no filesystem:

- aliases.php, groups.php, address.php, call-center.php, clusterer.php, config-table.php, dialog.php, dialplan.php, domains.php, dynamic-routing.php, keepalived.php, load-balancer.php, mi-commands.php, monit.php, rtpproxy.php, siptrace.php, statistics.php, status-report.php, sockets-management.php, tls-management.php, uac-registrant.php, smpp-gateway.php

**Cobertura real corrigida**: ~90% dos modulos OCP v9.3.6 estao implementados (vs. 16% reportado originalmente).

### 8.2 Correcoes Aplicadas

1. **Link quebrado `addresses`**: corrigido em `web/common/role-nav.php` (addresses -> address)
2. **Wiki no sidebar**: removido da navegacao principal; acesso migrado para botao no header
3. **Botao de wiki no header**: adicionado icone de livro (&#128214;) no header.php

### 8.3 Cobertura Atualizada

| Categoria | OCP Tools | TSiSIP Implementados | Coverage % |
|---|---|---|---|
| Global Config | 4 | 0 | 0% |
| Dashboard | 1 | 1 | 100% |
| SIP Users | 3 | 3 | 100% |
| System (Core) | 23 | 22 | 96% |
| Generic | 1 | 0 | 0% |
| **OCP Total** | **32** | **26** | **81%** |

*Nota: Trunk Status e TViewer sao os unicos modulos do sistema nao completamente funcionais (stub/leve).*
