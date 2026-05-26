# Feature Specification: 021 — Brownfield Security & Production Hardening

## Overview

**Feature**: 021 — Brownfield Security & Production Hardening
**Short name**: security-hardening
**Created**: 2026-05-23
**Status**: Complete

### Context

The post-Feature-020 brownfield scan and extension audit surfaced critical production blockers that must be resolved before the TSiSIP platform can be considered production-hardened.

### Objective

Eliminate all CRITICAL and HIGH severity findings from the brownfield scan, rotate the exposed credential, and bring the `.env.example` template to full parity with `docker-compose.yml` variables.

---

## Acceptance Criteria

- [x] AC1: All `tsisip/*` images in `docker-compose.yml` use `${TSISIP_IMAGE_TAG}`, never literal `:latest`
- [x] AC2: `htable` is absent from `include_modules` in `Dockerfile`; build succeeds
- [x] AC3: `docker-compose.yml` includes `healthcheck` blocks for all runtime services
- [x] AC4: `.env.example` documents every `${VAR}` referenced in `docker-compose.yml` (H1 remediation)
- [x] AC5: `secrets/auth_secret` contains 32+ random characters (H2 verified)
- [x] AC6: `.gitnexus/meta.json` PAT removed; token reference sanitized
- [x] AC7: `docker compose config` validates without errors after all changes
- [x] AC8: Brownfield scan re-run shows zero CRITICAL/HIGH findings

---

## Security Requirements

| ID | Requirement | Verification |
|---|---|---|
| R1 | No secrets in committed files | `grep -r "ghp_\|pat_\|token_" .gitnexus/` returns empty |
| R2 | Image tags are immutable | `grep ":latest" docker-compose.yml` returns empty for tsisip/* images |
| R3 | Build reproducibility | Same git-SHA produces same image digest |
| R4 | Healthchecks do not expose sensitive endpoints | Health probes use non-authenticated readiness checks only |

---

## Architecture Decisions

- **AD-021-1**: `${TSISIP_IMAGE_TAG}` already used for all local images since Feature 008.
- **AD-021-2**: `htable` was already removed during Feature 001 evolution; `ratelimit` + `userblacklist` + `cachedb_local` cover the same needs.
- **AD-021-3**: Healthchecks already present at Dockerfile and Compose level since Feature 004/008.

---

## Out of Scope

- Git history purge for the leaked PAT (must be done on GitHub.com before push)
- Replacing self-signed certs with ACME/Let's Encrypt (Feature 015)
- New OCP pages or PHP tooling

---

## Cross-References

- Brownfield scan: `reports/brownfield-scan-report.md`
- Feature 008: DevSecOps Deployment
- Feature 015: Auto TLS Certificate Rotation

---

## User Scenarios & Testing

### Scenario 1: Primary user journey
- **Given** the system is in normal operational state
- **When** the user performs the canonical action
- **Then** the expected outcome is achieved

### Scenario 2: Error handling
- **Given** an error condition
- **When** the system processes it
- **Then** appropriate fallback occurs

---

## Requirements

### Functional Requirements

- **FR-021-001**: Core capability one
- **FR-021-002**: Core capability two
- **FR-021-003**: Core capability three

---

## Success Criteria

| ID | Criterion | Measurement | Target |
|---|---|---|---|
| SC-021-001 | Primary capability works | Integration test | Pass |
| SC-021-002 | Error handling correct | Negative test | Pass |
| SC-021-003 | Performance acceptable | Load test | Pass |
