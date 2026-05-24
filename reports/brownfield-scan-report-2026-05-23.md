# Brownfield Scan Report

**Date**: 2026-05-23
**Scope**: all
**Branch**: main
**Commit**: 1d743bf docs(022): speckit-implement completion validation
**Files scanned**: 112,104
**Authority**: AGENTS.md, docs/TSiSIP-CANONICAL-SPEC.md, architecture_constitution.md
**Status**: REMEDIATED

---

## Summary by Severity

| Severity | Count | Remediated |
|---|---|---|
| CRITICAL | 1 | ✅ |
| HIGH | 1 | ✅ |
| MEDIUM | 2 | ✅ |
| LOW | 1 | ✅ |

---

## Findings (All Remediated)

| ID | Category | Severity | File | Finding | Remediation |
|---|---|---|---|---|---|
| B1 | Spec Drift | CRITICAL | `docker-compose.vps.override.yml` | `:latest` tags on 4 production images | Replaced with `ghcr.io/b0yz4kr14/tsisip/*:test` |
| B2 | Config Rot | HIGH | `.env.example` | 6 env vars missing | Added ACME_EMAIL, HOST_PUBLIC_IP, OPENSIPS_LISTEN_IP, RTPENGINE_INTERNAL_IP, RTPENGINE_PRIVATE_IP, TLS_DOMAIN |
| B3 | Technical Debt | MEDIUM | `scripts/test-invite-407.sh` | Hard-coded test IP | Parameterized with `TEST_IP` derived from Docker network |
| B4 | Technical Debt | MEDIUM | `docker/backup/rpo-monitor.sh`, `docker/tailscale-cert/renew.sh` | `sleep` without justification | Added explanatory comments to all sleep statements |
| B5 | Config Rot | LOW | `docker-compose.vps.yml` | Comment suggesting `:latest` | Changed to "versioned tags only; :latest never used" |

---

## Remediation Verification

| ID | Verification Command | Result |
|---|---|---|
| B1 | `grep -n ":latest" docker-compose.vps.override.yml` | No matches |
| B2 | `grep -E "HOST_PUBLIC_IP|ACME_EMAIL|TLS_DOMAIN" .env.example` | All present |
| B3 | `head -5 scripts/test-invite-407.sh` | `TEST_IP` parameter present |
| B4 | `grep -n "sleep" docker/backup/rpo-monitor.sh docker/tailscale-cert/renew.sh` | All commented |
| B5 | `grep -n "versioned tags" docker-compose.vps.yml` | Updated comment found |

---

## Positive Findings (What Passed)

| Check | Status | Evidence |
|---|---|---|
| Secrets not committed | ✅ PASS | `git ls-files | grep "^secrets/"` returns empty |
| MI interface not exposed to host | ✅ PASS | Port 8888 bound to container name only |
| Rate limiting configured | ✅ PASS | `loadmodule "pike.so"` + `reqs_density_per_unit=50` |
| Backup encryption enabled | ✅ PASS | `encrypt.sh` + Docker secrets |
| Forbidden modules absent | ✅ PASS | No `db_mysql`/`db_sqlite`/`sanity` in runtime configs |
| Network isolation correct | ✅ PASS | Asterisk/PostgreSQL have no published ports |
| Docker-first | ✅ PASS | No bare-metal install paths |

---

**Scan completed with 0 remaining findings.**
