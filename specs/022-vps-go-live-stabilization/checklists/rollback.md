# Feature 022 — Rollback & Recovery Requirements Quality Checklist

**Purpose**: Validate the quality, clarity, and completeness of rollback and recovery requirements in spec.md and plan.md for Feature 022.

**Created**: 2026-05-23
**Feature**: 022 — VPS Go-Live Stabilization

---

## Rollback Scope

- [ ] CHK001 - Are rollback trigger conditions explicitly defined (what constitutes a failed go-live)? [Clarity, AC5]
- [ ] CHK002 - Is the rollback scope limited to vps-lite services or does it include infrastructure (DNS, TLS)? [Ambiguity, Gap]
- [ ] CHK003 - Are rollback time boundaries defined (e.g., rollback must complete within N minutes)? [Measurability, Gap]

## Data Preservation

- [ ] CHK004 - Is volume backup explicitly required before destructive changes? [Completeness, R3]
- [ ] CHK005 - Are specific volume names/paths documented for backup? [Clarity, Gap]
- [ ] CHK006 - Is PostgreSQL data preservation verified (pg_dump or volume snapshot)? [Coverage, Gap]
- [ ] CHK007 - Are secrets/ directory preservation requirements defined? [Completeness, Gap]

## Rollback Procedures

- [ ] CHK008 - Is the rollback runbook step-by-step with explicit commands? [Clarity, AC5]
- [ ] CHK009 - Are rollback verification steps defined (how to confirm rollback succeeded)? [Completeness, Gap]
- [ ] CHK010 - Is rollback idempotency addressed (can rollback be run twice safely)? [Edge Case, Gap]
- [ ] CHK011 - Are service startup order requirements defined during rollback? [Completeness, Gap]

## Recovery Paths

- [ ] CHK012 - Are requirements defined for recovery after a failed rollback attempt? [Coverage, Gap]
- [ ] CHK013 - Is partial rollback defined (e.g., only OpenSIPS needs rollback, not full stack)? [Edge Case, Gap]
- [ ] CHK014 - Are requirements for rollback during active calls/SIP sessions defined? [Edge Case, Gap]

## Rollback Testing

- [ ] CHK015 - Is dry-run rollback testing required before go-live? [Completeness, T5]
- [ ] CHK016 - Are rollback test acceptance criteria defined? [Measurability, Gap]
- [ ] CHK017 - Is rollback runbook validation by a second operator required? [Completeness, AC5]

## Operational Context

- [ ] CHK018 - Are rollback communication requirements defined (who to notify, when)? [Coverage, Gap]
- [ ] CHK019 - Are rollback evidence/documentation requirements defined? [Completeness, AD-022-3]
- [ ] CHK020 - Is rollback runbook version control and change tracking specified? [Clarity, Gap]
