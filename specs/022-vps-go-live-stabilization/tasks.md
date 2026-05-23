# Feature 022 Tasks

## Wave 0: Baseline Setup
- [x] T1.1: Validate Docker/Compose versions on VPS
- [x] T1.2: Verify disk/CPU/RAM availability
- [x] T1.3: Inventory all secret files in secrets/ (must be 100% present)
- [x] T1.4: Verify .env file completeness against .env.example
- [x] T1.5: Capture baseline evidence (task-1-baseline.txt)

## Wave 1: RED Tests + Rollback Prep
- [x] T2.1: Write container health RED test (expect failure before fixes)
- [x] T2.2: Capture RED evidence (task-2-red-health.txt)
- [x] T3.1: Write SIP OPTIONS RED test (expect failure/no response)
- [x] T3.2: Write SIP INVITE RED test (expect 407 or no response)
- [x] T3.3: Capture RED evidence (task-3-red-sip.txt)
- [x] T4.1: Write OCP endpoint RED test (curl expect failure)
- [x] T4.2: Capture RED evidence (task-4-red-ocp.txt)
- [x] T5.1: Draft rollback runbook per service
- [x] T5.2: Define abort triggers and rollback dry-run steps
- [x] T5.3: Capture rollback evidence (task-5-rollback-dryrun.txt)

## Wave 2: GREEN Implementation
- [x] T6.1: Fix docker-compose.vps.yml runtime issues (restart loops, missing env)
- [x] T6.2: Verify stable docker compose up -d execution
- [x] T6.3: Capture GREEN evidence (task-6-green-runtime.txt)
- [ ] T7.1: Verify OpenSIPS schema tables in PostgreSQL
- [ ] T7.2: Verify version table contains expected module versions
- [ ] T7.3: Capture schema evidence (task-7-db-schema.txt)
- [ ] T8.1: Verify RTPengine port range viability
- [ ] T8.2: Confirm RTPengine reaches healthy status
- [ ] T8.3: Capture RTPengine evidence (task-8-rtpengine-healthy.txt)
- [ ] T9.1: Coordinated bring-up of full vps-lite stack
- [ ] T9.2: Run SIP OPTIONS smoke test (expect 200 OK)
- [ ] T9.3: Run OCP curl smoke test (expect 200 + content)
- [ ] T9.4: Capture smoke evidence (task-9-smoke-pass.txt)
- [x] T10.1: Audit all published ports in compose
- [x] T10.2: Confirm zero public ports for Asterisk/PostgreSQL
- [x] T10.3: Capture port audit evidence (task-10-port-policy.txt)

## Wave 3: REFACTOR
- [ ] T11.1: Standardize healthcheck params (interval/timeout/retries/start_period)
- [ ] T11.2: Verify no false positives over 10-minute window
- [ ] T11.3: Capture healthcheck evidence (task-11-healthcheck-config.txt)
- [ ] T12.1: Standardize log collection and triage commands per service
- [ ] T12.2: Verify root-cause identification in <=2 commands
- [ ] T12.3: Capture observability evidence (task-12-observability-triage.txt)
- [ ] T13.1: Review restart policies and dependency chains
- [ ] T13.2: Simulate single-service failure and verify recovery
- [ ] T13.3: Verify no restart cascade or infinite loop
- [ ] T13.4: Capture resilience evidence (task-13-resilience-pass.txt)
- [ ] T14.1: Consolidate all evidence artifacts
- [ ] T14.2: Generate readiness report
- [ ] T14.3: Capture evidence bundle (task-14-evidence-bundle-pass.txt)

## Security Hardening Tasks (Wave 2/3 boundary)
- [ ] S1: Validate no sensitive values in .sisyphus/evidence/ (grep scan)
- [ ] S2: Verify .gitignore excludes .env and secrets/ from evidence commits
- [ ] S3: Confirm TLS certificate validity and expiry on tls_certs volume
- [ ] S4: Validate MI HTTP binds to sip_internal only (no host port)
- [ ] S5: Verify OpenSIPS topology_hiding("C") is active in running config
- [ ] S6: Confirm auth_db uses calculate_ha1=0 and password_column=ha1

## Architecture Validation Tasks (Wave FINAL prerequisite)
- [ ] A1: Verify all loaded modules in running OpenSIPS match opensips.cfg.tpl
- [ ] A2: Confirm Docker network isolation: sip_edge, sip_internal, db_internal
- [ ] A3: Validate image tag immutability (:?must be set pattern)
- [ ] A4: Verify cap_drop: [ALL] and security_opt on all services
- [ ] A5: Confirm PostgreSQL has no host-published ports

## Wave FINAL: Verification
- [ ] F1: Plan compliance audit
- [ ] F2: Code/config quality review
- [ ] F3: Automated E2E QA execution
- [ ] F4: Scope fidelity check
