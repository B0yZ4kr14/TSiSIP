# Tasks: VPS Deploy Automation Pipeline
**Last Updated**: 2026-05-19


## Phase 1 — Pipeline Architecture

### [pending] T1.1: Update orchestrate-deploy.sh with gated stages
**Description**: Refactor `deploy/scripts/orchestrate-deploy.sh` to implement distinct stages: validation, impact-analysis, build, push, deploy, verify. Each stage must exit non-zero on failure and log stage status.
**Phase**: 1
**Depends on**: —
**Parallel**: No
**Acceptance**: Script runs and exits 0 when all stages pass, exits 1 on first failure.

### [pending] T1.2: Add GitNexus impact analysis pre-deploy hook
**Description**: Add a stage to `orchestrate-deploy.sh` that runs `npx gitnexus analyze` if index is stale, then `gitnexus_detect_changes()` and `gitnexus_impact()` on modified files. Block deploy if HIGH or CRITICAL risk detected on core configs.
**Phase**: 1
**Depends on**: T1.1
**Parallel**: No
**Acceptance**: Script halts with error message when impact analysis returns HIGH risk on opensips.cfg.tpl or compose files.

### [pending] T1.3: Add rollback mechanism
**Description**: Implement rollback in `orchestrate-deploy.sh` that captures current running image digests before deploy and reverts to them if post-deploy verification fails.
**Phase**: 1
**Depends on**: T1.1
**Parallel**: No
**Acceptance**: Simulated failure in verification stage triggers rollback to previous images.

## Phase 2 — OMK Agent Orchestration

### [pending] T2.1: Define OMK agent roles for deploy pipeline
**Description**: Create agent role definitions in `.omk/agents/` for Builder, Pusher, Deployer, Verifier. Each role has a focused prompt and tool access (Builder: Shell/Docker; Pusher: Shell/Docker; Deployer: Shell/SSH; Verifier: Shell/HTTP/SIP).
**Phase**: 2
**Depends on**: T1.1
**Parallel**: No
**Acceptance**: All 4 agent YAML files exist and are loadable by OMK.

### [pending] T2.2: Implement Builder agent logic
**Description**: Builder agent detects which Dockerfiles changed (via git diff), builds only those images, and tags them with registry prefix.
**Phase**: 2
**Depends on**: T2.1
**Parallel**: [P] with T2.3
**Acceptance**: Running Builder agent produces tagged images for all modified Dockerfiles.

### [pending] T2.3: Implement Pusher agent logic
**Description**: Pusher agent attempts docker login and push to registry. If credentials missing, signals fallback to build-on-target mode.
**Phase**: 2
**Depends on**: T2.1
**Parallel**: [P] with T2.2
**Acceptance**: Pusher successfully pushes images when credentials available; gracefully fails over when not.

### [pending] T2.4: Implement Deployer agent logic
**Description**: Deployer agent connects to target host, syncs code (git pull or rsync), runs docker compose pull/up, and waits for containers healthy.
**Phase**: 2
**Depends on**: T2.2, T2.3
**Parallel**: No
**Acceptance**: Deployer completes without error and all containers reach healthy status.

### [pending] T2.5: Implement Verifier agent logic
**Description**: Verifier agent runs post-deploy checks: container health, HTTP probes on OCP and wiki, SIP OPTIONS probe, backup metrics endpoint.
**Phase**: 2
**Depends on**: T2.4
**Parallel**: No
**Acceptance**: All probes pass; if any fail, triggers rollback.

## Phase 3 — Integration & Testing

### [pending] T3.1: Integrate pipeline with GitHub Actions
**Description**: Update `.github/workflows/ci.yml` deploy job to call `orchestrate-deploy.sh` instead of raw ansible-playbook. Pass target host and credentials via secrets.
**Phase**: 3
**Depends on**: T1.1, T2.4
**Parallel**: No
**Acceptance**: workflow_dispatch trigger runs pipeline end-to-end.

### [pending] T3.2: Run end-to-end dry-run test
**Description**: Execute `orchestrate-deploy.sh --dry-run` against the target VPS. Verify all stages execute without side effects.
**Phase**: 3
**Depends on**: T2.5
**Parallel**: No
**Acceptance**: Dry-run completes with stage-by-stage report and no container changes.

### [pending] T3.3: Run live deploy test
**Description**: Execute full pipeline against target VPS with current pending changes. Verify post-deploy health.
**Phase**: 3
**Depends on**: T3.2
**Parallel**: No
**Acceptance**: All containers healthy, SIP probe passes, wiki/OCP respond 200.

## Phase 4 — Documentation & Handoff

### [pending] T4.1: Update deploy README
**Description**: Update `deploy/README-VPS-DEPLOY.md` with new pipeline usage instructions.
**Phase**: 4
**Depends on**: T3.3
**Parallel**: No
**Acceptance**: README documents how to trigger pipeline, expected runtime, and troubleshooting.

### [pending] T4.2: Update AGENTS.md deploy section
**Description**: Add section to `AGENTS.md` describing the deploy pipeline and agent roles.
**Phase**: 4
**Depends on**: T3.3
**Parallel**: [P] with T4.1
**Acceptance**: AGENTS.md references Feature 009 and links to pipeline docs.
