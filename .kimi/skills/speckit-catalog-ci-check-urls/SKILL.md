---
name: speckit-catalog-ci-check-urls
description: Verify all URLs in catalog entries are accessible via HTTP HEAD
compatibility: Requires spec-kit project structure with .specify/ directory
metadata:
  author: github-spec-kit
  source: catalog-ci:commands/speckit.catalog-ci.check-urls.md
---

# Check Catalog URLs

Verify that all URLs referenced in catalog entries are accessible by performing HTTP HEAD requests.

## User Input

$ARGUMENTS

You **MUST** consider the user input before proceeding (if not empty). The user may provide a path to a specific catalog file or a specific entry ID to check. If no path is given, default to `extensions/catalog.community.json`.

## Prerequisites

- Locate and read the catalog JSON file
- Parse as JSON — if parsing fails, report the error and stop
- Ensure `curl` is available for HTTP HEAD requests

## URL Fields to Check

For each catalog entry, check the following URL fields (if present):

| Field | Required |
|-------|----------|
| `download_url` | Yes |
| `repository` | Yes |
| `homepage` | Optional |
| `documentation` | Optional |
| `changelog` | Optional |

## Execution

1. Parse the catalog file
2. If the user specified an entry ID, check only that entry; otherwise check all entries
3. For each URL field in each entry, perform an HTTP HEAD request:

```bash
curl -sI -o /dev/null -w "%{http_code}" --max-time 10 "<URL>"
```

4. A URL is **OK** if it returns HTTP 200, 301, or 302
5. A URL is **WARN** if it returns HTTP 403 or 429 (rate-limited / auth-required — may be valid)
6. A URL is **FAIL** if it returns any other status code or times out

## Output

Print a summary in this format:

```
URL Check: <path>
========================
Entries checked:  <N>
URLs checked:     <total>
OK:               <count>
Warnings:         <count>
Failures:         <count>

[If warnings or failures exist, list each:]
  WARN  [<entry-id>] <field>: <URL> (HTTP <status>)
  FAIL  [<entry-id>] <field>: <URL> (HTTP <status> | timeout)

Result: PASS | WARN | FAIL
```

- **PASS** — all URLs return OK
- **WARN** — some URLs returned 403/429 but none failed
- **FAIL** — one or more URLs returned error status or timed out