# Feature 021 Tasks

## Wave 1: Image Tag Pinning
- [x] T1.1: Verify all `tsisip/*` images use `${TSISIP_IMAGE_TAG}` — already resolved in Feature 008

## Wave 2: Build Cleanup
- [x] T2.1: Verify `htable` absent from `Dockerfile` — already removed in Feature 001 evolution

## Wave 3: Healthchecks
- [x] T3.1: Verify `healthcheck` blocks present in compose — already implemented in Features 004/008

## Wave 4: Environment Hardening
- [x] T4.1: Extract all `${VAR}` references from `docker-compose.yml`
- [x] T4.2: Add missing vars to `.env.example` (ALERTMANAGER_*, GRAFANA_ROOT_URL, RCLONE_*)
- [x] T4.3: Verify `secrets/auth_secret` length (32 chars — OK)

## Wave 5: Secret Remediation
- [x] T5.1: Remove PAT from `.gitnexus/meta.json`
- [x] T5.2: Add `.gitnexus/meta.json` to `.gitignore`
- [x] T5.3: Document PAT revocation requirement

## Wave 6: Validation
- [x] T6.1: `docker compose config` validates
- [x] T6.2: Brownfield scan shows zero CRITICAL/HIGH
- [x] T6.3: Committed with conventional commits
