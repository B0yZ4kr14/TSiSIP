# Orchestrated Implementation Plan: A1–A4 Corrections (Feature 018)

**Mode**: Socratic + Popperian (OMK enforced)  
**Source of Truth**: Cross-project analysis A4 + speckit-analyze findings  
**Objective**: Remediate HIGH/MEDIUM findings A1–A4 in Feature 018 spec/plan/tasks before Phase 2 migration begins.

---

## Socratic Mode — Questioning Assumptions

### A1: "Active specs" undefined
- Q: What constitutes an "inactive" spec? A historical report? A deleted feature?
- Q: Should archived specs in `.specify/backups/` be exempt?
- Q: Is the definition recursive — does it apply to future specs automatically?

### A2: Retroactive migration scope ambiguous
- Q: If we update spec.md but not plan.md, is the spec "migrated"?
- Q: Do tests in `tests/integration/` reference FR-IDs? If so, who updates them?
- Q: What about `docs/TSiSIP-CANONICAL-SPEC.md` which references requirements by number?

### A3: T1.2 self-referential
- Q: Can a task create its own container? Is this a recursion paradox?
- Q: If T1.2 is unchecked, does it block T2.1 (depends on T1.2)?

### A4: Duplicate detection behavior
- Q: Should the script fail fast on first duplicate, or report all duplicates?
- Q: Is a duplicate within the same spec (copy-paste error) different from across specs?
- Q: Should the script also detect orphaned IDs (defined but never referenced)?

---

## Popperian Mode — Attempting Falsification

### Falsification Hypotheses

1. **H1**: Defining "active specs" as `specs/*/` will accidentally include `.specify/backups/orchestrated-plans/` if moved.
   - **Test**: Verify `.specify/backups/` is outside `specs/`.
   - **Result**: PASS — backups are in `.specify/`, not `specs/`.

2. **H2**: Updating all 17 specs atomically is impossible without breaking intermediate git states.
   - **Test**: Can batches 001–005 be committed independently without leaving broken references?
   - **Result**: PARTIAL — cross-references between specs (e.g., Feature 008 referencing Feature 003) would break if not updated together.
   - **Mitigation**: Process batches in dependency order (lower feature numbers first) and update cross-references last.

3. **H3**: The CI validation script will produce false positives on legitimate text containing "FR-" (e.g., "FR-France" in a comment).
   - **Test**: Regex `FR-\d+` matches "FR-ance"? No. Matches "FR-001"? Yes.
   - **Result**: PASS — `FR-[0-9]+` is sufficiently specific.

4. **H4**: Removing T1.2 entirely breaks the task dependency chain (T2.1 depends on T1.2).
   - **Test**: If T1.2 is removed, does T2.1 become rootless?
   - **Result**: PARTIAL — T2.1 would need explicit dependency on T1.1 or nothing.
   - **Mitigation**: Mark T1.2 as completed rather than removing it.

---

## Approved Corrections (Post-Socratic/Popperian)

| ID | Correction | Files | Rationale |
|---|---|---|---|
| A1 | Add definition: "Active specs = all subdirectories under `specs/` excluding `.specify/backups/` and files matching `*.bak`" | spec.md | Prevents scope creep; excludes archival material |
| A2 | Expand FR-018-002 scope list: `spec.md`, `plan.md`, `tasks.md`, `docs/*.md`, `tests/**/*.py`, `AGENTS.md`, `README.md` | spec.md | Eliminates ambiguity; all FR-ID references must be updated |
| A3 | Mark T1.2 as `[x]` and add note: "Self-referential; artifacts created during speckit-specify phase" | tasks.md | Resolves inconsistency without breaking dependency chain |
| A4 | Add to FR-018-004: "Script SHALL exit code 1, emit conflicting IDs with file paths to stderr, and produce a JSON report at `reports/fr-id-duplicates.json`" | spec.md | Measurable, automatable, CI-friendly |

---

## Execution Order

1. Apply A1 to spec.md
2. Apply A2 to spec.md
3. Apply A4 to spec.md
4. Apply A3 to tasks.md
5. Commit all changes
6. Re-run speckit-analyze to verify zero A1–A4 regressions
