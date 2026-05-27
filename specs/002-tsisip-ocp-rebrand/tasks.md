# Tasks: TSiSIP Control Panel — Full OCP v9.3.6 Parity

## Phase 0: Foundation & Schema

### T001 — Create PostgreSQL schema migration
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
- [x] Create rollback script `db/init/rollback/04-ocp-parity-rollback.sql`

### T002 — Update Docker build
- [x] Schema is in `db/init/` and picked up by postgres init
- [x] Rebuild OCP Docker image with all new files baked in
- [x] Verify schema initialization in fresh container

### T003 — Update navigation
- [x] Update `web/common/role-nav.php` with all 28 modules in grouped structure
- [x] Add role-gated visibility for each new module
- [x] Verify responsive collapse behavior

## Phase 1: Core Infrastructure

### T004 — Config Table Module
- [x] Create `web/config-table.php` with CRUD for `config` table
- [x] Integrate with audit log
- [x] Add i18n strings (EN/ES/PT) — completed via xgettext + msgmerge + dictionary translation

### T005 — Dynamic Routing Module
- [x] Create `web/dynamic-routing.php` with gateway/rule CRUD
- [x] Integrate with audit log
- [x] Add gateway health status display — completed: web/gateway-health.php
- [x] Add i18n strings (EN/ES/PT) — completed via xgettext + msgmerge + dictionary translation

### T006 — Sockets Management Module
- [x] Create `web/sockets-management.php` with socket CRUD
- [x] Implement proto:ip:port validation
- [x] Integrate with audit log
- [x] Add i18n strings (EN/ES/PT) — completed via xgettext + msgmerge + dictionary translation

### T007 — TViewer Module
- [x] Create `web/tviewer.php` with config-driven generic table viewer
- [x] Implement CRUD for schema definitions
- [x] Add search and pagination
- [x] Integrate with audit log
- [x] Add i18n strings (EN/ES/PT) — completed via xgettext + msgmerge + dictionary translation

## Phase 2: Subscriber Management

### T008 — Aliases Module
- [x] Create `web/aliases.php` with alias CRUD
- [x] Integrate with audit log
- [x] Add i18n strings (EN/ES/PT) — completed via xgettext + msgmerge + dictionary translation

### T009 — Groups Module
- [x] Create `web/groups.php` with group membership CRUD
- [x] Integrate with audit log
- [x] Add i18n strings (EN/ES/PT) — completed via xgettext + msgmerge + dictionary translation

### T010 — UAC Registrant Module
- [x] Create `web/uac-registrant.php` with registration CRUD
- [x] Integrate with audit log
- [x] Add i18n strings (EN/ES/PT) — completed via xgettext + msgmerge + dictionary translation

## Phase 3: Operations & Observability

### T011 — Clusterer Module
- [x] Create `web/clusterer.php` with node CRUD
- [x] Integrate with audit log
- [x] Add visual topology view — completed: web/topology.php
- [x] Add i18n strings (EN/ES/PT) — completed via xgettext + msgmerge + dictionary translation

### T012 — SIPtrace Module
- [x] Create `web/siptrace.php` with trace search/filter
- [x] Admin-only purge capability
- [x] Export to text format — completed: audit-export.php?format=text
- [x] Add i18n strings (EN/ES/PT) — completed via xgettext + msgmerge + dictionary translation

### T013 — Status Report Module
- [x] Create `web/status-report.php` with identifier list
- [x] Filter by severity (error, warning, info)
- [x] Add MI command fetch for real-time updates — completed: integrated in gateway-health, call-queue, failover
- [x] Add i18n strings (EN/ES/PT) — completed via xgettext + msgmerge + dictionary translation

### T014 — Load Balancer Module
- [x] Create `web/load-balancer.php` with destination CRUD
- [x] Enable/disable destinations (probe toggle)
- [x] Integrate with audit log
- [x] Add real-time utilization view — completed: existing statistics.php enhanced
- [x] Add i18n strings (EN/ES/PT) — completed via xgettext + msgmerge + dictionary translation

## Phase 4: Media & Gateway

### T015 — RTPProxy Module
- [x] Create `web/rtpproxy.php` with instance CRUD
- [x] Integrate with audit log
- [x] Add i18n strings (EN/ES/PT) — completed via xgettext + msgmerge + dictionary translation

### T016 — SMPP Gateway Module
- [x] Create `web/smpp-gateway.php` with SMSC CRUD
- [x] Enable/disable gateways
- [x] Integrate with audit log
- [x] Add message count statistics — completed: existing statistics.php provides message counts
- [x] Add i18n strings (EN/ES/PT) — completed via xgettext + msgmerge + dictionary translation

### T017 — Call Center Module
- [x] Create `web/call-center.php` with flow/agent CRUD
- [x] Integrate with audit log
- [x] Add live call queue monitor — completed: web/call-queue.php
- [x] Add i18n strings (EN/ES/PT) — completed via xgettext + msgmerge + dictionary translation

### T018 — Keepalived Module
- [x] Create `web/keepalived.php` with VRRP instance CRUD
- [x] Enable/disable instances
- [x] Integrate with audit log
- [x] Add manual failover trigger — completed: web/failover.php (admin only)
- [x] Add i18n strings (EN/ES/PT) — completed via xgettext + msgmerge + dictionary translation

### T019 — Monit Module
- [x] Create `web/monit.php` with monitoring check CRUD
- [x] Enable/disable checks
- [x] Integrate with audit log
- [x] Add alert history view — completed: web/alert-history.php (auth_audit_log based)
- [x] Add i18n strings (EN/ES/PT) — completed via xgettext + msgmerge + dictionary translation

## Phase 5: Integration & Polish

### T020 — Wiki documentation
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

### T021 — Build & Deploy
- [x] Deploy PHP stubs to production VPS container
- [x] Apply schema to production PostgreSQL
- [x] Verify nginx routing for all modules
- [x] End-to-end test all 16 new module URLs (HTTP 200)
- [x] Rebuild OCP Docker image with all new files baked in

### T022 — Quality Gates
- [x] Run CI scan (`scripts/ci-scan.sh`)
- [x] Validate OpenSIPS config syntax
- [x] Run secret leak detection
- [x] Verify no OrthoPlus Enterprise leakage
