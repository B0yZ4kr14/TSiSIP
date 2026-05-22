# Checklist: Requirements Quality — Feature 018 (Global Requirement ID Migration)

**Purpose**: Validate the quality, clarity, and completeness of Feature 018 requirements.
**Created**: 2026-05-21
**Focus**: Requirement writing quality (not implementation verification)

---

## Requirement Completeness

- [ ] CHK001 — Are conflict-resolution rules defined when two specs assign different meanings to the same flat `FR-XXX` ID? [Completeness, Gap, Spec §FR-018-002]
- [ ] CHK002 — Is the definition of "active specs" quantified (e.g., all specs in `specs/*/`, excluding archived reports)? [Clarity, Gap, Acceptance Criteria]
- [ ] CHK003 — Are requirements specified for updating cross-project references outside the `specs/` directory (e.g., `AGENTS.md`, `README.md`, `docs/TSiSIP-CANONICAL-SPEC.md`)? [Completeness, Gap]
- [ ] CHK004 — Is a rollback strategy defined if a batch migration introduces broken links or references? [Coverage, Gap, Spec §Plan Phase 2]
- [ ] CHK005 — Are requirements documented for communicating the ID scheme change to external stakeholders or downstream consumers? [Completeness, Gap]

## Requirement Clarity

- [ ] CHK006 — Is the `FR-NNN-XXX` format unambiguously defined for edge cases such as feature numbers > 999 or requirement sequences > 999? [Clarity, Spec §FR-018-001]
- [ ] CHK007 — Are the terms "flat ID" and "feature-scoped ID" explicitly defined in the spec glossary or context? [Clarity, Gap]
- [ ] CHK008 — Is the batch size for retroactive migration (001–005, 006–010, 011–017) justified with a rationale? [Clarity, Spec §Plan Phase 2]
- [ ] CHK009 — Are the acceptance criteria measurable without manual inspection (e.g., via automated grep/CI script)? [Measurability, Spec §Acceptance Criteria]

## Requirement Consistency

- [ ] CHK010 — Do the acceptance criteria align with the plan phases (e.g., does Phase 3 CI gate map to "CI gate enforces the new scheme")? [Consistency, Spec §Acceptance Criteria vs Plan]
- [ ] CHK011 — Are the task dependencies in `tasks.md` consistent with the plan phases (T2.1–T2.4 sequential vs parallel)? [Consistency, tasks.md vs plan.md]
- [ ] CHK012 — Does the scheme document in `docs/architecture/global-requirement-id-scheme.md` contain any rules that conflict with FR-018-001 through FR-018-004? [Consistency, Cross-reference]

## Scenario Coverage

- [ ] CHK013 — Are requirements defined for specs currently under active PR or review that may still use flat IDs? [Coverage, Edge Case, Gap]
- [ ] CHK014 — Are requirements specified for handling historical/archived reports that legitimately contain flat IDs? [Coverage, Edge Case, Gap]
- [ ] CHK015 — Is the tooling integration requirement (FR-018-004) specific about what constitutes a "duplicate" (same ID in same spec vs same ID across different specs)? [Clarity, Spec §FR-018-004]

## Edge Case Coverage

- [ ] CHK016 — Are edge cases defined for partially migrated states (e.g., some specs use new scheme while others still use flat IDs during transition)? [Edge Case, Gap]
- [ ] CHK017 — Are requirements specified for the scenario where a new feature (018+) references a requirement in an old spec (001–017) before that old spec is migrated? [Edge Case, Gap]
- [ ] CHK018 — Is there a defined maximum batch rollback window if a migration error is discovered post-commit? [Edge Case, Gap]

## Non-Functional Requirements

- [ ] CHK019 — Are performance requirements specified for the CI validation script (e.g., max execution time across 17 specs)? [NFR, Gap]
- [ ] CHK020 — Are maintainability requirements defined for the validation script (e.g., language, test coverage, documentation)? [NFR, Gap, Spec §FR-018-004]

## Dependencies & Assumptions

- [ ] CHK021 — Is the assumption that all 17 specs have `spec.md`, `plan.md`, and `tasks.md` validated? [Assumption, tasks.md shows T1.2 not yet checked]
- [ ] CHK022 — Are dependencies on the `spec-validate.gate` extension explicitly documented, including fallback behavior if the extension is unavailable? [Dependency, Gap, Spec §FR-018-003]
- [ ] CHK023 — Is the dependency on the cross-project analysis report (A4) acknowledged as a blocking input for this feature? [Dependency, Spec §Context]

## Traceability

- [ ] CHK024 — Do all acceptance criteria trace directly to at least one functional requirement (FR-018-001 through FR-018-004)? [Traceability, Spec §Acceptance Criteria]
- [ ] CHK025 — Are the four functional requirements themselves traceable to the root cause described in the context (A4 analysis)? [Traceability, Spec §Context vs Requirements]
