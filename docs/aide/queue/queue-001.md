# TSiSIP Work Queue 001

> Generated from docs/aide/vision.md, docs/aide/roadmap.md, and docs/aide/progress.md
> Scope: Stage 1 completion, Stage 2 (VEX/SBOM), and Stage 3 kickoff
> Estimated duration: 1 week

---

### Item 001: Add RTPENGINE_INTERNAL_IP to .env.example
Add `RTPENGINE_INTERNAL_IP` to `.env.example` with a documented default value and override behavior. Ensure the variable is consumed by `docker-compose.yml` and the OpenSIPS entrypoint, and that `docker compose config` renders without warnings when `.env.example` is copied to `.env`.

### Item 002: Migrate sip_trunk_did_mappings.tenant_id from UUID to VARCHAR(36)
Alter `sip_trunk_did_mappings.tenant_id` from UUID to `VARCHAR(36)` to align with the rest of the tenant ID schema. Update the column definition in `db/init/04-trunk-schema.sql` and any migration script. Verify with `psql \d sip_trunk_did_mappings` that the column type is `character varying(36)`.

### Item 003: Update DDL, Seed Data, and OCP CRUD Forms for tenant_id Type Change
Propagate the `tenant_id` type change across all DDL references, seed data (`db/init/05-seed-trunk-data.sql`), and OCP CRUD forms that interact with `sip_trunk_did_mappings`. Ensure form validation, prepared statements, and any PHP type hints reflect `VARCHAR(36)` instead of UUID.

### Item 004: Add Schema Regression Test for tenant_id VARCHAR(36) Consistency
Create a schema regression test (pytest or bash) that queries the PostgreSQL information schema and fails if any table's `tenant_id` column deviates from `VARCHAR(36)`. The test should run locally against the Compose postgres service and be wired into CI.

### Item 005: Add SBOM Generation to CI Pipeline
Integrate Syft or Trivy into `.github/workflows/ci.yml` to generate SBOMs for all project-owned Docker images (opensips, rtpengine, ocp, postgres, backup, etc.). The step must run after a successful build and produce machine-readable output.

### Item 006: Generate CycloneDX SBOMs as CI Artifacts
Configure the CI workflow to emit CycloneDX-format SBOMs and attach them as build artifacts. Each SBOM must be named `reports/sbom-<image>-{sha}.json` and be validatable with `cyclonedx-cli`.

### Item 007: Add VEX Document Generation for Known Non-Exploitable Findings
Create a VEX document generator (script or CI step) that marks known non-exploitable findings as design decisions — e.g., the intentional absence of `db_mysql` and `sanity` modules. Output `reports/vex-tsisip-{sha}.json` in CycloneDX VEX format.

### Item 008: Store SBOM/VEX Artifacts in reports/ with Versioned Filenames
Ensure the CI pipeline copies all SBOM and VEX artifacts into `reports/` with versioned filenames on every master build. Add a `vex-validate` CI job that verifies VEX files are parseable by `cyclonedx-cli` before the build is marked green.

### Item 009: Pin All Dockerfile FROM Images to SHA256 Digests
Replace all floating tags (e.g., `:latest`, `:16`, `:3.6`) in project Dockerfiles with SHA256-digested references. Verify each pinned digest resolves correctly with `docker buildx imagetools inspect`.

### Item 010: Create Makefile release-tag Target and Rollback Script
Add a `make release-tag` target that generates semver tags (`vYYYY.MM.DD-N`) and writes a `release-manifest.json` with image-to-digest mappings. Create `deploy/scripts/rollback.sh` that reads the manifest and re-deploys the previous tag to the VPS stack in under 60 seconds.
