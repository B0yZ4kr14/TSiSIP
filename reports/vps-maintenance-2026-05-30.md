# VPS Maintenance Report — 2026-05-30

## Summary

Post-Feature-038 deployment maintenance performed on `tsiapp.io` (179.190.15.116).

## Actions Taken

### 1. Docker Cleanup

| Metric | Before | After | Saved |
|--------|--------|-------|-------|
| Images | 97 (23.65GB) | 59 (17.68GB) | ~6GB |
| Dangling Images | 38 | 0 | — |
| Build Cache | 11.35GB | 7.87GB | ~3.5GB |

**Commands executed:**
```bash
docker image prune -f
docker builder prune -f
```

### 2. Logrotate Fix

**Problem:** `/etc/logrotate.d/orthoplus` referenced non-existent user `ubuntu`.
**Fix:** Changed `create 0644 ubuntu ubuntu` → `create 0644 tsi tsi`.
**Status:** Will be validated on next run (2026-05-31 00:00:00).

### 3. PM2 Service Cleanup

**Problem:** `pm2-ubuntu.service` failed continuously (not used by TSiSIP).
**Fix:** Disabled service via `systemctl disable pm2-ubuntu`.
**Status:** ✅ Service disabled, will no longer trigger failed-unit alerts.

## Current VPS Status

| Metric | Value |
|--------|-------|
| Healthy Containers | 14/16 |
| Disk Usage | 41% (47G / 116G) |
| Memory Usage | 2.5G / 31G |
| OpenSIPS | 3.6.6 (healthy, Feature 038 deployed) |
| Anomaly Detector | healthy |
| Alertmanager | healthy |
| Continuous Audit | Timer active (next: 13:19) |

## Failed Units (Remaining)

| Unit | Status | Action Required |
|------|--------|-----------------|
| logrotate.service | Failed 13h ago | Fixed, validate on next run |
| pm2-ubuntu.service | Failed 6d ago | Disabled ✅ |

---

*Maintenance performed as part of Feature 038 deployment cycle.*
