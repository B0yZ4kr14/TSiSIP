---
description: "Compare catalog against a git ref to show added, modified, and removed entries"
---

# Diff Catalog

Compare the current catalog file against a previous git ref (branch, tag, or commit) to identify added, modified, and removed extension entries.

## User Input

$ARGUMENTS

You **MUST** consider the user input before proceeding (if not empty). The user may provide:
- A git ref to compare against (default: `main`)
- A path to the catalog file (default: `extensions/catalog.community.json`)

## Prerequisites

- Ensure the working directory is inside a git repository
- Verify the specified git ref exists: `git rev-parse --verify <ref> 2>/dev/null`
- If the ref does not exist, report the error and stop

## Execution

1. Read the **current** catalog file from the working tree
2. Read the **base** catalog file from the git ref:

```bash
git show <ref>:<catalog-path>
```

3. Parse both as JSON
4. Compare the `extensions` (or `presets`) objects:

### Detection Logic

- **Added**: key exists in current but not in base
- **Removed**: key exists in base but not in current
- **Modified**: key exists in both but any field value differs — report which fields changed

For modified entries, compare these fields:
`name`, `version`, `description`, `author`, `download_url`, `repository`, `tags`, `provides`, `requires`

## Output

Print a summary in this format:

```
Catalog Diff: <catalog-path>
Base ref: <ref>
========================
Added:    <count>
Modified: <count>
Removed:  <count>

[If any changes, list each:]

ADDED:
  + <entry-id> (<name>) v<version> by <author>

MODIFIED:
  ~ <entry-id>:
    version: <old> -> <new>
    description: changed
    tags: [old-list] -> [new-list]

REMOVED:
  - <entry-id> (<name>)
```

If no changes are found:

```
No changes detected between HEAD and <ref>.
```
