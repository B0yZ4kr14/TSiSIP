# Decision: Feature-Scoped Requirement ID Scheme

**Date**: 2026-05-21  
**Triggered by**: Cross-project analysis A4  
**Decision Owner**: Architecture Guard + Governance  
**Status**: Ratified

## Problem Statement

Flat `FR-XXX` IDs (e.g., `FR-001`) were reused across 11+ feature specifications with different meanings. This made cross-feature traceability, compliance mapping, and automated impact analysis impossible.

## Decision

Adopt `FR-NNN-XXX` scheme:
- `NNN` = feature number (001–999)
- `XXX` = requirement sequence within the feature (001–999)

## Rationale

- Guarantees global uniqueness without a central registry.
- Feature-scoped numbering allows parallel spec writing.
- Sortable and greppable.
- Aligns with directory structure `specs/NNN-feature/`.

## Consequences

- **Positive**: Unambiguous requirement references; CI can detect duplicates.
- **Negative**: Retroactive migration of 17 specs required (tracked as Feature 018).
- **Neutral**: New specs from 018 onward use the scheme natively.

## Migration State

- Phase 1 (documentation): ✅ Complete
- Phase 2 (retroactive update): 🔄 In progress (Feature 018)
- Phase 3 (CI gate): ⏳ Pending

## References

- `docs/architecture/global-requirement-id-scheme.md`
- `specs/018-global-requirement-id-migration/`
- `reports/speckit-analyze-cross-project-2026-05-21.md` (A4)
