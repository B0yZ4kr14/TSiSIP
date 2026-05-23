# Feature Memory — 019: Spec Kit Memory Hub Integration

> Created: 2026-05-23

---

## Current Scope

Integration of the spec-kit memory-md extension into the TSiSIP project, enabling durable markdown memory for agent context across all features.

## Relevant Decisions

- AD-019-1: Use memory-md in markdown-only mode (optimizer disabled) for maximum portability
- AD-019-2: Store durable memory in `docs/memory/` (project-level) and `specs/NNN-*/memory.md` (feature-level)

## Active Architecture Constraints

Docker-first, PostgreSQL-only, secret hygiene — all maintained.

## Accepted Deviations

- Memory synthesis requires manual review before commits (no auto-commit)

## Relevant Security Constraints

- No secrets in memory files
- Memory files are version-controlled (no PII)

## Related Historical Lessons

- Memory backfill is labor-intensive but essential for agent coherence
- Consistent section structure (8 sections) enables automated validation

## Conflict Warnings

None.

## Retrieval Notes

- Search: memory, memory-md, durable context, agent memory
- Related: Feature 020 (memory backfill applied there)
