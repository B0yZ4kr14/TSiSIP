---
name: speckit-catalog-ci-validate
description: Validate catalog JSON structure, required fields, ID format, semver,
  ordering, and duplicates
compatibility: Requires spec-kit project structure with .specify/ directory
metadata:
  author: github-spec-kit
  source: catalog-ci:commands/speckit.catalog-ci.validate.md
---

# Validate Catalog

Validate a spec-kit community catalog JSON file for structural correctness, required fields, format compliance, and cross-entry consistency.

## User Input

$ARGUMENTS

You **MUST** consider the user input before proceeding (if not empty). The user may provide a path to a specific catalog file. If no path is given, default to `extensions/catalog.community.json` in the current repository.

## Prerequisites

- Locate the catalog JSON file (user-provided path or default)
- Read the file contents
- Parse as JSON — if parsing fails, report the error and stop

## Validation Rules

### Top-Level Structure

Check the following top-level fields exist and are valid:

1. **`schema_version`** — must equal `"1.0"`
2. **`updated_at`** — must be a valid ISO 8601 timestamp (e.g., `2026-04-10T00:00:00Z`)
3. **`catalog_url`** — must be a valid HTTPS URL
4. **`extensions`** or **`presets`** — at least one key must exist and be a non-empty object

### Per-Entry Required Fields

For each entry in `extensions` (or `presets`), validate:

| Field | Rule |
|-------|------|
| `name` | Non-empty string |
| `id` | Must match `^[a-z0-9-]+$` and must equal the object key |
| `description` | Non-empty string, under 200 characters |
| `author` | Non-empty string |
| `version` | Valid semver format `X.Y.Z` (e.g., `1.0.0`, `0.4.3`) |
| `download_url` | Valid HTTPS URL |
| `repository` | Valid HTTPS URL |
| `license` | Non-empty string |
| `requires.speckit_version` | Non-empty string (e.g., `>=0.4.0`) |
| `provides.commands` | Integer >= 0 |
| `tags` | Array with at least 1 element |
| `created_at` | Valid ISO 8601 timestamp |
| `updated_at` | Valid ISO 8601 timestamp |

### Cross-Entry Checks

1. **No duplicate IDs** — every key in the `extensions` object must be unique (JSON naturally enforces this, but check `id` field matches key)
2. **Alphabetical order** — keys must be sorted in ascending alphabetical order. Report the first out-of-order entry found.
3. **Date consistency** — for each entry, `updated_at` must be >= `created_at`

## Execution

1. Read and parse the catalog file
2. Run all top-level checks; collect errors
3. Iterate through every entry; run per-entry checks; collect errors with the entry ID as context
4. Run cross-entry checks; collect errors
5. Report results

## Output

Print a summary in this format:

```
Catalog Validation: <path>
========================
Top-level checks:     <PASS/FAIL>
Entries validated:     <N>
Errors found:         <count>

[If errors exist, list each:]
  ERROR [<entry-id>]: <description of issue>
  ERROR [top-level]: <description of issue>

Result: PASS (0 errors) | FAIL (<N> errors)
```

If the file is valid, output:

```
Result: PASS (0 errors)
```