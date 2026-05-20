# B14 — Backup Script Residual Bug Remediation

## Finding
File: `docker/backup/backup.sh`
Line 31 referenced `ALLOW_UNENCRYPTED_BACKUPS`, which had been removed from declaration (line 14) in a prior commit. With `set -euo pipefail`, this caused an "unbound variable" error at runtime.

## Changes Made

### 1. Removed orphaned variable reference (line 30)
**Before:**
```bash
if [ "$ALLOW_UNENCRYPTED_BACKUPS" != "true" ] && { [ ! -f "$ENCRYPTION_KEY_FILE" ] || [ ! -s "$ENCRYPTION_KEY_FILE" ] }; then
```

**After:**
```bash
if [ ! -f "$ENCRYPTION_KEY_FILE" ] || [ ! -s "$ENCRYPTION_KEY_FILE" ]; then
```

### 2. Updated comment (line 14)
**Before:**
```bash
# Encryption is mandatory in all environments per TSiSIP security policy.
# The ALLOW_UNENCRYPTED_BACKUPS opt-out has been removed (brownfield B8).
```

**After:**
```bash
# Encryption is mandatory in all environments per TSiSIP security policy.
```

## Rationale
Encryption is mandatory per TSiSIP security policy. The conditional was simplified to check only for the presence and non-emptiness of the encryption key file, eliminating the dead variable reference while preserving the security gate.

## Validation
```bash
bash -n docker/backup/backup.sh
# Result: OK (exit 0)
```
