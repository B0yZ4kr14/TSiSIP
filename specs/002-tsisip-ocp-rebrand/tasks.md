# Tasks: TSiSIP Control Panel — Full OCP v9.3.6 Parity

## Phase 0: Foundation & Schema

### T0.1 — Create PostgreSQL schema migration
- [x] Create `db/init/04-ocp-parity-schema.sql` with idempotent CREATE TABLE IF NOT EXISTS for:
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
  - `keepalived` (keepalived module)
  - `monit` (monit module)
  - `tviewer_schemas` (tviewer module)
- [x] Add indexes on foreign keys and filterable columns
- [x] Add COMMENT ON for each table
- [ ] Create rollback script `db/init/rollback/04-ocp-parity-rollback.sql`

### T0.2 — Update Docker build
- [x] Schema is in `db/init/` and picked up by postgres init
- [ ] Rebuild OCP Docker image with all new files baked in
- [ ] Verify schema initialization in fresh container

### T0.3 — Update navigation
- [x] Update `web/common/role-nav.php` with all 28 modules in grouped structure
- [x] Add role-gated visibility for each new module
- [x] Verify responsive collapse behavior

## Phase 1: Core Infrastructure

### T1.1 — Config Table Module
- [x] Create `web/config-table.php` with CRUD for `config` table
- [x] Integrate with audit log
- [ ] Add i18n strings (EN/ES/PT) — deferred to i18n sprint

### T1.2 — Dynamic Routing Module
- [x] Create `web/dynamic-routing.php` with gateway/rule CRUD
- [x] Integrate with audit log
- [ ] Add gateway health status display — requires MI integration
- [ ] Add i18n strings (EN/ES/PT) — deferred to i18n sprint

### T1.3 — Sockets Management Module
- [x] Create `web/sockets-management.php` with socket CRUD
- [x] Implement proto:ip:port validation
- [x] Integrate with audit log
- [ ] Add i18n strings (EN/ES/PT) — deferred to i18n sprint

### T1.4 — TViewer Module
- [x] Create `web/tviewer.php` with config-driven generic table viewer
- [x] Implement CRUD for schema definitions
- [x] Add search and pagination
- [x] Integrate with audit log
- [ ] Add i18n strings (EN/ES/PT) — deferred to i18n sprint

## Phase 2: Subscriber Management

### T2.1 — Aliases Module
- [x] Create `web/aliases.php` with alias CRUD
- [x] Integrate with audit log
- [ ] Add i18n strings (EN/ES/PT) — deferred to i18n sprint

### T2.2 — Groups Module
- [x] Create `web/groups.php` with group membership CRUD
- [x] Integrate with audit log
- [ ] Add i18n strings (EN/ES/PT) — deferred to i18n sprint

### T2.3 — UAC Registrant Module
- [x] Create `web/uac-registrant.php` with registration CRUD
- [x] Integrate with audit log
- [ ] Add i18n strings (EN/ES/PT) — deferred to i18n sprint

## Phase 3: Operations & Observability

### T3.1 — Clusterer Module
- [x] Create `web/clusterer.php` with node CRUD
- [x] Integrate with audit log
- [ ] Add visual topology view — deferred to frontend sprint
- [ ] Add i18n strings (EN/ES/PT) — deferred to i18n sprint

### T3.2 — SIPtrace Module
- [x] Create `web/siptrace.php` with trace search/filter
- [x] Admin-only purge capability
- [ ] Export to text format — deferred
- [ ] Add i18n strings (EN/ES/PT) — deferred to i18n sprint

### T3.3 — Status Report Module
- [x] Create `web/status-report.php` with identifier list
- [x] Filter by severity (error, warning, info)
- [ ] Add MI command fetch for real-time updates — deferred
- [ ] Add i18n strings (EN/ES/PT) — deferred to i18n sprint

### T3.4 — Load Balancer Module
- [x] Create `web/load-balancer.php` with destination CRUD
- [x] Enable/disable destinations (probe toggle)
- [x] Integrate with audit log
- [ ] Add real-time utilization view — requires MI integration
- [ ] Add i18n strings (EN/ES/PT) — deferred to i18n sprint

## Phase 4: Media & Gateway

### T4.1 — RTPProxy Module
- [x] Create `web/rtpproxy.php` with instance CRUD
- [x] Integrate with audit log
- [ ] Add i18n strings (EN/ES/PT) — deferred to i18n sprint

### T4.2 — SMPP Gateway Module
- [x] Create `web/smpp-gateway.php` with SMSC CRUD
- [x] Enable/disable gateways
- [x] Integrate with audit log
- [ ] Add message count statistics — requires MI integration
- [ ] Add i18n strings (EN/ES/PT) — deferred to i18n sprint

### T4.3 — Call Center Module
- [x] Create `web/call-center.php` with flow/agent CRUD
- [x] Integrate with audit log
- [ ] Add live call queue monitor — requires MI integration
- [ ] Add i18n strings (EN/ES/PT) — deferred to i18n sprint

### T4.4 — Keepalived Module
- [x] Create `web/keepalived.php` with VRRP instance CRUD
- [x] Enable/disable instances
- [x] Integrate with audit log
- [ ] Add manual failover trigger — requires privileged system access
- [ ] Add i18n strings (EN/ES/PT) — deferred to i18n sprint

### T4.5 — Monit Module
- [x] Create `web/monit.php` with monitoring check CRUD
- [x] Enable/disable checks
- [x] Integrate with audit log
- [ ] Add alert history view — requires Monit daemon integration
- [ ] Add i18n strings (EN/ES/PT) — deferred to i18n sprint

## Phase 5: Integration & Polish

### T5.1 — Wiki documentation
- [x] Create `docs/wiki/aliases.md`
- [x] Create `docs/wiki/groups.md`
- [x] Create `docs/wiki/config-table.md`
- [x] Create `docs/wiki/dynamic-routing.md`
- [x] Create `docs/wiki/sockets-management.md`
- [x] Create `docs/wiki/uac-registrant.md`
- [x] Create `docs/wiki/tviewer.md`
- [x] Create `docs/wiki/clusterer.md`
- [x] Create `docs/wiki/siptrace.md`
- [x] Create `docs/wiki/status-report.md`
- [x] Create `docs/wiki/load-balancer.md`
- [x] Create `docs/wiki/rtpproxy.md`
- [x] Create `docs/wiki/smpp-gateway.md`
- [x] Create `docs/wiki/call-center.md`
- [x] Create `docs/wiki/keepalived.md`
- [x] Create `docs/wiki/monit.md`
- [x] Deploy wiki docs to OCP container

### T5.2 — Build & Deploy
- [x] Deploy PHP stubs to production VPS container
- [x] Apply schema to production PostgreSQL
- [x] Verify nginx routing for all modules
- [x] End-to-end test all 16 new module URLs (HTTP 200)
- [ ] Rebuild OCP Docker image with all new files baked in

### T5.3 — Quality Gates
- [ ] Run CI scan (`scripts/ci-scan.sh`)
- [ ] Validate OpenSIPS config syntax
- [ ] Run secret leak detection
- [ ] Verify no OrthoPlus Enterprise leakage
