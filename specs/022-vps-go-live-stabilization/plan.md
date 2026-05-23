# Implementation Plan: 022 — VPS Go-Live Stabilization

## Tech Stack

- Docker Compose v2 (vps-lite profile)
- OpenSIPS 3.6 LTS
- PostgreSQL 15+
- RTPengine
- Asterisk (private network only)
- OCP (PHP/nginx)
- Test tools: bash, sipsak, curl, Python 3

## File Structure

```
.sisyphus/evidence/           # Operational evidence per task
├── task-1-baseline.txt
├── task-2-red-health.txt
├── task-3-red-sip.txt
├── task-4-red-ocp.txt
├── task-5-rollback-dryrun.txt
├── task-6-green-runtime.txt
├── task-7-db-schema.txt
├── task-8-rtpengine-healthy.txt
├── task-9-smoke-pass.txt
├── task-10-port-policy.txt
├── task-11-healthcheck-config.txt
├── task-12-observability-triage.txt
├── task-13-resilience-pass.txt
└── task-14-evidence-bundle-pass.txt
```

## Execution Waves

### Wave 0: Baseline
- T1: VPS environment + secrets inventory

### Wave 1: RED Tests + Rollback Prep
- T2: RED health tests
- T3: RED SIP signaling tests
- T4: RED OCP endpoint tests
- T5: Rollback runbook

### Wave 2: GREEN Implementation
- T6: Runtime/compose stabilization
- T7: DB schema alignment
- T8: RTPengine network/ports
- T9: Coordinated stack bring-up + smoke test
- T10: Port exposure security audit

### Wave 3: REFACTOR
- T11: Healthcheck refinement
- T12: Observability/diagnostic refinement
- T13: Operational robustness
- T14: Evidence consolidation

### Wave FINAL: Verification
- F1-F4: Compliance, quality, QA, scope fidelity
