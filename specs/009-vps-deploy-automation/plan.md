# Implementation Plan: VPS Deploy Automation Pipeline

**Branch**: `009-vps-deploy-automation` | **Date**: 2026-05-19 | **Spec**: spec.md

**Input**: Feature specification from `specs/009-vps-deploy-automation/spec.md`

## Summary

Build an automated deploy pipeline that replaces ad-hoc manual deploys with a structured, validated, and auditable process. The pipeline integrates existing project tooling: CI scan for validation, GitNexus for impact analysis, and OMK multi-agent orchestration for build/push/deploy/verify.

## Technical Context

**Language/Version**: Bash, Docker, Docker Compose, Python 3 (for tests)
**Primary Dependencies**: GitHub Actions, Ansible, GitNexus CLI, OMK ensemble
**Storage**: N/A (stateless pipeline, artifacts stored in git and container registry)
**Testing**: pytest integration tests, shell-based health probes
**Target Platform**: Ubuntu VPS (vps-lite profile)
**Project Type**: DevSecOps automation / infrastructure pipeline
**Performance Goals**: Deploy completes in under 10 minutes
**Constraints**: VPS has ~4GB RAM; vps-lite profile only; no downtime for SIP signaling
**Scale/Scope**: Single VPS target, 7 services, Docker Compose orchestration

## Constitution Check

- Docker-first delivery: All services deployed as containers
- PostgreSQL-only: No database changes during deploy
- OpenSIPS 3.6 LTS: Config must validate before deploy
- No committed secrets: Secrets managed via Docker secrets on target

## Project Structure

### Documentation (this feature)

```text
specs/009-vps-deploy-automation/
├── spec.md              # Feature specification
├── plan.md              # This file
├── checklists/
│   └── requirements.md  # Spec quality checklist
└── tasks.md             # Actionable tasks
```

### Source Code (repository root)

```text
deploy/
├── scripts/
│   └── orchestrate-deploy.sh   # Main pipeline executor (updated)
├── ansible/
│   └── playbook-deploy.yml     # Ansible deploy (updated if needed)
└── ...
```

## Implementation Phases

### Phase 0 — Research & Analysis (Complete)

- [x] GitNexus index verified up-to-date
- [x] Existing deploy scripts inventoried (`vps-deploy.sh`, `deploy-to-tsiapp.sh`, `orchestrate-deploy.sh`)
- [x] VPS current state verified (7 services healthy)
- [x] Quality gates report verified PASS

### Phase 1 — Pipeline Design

**Objective**: Design the deploy pipeline architecture and integration points.

**Deliverables**:
1. Updated `deploy/scripts/orchestrate-deploy.sh` with gated stages
2. GitNexus impact analysis integration (pre-deploy hook)
3. OMK agent definitions for Builder, Pusher, Deployer, Verifier
4. Rollback mechanism (docker compose down + up with previous image)

**Key Decisions**:
- Use existing `docker-compose.vps.yml` as the deploy target
- Use GHCR for image registry (fallback: build on target)
- Use Tailscale for secure VPS access
- Store pipeline state in `.omk/memory/` for audit trail

### Phase 2 — Implementation Tasks

See `tasks.md` for detailed task breakdown.

## Risk Mitigation

| Risk | Mitigation |
|---|---|
| Registry credentials missing | Fallback to build-on-target mode |
| VPS disk full | Pre-flight check before any pull/build |
| Config syntax invalid | Gate: `opensips -c` must pass before deploy |
| Network partition | Verifier timeout + alert (no auto-retry) |
| Git mutations blocked | Explicit user approval gate |

## Success Validation

- Pipeline runs end-to-end without manual intervention
- All 7 containers healthy after deploy
- SIP OPTIONS probe returns 200 OK
- Wiki and OCP endpoints return 200
- Checklist artifacts generated for every run
