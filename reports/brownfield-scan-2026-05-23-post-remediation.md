# Brownfield Scan Report — TSiSIP (Post-Remediation Validation)

**Date**: 2026-05-23
**Branch**: main
**Commit**: HEAD
**Files scanned**: 3,990 (excluding `.git/`, `node_modules/`, `.venv/`, `.ansible-venv/`, `.opencode/`, `.omk/`)
**Scope**: all
**Previous scan**: reports/brownfield-scan-2026-05-21.md

---

## Executive Summary

This scan validates the Feature 021 remediation cycle. **All 3 previous findings (B1–B3 from 2026-05-21 scan) are fully resolved. Zero new findings identified.**

| Category | Previous | Resolved | Residual | New |
|---|---|---|---|---|
| CRITICAL | 0 | — | 0 | 0 |
| HIGH | 1 | 1 | 0 | 0 |
| MEDIUM | 2 | 2 | 0 | 0 |
| LOW | 0 | — | 0 | 0 |
| **Total** | **3** | **3** | **0** | **0** |

---

## Previous Findings — Verification

| ID | Severity | Status | Verification |
|---|---|---|---|
| B1 | HIGH | **RESOLVED** | `docker-compose.vps.yml:65` changed from `${RTPENGINE_INTERNAL_IP:-127.0.0.1}` to `${RTPENGINE_INTERNAL_IP:?must be set}`. No loopback fallback remains. |
| B2 | MEDIUM | **RESOLVED** | All `tsisip/*` images in `docker-compose.vps.yml` use `${TSISIP_IMAGE_TAG:?must be set}`. The certbot service no longer has a `:-latest` fallback. |
| B3 | MEDIUM | **RESOLVED** | `deploy/scripts/vps-deploy.sh` uses Docker network gateway discovery instead of hard-coded sed replacement. |

---

## Historical Findings (B1–B16) — Verification

All historical brownfield findings from the original scan remain resolved. Key verifications:

- **B1 (htable)**: Removed from Dockerfile `include_modules`.
- **B2 (Debian pin)**: `debian:bookworm-slim` pinned to SHA256 digest.
- **B8 (Image tags)**: Zero `:latest` tags for `tsisip/*` images; all use `${TSISIP_IMAGE_TAG:?must be set}`.
- **B9 (Healthchecks)**: `healthcheck:` blocks present for all 13 services in production compose.
- **B15 (Env vars)**: `.env.example` documents all `${VAR}` references from `docker-compose.yml`.
- **B16 (Exposed credential)**: Hardcoded token removed from `.gitnexus/meta.json`; file added to `.gitignore`.

---

## Clean Scans

### A. Spec Drift — PASS
### B. Anti-Pattern — PASS
### C. Config Rot — PASS
### D. Security Surface — PASS

---

## Remediation Status

| ID | Severity | File | Fix Applied | Commit |
|---|---|---|---|---|
| B1 (2026-05-21) | HIGH | `docker-compose.vps.yml:65` | `:-127.0.0.1` → `:?must be set` | `1aa2209` |
| B2 (2026-05-21) | MEDIUM | `docker-compose.vps.yml` certbot | `:-latest` → `:?must be set` | `1aa2209` |
| B3 (2026-05-21) | MEDIUM | `deploy/scripts/vps-deploy.sh:101` | Hard-coded sed → network discovery | `1aa2209` |
| H1 | — | `.env.example` | Added 5 missing vars | `49d71a1` |
| H2 | — | `secrets/auth_secret` | Verified 32 random chars | `49d71a1` |
| B16 | CRITICAL | `.gitnexus/meta.json` | Token removed; `.gitignore` updated | `49d71a1` |

**Post-remediation scan**: 2026-05-23. **All findings resolved. Zero remaining brownfield items.**

---

**Scanner**: Kimi Code CLI
**Authority**: `docs/TSiSIP-CANONICAL-SPEC.md`, `AGENTS.md`
**Scan date**: 2026-05-23
**Next scan recommended**: Before next feature implementation or VPS deployment
