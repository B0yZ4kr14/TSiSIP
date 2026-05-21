# Plan: Global Requirement ID Migration

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
