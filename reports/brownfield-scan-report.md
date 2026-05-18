# Brownfield Scan Report

**Scan Metadata:**
- Scope: all
- Branch: master
- Commit: b271047 feat(rate-limiting): implement Feature 006 DDoS protection
- Files scanned: 101,273
- Baseline: docs/TSiSIP-CANONICAL-SPEC.md + AGENTS.md

---

## Findings

| ID | Category | Severity | File | Line | Finding | Recommendation |
|----|----------|----------|------|------|---------|----------------|
| B1 | Spec Drift | HIGH | Dockerfile | 1 | `include_modules` contains `htable`; module does not compile on Debian bookworm | Remove `htable` from include_modules; use `ratelimit` + `userblacklist` + `cachedb_local` as already implemented |
| B2 | Spec Drift | MEDIUM | Dockerfile | 1 | Debian `bookworm-slim` tag without digest pin | Pin to `debian:bookworm-slim@sha256:...` for reproducible builds |
| B3 | Spec Drift | LOW | docs/TSiSIP-CANONICAL-SPEC.md | 561 | Reference to `apt.opensips.org` package install in spec | Ensure spec clearly states Docker-image-first; remove bare-metal apt references or mark as legacy only |
| B4 | Anti-Pattern | MEDIUM | db/init/01-stock-opensips-schema.sql | 10 | `subscriber.password` column exists (plaintext field) | Column is required by stock schema but must never be populated; ensure only `ha1*` hashes are used per AGENTS.md |
| B5 | Anti-Pattern | LOW | db/init/03-seed-data.sql | 27 | Seed data inserts into `subscriber` including `password` field | Ensure seed data uses empty/NULL password and valid HA1 hashes only |
| B6 | Anti-Pattern | MEDIUM | deploy/scripts/vps-deploy.sh | 95 | Hard-coded `RTPENGINE_PRIVATE_IP=172.19.0.1` | Move to `.env` or generate dynamically; document why this subnet |
| B7 | Anti-Pattern | LOW | deploy/scripts/*.sh | multiple | Hard-coded RFC1918 IPs in nginx allow lists (`10.0.0.0/8`, `172.16.0.0/12`, `192.168.0.0/16`) | Acceptable for private-admin ACLs, but ensure these match actual Tailscale/internal nets |
| B8 | Config Rot | CRITICAL | docker-compose.yml | 6 | All local images use `:latest` tag (`tsisip/postgres:latest`, `tsisip/rtpengine:latest`, etc.) | Pin to git-sha or semantic version tags; `:latest` prevents rollback and traceability |
| B9 | Config Rot | MEDIUM | docker-compose.yml | multiple | No healthcheck definitions in compose (only in Dockerfiles) | Add compose-level `healthcheck` blocks for runtime orchestration |
| B10 | Config Rot | LOW | docker-compose.yml | multiple | Mix of `restart: unless-stopped` and `restart: on-failure` | Standardize per service role; edge services should use `unless-stopped` |
| B11 | Security Surface | MEDIUM | docker-compose.yml | 35 | RTP port range `10000-20000:10000-20000/udp` published | Required by design, but ensure firewall rules restrict source IPs; document DDoS mitigation |
| B12 | Security Surface | LOW | secrets/ | N/A | Self-signed certs in `secrets/` directory | `.gitignore` correctly excludes; ensure rotation runbook exists (CA-tool provides this) |
| B13 | Technical Debt | LOW | opensips/opensips.cfg.tpl | multiple | 62 commented lines in config | Review for dead code; remove obsolete comments to reduce config size |
| B14 | Technical Debt | LOW | deploy/scripts/ | multiple | `sleep` statements without retry loops | Replace fixed sleeps with proper health-check polling (`wait-for-it` or similar) |
| B15 | Config Rot | LOW | docker-compose.yml | N/A | `.env.example` has 27 lines but compose references vars not in example | Audit env vars; ensure all `${VAR}` in compose have defaults or are documented in `.env.example` |

---

## Summary by Severity

- **Critical**: 1
- **High**: 1
- **Medium**: 6
- **Low**: 7

---

## Top 3 Action Items

1. **Pin image tags** (B8 - CRITICAL): Replace all `:latest` local image tags in docker-compose.yml with git-SHA or semver tags. This is essential for production reproducibility.
2. **Remove htable from Dockerfile** (B1 - HIGH): The `htable` module is listed in `include_modules` but fails to compile. It should be removed to clean up the build and avoid confusion.
3. **Add healthchecks to compose** (B9 - MEDIUM): While Dockerfiles have HEALTHCHECK, compose-level healthchecks enable Docker's dependency-aware startup ordering.

---

*Would you like me to generate a remediation plan for the top N findings?*
