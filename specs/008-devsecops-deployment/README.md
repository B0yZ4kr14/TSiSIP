# Feature 008: DevSecOps Deployment Automation

## Overview

Automated, secure deployment of the TSiSIP Docker-first SIP edge-proxy stack to VPS **TSiAPP**. This feature covers provisioning, hardening, secret management, reverse-proxy configuration, and resilience validation for the production runtime environment.

## Current Status

**Live VPS production stack running; upstream SIP edge exposure, deterministic image pinning, and formal public TLS grade evidence remain pending.**

The vps-lite+PBX profile (7 services, <3 GB RAM) is active on TSiAPP. All core services — PostgreSQL, OpenSIPS, RTPengine, OCP, backup, and two internal Asterisk PBX nodes — are healthy. See [`deploy/VPS-DEPLOY-READINESS.md`](../../deploy/VPS-DEPLOY-READINESS.md) for the full live checklist.

## Acceptance Criteria Summary

| ID | Criterion | Status |
|---|---|---|
| SC-001 | Secret discovery without exposure | ✅ Pass |
| SC-002 | Ansible deploy success rate | ✅ Pass |
| SC-003 | Health check pass time (< 60 s) | ✅ Pass |
| SC-004 | Reverse proxy SSL grade (A+) | ⏳ Pending formal scan |
| SC-005 | Audit document completeness (all SPoF covered) | ✅ Pass |
| SC-006 | SIP authenticated route end-to-end | ✅ Pass |

## Quick Start for Deploy Operators

```bash
# 1. Validate local environment
make -C deploy secrets

# 2. Run server hardening (first time only)
make -C deploy hardening

# 3. Deploy TSiSIP stack to TSiAPP
make -C deploy deploy

# 4. Validate deployment
make -C deploy validate

# 5. Run SPoF falsification tests
make -C deploy test-spof
```

For detailed procedures, see [`deploy/README-VPS-DEPLOY.md`](../../deploy/README-VPS-DEPLOY.md).

## Feature Documents

| Document | Purpose |
|---|---|
| [spec.md](spec.md) | Feature specification (WHAT/WHY) |
| [plan.md](plan.md) | Implementation plan and architecture |
| [tasks.md](tasks.md) | Task list with current statuses |
| [data-model.md](data-model.md) | Deployment entities, secrets, and network topology |
| [research.md](research.md) | Research, decisions, and rationale |
| [checklists/infra-quality.md](checklists/infra-quality.md) | Infrastructure security and quality checklist |
| [checklists/requirements.md](checklists/requirements.md) | Requirements traceability and compliance checklist |
