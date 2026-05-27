# Implementation Plan: TSiSIP OCP Frontend — 100% OpenSIPS 3.6 MI Parity

## Wave 1: MI Actions on Existing Pages (2 hours)

### 1.1 Common AJAX Action Handler
- Create `web/common/mi-action.php`: Generic POST endpoint for MI mutation commands
- Validates CSRF token, role, and command whitelist
- Calls `miHttpCall()`, returns JSON
- Audit logs every action

### 1.2 Address Reload
- Modify `web/address.php`: Add reload button in header actions bar
- JS: POST to `mi-action.php` with `cmd=address_reload`

### 1.3 Dialplan Reload
- Modify `web/dialplan.php`: Add reload button
- JS: POST to `mi-action.php` with `cmd=dialplan_reload`

### 1.4 Domain Reload
- Modify `web/domains.php`: Add reload button
- JS: POST to `mi-action.php` with `cmd=domain_reload`

### 1.5 Dynamic Routing Reload & Gateway Status
- Modify `web/dynamic-routing.php`:
  - Add "Reload DRouting" button
  - Add gateway status section calling `dr_gw_status`
  - Visual up/down indicators

### 1.6 Dialog Termination
- Modify `web/dialog.php`:
  - Add "Terminate" button per dialog row
  - Confirmation modal
  - POST `dlg_end_dlg` with callid, from_tag

### 1.7 SIPtrace Start/Stop
- Modify `web/siptrace.php`:
  - Add toggle button for capture status
  - Call `sip_trace_status` on load
  - POST `sip_trace_start` / `sip_trace_stop`

### 1.8 Load Balancer Controls
- Modify `web/load-balancer.php`:
  - Add per-row enable/disable toggle
  - Add "Reload" button
  - Call `lb_status` and `lb_reload`

### 1.9 Clusterer Actions
- Modify `web/clusterer.php`:
  - Add "Set State" dropdown per node
  - Add "Ping" button per node
  - Call `clusterer_set_state` and `clusterer_ping`

### 1.10 RTPEngine Enable/Disable
- Modify `web/rtpengine.php`:
  - Add per-instance toggle
  - Call `rtpengine_enable` / `rtpengine_disable`

### 1.11 UAC Registrant Actions
- Modify `web/uac-registrant.php`:
  - Add "Refresh" button per row
  - Add enable/disable toggle per row
  - Call `uac_reg_refresh`, `uac_reg_enable`, `uac_reg_disable`

### 1.12 Statistics Reset
- Modify `web/statistics.php`:
  - Add "Reset" button with confirmation
  - Call `reset_statistics`

### 1.13 Call Center Agent Controls
- Modify `web/call-center.php`:
  - Add login/logout toggle per agent
  - Call `cc_agent_login` / `cc_agent_logout`

---

## Wave 2: P0 Missing Module Pages (2.5 hours)

### 2.1 Memory Status Dashboard (`memory-status.php`)
- Call `get_statistics` with `pkg:` and `shm:` prefixes
- Parse usage/available/total
- Visual progress bars
- SSE auto-refresh integration

### 2.2 Pike Monitor (`pike-monitor.php`)
- Call `pike_list`
- Table of blocked IPs with timestamp
- Search box for `pike_check_ip`
- Threat level color coding

### 2.3 Rate Limit Dashboard (`ratelimit.php`)
- Call `ratelimit_status`
- Pipe utilization table
- "Reset Pipe" buttons

### 2.4 USRLoc Live Dump (`usrloc.php`)
- Call `ul_dump`
- AoR search input
- Contact table with expires, path, flags
- Compare DB count vs live count

---

## Wave 3: P1 Missing Module Pages (2.5 hours)

### 3.1 Hash Table Inspector (`hash-tables.php`)
- Dropdown to select htable name
- Call `htable_dump`
- Search/filter entries
- "Flush" button calling `htable_flush`

### 3.2 NAT Helper Status (`nat-helper.php`)
- Call `nh_show_sockets` and `nh_show_ping`
- Socket list table
- Ping statistics

### 3.3 TCP Connection Manager (`tcp-connections.php`)
- Call `tcp_list`
- Connection table with state filter
- Visual state indicators

### 3.4 Topology Hiding Inspector (`topology-hiding.php`)
- Call `dlg_list` and filter by topology_hidden flag
- Show dialog-to-topology mapping
- Explanation note: no native MI for topology_hiding

### 3.5 Process List (`processes.php`)
- Call `ps`
- Table with PID, type, description, memory
- Visual indicators for status

### 3.6 Blacklists (`blacklists.php`)
- Call `list_blacklists`
- Table of blacklisted entries

### 3.7 Version & Module Info (`version.php`)
- Call `version` and `which`
- Display version string, git hash
- List all loaded modules

### 3.8 Timer Status (`timers.php`)
- Call `list_timers`
- Timer schedule table

---

## Wave 4: P2 Pages + MI Whitelist + Navigation (2.5 hours)

### 4.1 Presence Dashboard (`presence.php`)
- Call `pres_refresh_watchers`
- Watcher/presentity tables

### 4.2 AVP Inspector (`avp-inspector.php`)
- Read-only view of AVP values
- Display from DB or MI where available

### 4.3 MI Command Whitelist Expansion
- Rewrite `web/mi-commands.php`:
  - Expand whitelist to 40+ commands
  - Categorize by module
  - Add search/filter UI
  - Add parameter input fields per command

### 4.4 Navigation Update (`web/common/role-nav.php`)
- Add all new pages to sidebar
- Group under:
  - Runtime: Memory, Processes, TCP, USRLoc, Blacklists, Timers, Version
  - Security: Pike, Rate Limit
  - NAT/Presence: NAT Helper, Topology Hiding, Presence
  - Advanced: Hash Tables, AVP Inspector, MI Commands

### 4.5 Update `web/dashboard.php`
- Add quick links to new runtime pages
- Update widget registry if needed

---

## Wave 5: Integration Tests + Validation (2.5 hours)

### 5.1 Create test framework additions
- `tests/integration/test-ocp-mi-actions.sh`: Test all reload/mutation actions
- `tests/integration/test-ocp-new-pages.sh`: Test all new page loads
- `tests/integration/test-ocp-mi-whitelist.sh`: Test whitelist expansion

### 5.2 Update `tests/integration/test-ocp-all.sh`
- Include all new tests

### 5.3 Run full test suite
- Validate all tests pass
- Fix any regressions

### 5.4 Final validation
- Run `docker compose build ocp`
- Verify all pages load without PHP errors
- Verify mobile responsive breakpoints
- Commit all changes

---

## Files to Create

### New Pages
- `web/memory-status.php`
- `web/pike-monitor.php`
- `web/ratelimit.php`
- `web/usrloc.php`
- `web/hash-tables.php`
- `web/nat-helper.php`
- `web/tcp-connections.php`
- `web/topology-hiding.php`
- `web/processes.php`
- `web/blacklists.php`
- `web/version.php`
- `web/timers.php`
- `web/presence.php`
- `web/avp-inspector.php`
- `web/common/mi-action.php`

### Modified Pages
- `web/address.php`
- `web/dialplan.php`
- `web/domains.php`
- `web/dynamic-routing.php`
- `web/dialog.php`
- `web/siptrace.php`
- `web/load-balancer.php`
- `web/clusterer.php`
- `web/rtpengine.php`
- `web/uac-registrant.php`
- `web/statistics.php`
- `web/call-center.php`
- `web/mi-commands.php`
- `web/common/role-nav.php`
- `web/dashboard.php`

### New Tests
- `tests/integration/test-ocp-mi-actions.sh`
- `tests/integration/test-ocp-new-pages.sh`
- `tests/integration/test-ocp-mi-whitelist.sh`
