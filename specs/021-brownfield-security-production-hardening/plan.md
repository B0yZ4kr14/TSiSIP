## Summary

This plan implements the feature for the TSiSIP SIP edge-proxy platform.

## Technical Context

- **OpenSIPS 3.6 LTS**: Core SIP proxy and signaling edge
- **PostgreSQL**: Database backend for configuration and state
- **Docker & Docker Compose**: Container orchestration and deployment

## Project Structure

Relevant directories and files for this feature are located under specs/021-brownfield-security-production-hardening/.

# Feature 021 — Implementation Plan

## Tech Stack

- Docker Compose v3.8
- OpenSIPS 3.6 LTS (source build)
- PostgreSQL 16
- Bash / POSIX sh

## Files to Modify

| File | Change |
|---|---|
| `docker-compose.yml` | Pin image tags; add healthchecks |
| `Dockerfile` | Remove `htable` from `include_modules` |
| `.env.example` | Add missing vars (H1 remediation) |
| `secrets/auth_secret` | Regenerate if < 32 chars |
| `.gitnexus/meta.json` | Remove leaked PAT |

## Wave Breakdown

### Wave 1: Image Tag Pinning (B8)
- Replace all literal `:latest` tags in `docker-compose.yml` with `${TSISIP_IMAGE_TAG}`
- Verify no `tsisip/*:latest` remains

### Wave 2: Build Cleanup (B1)
- Remove `htable` from `include_modules` in `Dockerfile`
- Verify build context still compiles

### Wave 3: Healthchecks (B9)
- Add `healthcheck` blocks to `postgres`, `opensips`, `rtpengine` services
- Use appropriate probes (pg_isready, OPTIONS SIP, pidof)

### Wave 4: Environment Hardening (H1, H2)
- Audit all `${VAR}` in compose against `.env.example`
- Add missing vars with sensible defaults
- Regenerate `auth_secret` if needed

### Wave 5: Secret Remediation
- Sanitize `.gitnexus/meta.json`
- Document PAT revocation steps

### Wave 6: Validation
- `docker compose config`
- Brownfield re-scan
