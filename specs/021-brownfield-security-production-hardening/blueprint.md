# Blueprint — Brownfield Security & Production Hardening

## Overview

Eliminate all CRITICAL and HIGH severity findings from the brownfield scan, rotate the exposed credential, and bring the `.env.example` template to full parity with `docker-compose.yml` variables — resolving all production blockers before go-live.

## Requirements

- **AC1**: All `tsisip/*` images in `docker-compose.yml` use `${TSISIP_IMAGE_TAG}`, never literal `:latest`.
- **AC2**: `htable` is absent from `include_modules` in `Dockerfile`; build succeeds.
- **AC3**: `docker-compose.yml` includes `healthcheck` blocks for all runtime services.
- **AC4**: `.env.example` documents every `${VAR}` referenced in `docker-compose.yml` (H1 remediation).
- **AC5**: `secrets/auth_secret` contains 32+ random characters (H2 verified).
- **AC6**: `.gitnexus/meta.json` PAT removed; token reference sanitized.
- **AC7**: `docker compose config` validates without errors after all changes.
- **AC8**: Brownfield scan re-run shows zero CRITICAL/HIGH findings.

## Architecture

- **Stack**: Docker Compose v3.8, OpenSIPS 3.6 LTS (source build), PostgreSQL 16, Bash/POSIX sh.
- **Files**: `docker-compose.yml`, `Dockerfile`, `.env.example`, `secrets/auth_secret`, `.gitnexus/meta.json`.
- **Security Requirements**: No secrets in committed files; immutable image tags; build reproducibility; healthchecks use non-authenticated readiness checks only.

## Implementation Plan

### Wave 1: Image Tag Pinning (B8)
- Replace all literal `:latest` tags in `docker-compose.yml` with `${TSISIP_IMAGE_TAG}`.
- Verify no `tsisip/*:latest` remains.

### Wave 2: Build Cleanup (B1)
- Remove `htable` from `include_modules` in `Dockerfile`.
- Verify build context still compiles.

### Wave 3: Healthchecks (B9)
- Add `healthcheck` blocks to `postgres`, `opensips`, `rtpengine` services.
- Use appropriate probes (`pg_isready`, OPTIONS SIP, `pidof`).

### Wave 4: Environment Hardening (H1, H2)
- Audit all `${VAR}` in compose against `.env.example`.
- Add missing vars with sensible defaults.
- Regenerate `auth_secret` if needed.

### Wave 5: Secret Remediation
- Sanitize `.gitnexus/meta.json`.
- Document PAT revocation steps.

### Wave 6: Validation
- `docker compose config`.
- Brownfield re-scan.

## Tasks

**Wave 1: Image Tag Pinning**
- T1.1: Verify all `tsisip/*` images use `${TSISIP_IMAGE_TAG}`

**Wave 2: Build Cleanup**
- T2.1: Verify `htable` absent from `Dockerfile`

**Wave 3: Healthchecks**
- T3.1: Verify `healthcheck` blocks present in compose

**Wave 4: Environment Hardening**
- T4.1: Extract all `${VAR}` references from `docker-compose.yml`
- T4.2: Add missing vars to `.env.example` (ALERTMANAGER_*, GRAFANA_ROOT_URL, RCLONE_*)
- T4.3: Verify `secrets/auth_secret` length (32 chars)

**Wave 5: Secret Remediation**
- T5.1: Remove PAT from `.gitnexus/meta.json`
- T5.2: Add `.gitnexus/meta.json` to `.gitignore`
- T5.3: Document PAT revocation requirement

**Wave 6: Validation**
- T6.1: `docker compose config` validates
- T6.2: Brownfield scan shows zero CRITICAL/HIGH
- T6.3: Committed with conventional commits

## Validation

- `grep ":latest" docker-compose.yml` returns empty for `tsisip/*` images.
- `grep "htable" Dockerfile` returns empty.
- `docker compose config` renders without errors.
- `.env.example` contains every variable referenced in `docker-compose.yml`.
- `wc -c secrets/auth_secret` shows ≥32 characters.
- `grep -r "ghp_\|pat_\|token_" .gitnexus/` returns empty.
- Brownfield scan re-run confirms zero CRITICAL/HIGH findings.

## Risks & Dependencies

| Risk | Mitigation |
|---|---|
| Removing `htable` breaks rate limiting features | `ratelimit` + `userblacklist` + `cachedb_local` already cover same needs |
| `.env.example` parity gaps missed | Extract all `${VAR}` programmatically; diff against `.env.example` |
| PAT already exposed in Git history | Document revocation on GitHub.com before push |
| Healthcheck probes expose internal state | Use non-authenticated readiness checks only |

**Dependencies**: Docker Compose; Bash; GitHub Actions CI; brownfield scan tooling.
