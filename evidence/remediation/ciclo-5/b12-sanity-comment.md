# Evidence: B12 — "Sanity" Comment Rephrasing

## Finding
- **ID**: B12
- **Severity**: LOW
- **Category**: Spec Drift
- **File**: `docker/backup/replicate.sh:45`
- **Finding**: Comment uses word "sanity" — not a module reference, but could confuse audits

## Fix Applied
Replaced "sanity check" with "validation check" in comment.

### Before
```bash
    # Connectivity + credential sanity check via rclone ls
```

### After
```bash
    # Connectivity + credential validation check via rclone ls
```

## Rationale
The `sanity` module is explicitly forbidden in TSiSIP (see AGENTS.md Section 7). While this comment was not a module reference, using the word "sanity" in code comments can trigger false positives during automated audits and confuse reviewers. "Validation check" is semantically equivalent and audit-safe.

## Verification
No other occurrences of "sanity" remain in non-test/non-vendor code.
