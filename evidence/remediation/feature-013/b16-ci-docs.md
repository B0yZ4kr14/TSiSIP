# B16 Remediation Evidence — CI Pipeline Publishes `latest` Tag

## Finding
- **ID:** B16
- **File:** `.github/workflows/deploy.yml`
- **Problem:** The deploy workflow builds and pushes `:latest` tag to GHCR. While production compose files now require `TSISIP_IMAGE_TAG`, the CI still promotes a mutable `latest` tag without explicit policy documentation.

## Fix Applied
Added two YAML comment blocks in `.github/workflows/deploy.yml` to document the image tag policy. No workflow logic was changed.

### Comment Block 1 — Build Job (lines 124-130)
Inserted before the `Build OpenSIPS` step in the `build` job:

```yaml
      # ------------------------------------------------------------------
      # IMAGE TAG POLICY — B16 Remediation
      # The `:latest` tag is a CI artifact tag for smoke-test builds ONLY.
      # Production deployments MUST use pinned tags via TSISIP_IMAGE_TAG.
      # The SHA-based tag (github.sha) is also pushed and is preferred
      # for production because it is immutable and traceable.
      # ------------------------------------------------------------------
```

### Comment Block 2 — Push Job (lines 191-197)
Inserted before the `Tag and push` step in the `push` job:

```yaml
      # ------------------------------------------------------------------
      # IMAGE TAG POLICY — B16 Remediation
      # `:latest` is pushed as a convenience tag for quick smoke tests.
      # Do NOT reference `:latest` in production compose files.
      # Use the SHA-based tag (e.g., ghcr.io/.../opensips:<sha>) for
      # production deployments and set it via TSISIP_IMAGE_TAG.
      # ------------------------------------------------------------------
```

## Verification
- YAML syntax validated with `python3 -c "import yaml; yaml.safe_load(...)` — **PASS**.
- No workflow steps were added, removed, or modified.
- Only documentation comments (`#`) were introduced.

## Policy Summary
| Tag | Purpose | Production Use |
|---|---|---|
| `:latest` | CI artifact / smoke-test convenience tag | **FORBIDDEN** |
| `<github.sha>` | Immutable, traceable per-commit tag | **RECOMMENDED** |
| `TSISIP_IMAGE_TAG` env var | Production compose pinning mechanism | **REQUIRED** |

---
*Remediation completed by docs agent for Feature 013.*
