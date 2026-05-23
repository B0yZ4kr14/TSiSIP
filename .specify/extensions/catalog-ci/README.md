# Catalog CI — Automated Validation for Spec Kit Catalogs

Automated validation extension for spec-kit community catalog entries. Checks structure, URLs, diffs, and lints extension manifests against catalog data.

## Commands

| Command | Purpose |
|---------|---------|
| `/speckit.catalog-ci.validate` | Validate catalog JSON: structure, required fields, ID format, semver, ordering, duplicates |
| `/speckit.catalog-ci.check-urls` | Verify all URLs (download, repo, docs) are accessible via HTTP HEAD |
| `/speckit.catalog-ci.diff` | Compare catalog against a git ref — show added/modified/removed entries |
| `/speckit.catalog-ci.lint` | Compare local `extension.yml` against its catalog entry for consistency |

## Installation

```bash
specify install https://github.com/Quratulain-bilal/spec-kit-catalog-ci/archive/refs/tags/v1.0.0.zip
```

## Usage

### Validate catalog structure

```
/speckit.catalog-ci.validate
/speckit.catalog-ci.validate extensions/catalog.community.json
```

### Check all URLs are reachable

```
/speckit.catalog-ci.check-urls
/speckit.catalog-ci.check-urls --entry my-extension
```

### Diff catalog against main branch

```
/speckit.catalog-ci.diff
/speckit.catalog-ci.diff main
```

### Lint local extension against catalog

```
/speckit.catalog-ci.lint
/speckit.catalog-ci.lint ./extension.yml
```

## Validation Rules

### Top-level checks
- `schema_version` equals `"1.0"`
- `updated_at` is valid ISO 8601
- `catalog_url` is HTTPS
- `extensions` or `presets` key exists

### Per-entry checks
- Required fields: `name`, `id`, `description`, `author`, `version`, `download_url`, `repository`, `license`, `requires.speckit_version`, `provides.commands`, `tags`, `created_at`, `updated_at`
- `id` matches `^[a-z0-9-]+$` and equals the object key
- `description` under 200 characters
- `version` is valid semver (`X.Y.Z`)
- URLs use HTTPS

### Cross-entry checks
- No duplicate IDs
- Alphabetical order by ID
- `updated_at` >= `created_at` per entry

## Requirements

- spec-kit >= 0.4.0
- `curl` (for URL checking)
- `git` (for diff command)

## License

MIT
