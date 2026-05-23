# Memory Synthesis — Feature 021: Brownfield Security & Production Hardening

---

## Current Scope

Resolve CRITICAL/HIGH brownfield findings: image tags, htable removal, healthchecks, env gaps, PAT rotation.

## Relevant Decisions

- AD-021-1: Image tags already pinned via TSISIP_IMAGE_TAG
- AD-021-2: htable already absent
- AD-021-3: Healthchecks already present

## Active Architecture Constraints

Docker-first, PostgreSQL-only, secret hygiene, network isolation.

## Accepted Deviations

- Git history purge for PAT out of scope

## Relevant Security Constraints

No secrets in commits; immutable tags; safe health probes.

## Related Historical Lessons

Regular hygiene cycles prevent finding accumulation; env gaps easy to miss.

## Conflict Warnings

None.

## Retrieval Notes

- Search: brownfield, security, hardening, B8, B1, B9, H1, H2
- Related: Feature 013, Feature 008
