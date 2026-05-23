---
name: speckit-catalog-ci-lint
description: Compare local extension.yml against its catalog entry for consistency
compatibility: Requires spec-kit project structure with .specify/ directory
metadata:
  author: github-spec-kit
  source: catalog-ci:commands/speckit.catalog-ci.lint.md
---

# Lint Extension Against Catalog

Compare a local `extension.yml` file against its corresponding entry in the community catalog to detect inconsistencies.

## User Input

$ARGUMENTS

You **MUST** consider the user input before proceeding (if not empty). The user may provide:
- A path to the local `extension.yml` (default: `extension.yml` in current directory)
- A path to the catalog file (default: `extensions/catalog.community.json` in the spec-kit repo)

## Prerequisites

- Read and parse the local `extension.yml`
- Read and parse the catalog JSON file
- Look up the entry matching `extension.id` from the YAML in the catalog's `extensions` object
- If no matching entry is found, report that the extension is not in the catalog and stop

## Fields to Compare

| extension.yml field | Catalog field | Check |
|---------------------|---------------|-------|
| `extension.name` | `name` | Exact match |
| `extension.id` | `id` | Exact match |
| `extension.version` | `version` | Exact match (warn if local is newer) |
| `extension.description` | `description` | Exact match |
| `extension.author` | `author` | Exact match |
| `extension.repository` | `repository` | Exact match |
| `extension.license` | `license` | Exact match |
| `requires.speckit_version` | `requires.speckit_version` | Exact match |
| `provides.commands` (count) | `provides.commands` | Count of commands array must equal catalog integer |
| `tags` (array) | `tags` (array) | Same elements (order-independent) |

## Execution

1. Parse both files
2. Locate the catalog entry by extension ID
3. Compare each field pair from the table above
4. Classify each result:
   - **MATCH** — values are identical
   - **MISMATCH** — values differ (report both)
   - **WARN** — local version is newer than catalog (may need catalog update)
   - **MISSING** — field exists in one source but not the other

## Output

Print a summary in this format:

```
Lint: <extension-id>
Local:   <extension.yml path>
Catalog: <catalog path>
========================

  name:             MATCH
  id:               MATCH
  version:          MISMATCH (local: 1.1.0, catalog: 1.0.0)
  description:      MATCH
  author:           MATCH
  repository:       MATCH
  license:          MATCH
  speckit_version:  MATCH
  commands count:   MISMATCH (local: 5, catalog: 4)
  tags:             MATCH

Mismatches: <count>
Warnings:   <count>

Result: PASS | WARN | FAIL
```

- **PASS** — all fields match
- **WARN** — only version is newer locally (catalog may need update)
- **FAIL** — one or more fields have mismatches