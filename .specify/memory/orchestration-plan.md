# TSiSIP Orchestration Plan — Speckit Governance Refresh

## Phase 0: Foundation (COMPLETE)
- [x] Clone speckit official repository
- [x] Install specify-cli v0.8.12.dev0 via uv
- [x] Configure community catalogs (extensions, workflows, presets) with install_allowed: true
- [x] Install extensions: Blueprint, Agent Governance, Architecture Guard, Spec Validate, SDD Utilities, Memory Loader
- [x] Install presets: Agent Parity Governance, Architecture Governance, Security Governance
- [x] Install workflow: Full SDD Cycle (speckit)
- [x] Set default integration to Kimi
- [x] Create `.specify/memory/agent-governance.md`
- [x] Create `.specify/memory/constitution.md`
- [x] Create `.specify/memory/architecture_constitution.md`
- [x] Update `AGENTS.md` with Speckit Governance section

## Phase 1: Project Health Validation (COMPLETE)
- [x] Run `speckit.speckit-utils.doctor` — validate templates, agent config, scripts, constitution, feature artifacts
- [x] Run `speckit.memory-loader.load` — load all governance memory files into context
- [x] Run `speckit.spec-validate.status` — check approval state of all spec artifacts
- [x] Fix any P0 health check failures

## Phase 2: Spec Drift Detection Phase 2: Spec Drift Detection & Remediation Remediation (COMPLETE)
- [x] Run `speckit.architecture-guard.architecture-review` on specs 001-009
- [x] Detect violations in plans, tasks, and implementation summaries
- [x] Generate refactor tasks for P1/P2 drift
- [x] Update outdated specs to match current implementation (TLS fix, cachedb_local, admin auth)

## Phase 3: Documentation Refresh (COMPLETE)
- [x] Update `docs/TSiSIP-CANONICAL-SPEC.md` with:
  - OCP admin authentication section
  - Updated auth contract (401 vs 407 clarification)
  - `cachedb_local` vs `htable` documentation
  - Rate limiting implementation details
- [x] Update `docs/TSiSIP-OPERATOR-RUNBOOK.md` with:
  - Admin user creation procedure
  - OCP login troubleshooting
  - PostgreSQL password reset procedure
- [x] Update `docs/TSiSIP-AGENT-ORCHESTRATION-PLAYBOOK.md` with:
  - Speckit integration workflow
  - Architecture Guard usage
  - GitNexus + OMK + Speckit triad

## Phase 4: Blueprint Generation (COMPLETE)
- [x] Run `speckit.blueprint.generate` for spec 009 (VPS Deploy Automation)
- [x] Run `speckit.blueprint.generate scaffold` for next feature (if defined)
- [x] Validate blueprints against existing codebase

## Phase 5: Governance Automation Setup
- [x] Configure `speckit.spec-validate` gates for CI
- [x] Set up periodic `speckit.architecture-guard.violation-detection` cron
- [x] Configure `speckit.agent-governance.refresh` as pre-commit hook
- [x] Document the governance triad: GitNexus (impact) → OMK (orchestration) → Speckit (specs)

## Phase 6: TSI-Vault Integration
- [x] Sync updated docs to Obsidian vault via MCP
- [x] Create vault notes for: constitution, architecture_constitution, agent-governance
- [x] Tag vault notes with `#speckit`, `#governance`, `#architecture`

## Execution Order
1. Phase 1 → immediate validation
2. Phase 2 → parallel review of all 9 specs
3. Phase 3 → sequential doc updates (canonical first)
4. Phase 4 → blueprint for active feature
5. Phase 5 → automation configuration
6. Phase 6 → vault sync
