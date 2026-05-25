# Tasks: TSiSIP Control Panel — Full OCP v9.3.6 Parity

## Phase 0: Foundation & Schema

### T0.1 — Create PostgreSQL schema migration
- [ ] Create `db/init/04-ocp-parity-schema.sql` with idempotent CREATE TABLE IF NOT EXISTS for:
  - `aliases` (alias_db module)
  - `grp` (group module)
  - `cc_flows`, `cc_agents`, `cc_calls` (call_center module)
  - `clusterer` (clusterer module)
  - `config` (cfgutils/config module)
  - `dr_gateways`, `dr_rules`, `dr_carriers`, `dr_groups` (drouting module)
  - `load_balancer` (load_balancer module)
  - `rtpproxy_sockets` (rtpproxy module)
  - `sip_trace` (siptrace module)
  - `sockets` (sockets management)
  - `status_report` (status_report module)
  - `smpp` (smpp module)
  - `uacreg` (uac_registrant module)
- [ ] Add indexes on foreign keys and filterable columns
- [ ] Add COMMENT ON for each table
- [ ] Create rollback script `db/init/rollback/04-ocp-parity-rollback.sql`

### T0.2 — Update Docker build
- [ ] Update `docker/ocp/Dockerfile` to copy `04-ocp-parity-schema.sql`
- [ ] Rebuild OCP image locally
- [ ] Verify schema initialization in fresh container

### T0.3 — Update navigation
- [ ] Update `web/common/role-nav.php` with all 32 modules in grouped structure
- [ ] Add role-gated visibility for each new module
- [ ] Verify responsive collapse behavior

## Phase 1: Core Infrastructure

### T1.1 — Config Table Module
- [ ] Create `web/config-table.php` with CRUD for `config` table
- [ ] Implement category grouping
- [ ] Add immediate effect indicator
- [ ] Integrate with audit log
- [ ] Add i18n strings (EN/ES/PT)

### T1.2 — Dynamic Routing Module
- [ ] Create `web/dynamic-routing.php` with gateway/rule/carrier CRUD
- [ ] Implement rule priority ordering
- [ ] Add gateway health status display
- [ ] Integrate with audit log
- [ ] Add i18n strings (EN/ES/PT)

### T1.3 — Sockets Management Module
- [ ] Create `web/sockets-management.php` with socket CRUD
- [ ] Implement proto:ip:port validation
- [ ] Add active/inactive toggle
- [ ] Integrate with audit log
- [ ] Add i18n strings (EN/ES/PT)

### T1.4 — TViewer Module
- [ ] Create `web/tviewer.php` with config-driven generic table viewer
- [ ] Implement CRUD for configured tables
- [ ] Add search and pagination
- [ ] Integrate with audit log
- [ ] Add i18n strings (EN/ES/PT)

## Phase 2: Subscriber Management

### T2.1 — Aliases Module
- [ ] Create `web/aliases.php` with alias CRUD
- [ ] Link aliases to existing subscribers
- [ ] Add search by username/alias
- [ ] Integrate with audit log

### T2.2 — Groups Module
- [ ] Create `web/groups.php` with group CRUD
- [ ] Add/remove subscribers from groups
- [ ] Show member count per group
- [ ] Integrate with audit log

### T2.3 — UAC Registrant Module
- [ ] Create `web/uac-registrant.php` with registration CRUD
- [ ] Show registration status (last register, expiry)
- [ ] Enable/disable registrations
- [ ] Integrate with audit log

## Phase 3: Operations & Observability

### T3.1 — Clusterer Module
- [ ] Create `web/clusterer.php` with node CRUD
- [ ] Visual topology view
- [ ] Health status per node
- [ ] Integrate with audit log

### T3.2 — SIPtrace Module
- [ ] Create `web/siptrace.php` with trace search/filter
- [ ] Expand trace to view full message flow
- [ ] Export to text format
- [ ] Admin-only purge capability

### T3.3 — Status Report Module
- [ ] Create `web/status-report.php` with identifier list
- [ ] Filter by severity (error, warning, info)
- [ ] View detailed report per identifier

### T3.4 — Load Balancer Module
- [ ] Create `web/load-balancer.php` with destination CRUD
- [ ] Enable/disable destinations
- [ ] Real-time utilization view
- [ ] Integrate with audit log

## Phase 4: Media & Gateway

### T4.1 — RTPProxy Module
- [ ] Create `web/rtpproxy.php` with instance CRUD
- [ ] Enable/disable instances

### T4.2 — SMPP Gateway Module
- [ ] Create `web/smpp-gateway.php` with SMSC CRUD
- [ ] Message count statistics
- [ ] Enable/disable gateways

### T4.3 — Call Center Module
- [ ] Create `web/call-center.php` with flow/agent/call CRUD
- [ ] Live call queue monitor

### T4.4 — Keepalived Module
- [ ] Create `web/keepalived.php` with status overview
- [ ] Manual failover trigger (admin only)

### T4.5 — Monit Module
- [ ] Create `web/monit.php` with service status display
- [ ] Alert history view

## Phase 5: Integration & Polish

### T5.1 — Wiki documentation
- [ ] Create `docs/wiki/aliases.md`
- [ ] Create `docs/wiki/groups.md`
- [ ] Create `docs/wiki/config-table.md`
- [ ] Create `docs/wiki/dynamic-routing.md`
- [ ] Create `docs/wiki/sockets-management.md`
- [ ] Create `docs/wiki/uac-registrant.md`
- [ ] Create `docs/wiki/tviewer.md`
- [ ] Create `docs/wiki/clusterer.md`
- [ ] Create `docs/wiki/siptrace.md`
- [ ] Create `docs/wiki/status-report.md`
- [ ] Create `docs/wiki/load-balancer.md`
- [ ] Create `docs/wiki/rtpproxy.md`
- [ ] Create `docs/wiki/smpp-gateway.md`
- [ ] Create `docs/wiki/call-center.md`

### T5.2 — Build & Deploy
- [ ] Rebuild OCP Docker image with all new files
- [ ] Deploy to production VPS
- [ ] Verify nginx routing for all modules
- [ ] End-to-end test all 32 module URLs

### T5.3 — Quality Gates
- [ ] Run CI scan (scripts/ci-scan.sh)
- [ ] Validate OpenSIPS config syntax
- [ ] Run secret leak detection
- [ ] Verify no OrthoPlus Enterprise leakage
