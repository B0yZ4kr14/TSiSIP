# Feature 022 Tasks

## Wave 0: Baseline Setup
- [ ] T1.1: Validate Docker/Compose versions on VPS
- [ ] T1.2: Verify disk/CPU/RAM availability
- [ ] T1.3: Inventory all secret files in `secrets/` (must be 100% present)
- [ ] T1.4: Verify `.env` file completeness against `.env.example`
- [ ] T1.5: Capture baseline evidence (`task-1-baseline.txt`)

## Wave 1: RED Tests + Rollback Prep
- [ ] T2.1: Write container health RED test (expect failure before fixes)
- [ ] T2.2: Capture RED evidence (`task-2-red-health.txt`)
- [ ] T3.1: Write SIP OPTIONS RED test (expect failure/no response)
- [ ] T3.2: Write SIP INVITE RED test (expect 407 or no response)
- [ ] T3.3: Capture RED evidence (`task-3-red-sip.txt`)
- [ ] T4.1: Write OCP endpoint RED test (`curl` expect failure)
- [ ] T4.2: Capture RED evidence (`task-4-red-ocp.txt`)
- [ ] T5.1: Draft rollback runbook per service
- [ ] T5.2: Define abort triggers and rollback dry-run steps
- [ ] T5.3: Capture rollback evidence (`task-5-rollback-dryrun.txt`)

## Wave 2: GREEN Implementation
- [ ] T6.1: Fix `docker-compose.vps.yml` runtime issues (restart loops, missing env)
- [ ] T6.2: Verify stable `docker compose up -d` execution
- [ ] T6.3: Capture GREEN evidence (`task-6-green-runtime.txt`)
- [ ] T7.1: Verify OpenSIPS schema tables in PostgreSQL
- [ ] T7.2: Verify `version` table contains expected module versions
- [ ] T7.3: Capture schema evidence (`task-7-db-schema.txt`)
- [ ] T8.1: Verify RTPengine port range viability
- [ ] T8.2: Confirm RTPengine reaches `healthy` status
- [ ] T8.3: Capture RTPengine evidence (`task-8-rtpengine-healthy.txt`)
- [ ] T9.1: Coordinated bring-up of full vps-lite stack
- [ ] T9.2: Run SIP OPTIONS smoke test (expect 200 OK)
- [ ] T9.3: Run OCP curl smoke test (expect 200 + content)
- [ ] T9.4: Capture smoke evidence (`task-9-smoke-pass.txt`)
- [ ] T10.1: Audit all published ports in compose
- [ ] T10.2: Confirm zero public ports for Asterisk/PostgreSQL
- [ ] T10.3: Capture port audit evidence (`task-10-port-policy.txt`)

## Wave 3: REFACTOR
- [ ] T11.1: Standardize healthcheck params (interval/timeout/retries/start_period)
- [ ] T11.2: Verify no false positives over 10-minute window
- [ ] T11.3: Capture healthcheck evidence (`task-11-healthcheck-config.txt`)
- [12.1: Standardize log collection and triage commands per service
- [ ] T12.2: Verify root-cause identification in <=2 commands
- [ ] T12.3: Capture observability evidence (`task-12-observability-triage.txt`)
- [ ] T13.1: Review restart policies and dependency chains
- [ ] T13.2: Simulate single-service failure and verify recovery
- [ ] T13.3: Verify no restart cascade or infinite loop
- [ ] T13.4: Capture resilience evidence (`task-13-resilience-pass.txt`)
- [ ] T14.1: Consolidate all evidence artifacts
- [ ] T14.2: Generate readiness report
- [ ] T14.3: Capture evidence bundle (`task-14-evidence-bundle-pass.txt`)

## Wave FINAL: Verification
- [ ] F1: Plan compliance audit
- [ ] F2: Code/config quality review
- [ ] F3: Automated E2E QA execution
- [ ] F4: Scope fidelity check
