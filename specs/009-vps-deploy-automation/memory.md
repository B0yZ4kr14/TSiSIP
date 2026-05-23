# Feature 009 Memory: VPS Deploy Automation Pipeline

## Current Scope
Structured deploy pipeline replacing ad-hoc manual deploys with validation gates, impact analysis, multi-agent build/push/deploy/verify orchestration, and auditable artifacts. Status: Implemented with known limitations.

## Relevant Decisions
- **Shell-based agent functions** (not containerized services): builder(), pusher(), deployer(), verifier() implemented as bash functions in orchestrate-deploy.sh.
- **Separate CI and deploy workflows**: .github/workflows/ci.yml for validation/build on push/PR; .github/workflows/deploy.yml as workflow_dispatch-only pipeline.
- **GHCR with build-on-target fallback**: Artifact transfer mode (.tar.gz via SSH pipe) preferred; on-target build as fallback.
- **GitNexus impact analysis pre-deploy hook**: Blocks deploy if HIGH or CRITICAL risk detected on core configs.

## Active Architecture Constraints
- Docker-first delivery: only container images deployed, not build tools.
- OpenSIPS config syntax must validate before deploy.
- VPS has ~4GB RAM; vps-lite profile only.
- Explicit approval gate for git mutations (commit/push).
- No downtime for SIP signaling.

## Accepted Deviations
- Blue-green, canary, and multi-region deploy strategies are out of scope.
- Automated rollback to arbitrary historical states is out of scope.
- Kubernetes or Swarm orchestration not used (Docker Compose only).

## Relevant Security Constraints
- No secrets committed; managed via Docker secrets on target.
- Pre-flight committed-secrets scan.
- Registry credentials missing triggers build-on-target fallback (no secret exposure).

## Related Historical Lessons
- GHCR write_package permission denied (2026-05-19): GITHUB_TOKEN lacks cross-repo scope; fallback modes documented.
- RTPengine init error (2026-05-19): ENTRYPOINT/CMD separation required for compose command: override.
- VPS critical load during deploy (2026-05-19): concurrent docker compose up retries caused memory pressure; pre-flight load check added.
- Docker Compose build context mismatch: per-service context paths required in docker-compose.build.yml.
- Postgres permission denied under cap_drop: ALL: required capabilities added.

## Conflict Warnings
- Builds on Feature 008 (DevSecOps scripts and Ansible playbooks).
- Feature 001 container images are the deploy artifacts.

## Retrieval Notes
- Search terms: deploy pipeline, orchestrate-deploy.sh, GitHub Actions, workflow_dispatch, build-on-target, impact analysis, verifier.
- Related features: 008 (DevSecOps foundation), 001 (container images).
