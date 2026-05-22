# Brownfield Scan Report — TSiSIP (Post-Remediation Validation)
**Date**: 2026-05-20
**Branch**: master
**Commit**: cbc771c
**Files scanned**: 29,841
**Scope**: all
**Previous scan**: reports/brownfield-scan-2026-05-20.md (commit 4dfe5af)

---

## Executive Summary

This scan validates the 5-cycle remediation performed on the 2026-05-20 brownfield findings. **11 of 13 previous findings are fully resolved. One previous finding (B8) has a residual bug introduced during remediation. Two new findings were identified.**

| Category | Previous | Resolved | Residual | New |
|---|---|---|---|---|
| CRITICAL | 1 | 1 | 0 | 0 |
| HIGH | 3 | 3 | 0 | 0 |
| MEDIUM | 5 | 4 | 1 | 1 |
| LOW | 4 | 4 | 0 | 1 |
| **Total** | **13** | **12** | **1** | **2** |

---

## Previous Findings — Verification

| ID | Severity | Status | Verification |
|---|---|---|---|
| B1 | CRITICAL | **RESOLVED** | Plaintext password comment removed. `admin123!` only exists inside `crypt(...)` hash call. |
| B2 | HIGH | **RESOLVED** | RTPengine `--listen-ng` uses `${RTPENGINE_INTERNAL_IP}:22222` without `0.0.0.0` fallback. |
| B3 | HIGH | **RESOLVED** | False positive confirmed — all env vars present in `.env.example`. |
| B4 | HIGH | **RESOLVED** | False positive confirmed — auth contract uses `proxy_authorize` for non-REGISTER. |
| B5 | MEDIUM | **RESOLVED** | Acceptable documented — `sed` stub is syntax-check only. |
| B6 | MEDIUM | **RESOLVED** | E2E test host parameterized via `TARGET_HOST` env var. |
| B7 | MEDIUM | **RESOLVED** | OCP manual container workaround documented in OPERATOR-RUNBOOK. |
| B8 | MEDIUM | **RESIDUAL BUG** | `ALLOW_UNENCRYPTED_BACKUPS` variable removed, but line 31 still references it. See **B14** below. |
| B9 | MEDIUM | **RESOLVED** | False positive confirmed — observability services active in prod/dev compose. |
| B10 | LOW | **RESOLVED** | Explicit `OPENSIPS_HOST: 127.0.0.1` added to all production compose files. |
| B11 | LOW | **RESOLVED** | Example IP changed to `192.0.2.1` (TEST-NET-1). |
| B12 | LOW | **RESOLVED** | "sanity check" rephrased to "validation check" — no occurrences remain. |
| B13 | LOW | **RESOLVED** | `latest` fallback removed from production compose files. |

---

## New Findings

### B14 — Residual Bug from B8 Remediation

| Field | Value |
|---|---|
| **ID** | B14 |
| **Category** | Security |
| **Severity** | MEDIUM |
| **File** | `docker/backup/backup.sh` |
| **Line** | 31 |
| **Finding** | Variable `ALLOW_UNENCRYPTED_BACKUPS` was removed from declaration (line 14), but line 31 still references it in a conditional. With `set -euo pipefail`, an unbound variable will cause the script to fail. |
| **Recommendation** | Replace the conditional on line 31 with a simple check for encryption key presence only. Remove all references to `ALLOW_UNENCRYPTED_BACKUPS`. |

**Root cause**: Ciclo 3 remediation removed the variable declaration but missed the conditional that used it.

### B15 — Missing Healthchecks

| Field | Value |
|---|---|
| **ID** | B15 |
| **Category** | Config Rot |
| **Severity** | MEDIUM |
| **File** | `docker-compose.yml`, `docker-compose.prod.yml`, `docker-compose.vps.yml` |
| **Finding** | `backup` service has no `healthcheck` block in any compose file. `anomaly-detector` has no `healthcheck` in `docker-compose.yml` and `docker-compose.prod.yml`. |
| **Recommendation** | Add healthcheck commands to these services. For backup, a simple cron/timer check or lockfile age check is sufficient. For anomaly-detector, check its HTTP health endpoint. |

### B16 — CI Pipeline Publishes `latest` Tag

| Field | Value |
|---|---|
| **ID** | B16 |
| **Category** | Config Rot |
| **Severity** | LOW |
| **File** | `.github/workflows/deploy.yml` |
| **Line** | 125-136, 188-193 |
| **Finding** | The deploy workflow builds images as `:latest` and pushes `:latest` to GHCR. While production compose files now require `TSISIP_IMAGE_TAG`, the CI still promotes a mutable `latest` tag to the registry, which can be accidentally pulled by operators or other pipelines. |
| **Recommendation** | Stop pushing `:latest` from CI. Only push SHA-based tags and explicitly named release tags. Alternatively, document that `latest` is a CI artifact and not for production use. |

---

## Positive Findings

- No `db_mysql` or `db_sqlite` references found in application code
- No `sanity` module references found in application code
- No forbidden modules in OpenSIPS config
- OpenSIPS 3.6 branch confirmed in Dockerfile
- Network names use snake_case (`sip_edge`, `sip_internal`, `db_internal`)
- PostgreSQL not exposed publicly
- Asterisk has no published ports
- All services have `restart` policies
- Auth contract correct: `calculate_ha1=0`, `password_column=ha1`
- Topology hiding uses `topology_hiding("C")`
- Explicit `rtpengine_offer()` / `rtpengine_answer()` / `rtpengine_delete()` (no `rtpengine_manage()`)
- `maxfwd` and `rr` modules loaded and used
- No plaintext passwords in committed files
- No secrets tracked by git

---

## Top 3 Action Items

1. **B14 (MEDIUM)**: Fix residual `ALLOW_UNENCRYPTED_BACKUPS` reference in `docker/backup/backup.sh` line 31
2. **B15 (MEDIUM)**: Add healthchecks to `backup` and `anomaly-detector` services
3. **B16 (LOW)**: Document or remove `latest` tag push from CI deploy workflow

---

*Scan completed in read-only mode. No files were modified.*
