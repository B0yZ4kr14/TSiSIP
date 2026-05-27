# Feature Specification: TSiSIP OCP Frontend — 100% OpenSIPS 3.6 MI Parity

## Overview

**Feature**: TSiSIP OCP Frontend — 100% OpenSIPS 3.6 MI Parity
**Short name**: tsisip-ocp-mi-full-parity
**Created**: 2026-05-27
**Status**: Complete
**Last Updated**: 2026-05-27

### Context

The TSiSIP OCP frontend currently implements 37+ PHP pages covering provisioning (DB CRUD) and partial MI HTTP read-only monitoring. However, a comprehensive gap analysis reveals that many pages lack runtime MI command integration (read-only DB views without reload/refresh actions), and several OpenSIPS 3.6 modules have no dedicated UI page at all.

This feature delivers 100% functional parity with every identifiable and validatable OpenSIPS 3.6 MI HTTP capability, organized into five implementation waves. The goal is that any OpenSIPS operator can view, control, and validate every runtime aspect of the OpenSIPS instance through the TSiSIP OCP web interface.

### Objective

1. Add MI actions to all existing DB-only pages: Every provisioning page must have one-click reload/refresh/enable/disable actions where applicable.
2. Create dedicated UI pages for all missing OpenSIPS 3.6 modules: Every module with MI HTTP commands must have a dedicated page.
3. Expose runtime inspection pages: Memory, processes, TCP connections, blacklists, USRLoc.
4. Expand MI command whitelist: From 6 to 40+ commands for the generic MI command executor.
5. Validate every page: Every new or modified page must have an integration test.

---

## Gap Analysis

### Existing Pages Missing MI Actions

| Page | Module | Missing Action | MI Command |
|------|--------|----------------|------------|
| address.php | permissions | Reload button | address_reload |
| dialplan.php | dialplan | Reload button | dialplan_reload |
| domains.php | domain | Reload button | domain_reload |
| dynamic-routing.php | drouting | Reload + GW status | dr_reload, dr_gw_status |
| dialog.php | dialog | Terminate button | dlg_end_dlg |
| siptrace.php | siptrace | Start/stop toggle | sip_trace_start, sip_trace_stop |
| load-balancer.php | load_balancer | Status toggle + reload | lb_status, lb_reload |
| clusterer.php | clusterer | Set state + ping | clusterer_set_state, clusterer_ping |
| rtpengine.php | rtpengine | Enable/disable | rtpengine_enable, rtpengine_disable |
| uac-registrant.php | uac_registrant | Refresh + enable/disable | uac_reg_refresh, uac_reg_disable, uac_reg_enable |
| statistics.php | statistics | Reset button | reset_statistics |
| call-center.php | call_center | Agent login/logout, flow status | cc_agent_login, cc_agent_logout, cc_flow_status |
| subscribers.php | usrloc | Live USRLoc dump view | ul_dump |

### Missing Module Pages

| Module | Priority | MI Commands | Page Name |
|--------|----------|-------------|-----------|
| pike | P0 | pike_list, pike_check_ip | pike-monitor.php |
| ratelimit | P0 | ratelimit_status, ratelimit_reset | ratelimit.php |
| htable | P1 | htable_dump, htable_flush | hash-tables.php |
| nathelper | P1 | nh_show_sockets, nh_show_ping | nat-helper.php |
| tcp_mgm | P1 | tcp_list | tcp-connections.php |
| topology_hiding | P1 | Aggregate from dlg_list | topology-hiding.php |
| presence | P2 | pres_refresh_watchers, pua_refresh | presence.php |
| avpops | P2 | avp_db_query (read-only) | avp-inspector.php |
| core / global | P1 | ps, list_timers, list_blacklists, version, which | processes.php, timers.php, blacklists.php, version.php |

---

## Functional Requirements

### FR-029-001: Address Reload
Add "Reload Address Table" button to address.php that calls address_reload via MI. Button visible to devops and admin roles only. Success/error toast notification. Audit log entry.

### FR-029-002: Dialplan Reload
Add "Reload Dialplan" button to dialplan.php that calls dialplan_reload via MI.

### FR-029-003: Domain Reload
Add "Reload Domains" button to domains.php that calls domain_reload via MI.

### FR-029-004: Dynamic Routing Reload & Gateway Status
Add reload and gateway status to dynamic-routing.php. "Reload DRouting" button calls dr_reload. Gateway status table calls dr_gw_status per gateway. Visual indicator (up/down/unknown).

### FR-029-005: Dialog Termination
Add "Terminate" button to each row in dialog.php dialog list. Confirmation dialog. Calls dlg_end_dlg with call-id and from-tag. Row removed or status updated after success.

### FR-029-006: SIPtrace Capture Controls
Add start/stop capture toggle to siptrace.php. Toggle reads current status via sip_trace_status. Start calls sip_trace_start; stop calls sip_trace_stop.

### FR-029-007: Load Balancer Controls
Add status toggle and reload to load-balancer.php. Per-row enable/disable toggle calls lb_status. "Reload Load Balancer" button calls lb_reload.

### FR-029-008: Clusterer Actions
Add set state and ping buttons to clusterer.php. "Set State" dropdown per node (active/backup/down). "Ping Node" button per node calls clusterer_ping.

### FR-029-009: RTPEngine Enable/Disable
Add enable/disable per-instance in rtpengine.php. Toggle per RTPengine instance. Calls rtpengine_enable / rtpengine_disable.

### FR-029-010: UAC Registrant Live Actions
Add refresh, enable, disable to uac-registrant.php. "Refresh Registration" button per row calls uac_reg_refresh. Enable/disable toggle per row.

### FR-029-011: Statistics Reset
Add "Reset Statistics" button to statistics.php. Confirmation dialog (destructive action). Calls reset_statistics for selected module or all.

### FR-029-012: Call Center Agent Controls
Add login/logout controls to call-center.php. Per-agent login/logout toggle. Calls cc_agent_login / cc_agent_logout. Flow status indicator via cc_flow_status.

### FR-029-013: Memory Status Dashboard
Create memory-status.php showing pkg and shm memory usage. Calls get_statistics with pkg: and shm: prefixes. Visual progress bars for usage percentage. Alert if usage > 80%. Auto-refresh via SSE.

### FR-029-014: Pike Monitor
Create pike-monitor.php for rate-limiting and DoS visibility. Calls pike_list to show blocked IPs. "Check IP" search box calling pike_check_ip. Visual threat level indicator. Role: devops and admin.

### FR-029-015: Rate Limit Dashboard
Create ratelimit.php for per-tenant rate limiting. Calls ratelimit_status for all pipes. "Reset Pipe" button calling ratelimit_reset. Visual pipe utilization. Role: devops and admin.

### FR-029-016: Hash Table Inspector
Create hash-tables.php for runtime htable inspection. Calls htable_dump for selected table. "Flush Table" button calling htable_flush. Search/filter within table entries. Role: devops and admin.

### FR-029-017: NAT Helper Status
Create nat-helper.php for NAT keepalive visibility. Calls nh_show_sockets and nh_show_ping. Socket list with keepalive status. Ping statistics table. Role: all roles can view.

### FR-029-018: TCP Connection Manager
Create tcp-connections.php for TCP/TLS connection inspection. Calls tcp_list. Connection table with state, peer, local address. Filter by state. Role: devops and admin.

### FR-029-019: Topology Hiding Inspector
Create topology-hiding.php aggregating topology hidden dialogs. Calls dlg_list filtered for topology-hidden dialogs. Shows dialog to topology mapping. Role: all roles can view.

### FR-029-020: Process List
Create processes.php showing OpenSIPS worker processes. Calls ps MI command. Table with PID, type, description, memory usage. Visual indicator for crashed/restarted processes. Role: all roles can view.

### FR-029-021: USRLoc Live Dump
Create usrloc.php for live subscriber location view. Calls ul_dump for selected AoR. Shows registered contacts with expires, path, flags. Compare with DB subscriber count. Role: all roles can view.

### FR-029-022: Blacklists
Create blacklists.php for runtime blacklist inspection. Calls list_blacklists. Shows blacklisted IPs/subnets with reason. Role: all roles can view.

### FR-029-023: Version & Module Info
Create version.php showing OpenSIPS version and loaded modules. Calls version and which. Lists version, git hash, build flags. Lists all loaded modules. Role: all roles can view.

### FR-029-024: Timer Status
Create timers.php for internal timer inspection. Calls list_timers. Shows timer name, interval, last run, next run. Role: devops and admin.

### FR-029-025: Presence Dashboard
Create presence.php for presence server management. Calls pres_refresh_watchers. Shows active watchers and presentities. Role: devops and admin.

### FR-029-026: MI Command Whitelist Expansion
Expand mi-commands.php whitelist from 6 to 40+ commands. All commands from FR-029-001 through FR-029-025 available in whitelist. Categorized by module. Search/filter in whitelist UI. Role: devops and admin.

### FR-029-027: Navigation Update
Update sidebar navigation to include all new pages. New pages grouped logically. Role-aware visibility. Mobile responsive.

### FR-029-028: Integration Tests
Create integration tests for every new/modified page. One test per new page. One test per modified page action. All tests pass in Docker environment.

---

## Success Criteria

| ID | Criterion | Measurement | Target |
|---|---|---|---|
| SC-029-001 | MI action coverage | % of existing DB-only pages with MI actions | 100% |
| SC-029-002 | Missing module coverage | % of identified missing modules with dedicated page | 100% of P0-P2 |
| SC-029-003 | MI whitelist size | Number of whitelisted commands | >= 40 |
| SC-029-004 | Navigation completeness | All new pages accessible from sidebar | 100% |
| SC-029-005 | Test coverage | Integration tests for every new/modified page | 100% |
| SC-029-006 | Audit coverage | Audit log entries for all mutation actions | 100% |
| SC-029-007 | Role enforcement | All new actions respect role hierarchy | 100% |
| SC-029-008 | Mobile responsive | All new pages pass mobile viewport test | 100% |

---

## Dependencies

- OpenSIPS 3.6 LTS MI HTTP endpoint
- Existing mi-http.php wrapper with circuit breaker and cache
- Existing config.php authentication and authorization
- Existing theme/CSS custom properties
- Existing audit logging infrastructure

---

## Risks

| Risk | Impact | Likelihood | Mitigation |
|---|---|---|---|
| OpenSIPS module not compiled/loaded | High | Medium | Graceful fallback: show "Module not loaded" message; disable buttons |
| MI command returns error | Medium | High | Error toast with exact MI error message; log to audit |
| Page count explosion | Medium | Medium | Group related small features into composite pages |
| Performance of runtime queries | Medium | Medium | Cache TTL 5s; SSE instead of polling |
| Role misconfiguration | High | Low | Explicit requireRole() on every mutation endpoint |
