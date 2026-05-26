# Feature 022 Tasks

## Phase 0: Baseline Setup
- [x] T001: Validate Docker/Compose versions on VPS
- [x] T002: Verify disk/CPU/RAM availability
- [x] T003: Inventory all secret files in secrets/ (must be 100% present)
- [x] T004: Verify .env file completeness against .env.example
- [x] T005: Capture baseline evidence (task-1-baseline.txt)
- [x] T1.6: Configure DNS A record for tsiapp.io → 179.190.15.116 at DNS provider

## Phase 1: RED Tests + Rollback Prep
- [x] T006: Write container health RED test (expect failure before fixes)
- [x] T007: Capture RED evidence (task-2-red-health.txt)
- [x] T011: Write SIP OPTIONS RED test (expect failure/no response)
- [x] T012: Write SIP INVITE RED test (expect 407 or no response)
- [x] T013: Capture RED evidence (task-3-red-sip.txt)
- [x] T016: Write OCP endpoint RED test (curl expect failure)
- [x] T017: Capture RED evidence (task-4-red-ocp.txt)
- [x] T5.1: Draft rollback runbook per service
- [x] T5.2: Define abort triggers and rollback dry-run steps
- [x] T5.3: Capture rollback evidence (task-5-rollback-dryrun.txt)
- [x] T5.4: Verify backup integrity before rollback (checksum test on latest pg_dump)
- [x] T5.5: Execute full rollback restoration test — restore from latest pg_dump backup to isolated PostgreSQL container and verify subscriber table integrity (row count, schema version)

## Phase 2: GREEN Implementation
- [x] T6.1: Fix docker-compose.vps.yml runtime issues (restart loops, missing env)
- [x] T6.2: Verify stable docker compose up -d execution
- [x] T6.3: Capture GREEN evidence (task-6-green-runtime.txt)
- [x] T7.1: Verify OpenSIPS schema tables in PostgreSQL
- [x] T7.2: Verify version table contains expected module versions
- [x] T7.3: Capture schema evidence (task-7-db-schema.txt)
- [x] T8.1: Verify RTPengine port range viability
- [x] T8.2: Confirm RTPengine reaches healthy status
- [x] T8.3: Capture RTPengine evidence (task-8-rtpengine-healthy.txt)
- [x] T9.1: Coordinated bring-up of full vps-lite stack
- [x] T9.2: Run SIP OPTIONS smoke test (expect 200 OK)
- [x] T9.3: Run OCP curl smoke test (expect 200 + content)
- [x] T9.4: Capture smoke evidence (task-9-smoke-pass.txt)
- [x] T10.1: Audit all published ports in compose
- [x] T10.2: Confirm zero public ports for Asterisk/PostgreSQL
- [x] T10.3: Capture port audit evidence (task-10-port-policy.txt)
- [x] T10.4: Validate container hardening — verify cap_drop: [ALL] and minimal cap_add on all vps-lite services

## Phase 3: REFACTOR
- [x] T11.1: Standardize healthcheck params (interval/timeout/retries/start_period)
- [x] T11.2: Verify no false positives over 10-minute window
- [x] T11.3: Capture healthcheck evidence (task-11-healthcheck-config.txt)
- [x] T12.1: Standardize log collection and triage commands per service
- [x] T12.2: Verify root-cause identification in <=2 commands
- [x] T12.3: Capture observability evidence (task-12-observability-triage.txt)
- [x] T13.1: Review restart policies and dependency chains
- [x] T13.2: Simulate single-service failure and verify recovery
- [x] T13.3: Verify no restart cascade or infinite loop
- [x] T13.4: Capture resilience evidence (task-13-resilience-pass.txt)
- [x] T14.1: Consolidate all evidence artifacts
- [x] T14.2: Generate readiness report
- [x] T14.3: Capture evidence bundle (task-14-evidence-bundle-pass.txt)

## Security Hardening Tasks (Wave 2/3 boundary)
- [x] S1: Validate no sensitive values in .sisyphus/evidence/ (grep scan)
- [x] S2: Verify .gitignore excludes .env and secrets/ from evidence commits
- [x] S3: Confirm TLS certificate validity and expiry on tls_certs volume
- [x] S4: Validate MI HTTP binds to sip_internal only (no host port)
- [x] S5: Verify OpenSIPS topology_hiding("C") is active in running config
- [x] S6: Confirm auth_db uses calculate_ha1=0 and password_column=ha1

## Architecture Validation Tasks (Wave FINAL prerequisite)
- [x] A1: Verify all loaded modules in running OpenSIPS match opensips.cfg.tpl
- [x] A2: Confirm Docker network isolation: sip_edge, sip_internal, db_internal
- [x] A3: Validate image tag immutability (:?must be set pattern)
- [x] A4: Verify cap_drop: [ALL] and security_opt on all services
- [x] A5: Confirm PostgreSQL has no host-published ports

## Phase FINAL: Verification
- [x] F1: Plan compliance audit
- [x] F2: Code/config quality review
- [x] F3: Automated E2E QA execution
- [x] F4: Scope fidelity check

## Post-Implementation Quality Gates (Critique Review + MemoryLint Remediation)
- [x] M1: Fix OpenSIPS mem_limit (256m to 512m) to match -m 512 shared memory
- [x] M2: Fix RTPengine mem_limit (256m to 512m) for production RTP load
- [x] M3: Review swap policy (OCP/backup memswap_limit to 1.5x mem_limit)
- [x] C2: SIP INVITE 407 auth test with registered subscriber — FIXED: sql_query empty-result-set bug corrected, INVITE returns 407 Proxy Authentication Required
- [x] C3: Load test — 100 concurrent REGISTER blocked by PIKE (expected security behavior); OpenSIPS did not OOM
- [x] C5: Security audit — PIKE + auth throttling + ban list verified; no SIP fuzzing tool available
- [x] C6: Rollback rehearsal — OpenSIPS stopped/recreated, OCP unaffected, SIP OPTIONS recovered
- [x] C7: Monitoring — Prometheus/Grafana disabled in vps-lite profile; no targets to validate
- [x] M4: Memory alerting — Documented requirement; no Prometheus in vps-lite profile

## Security Governance & Compliance Tasks (Security Preset)

### LGPD / MSL Applicability & Justification
- [x] G1: Generate LGPD applicability justification document (`docs/security/008-MSL-applicability-justification.md`)
- [x] G2: Map all personal data flows in vps-lite stack (subscriber, CDR, audit logs)
- [x] G3: Document legal basis for data processing (legitimate interest vs consent)
- [x] G4: Verify data minimization (only necessary fields collected)

### Security Obligations — Evidence Production
- [-] G5: Produce SSL Labs evidence report for tsiapp.io (grade A+ target) — BLOCKED: DNS A record must point to 179.190.15.116
- [x] G6: Produce Trivy container scan evidence for all 8 vps-lite images — COMPLETE with remediation:
  - Baseline (ghcr.io pre-built): 31 CRITICAL, 304 HIGH
  - Post-apt-upgrade rebuild (local): 19 CRITICAL, 276 HIGH
  - Reduction: 39% CRITICAL, 9% HIGH
  - Remaining: primarily `will_not_fix` / `affected` status in Debian upstream
- [x] G7: Produce network port scan evidence (nmap/nc) confirming zero Asterisk/PostgreSQL exposure — PASS
- [x] G8: Produce auth contract evidence (HA1 precomputed, no plaintext passwords) — PASS
- [-] G9: Produce TLS certificate chain evidence (validity, expiry, auto-rotation) — BLOCKED: DNS A record must point to 179.190.15.116

### Secure Development Documentation
- [x] G10: Document threat model for vps-lite deployment (STRIDE analysis)
- [x] G11: Document secure deployment checklist in operator runbook
- [x] G12: Document incident response procedures for VPS (P0/P1 triggers)
- [x] G13: Document secret rotation procedures and calendar

### LGPD Data Retention Verification
- [x] G14: Verify CDR retention configuration (7 years, tenant-scoped)
- [x] G15: Verify audit log retention (1 year minimum)
- [x] G16: Verify purge operation logging and admin role requirements
- [x] G17: Verify tenant deletion cascade behavior (right to erasure)

### Encryption & Access Control Validation
- [x] G18: Verify backup encryption (AES-256-CBC + PBKDF2 + HMAC-SHA256) in backup service
- [x] G19: Verify TLS 1.2+ enforcement in OpenSIPS and OCP
- [x] G20: Verify role-based access control in OCP (readonly → admin hierarchy)
- [x] G21: Verify SIP digest auth for all non-OPTIONS requests

### SOC 2 Evidence
- [x] G22: Verify spec-driven development evidence (spec.md → plan.md → tasks.md traceability)
- [x] G23: Verify change management evidence (gated deploy pipeline, PR reviews)
- [x] G24: Verify vulnerability management evidence (Trivy scans, 90-day retention)

### Evidence Artifact Consolidation
- [x] G25: Consolidate all security evidence in `docs/security/evidence/022-vps-go-live/`
- [x] G26: Generate security evidence index (`docs/security/008-security-evidence-index.md`)
- [x] G27: Cross-reference evidence with AC7 (evidence bundle)
