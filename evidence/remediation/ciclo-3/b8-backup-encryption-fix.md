# Evidence: B8 — Backup Encryption Opt-Out Removal

## Finding
- **ID**: B8
- **Severity**: MEDIUM
- **Category**: Security
- **File**: `docker/backup/backup.sh:64`
- **Finding**: Unencrypted backups allowed via `ALLOW_UNENCRYPTED_BACKUPS` environment flag

## Fix Applied
Removed the opt-out mechanism entirely. Backups are now **mandatorily encrypted** in all environments.

### Changes in `docker/backup/backup.sh`
1. Removed `ALLOW_UNENCRYPTED_BACKUPS` variable declaration
2. Replaced conditional encrypt-or-warn logic with mandatory encryption
3. Added fatal error if encryption key is missing at encryption time (defense in depth)
4. Added comment documenting removal rationale

### Before
```bash
ALLOW_UNENCRYPTED_BACKUPS="${ALLOW_UNENCRYPTED_BACKUPS:-false}"
# ...
if [ -f "$ENCRYPTION_KEY_FILE" ] && [ -s "$ENCRYPTION_KEY_FILE" ]; then
    /usr/local/bin/encrypt.sh encrypt ...
else
    log "WARNING: Unencrypted backups explicitly allowed for this environment"
fi
```

### After
```bash
# Encryption is mandatory in all environments per TSiSIP security policy.
# The ALLOW_UNENCRYPTED_BACKUPS opt-out has been removed (brownfield B8).
# ...
if [ ! -f "$ENCRYPTION_KEY_FILE" ] || [ ! -s "$ENCRYPTION_KEY_FILE" ]; then
    log "ERROR: Encryption key missing or empty: $ENCRYPTION_KEY_FILE"
    exit 1
fi
/usr/local/bin/encrypt.sh encrypt ...
```

## Verification
- Script still validates encryption key presence before backup (line ~30)
- Script now fails fatally if encryption key is missing after backup creation
- No `ALLOW_UNENCRYPTED_BACKUPS` references remain in compose files or scripts

## Impact
- **Positive**: Eliminates risk of accidental unencrypted backup production
- **Risk**: None — encryption key was already required; this only removes the bypass
