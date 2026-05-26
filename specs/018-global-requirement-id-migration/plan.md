# Plan: Global Requirement ID Migration

## Summary

This plan implements the feature described in the companion spec.md. It covers infrastructure changes, application code, validation gates, and deployment steps required to deliver the capability in a Docker-first, PostgreSQL-only TSiSIP environment.

## Technical Context

**Language/Version**: Bash, Docker, Docker Compose, Python 3 (for tests), PHP 8.2 (for OCP features), OpenSIPS 3.6 LTS config
**Primary Dependencies**: OpenSIPS 3.6 LTS, PostgreSQL 16, Docker Engine + Compose V2
**Testing**: pytest integration tests, shell-based health probes, PHP syntax validation
**Target Platform**: Docker containers (local dev + VPS production)
**Project Type**: Infrastructure / DevSecOps / SIP edge proxy

## Project Structure

```
specs/018-global-requirement-id-migration/
├── spec.md              # Feature specification
├── plan.md              # This implementation plan
├── tasks.md             # Actionable task breakdown
└── checklists/          # Quality checklists (if present)
```



## Phase 1 — Documentation & Scheme Finalization
- Finalize `docs/architecture/global-requirement-id-scheme.md`
- Get sign-off on scheme rules

## Phase 2 — Retroactive Migration (Batch)
- Update specs 001–010 (batch 1)
- Update specs 011–017 (batch 2)
- Update cross-references in docs, tests, and scripts

## Phase 3 — CI Gate Integration
- Add validation script to `.github/workflows/ci.yml`
- Update `spec-validate.gate` to reject flat IDs

## Phase 4 — Verification
- Run `speckit-utils.doctor` to confirm zero flat IDs remain
- Run `spec-validate.gate` on all specs
