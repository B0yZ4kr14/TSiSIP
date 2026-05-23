# Memory Synthesis — Feature 022: VPS Go-Live Stabilization

---

## Current Scope

24h TDD-first go-live stabilization for vps-lite stack on tsiapp.io.

## Relevant Decisions

- AD-022-1: docker-compose.vps.yml as canonical production runtime
- AD-022-2: Existing toolchain for TDD (bash, sipsak, curl, Python)
- AD-022-3: Evidence in .sisyphus/evidence/ separate from reports/

## Active Architecture Constraints

Docker-first, PostgreSQL-only, secret hygiene, network isolation.

## Accepted Deviations

NAT/transcoding out of scope; rollback assumes pre-provisioned secrets.

## Relevant Security Constraints

No secrets in evidence; unpublished Asterisk/PostgreSQL ports; data-preserving rollback.

## Related Historical Lessons

Feature 021 eliminated all CRITICAL/HIGH findings. Feature 008 established baseline. Feature 015 handles TLS.

## Conflict Warnings

None.

## Retrieval Notes

- Search: vps, go-live, stabilization, tdd, smoke test, rollback
- Related: Feature 021, 008, 015, .sisyphus/plans/vps-24h-tdd-stabilization.md
