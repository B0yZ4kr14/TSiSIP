# Blueprint — Global Requirement ID Migration

## Overview

Migrate from flat requirement IDs (`FR-XXX`) to a feature-scoped scheme (`FR-NNN-XXX`) that guarantees global uniqueness and enables automated traceability across all specs, docs, tests, and scripts.

## Requirements

- **FR-018-001**: Feature-Scoped ID Scheme — use `FR-NNN-XXX` where `NNN` is the feature number and `XXX` is the requirement sequence.
- **FR-018-002**: Retroactive Migration — update all existing specs (001–017), docs, tests, `AGENTS.md`, `README.md`.
- **FR-018-003**: CI Validation — `spec-validate.gate` rejects specs containing flat `FR-XXX` IDs.
- **FR-018-004**: Tooling Integration — validation script checks for duplicate `FR-NNN-XXX` across all specs; exits code 1 on conflict; produces `reports/fr-id-duplicates.json`.

## Architecture

- **Scheme**: `FR-NNN-XXX` (feature-scoped, globally unique).
- **Scope**: All `specs/<NNN-feature>/spec.md`, `plan.md`, `tasks.md`; `docs/**/*.md`; `tests/**/*.py`; `AGENTS.md`; `README.md`.
- **Validation**: CI gate script greps for flat `FR-[0-9]+`; duplicate detection script produces JSON report.

## Implementation Plan

### Phase 1 — Documentation & Scheme Finalization
- Finalize `docs/architecture/global-requirement-id-scheme.md`.
- Get sign-off on scheme rules.

### Phase 2 — Retroactive Migration (Batch)
- Update specs 001–010 (batch 1).
- Update specs 011–017 (batch 2).
- Update cross-references in docs, tests, and scripts.

### Phase 3 — CI Gate Integration
- Add validation script to `.github/workflows/ci.yml`.
- Update `spec-validate.gate` to reject flat IDs.

### Phase 4 — Verification
- Run `speckit-utils.doctor` to confirm zero flat IDs remain.
- Run `spec-validate.gate` on all specs.

## Tasks

**Phase 1 — Documentation**
- T1.1: Create `docs/architecture/global-requirement-id-scheme.md`
- T1.2: Create Feature 018 spec, plan, and tasks

**Phase 2 — Retroactive Migration**
- T2.1: Migrate specs 001–005
- T2.2: Migrate specs 006–010
- T2.3: Migrate specs 011–017
- T2.4: Update cross-references in docs and tests

**Phase 3 — CI Gate**
- T3.1: Add FR-ID validation script
- T3.2: Update `spec-validate.gate`

**Phase 4 — Verification**
- T4.1: Run doctor and validate zero flat IDs

## Validation

- Zero flat `FR-XXX` IDs remain in active specs.
- All cross-references updated across `spec.md`, `plan.md`, `tasks.md`, `docs/**/*.md`, `tests/**/*.py`, `AGENTS.md`, `README.md`.
- CI gate enforces the new scheme.
- Duplicate detection script exits code 1, emits conflicting IDs with file paths to stderr, and produces `reports/fr-id-duplicates.json`.

## Risks & Dependencies

| Risk | Mitigation |
|---|---|
| Missed flat ID in obscure file | Run `speckit-utils.doctor` across entire repo |
| Cross-reference breakage after migration | Batch update with scripted search/replace; review diff |
| CI gate false positive on non-requirement "FR-" text | Regex anchors to `FR-[0-9]+` pattern only |

**Dependencies**: Cross-project analysis A4 completion; CI pipeline access; `speckit-utils.doctor`.
