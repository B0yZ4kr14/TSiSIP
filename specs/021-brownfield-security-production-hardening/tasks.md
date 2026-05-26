# Feature 021 Tasks

## Phase 1: Image Tag Pinning
- [x] T001: Verify all `tsisip/*` images use `${TSISIP_IMAGE_TAG}` — already resolved in Feature 008

## Phase 2: Build Cleanup
- [x] T006: Verify `htable` absent from `Dockerfile` — already removed in Feature 001 evolution

## Phase 3: Healthchecks
- [x] T011: Verify `healthcheck` blocks present in compose — already implemented in Features 004/008

## Phase 4: Environment Hardening
- [x] T016: Extract all `${VAR}` references from `docker-compose.yml`
- [x] T017: Add missing vars to `.env.example` (ALERTMANAGER_*, GRAFANA_ROOT_URL, RCLONE_*)
- [x] T018: Verify `secrets/auth_secret` length (32 chars — OK)

## Phase 5: Secret Remediation
- [x] T5.1: Remove PAT from `.gitnexus/meta.json`
- [x] T5.2: Add `.gitnexus/meta.json` to `.gitignore`
- [x] T5.3: Document PAT revocation requirement

## Phase 6: Validation
- [x] T6.1: `docker compose config` validates
- [x] T6.2: Brownfield scan shows zero CRITICAL/HIGH
- [x] T6.3: Committed with conventional commits
