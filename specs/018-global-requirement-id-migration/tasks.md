# Tasks: Global Requirement ID Migration

**Last Updated**: 2026-05-21

## Phase 1 — Documentation

### [x] T1.1: Create global-requirement-id-scheme.md
**Description**: Document the scheme, migration plan, and acceptance criteria.
**Files affected**: `docs/architecture/global-requirement-id-scheme.md`
**Depends on**: —

### [x] T1.2: Create Feature 018 spec, plan, and tasks
**Description**: Create tracking artifacts for the migration work. *(Self-referential: artifacts created during speckit-specify phase.)*
**Files affected**: `specs/018-global-requirement-id-migration/`
**Depends on**: T1.1

## Phase 2 — Retroactive Migration

### [ ] T2.1: Migrate specs 001–005
**Description**: Update `spec.md`, `plan.md`, `tasks.md` for features 001–005.
**Depends on**: T1.2

### [ ] T2.2: Migrate specs 006–010
**Description**: Update `spec.md`, `plan.md`, `tasks.md` for features 006–010.
**Depends on**: T2.1

### [ ] T2.3: Migrate specs 011–017
**Description**: Update `spec.md`, `plan.md`, `tasks.md` for features 011–017.
**Depends on**: T2.2

### [ ] T2.4: Update cross-references in docs and tests
**Description**: Update all docs, reports, and test files that reference flat FR-IDs.
**Depends on**: T2.3

## Phase 3 — CI Gate

### [ ] T3.1: Add FR-ID validation script
**Description**: Script that greps all specs for flat `FR-[0-9]+` and fails if found.
**Depends on**: T2.4

### [ ] T3.2: Update spec-validate.gate
**Description**: Integrate FR-ID validation into the spec validation gate.
**Depends on**: T3.1

## Phase 4 — Verification

### [ ] T4.1: Run doctor and validate zero flat IDs
**Description**: Execute `speckit-utils.doctor` and confirm no flat FR-IDs remain.
**Depends on**: T3.2
