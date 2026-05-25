# Feature Specification: VPS Deploy Automation Pipeline

## Overview

**Feature**: VPS Deploy Automation Pipeline
**Short name**: vps-deploy-automation
**Created**: 2026-05-19
**Status**: Completed

### Context

TSiSIP is a Docker-first SIP edge proxy platform deployed to a production VPS running a vps-lite profile. Currently, deploys rely on manual scripts and ad-hoc commands. This feature introduces a structured deploy pipeline using the project's existing tooling ecosystem for validation, impact analysis, and multi-agent orchestration.

### Objective

Establish an automated deploy pipeline that validates every deploy against quality gates, analyzes code impact, orchestrates build/push/deploy/verify steps, and produces auditable artifacts.

---

## User Scenarios & Testing

### Primary Flows

#### Scenario 1: Automated deploy of pending changes
- **Given** there are validated, committed changes ready for deploy
- **When** the deploy pipeline is triggered
- **Then** the system builds images, pushes to registry, deploys to VPS, and verifies health

#### Scenario 2: Impact analysis before deploy
- **Given** a developer has modified core configuration files
- **When** the deploy pipeline starts
- **Then** impact analysis runs and halts if HIGH or CRITICAL risk is detected

#### Scenario 3: Rollback on verification failure
- **Given** a deploy has completed but post-deploy checks fail
- **When** health checks or SIP probes return errors
- **Then** the pipeline automatically rolls back to the previous known-good state

### Edge Cases & Error Conditions

- Registry unreachable: pipeline must fall back to building images on the target host
- VPS disk full: pre-flight check must halt before pulling images
- OpenSIPS config syntax invalid: build agent must fail before deploy
- Network partition during deploy: verifier agent must timeout and alert

---

## Functional Requirements

### FR-009-001: Pre-Deploy Validation Gate
**Description**: Every deploy must pass automated validation before execution.
**Acceptance Criteria**:
- CI scan passes (no hardcoded latest tags, no forbidden modules, images pinned, memory limits present, no committed secrets)
- Quality gates report shows PASS or PASS_WITH_WARNINGS with zero blocking issues
- OpenSIPS config syntax validates successfully inside container

### FR-009-002: Impact Analysis Before Deploy
**Description**: Modified files must be analyzed for blast radius before deploy.
**Acceptance Criteria**:
- Change detection maps pending diff to affected symbols and execution flows
- Impact analysis reports risk level for each modified component
- Deploy halts if HIGH or CRITICAL risk is detected on core config or compose files

### FR-009-003: Multi-Agent Build and Push
**Description**: Container images must be built and pushed to the registry by isolated agents.
**Acceptance Criteria**:
- Builder agent builds only images with modified Dockerfiles or dependent configs
- Pusher agent tags and pushes to container registry using available credentials
- If registry credentials are unavailable, pipeline falls back to build-on-target mode

> **Architecture Note**: The "agents" referenced above are local shell scripts or CI job stages (not long-running containerized services). They execute on the operator's workstation or CI runner and invoke standard Docker CLI commands. This aligns with Constitution §1 (Docker-first delivery of runtime components) by ensuring only the resulting container images are deployed, not the build tools themselves.

### FR-009-004: Coordinated Deploy to VPS
**Description**: The target VPS must receive updated code and containers with zero-downtime where possible.
**Acceptance Criteria**:
- Deployer agent syncs code to target host
- Docker Compose pulls updated images and recreates changed containers
- Database data and secrets are preserved across deploys
- Core services restart in correct dependency order

### FR-009-005: Post-Deploy Verification
**Description**: After deploy, the system must validate that all services are healthy and functional.
**Acceptance Criteria**:
- All containers report healthy status
- Web login page responds with correct branding
- Wiki endpoints return HTTP 200
- SIP OPTIONS probe receives 200 OK from proxy
- Backup metrics endpoint responds on loopback

### FR-009-006: Audit Trail and Observability
**Description**: Every deploy must produce auditable artifacts.
**Acceptance Criteria**:
- Deploy is documented in a checklist with pass/fail status for each gate
- Git commit SHA is recorded
- Container image digests are recorded
- Post-deploy validation results are logged

---

## Success Criteria

| ID | Criterion | Measurement | Target |
|---|---|---|---|
| SC-001 | Deploy completes without manual intervention | Pipeline run time from trigger to verification | Under 10 minutes |
| SC-002 | Zero regressions from validated deploys | Post-deploy failure rate | 0% for deploys passing all gates |
| SC-003 | Deploy process is reusable | Time to set up pipeline on new host | Under 30 minutes |
| SC-004 | All stakeholders can audit deploys | Checklist and logs accessible | 100% of deploys documented |

---

## Key Entities

### Entity: Deploy Pipeline Run
- **Attributes**: run_id, trigger_time, git_sha, feature_dir, validation_status, deploy_status, verification_status
- **Relationships**: references Feature spec, produces Checklist artifacts

### Entity: Validation Gate
- **Attributes**: gate_name, status (pass/fail/block), output_log
- **Relationships**: belongs to Deploy Pipeline Run

### Entity: Target Host
- **Attributes**: hostname, profile (vps-lite/prod), docker_version, available_ram, available_disk
- **Relationships**: receives Deploy Pipeline Run

---

## Scope

### In Scope
- Automated validation gates before deploy
- Impact analysis integration
- Multi-agent orchestration for build/push/deploy/verify
- VPS vps-lite profile deploy target
- Post-deploy health and SIP validation
- Audit trail generation

### Out of Scope
- Blue-green or canary deploy strategies
- Multi-region deploy orchestration
- Automated rollback to arbitrary historical states
- Kubernetes or swarm orchestration (Docker Compose only)

---

## Dependencies

- Feature 008 (DevSecOps Deployment): Ansible playbooks, deploy scripts, and registry setup
- Feature 001 (OpenSIPS Docker Edge Proxy): Container images and compose topology
- Code intelligence index must be up-to-date for impact analysis
- Agent ensemble must be configured for multi-agent execution

---

## Assumptions

- Target VPS has Docker and Docker Compose plugin installed
- Target VPS has network access to container registry or accepts build-on-fallback
- Secrets directory exists on target and is not committed to git
- Environment file on target is compatible with compose variable substitution
- Target VPS has sufficient RAM (32GB) and disk (100GB+) for full stack or uses vps-lite profile
- SSH connectivity is stable; deployer handles transient timeouts with retry logic

---

## Risks

| Risk | Impact | Likelihood | Mitigation |
|---|---|---|---|
| Registry credentials missing blocks push | High | Medium | Fallback to build-on-target mode |
| VPS disk full during pull | High | Low | Pre-flight disk check halts before pull |
| Config invalidates after deploy | High | Low | Syntax check gate before deploy |
| Network partition during deploy | Medium | Low | Verifier timeout and manual recovery playbook |
| Git mutations not confirmed by user | Medium | High | Explicit approval gate before commit/push |
| VPS extreme load during deploy (load avg >100) | Critical | Medium | Pre-flight load check; abort deploy if load >50 |
| SSH pipe break during compose up | High | Medium | Artifact-based image transfer; non-interactive SSH |
| GHCR permission denied (`write_package`) | High | High | Use GITHUB_TOKEN with `packages: write`; fallback to build-on-target |

---

## Notes

- This feature formalizes the deploy process that was previously handled by ad-hoc scripts
- The pipeline should be triggerable both locally (developer workflow) and via CI/CD (workflow_dispatch)
- Consider integrating with existing orchestration scripts as the concrete executor

## Deploy Incidents and Resolutions

### Incident 2026-05-19: GHCR Push Permission Denied
**Symptom**: `denied: permission_denied: write_package` despite `packages: write` in workflow.
**Root Cause**: `GITHUB_TOKEN` in workflow lacks package write scope for cross-repository pushes to `ghcr.io/b0yz4kr14/tsisip/*`.
**Resolution**: Build-on-target fallback enabled. Images built as `.tar.gz` artifacts in CI, transferred to VPS via SSH pipe to `docker load`, or built directly on target using `docker-compose.build.yml` overlay.

### Incident 2026-05-19: RTPengine Init Error
**Symptom**: `exec: "--interface=...": no such file or directory` on container start.
**Root Cause**: Dockerfile used `ENTRYPOINT ["rtpengine", "--foreground", "--log-stderr"]` causing command args to be passed as executable path.
**Resolution**: Separated `ENTRYPOINT ["rtpengine"]` and `CMD ["--foreground", "--log-stderr"]` so compose `command:` array replaces CMD correctly.

### Incident 2026-05-19: VPS Critical Load During Deploy
**Symptom**: Load average spiked to ~166, docker commands hung, SSH connections dropped with `Broken pipe`.
**Root Cause**: Multiple concurrent `docker compose up` processes from retry attempts compounded by memory pressure (26GB/31GB used).
**Resolution**: Added pre-flight load check; abort deploy if load average >50. Background compose operations must be monitored and deduplicated.

### Incident 2026-05-19: Docker Compose Build Context Mismatch
**Symptom**: `COPY` commands failed during build because build context did not match Dockerfile expectations.
**Root Cause**: `docker-compose.build.yml` used incorrect `context` paths for services that COPY from project root (e.g., postgres healthcheck scripts).
**Resolution**: Created `docker-compose.build.yml` with per-service contexts matching CI build job: some use `.` (project root), others use `./docker/<service>` depending on Dockerfile COPY paths.

### Incident 2026-05-19: Postgres Permission Denied
**Symptom**: Postgres container failed to start with permission errors under `cap_drop: ALL`.
**Root Cause**: Postgres init scripts need `CHOWN`, `SETUID`, `SETGID`, `DAC_OVERRIDE` for data directory setup.
**Resolution**: Added `cap_add: [CHOWN, SETUID, SETGID, DAC_OVERRIDE]` to postgres service in `docker-compose.prod.yml`.

## Build-on-Target Fallback

When GHCR push fails, the pipeline falls back to building images directly on the VPS:

1. **Artifact Transfer Mode** (preferred): CI builds images, exports as `.tar.gz`, downloads as artifacts, loads on VPS via SSH pipe.
2. **On-Target Build Mode** (fallback): VPS runs `docker compose -f docker-compose.prod.yml -f docker-compose.build.yml build --parallel`.

The `docker-compose.build.yml` file provides build contexts for all services:
```yaml
services:
  opensips:    build: { context: ., dockerfile: Dockerfile }
  rtpengine:   build: { context: ./docker/rtpengine, dockerfile: Dockerfile }
  ocp:         build: { context: ., dockerfile: docker/ocp/Dockerfile }
  postgres:    build: { context: ., dockerfile: docker/postgres/Dockerfile }
  asterisk:    build: { context: ./docker/asterisk, dockerfile: Dockerfile }
  prometheus:  build: { context: ./docker/prometheus, dockerfile: Dockerfile }
  grafana:     build: { context: ./docker/grafana, dockerfile: Dockerfile }
  # ... additional services
```
