# Feature 018 Memory Synthesis: Global Requirement ID Migration

## Current Scope
Migrate all requirement IDs from flat `FR-XXX` to feature-scoped `FR-NNN-XXX` with CI enforcement and duplicate detection.

## Relevant Decisions
- `FR-NNN-XXX` scheme (feature number + sequence).
- Batch migration: 001–010 then 011–017.
- CI gate rejects flat IDs.
- Duplicate script produces `reports/fr-id-duplicates.json`.

## Active Architecture Constraints
- All `specs/` must comply; exclude backups.
- Retroactive updates across specs, docs, tests, `AGENTS.md`, `README.md`.

## Accepted Deviations
None.

## Relevant Security Constraints
N/A.

## Related Historical Lessons
- Flat IDs broke cross-project traceability (report A4).
- Validation gates prevent regression.
- Batch migration more efficient than piecemeal.

## Conflict Warnings
- Self-referential; new specs must adopt scheme immediately.

## Retrieval Notes
- Keywords: requirement ID, FR-NNN-XXX, traceability, spec validation.
- Related: All features 001–017.
