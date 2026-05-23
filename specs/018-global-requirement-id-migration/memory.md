# Feature 018 Memory: Global Requirement ID Migration

## Current Scope
Migrate all requirement identifiers from flat `FR-XXX` to feature-scoped `FR-NNN-XXX` scheme across all specs, docs, tests, and scripts; add CI validation gate and duplicate detection.

## Relevant Decisions
- **Feature-scoped scheme**: `FR-NNN-XXX` where `NNN` is the feature number and `XXX` is the requirement sequence within that feature.
- **Batch migration**: Two batches (001–010, then 011–017) plus cross-reference updates in docs and tests.
- **CI gate rejects flat IDs**: `spec-validate.gate` fails on detection of any `FR-XXX` pattern.
- **Duplicate detection script**: Exits 1, emits conflicting IDs to stderr, and produces `reports/fr-id-duplicates.json`.

## Active Architecture Constraints
- All active specs under `specs/` must comply; exclude `.specify/backups/` and `*.bak`.
- Retroactive updates required across `spec.md`, `plan.md`, `tasks.md`, `docs/**/*.md`, `tests/**/*.py`, `AGENTS.md`, `README.md`.
- Machine-readable JSON report for duplicate detection.

## Accepted Deviations
- None.

## Relevant Security Constraints
- N/A (documentation and process feature).

## Related Historical Lessons
- Flat IDs across features caused ambiguity in cross-project analysis (report A4) and broke requirement traceability.
- Automated validation gates prevent regression to flat IDs after migration.
- Batch migration is more efficient than per-feature piecemeal updates.

## Conflict Warnings
- Self-referential: Feature 018 spec itself must use the new scheme.
- Any in-flight specs being written during migration must adopt the new scheme from creation.

## Retrieval Notes
- Search terms: requirement ID, FR-NNN-XXX, FR-XXX, traceability, spec validation, migration.
- Related features: All features 001–017 (migration targets).
