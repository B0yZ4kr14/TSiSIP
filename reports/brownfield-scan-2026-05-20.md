# Brownfield Scan Report — TSiSIP
**Date**: 2026-05-20
**Branch**: master
**Commit**: 4dfe5af
**Files scanned**: 61,646
**Scope**: all

## Summary by Severity

| Severity | Count |
|---|---|
| CRITICAL | 1 |
| HIGH | 3 |
| MEDIUM | 5 |
| LOW | 4 |
| **Total** | **13** |

## Findings

### CRITICAL

| ID | Category | File | Line | Finding | Recommendation |
|---|---|---|---|---|---|
| B1 | Security | db/init/03-seed-data.sql | 79 | Default admin password committed in seed data with plaintext comment | Remove comment; force password change on first login; rotate default in deployed instances |

### HIGH

| ID | Category | File | Line | Finding | Recommendation |
|---|---|---|---|---|---|
| B2 | Spec Drift | docker-compose.vps.yml | 62 | RTPengine --listen-ng binds to fallback 0.0.0.0 exposing control port on all interfaces | Remove 0.0.0.0 fallback; enforce explicit internal IP only |
| B3 | Config Rot | .env.example | — | 4 environment variables used in compose are missing from .env.example | Add all referenced variables with documentation |
| B4 | Anti-Pattern | opensips/opensips.cfg.tpl | — | Auth contract uses www_authorize for all methods instead of proxy_authorize for non-REGISTER per RFC 3261 | Migrate non-REGISTER methods to proxy_authorize (407) |

### MEDIUM

| ID | Category | File | Line | Finding | Recommendation |
|---|---|---|---|---|---|
| B5 | Technical Debt | deploy/scripts/orchestrate-deploy.sh | 111 | Hard-coded 127.0.0.1 substitution in sed for template rendering | Use actual env var substitution or templating engine |
| B6 | Technical Debt | tests/integration/test_end_to_end_call.py | 95 | Hard-coded 127.0.0.1:5060 in _send_receive | Parameterize via env var or config |
| B7 | Config Rot | docker-compose.vps.yml | — | OCP service defined but container runs manually outside compose due to network removal bug | Fix compose state or document manual procedure |
| B8 | Security | docker/backup/backup.sh | 64 | Unencrypted backups allowed via environment flag | Remove opt-out; enforce encryption always in production |
| B9 | Config Rot | docker-compose.prod.yml | — | Prometheus/Grafana/Alertmanager services present but disabled by comment | Either remove disabled services or enable with healthchecks |

### LOW

| ID | Category | File | Line | Finding | Recommendation |
|---|---|---|---|---|---|
| B10 | Config Rot | docker/healthcheck/opensips-health.sh | 8 | Default 127.0.0.1 for OPENSIPS_HOST could mask misconfiguration | Add explicit OPENSIPS_HOST env var in all compose files |
| B11 | Technical Debt | docker/ca-tool/cert-gen.sh | 17 | Example uses RFC1918 IP as SAN example | Use documentation-only IP |
| B12 | Spec Drift | docker/backup/replicate.sh | 45 | Comment uses word "sanity" — not a module reference, but could confuse audits | Rephrase to "validation check" |
| B13 | Config Rot | docker-compose.yml / docker-compose.vps.yml | — | Image tags use latest fallback which is unpinned | Pin to specific tag in production |

## Top 3 Action Items

1. B1 (CRITICAL): Remove plaintext default password from seed data
2. B2 (HIGH): Fix RTPengine --listen-ng to never fall back to 0.0.0.0
3. B4 (HIGH): Complete auth contract migration (T5.3) — proxy_authorize for non-REGISTER

## Positive Findings

- No db_mysql or db_sqlite references found
- No sanity module references
- OpenSIPS 3.6 branch used in Dockerfile
- Network names use snake_case
- PostgreSQL not exposed publicly
- Asterisk has no published ports
- All services have restart policies
- Rate limiting configured in nginx
- Backup encryption + HMAC implemented
- HA1-only storage for SIP auth

*Scan completed in read-only mode. No files were modified.*
