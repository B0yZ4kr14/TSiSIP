# Feature 013: Wave 5 Security Review — Brownfield Residual Findings Remediation

**Reviewer:** reviewer agent  
**Date:** 2026-05-19  
**Scope:** B14 (backup.sh encryption fix) + B15 (healthcheck additions)

---

## Review Checklist

### 1. backup.sh no longer references ALLOW_UNENCRYPTED_BACKUPS anywhere
**Result: PASS**

- Searched `docker/backup/backup.sh` (82 lines). No occurrence of `ALLOW_UNENCRYPTED_BACKUPS` found.
- The B14 anti-pattern (optional unencrypted backup path) has been fully removed.

### 2. backup.sh still exits fatally if encryption key is missing
**Result: PASS**

- Lines 30–33: Fatal exit if `ENCRYPTION_KEY_FILE` is missing or empty before backup begins.
- Lines 58–61: Double-check fatal exit immediately before the encryption step.
- Both blocks call `exit 1`, ensuring backup will never be stored unencrypted.

### 3. backup.sh does not expose secrets in log messages
**Result: PASS**

- The `log()` helper (line 21) only echoes `[timestamp] message`.
- Line 26 logs the **path** to `PGPASSWORD_FILE`, not its contents.
- Line 31 logs the **path** to `ENCRYPTION_KEY_FILE`, not its contents.
- Line 41 reads `PGPASSWORD` via command substitution into an environment variable for `pg_dump`; the value is never printed.
- No `set -x`, no `echo "$PGPASSWORD"`, no secret leakage observed.

### 4. healthcheck.sh does not expose sensitive data (db passwords, encryption keys)
**Result: PASS**

- `docker/backup/healthcheck.sh` (16 lines) performs only two checks:
  1. Existence of `/tmp/backup.lock` (line 8)
  2. Count of `*.enc` files under `/backup/daily` newer than 1440 minutes (line 12)
- The script does not read, reference, or output any secret files (`db_password`, `backup_encryption_key`, etc.).

### 5. healthcheck endpoints in compose files are safe (no privileged info leakage)
**Result: PASS**

| File | Service | Healthcheck | Risk Assessment |
|---|---|---|---|
| `docker-compose.yml` | `backup` | `test: ["CMD-SHELL", "/usr/local/bin/healthcheck.sh"]` (line 435) | Shell-only; no network exposure. Safe. |
| `docker-compose.yml` | `anomaly-detector` | `curl -fsSL http://localhost:8080/health` (line 320) | Standard HTTP health endpoint on loopback. No secrets in URL. Safe. |
| `docker-compose.prod.yml` | `backup` | `test: ["CMD-SHELL", "/usr/local/bin/healthcheck.sh"]` (line 385) | Same as above. Safe. |
| `docker-compose.prod.yml` | `anomaly-detector` | `curl -fsSL http://localhost:8080/health` (line 271) | Same as above. Safe. |
| `docker-compose.vps.yml` | `backup` | `test: ["CMD-SHELL", "/usr/local/bin/healthcheck.sh"]` (line 229) | Same as above. Safe. |

No healthcheck commands leak credentials, expose secret paths, or bind to public interfaces.

### 6. No new hard-coded IPs or secrets introduced
**Result: PASS**

- Healthcheck additions do not introduce any new `127.0.0.1` bindings, IP literals, or secret strings.
- Pre-existing hard-coded IPs (e.g., `127.0.0.1:8080`, `127.0.0.1:9101`) are unchanged from prior waves and were not introduced by this feature.

### 7. scripts/ci-scan.sh passes
**Result: PASS**

```
=== TSiSIP CI Scan ===
[brownfield] Checking for hardcoded :latest tags... PASS
[brownfield] Checking for forbidden modules... PASS
[version-guard] Checking for unpinned base images... PASS
[memorylint] Checking for container memory limits... PASS
[security] Checking for committed secrets... PASS
=== CI SCAN PASSED ===
```

---

## Overall Conclusion

**ALL CHECKLIST ITEMS PASS.**

The B14 and B15 remediation changes are security-safe:
- The backup script enforces mandatory encryption without any opt-out flag.
- The new healthcheck script is stateless and secret-free.
- Compose healthcheck definitions use safe, non-leaking probes.
- No secrets, hard-coded IPs, or other security regressions were introduced.
