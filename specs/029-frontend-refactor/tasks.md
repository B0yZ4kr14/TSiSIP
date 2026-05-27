# Tasks: TSiSIP OCP Frontend — 100% OpenSIPS 3.6 MI Parity

## Wave 1: MI Actions on Existing Pages

### Task 1.1: Create Generic MI Action Handler [X]
- File: `web/common/mi-action.php`
- Accept POST with: cmd, params (JSON), csrf_token
- Validate CSRF, validate role >= devops for mutations
- Validate cmd against whitelist
- Call miHttpCall(cmd, params)
- Log to audit table
- Return JSON: {success, data, error}

### Task 1.2: Address Reload Button [X]
- Modify: `web/address.php`
- Add action bar with "Reload Address Table" button
- JS POST to mi-action.php
- Toast notification on result

### Task 1.3: Dialplan Reload Button [X]
- Modify: `web/dialplan.php`
- Add "Reload Dialplan" button
- Same pattern as 1.2

### Task 1.4: Domain Reload Button [X]
- Modify: `web/domains.php`
- Add "Reload Domains" button
- Same pattern as 1.2

### Task 1.5: Dynamic Routing Reload & GW Status [X]
- Modify: `web/dynamic-routing.php`
- Add "Reload DRouting" button
- Add section calling `dr_gw_status` per gateway
- Status badge: up (green), down (red), unknown (gray)

### Task 1.6: Dialog Terminate Button [X]
- Modify: `web/dialog.php`
- Add "Terminate" button per row
- Modal confirmation with call-id display
- POST dlg_end_dlg with callid, from_tag
- Refresh table after success

### Task 1.7: SIPtrace Start/Stop Toggle [X]
- Modify: `web/siptrace.php`
- Read current status on load via sip_trace_status
- Toggle button: Start Capture / Stop Capture
- Status badge

### Task 1.8: Load Balancer Status & Reload [X]
- Modify: `web/load-balancer.php`
- Per-row toggle calling lb_status
- "Reload" button calling lb_reload
- Status indicator

### Task 1.9: Clusterer Set State & Ping [X]
- Modify: `web/clusterer.php`
- Per-row "Set State" dropdown (active/backup/down)
- Per-row "Ping" button
- Status update after action

### Task 1.10: RTPEngine Enable/Disable [X]
- Modify: `web/rtpengine.php`
- Per-instance toggle
- Status update after toggle

### Task 1.11: UAC Registrant Refresh & Toggle [X]
- Modify: `web/uac-registrant.php`
- Per-row "Refresh" button
- Per-row enable/disable toggle
- Timestamp update after refresh

### Task 1.12: Statistics Reset [X]
- Modify: `web/statistics.php`
- "Reset Statistics" button with confirmation modal
- Option: reset all or selected module
- Refresh stats after reset

### Task 1.13: Call Center Agent Login/Logout [X]
- Modify: `web/call-center.php`
- Per-agent login/logout toggle
- Flow status section via cc_flow_status

## Wave 2: P0 Missing Pages

### Task 2.1: Memory Status Dashboard [X]
- File: `web/memory-status.php`
- get_statistics pkg:* and shm:*
- Progress bars for used/total
- Alert banner if > 80%
- SSE integration

### Task 2.2: Pike Monitor [X]
- File: `web/pike-monitor.php`
- pike_list table
- IP search with pike_check_ip
- Color-coded threat levels

### Task 2.3: Rate Limit Dashboard [X]
- File: `web/ratelimit.php`
- ratelimit_status table
- Per-pipe reset button
- Utilization bars

### Task 2.4: USRLoc Live Dump [X]
- File: `web/usrloc.php`
- Search input for AoR
- ul_dump contact table
- DB count vs live count comparison

## Wave 3: P1 Missing Pages

### Task 3.1: Hash Table Inspector [X]
- File: `web/hash-tables.php`
- Table selector dropdown
- htable_dump results
- Search/filter
- Flush button

### Task 3.2: NAT Helper Status [X]
- File: `web/nat-helper.php`
- nh_show_sockets table
- nh_show_ping statistics

### Task 3.3: TCP Connection Manager [X]
- File: `web/tcp-connections.php`
- tcp_list table
- State filter dropdown
- Connection count summary

### Task 3.4: Topology Hiding Inspector [X]
- File: `web/topology-hiding.php`
- dlg_list filtered analysis
- Explanation of aggregation approach
- Dialog mapping table

### Task 3.5: Process List [X]
- File: `web/processes.php`
- ps command output table
- PID, type, description, memory
- Status indicators

### Task 3.6: Blacklists [X]
- File: `web/blacklists.php`
- list_blacklists output
- Simple table view

### Task 3.7: Version & Module Info [X]
- File: `web/version.php`
- version and which output
- Formatted version card
- Module list grid

### Task 3.8: Timer Status [X]
- File: `web/timers.php`
- list_timers output
- Schedule table

## Wave 4: P2 Pages + Navigation + MI Whitelist

### Task 4.1: Presence Dashboard [X]
- File: `web/presence.php`
- pres_refresh_watchers output
- Watcher/presentity tables

### Task 4.2: AVP Inspector [X]
- File: `web/avp-inspector.php`
- Read-only AVP display
- DB query for avpops data if available

### Task 4.3: MI Whitelist Expansion [X]
- Modify: `web/mi-commands.php`
- 40+ commands organized by module
- Parameter input fields
- Search/filter

### Task 4.4: Navigation Update [X]
- Modify: `web/common/role-nav.php`
- Add all new pages
- Logical grouping
- Role visibility

### Task 4.5: Dashboard Quick Links [X]
- Modify: `web/dashboard.php`
- Add quick link cards for new runtime pages
- Update widget registry

## Wave 5: Tests + Validation

### Task 5.1: MI Actions Test Suite [X]
- File: `tests/integration/test-ocp-mi-actions.sh`
- Test each reload/terminate/toggle action
- Verify JSON responses
- Verify audit log entries

### Task 5.2: New Pages Test Suite [X]
- File: `tests/integration/test-ocp-new-pages.sh`
- HTTP 200 check for every new page
- Verify MI data display
- Verify role restrictions

### Task 5.3: MI Whitelist Test Suite [X]
- File: `tests/integration/test-ocp-mi-whitelist.sh`
- Verify all 40+ commands in whitelist
- Test command execution

### Task 5.4: Run Full Test Suite [X]
- Update `tests/integration/test-ocp-all.sh`
- Execute and fix regressions

### Task 5.5: Final Build & Commit [X]
- docker compose build ocp
- Verify all pages load
- Commit all changes with conventional commits
