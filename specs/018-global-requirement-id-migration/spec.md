# Feature 018: Global Requirement ID Migration

## Overview

**Feature**: Global Requirement ID Migration  
**Short name**: global-requirement-id-migration  
**Created**: 2026-05-21  
**Status**: In Progress  
**Depends on**: Cross-project analysis A4 completion

### Context

Cross-project analysis (report `speckit-analyze-cross-project-2026-05-21.md`) identified that `FR-001` through `FR-010` are reused across 11+ feature specifications with different meanings. This breaks requirement traceability, compliance mapping, and impact analysis.

### Objective

Establish and migrate to a feature-scoped requirement ID scheme (`FR-NNN-XXX`) that guarantees global uniqueness and enables automated traceability.

## Requirements

### FR-018-001: Feature-Scoped ID Scheme
The project SHALL use `FR-NNN-XXX` where `NNN` is the feature number and `XXX` is the requirement sequence within that feature.

### FR-018-002: Retroactive Migration
All existing specs (001–017) SHALL be updated to use the new scheme.

### FR-018-003: CI Validation
The `spec-validate.gate` SHALL reject specs containing flat `FR-XXX` IDs.

### FR-018-004: Tooling Integration
A validation script SHALL check for duplicate `FR-NNN-XXX` across all specs in CI.

## Acceptance Criteria
- Zero flat `FR-XXX` IDs remain in active specs.
- All cross-references (plans, tasks, tests, docs) updated.
- CI gate enforces the new scheme.

## References
- `docs/architecture/global-requirement-id-scheme.md`
- `reports/speckit-analyze-cross-project-2026-05-21.md` (A4)
