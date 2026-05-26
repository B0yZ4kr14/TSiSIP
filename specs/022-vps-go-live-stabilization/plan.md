## Summary

This plan implements the feature for the TSiSIP SIP edge-proxy platform.

## Technical Context

- **OpenSIPS 3.6 LTS**: Core SIP proxy and signaling edge
- **PostgreSQL**: Database backend for configuration and state
- **Docker & Docker Compose**: Container orchestration and deployment

## Project Structure

Relevant directories and files for this feature are located under specs/022-vps-go-live-stabilization/.

# Implementation Plan: 022 — VPS Go-Live Stabilization

## Tech Stack

- Docker Compose v2 (vps-lite profile)
- OpenSIPS 3.6 LTS
- PostgreSQL 15 (aligned with docker-compose.yml postgres:15-alpine)
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
docs/security/evidence/022-vps-go-live/  # Security governance evidence
├── 001-tls-certificate-scan.md
├── 002-container-image-scan.md
├── 003-host-os-security-audit.md
├── 004-network-segmentation-test.md
├── 005-secret-management-audit.md
├── 006-security-headers-scan.md
├── 007-penetration-test-invite.md
├── 008-compliance-mapping.md
├── 009-incident-response-playbook.md
├── 010-security-monitoring-setup.md
├── 011-backup-encryption-verification.md
├── 012-disaster-recovery-test.md
├── 013-vulnerability-management-report.md
├── 014-security-training-completion.md
├── 015-third-party-risk-assessment.md
└── 016-security-evidence-index.md
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
