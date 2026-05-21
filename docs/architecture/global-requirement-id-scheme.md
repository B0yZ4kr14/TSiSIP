# Global Requirement ID Scheme

> **Status**: Proposed  
> **Created**: 2026-05-21  
> **Context**: Cross-project analysis A4 identified that `FR-001` through `FR-010` are reused across 11+ feature specifications with different meanings, breaking traceability.

## Problem

The current flat `FR-XXX` numbering scheme causes identifier collisions:
- `FR-001` means "Docker Image" in Feature 001, "Theme System" in Feature 002, "Prometheus Scrape Config" in Feature 003, etc.
- This makes cross-feature impact analysis, compliance mapping, and test traceability impossible.

## Proposed Scheme

### Format
```
FR-NNN-XXX
```

| Segment | Meaning | Range |
|---|---|---|
| `FR` | Fixed prefix — Functional Requirement | — |
| `NNN` | Feature number (from `specs/NNN-feature/`) | `001`–`999` |
| `XXX` | Requirement sequence within the feature | `001`–`999` |

### Examples
| Old ID | New ID | Meaning |
|---|---|---|
| `FR-001` (in 001) | `FR-001-001` | OpenSIPS Docker image |
| `FR-001` (in 002) | `FR-002-001` | TSiSIP theme system |
| `FR-003` (in 016) | `FR-016-003` | Audit log retention |
| `FR-001` (in 017) | `FR-017-001` | Trunk provider schema |

### Rules
1. **Feature-scoped**: Requirement numbers reset per feature.
2. **Immutable**: Once assigned, an ID never changes scope.
3. **Sequential**: Gaps are allowed; reordering is forbidden.
4. **Traceability**: Every `FR-NNN-XXX` must appear in exactly one `specs/NNN-*/spec.md`.

## Migration Plan

### Phase 1 — New Features (from 018 onward)
- All new specs use `FR-NNN-XXX` natively.
- No retrofitting of old specs required before Phase 2.

### Phase 2 — Retroactive Update (backlog)
- Update each existing `specs/NNN-*/spec.md` to use `FR-NNN-XXX`.
- Update `specs/NNN-*/plan.md` and `specs/NNN-*/tasks.md` to reference new IDs.
- Update cross-project analysis reports and constitution references.
- Update test files and integration test headers.

### Phase 3 — Tooling
- Add a validation script to CI that checks for duplicate `FR-NNN-XXX` across specs.
- Reject specs with flat `FR-XXX` IDs in `spec-validate.gate`.

## Acceptance Criteria
- [ ] Zero flat `FR-XXX` IDs remain in active specs (excluding historical reports).
- [ ] `spec-validate.gate` rejects specs without feature-scoped IDs.
- [ ] Cross-project analysis can uniquely identify any requirement in ≤ 3 keystrokes.

## References
- Cross-project analysis report: `reports/speckit-analyze-cross-project-2026-05-21.md` (A4)
