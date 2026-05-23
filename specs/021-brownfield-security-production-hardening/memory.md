# Feature Memory — 021: Brownfield Security & Production Hardening

> Created: 2026-05-23

---

## Current Scope

Resolve all CRITICAL and HIGH brownfield scan findings: pin image tags, remove htable, add healthchecks, fix .env.example gaps, rotate leaked PAT.

## Relevant Decisions

- AD-021-1: `${TSISIP_IMAGE_TAG}` already used since Feature 008
- AD-021-2: htable already removed in Feature 001 evolution
- AD-021-3: Healthchecks already present since Features 004/008

## Active Architecture Constraints

Docker-first, PostgreSQL-only, secret hygiene, network isolation.

## Accepted Deviations

- Git history purge for PAT is out of scope (requires GitHub ops)

## Relevant Security Constraints

- No secrets in committed files
- Image tags must be immutable
- Health probes must not expose sensitive endpoints

## Related Historical Lessons

- Brownfield scans accumulate findings quickly; hygiene cycles must be regular
- .env.example gaps are easy to miss during feature development

## Conflict Warnings

None.

## Retrieval Notes

- Search: brownfield, security, hardening, B8, B1, B9, H1, H2
- Related: Feature 013 (previous brownfield cycle), Feature 008 (DevSecOps baseline)
