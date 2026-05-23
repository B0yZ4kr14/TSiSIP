# Feature Memory — 022: VPS Go-Live Stabilization

> Created: 2026-05-23

---

## Current Scope

24-hour TDD-first stabilization to achieve production go-live of the vps-lite stack on tsiapp.io. Covers container health, SIP signaling, OCP accessibility, rollback readiness, and operational observability.

## Relevant Decisions

- AD-022-1: Use docker-compose.vps.yml as canonical production runtime
- AD-022-2: TDD tests use existing toolchain (bash, docker compose, sipsak, curl, Python)
- AD-022-3: Evidence stored in .sisyphus/evidence/ (operational) separate from reports/ (static scans)

## Active Architecture Constraints

Docker-first, PostgreSQL-only, secret hygiene, network isolation. No new components outside vps-lite baseline.

## Accepted Deviations

- 24h window excludes NAT advanced/transcoding
- Rollback runbook assumes secrets/ is pre-provisioned on VPS

## Relevant Security Constraints

- No secrets in committed evidence files
- Asterisk/PostgreSQL ports must remain unpublished
- Rollback must preserve data integrity

## Related Historical Lessons

- Feature 021 hardening eliminated all CRITICAL/HIGH brownfield findings
- Feature 008 established DevSecOps baseline compose
- Feature 015 auto-TLS handles certificate lifecycle

## Conflict Warnings

None.

## Retrieval Notes

- Search: vps, go-live, stabilization, tdd, smoke test, rollback, evidence
- Related: Feature 021, Feature 008, Feature 015, .sisyphus/plans/vps-24h-tdd-stabilization.md
