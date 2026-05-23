# Feature 009 Memory Synthesis: VPS Deploy Automation Pipeline

## Current Scope
Structured deploy pipeline with gated validation, impact analysis, build/push/deploy/verify agents, and audit trail. Status: Implemented.

## Relevant Decisions
- Bash agent functions in orchestrate-deploy.sh (not containerized services).
- Separate CI (ci.yml) and deploy (deploy.yml) workflows.
- GHCR primary, build-on-target fallback.
- GitNexus impact analysis blocks HIGH-risk deploys.

## Active Architecture Constraints
- Docker-first; syntax validation before deploy.
- VPS ~4GB RAM, vps-lite only.
- Explicit git mutation approval.
- No SIP downtime.

## Accepted Deviations
- No blue-green, canary, or multi-region.
- No automated rollback to arbitrary states.

## Relevant Security Constraints
- No committed secrets.
- Pre-flight secrets scan.
- Fallback avoids secret exposure.

## Related Historical Lessons
- GHCR permission denied -> fallback modes.
- RTPengine ENTRYPOINT/CMD separation required.
- VPS load spike -> pre-flight load check.
- Build context mismatch -> per-service contexts.
- Postgres capabilities needed under cap_drop: ALL.

## Conflict Warnings
- Builds on Feature 008 scripts.

## Retrieval Notes
- Keywords: deploy pipeline, orchestrate-deploy.sh, workflow_dispatch, build-on-target.
- Related: 008, 001.
