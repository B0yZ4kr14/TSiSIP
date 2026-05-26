# Feature 018: Global Requirement ID Migration

## Overview

**Feature**: Global Requirement ID Migration  
**Short name**: global-requirement-id-migration  
**Created**: 2026-05-21  
**Status**: Completed  
**Depends on**: Cross-project analysis A4 completion

### Context

Cross-project analysis (report `speckit-analyze-cross-project-2026-05-21.md`) identified that flat IDs (e.g., `FR-001-001` in Feature 001 vs `FR-002-001` in Feature 002) were previously ambiguous because the old scheme reused the same requirement numbers (e.g., requirement 001 in Feature 001, Feature 002, and Feature 003) across features with different meanings. This breaks requirement traceability, compliance mapping, and impact analysis.

### Objective

Establish and migrate to a feature-scoped requirement ID scheme (`FR-NNN-XXX`) that guarantees global uniqueness and enables automated traceability.

## Requirements

### FR-018-001: Feature-Scoped ID Scheme
The project SHALL use `FR-NNN-XXX` where `NNN` is the feature number and `XXX` is the requirement sequence within that feature.

### FR-018-002: Retroactive Migration
All existing specs (001–017) SHALL be updated to use the new scheme. The migration scope includes all occurrences of flat `FR-XXX` IDs in:
- `specs/<NNN-feature>/spec.md`, `plan.md`, `tasks.md`
- `docs/**/*.md` (architecture, runbooks, evidence)
- `tests/**/*.py` (integration test headers and assertions)
- `AGENTS.md`, `README.md`, and any other project-level markdown that references requirement IDs

### FR-018-003: CI Validation
The `spec-validate.gate` SHALL reject specs containing flat `FR-XXX` IDs.

### FR-018-004: Tooling Integration
A validation script SHALL check for duplicate `FR-NNN-XXX` across all specs in CI. On detection of any duplicate:
- The script SHALL exit with code 1.
- The script SHALL emit each conflicting ID and its file path(s) to stderr.
- The script SHALL produce a machine-readable JSON report at `reports/fr-id-duplicates.json`.

## Definitions
- **Active specs**: All subdirectories under `specs/` containing `spec.md`, excluding `.specify/backups/` and any `*.bak` files.
- **Flat ID**: A requirement identifier in the form `FR-XXX` (without feature scoping).
- **Feature-scoped ID**: A requirement identifier in the form `FR-NNN-XXX`.

## Acceptance Criteria
- Zero flat `FR-XXX` IDs remain in active specs.
- All cross-references updated across: `spec.md`, `plan.md`, `tasks.md`, `docs/**/*.md`, `tests/**/*.py`, `AGENTS.md`, `README.md`.
- CI gate enforces the new scheme.
- Duplicate detection script exits code 1, emits conflicting IDs with file paths to stderr, and produces `reports/fr-id-duplicates.json`.

## References
- `docs/architecture/global-requirement-id-scheme.md`
- `reports/speckit-analyze-cross-project-2026-05-21.md` (A4)

## User Scenarios & Testing

### Scenario 1: Primary happy-path flow
- **Given** the feature is enabled and all dependencies are healthy
- **When** an authorized user performs the canonical action
- **Then** the system responds correctly and produces the expected outcome

### Scenario 2: Error or edge-case handling
- **Given** the feature is enabled
- **When** an invalid input or failure condition occurs
- **Then** the system fails gracefully with a clear error and no data corruption

### Scenario 3: Administrative or operational flow
- **Given** an operator with appropriate role permissions
- **When** the operator inspects or modifies configuration
- **Then** the change is persisted, auditable, and reflected in runtime behavior


## Success Criteria

| ID | Criterion | Measurement | Target |
|---|---|---|---|
| SC-018-001 | Feature functional completeness | End-to-end validation test pass rate | 100% |
| SC-018-002 | Configuration persistence | Restart test with prior configuration | Pass |
| SC-018-003 | Zero regression in existing flows | Existing integration tests pass rate | 100% |
| SC-018-004 | Observability coverage | Metrics/audit events present | 100% of mutating actions |

