# Brownfield Scan Report

**Scan Metadata:**
- Scope: all
- Branch: main
- Commit: `ce91dc8` — docs(feature-017): add operational notes about db_postgres UUID warning
- Files scanned: 2,191
- Scan date: 2026-05-24
- Authority: `docs/TSiSIP-CANONICAL-SPEC.md`, `AGENTS.md`, `.github/copilot-instructions.md`

---

## Previous-Findings Status (2026-05-19 Baseline)

| ID | Category | Previous Finding | Status | Notes |
|----|----------|-----------------|--------|-------|
| M1 | Config Rot | Missing `RTPENGINE_INTERNAL_IP` in `.env.example` | **RESOLVED** | Present on line 22 (commented with example value). |
| M2 | Spec Drift | `sip_trunk_did_mappings.tenant_id` is `UUID` (should be `VARCHAR(36)`) | **UNRESOLVED** | `db/init/04-trunk-schema.sql:42` still uses `UUID`. All other tenant-scoped tables use `VARCHAR(36)`. |
| L1 | Anti-Pattern | UI placeholders using RFC 1918 addresses | **UNRESOLVED** | Still present in 6 OCP PHP files (`web/rtpproxy.php`, `web/keepalived.php`, `web/clusterer.php`, `web/smpp-gateway.php`, `web/call-center.php`). |
| L2 | Technical Debt | `sleep` statements in test/deploy scripts without justification | **UNRESOLVED** | 18 occurrences across `deploy/scripts/`, `tests/`, `docker/backup/`, `docker/certbot/`, `docker/tailscale_cert/`. |
| L3 | Security Surface | VEX/SBOM generation still pending | **UNRESOLVED** | No CycloneDX, SPDX, or VEX artifacts found in `reports/` or project root. |
| B1 | Spec Drift | RTPengine `--listen-ng=0.0.0.0:22222` exposed control socket on `sip_edge` | **RESOLVED** | Now binds to `${RTPENGINE_INTERNAL_IP}:22222` in all compose files. |
| B2 | Spec Drift | Missing `topology_hiding_match()` in in-dialog gate | **RESOLVED** | Present on line 248 of `opensips/opensips.cfg.tpl`. |
| B3 | Anti-Pattern | Credential header stripping in `route[SANITIZE]` before `route[AUTH]` | **RESOLVED** | Stripping moved to `route[RELAY]` (line 931–932) with explanatory comment on line 415. |
| B4 | Spec Drift | `rtpengine_answer()` missing canonical flags | **RESOLVED** | Now uses canonical flags on line 364. |
| B5 | Spec Drift | RTPengine port range `10000-10050` instead of `10000-20000` | **RESOLVED** | Main compose now uses `10000-20000`; VPS profile uses `10000-10999` (documented for VPS-lite). |
| B6 | Spec Drift | `ds_probing_threshold=3` instead of canonical `2` | **RESOLVED** | Corrected to `2` on line 113. |
| B7 | Config Rot | `alertmanager` image lacked SHA256 digest pinning | **RESOLVED** | Now pinned to a stable digest. |
| B8 | Security Surface | SQL queries without `{s.escape.common}` escaping | **UNRESOLVED** | 10 `sql_query*` calls in `opensips.cfg.tpl` still interpolate AVPs/variables directly (lines 445, 509, 529, 614, 647, 668, 790, 877, 893, 971). |
| B9 | Anti-Pattern | `anomaly_detector` on `sip_internal` with host port `127.0.0.1:8082` | **UNRESOLVED** | Should attach to `metrics_host` per canonical spec section 5. |
| B10 | Config Rot | `tailscale_cert` missing `healthcheck` in `docker-compose.prod.yml` | **UNRESOLVED** | Still no healthcheck stanza. |
| B12 | Config Rot | `.env.example` defaults to `TSISIP_IMAGE_TAG=latest` | **UNRESOLVED** | Encourages mutable tags; AGENTS.md section 9 requires pinned images. |
| B13 | Technical Debt | `TRUNK_ROUTING` never populates `$avp(trunk_auth_pass)` | **UNRESOLVED** | Inline comment on line 753 admits resolver is missing; `uac_auth()` will fail on trunk auth challenge. |
| B16 | Spec Drift | `remove_hf("X-TSiSIP-Internal")` / `remove_hf("X-Backend-IP")` not in canonical spec | **UNRESOLVED** | Present on lines 408–409 and 413; undocumented in `docs/TSiSIP-CANONICAL-SPEC.md`. |
| B17 | Spec Drift | WebSocket ports `8080/tcp` and `4443/tcp` published on host | **UNRESOLVED** | Canonical spec section 5 only lists `5060/udp`, `5060/tcp`, `5061/tcp` as published ports. |

---

## Current Findings

| ID | Category | Severity | File | Line | Finding | Recommendation |
|----|----------|----------|------|------|---------|----------------|
| F1 | Spec Drift | **HIGH** | `db/init/04-trunk-schema.sql` | 42 | `sip_trunk_did_mappings.tenant_id` is `UUID NOT NULL`, while all other tenant-scoped tables (`subscriber`, `header_routing_rules`, `pbx_backends`, `auth_audit_log`, `ocp_users`) use `VARCHAR(36)`. This is a schema inconsistency and spec drift. | Change to `VARCHAR(36) NOT NULL` to align with the rest of the TSiSIP schema and `AGENTS.md` conventions. |
| F2 | Anti-Pattern | **HIGH** | `docker-compose.vps.yml` | 194, 224 | `asterisk_pbx_1` and `asterisk_pbx_2` expose `5038/tcp` (Asterisk Manager Interface). AGENTS.md section 4 states Asterisk must have **zero host-published ports**; `expose:` is informational but still reveals a management interface on the internal network. | Remove `5038/tcp` from `expose` lists unless an ADR explicitly documents AMI access from the OCP/admin containers. |
| F3 | Anti-Pattern | **HIGH** | `docker-compose.prod.yml` | 614, 655 | Same as F2: Asterisk services expose `5038/tcp` in production compose. | Remove `5038/tcp` from `expose` or document with an ADR. |
| F4 | Anti-Pattern | **HIGH** | `docker-compose.yml` | 466–503 | `anomaly_detector` attaches to `sip_internal` and publishes `127.0.0.1:8082:8080` on host. Canonical spec section 5 places loopback-exposed management interfaces on `metrics_host`, not the SIP signaling network. | Move `anomaly_detector` to `metrics_host` network; remove `sip_internal` membership. |
| F5 | Config Rot | **HIGH** | `.env.example` | 8 | `TSISIP_IMAGE_TAG=latest` is the default. AGENTS.md section 9 requires pinned base images; mutable tags risk uncontrolled rollouts. | Default to a sentinel value like `TSISIP_IMAGE_TAG=set-me` and add a pre-flight validation script. |
| F6 | Technical Debt | **HIGH** | `opensips/opensips.cfg.tpl` | 753 | `route[TRUNK_ROUTING]` sets `$avp(trunk_auth_realm)` but never populates `$avp(trunk_auth_pass)`. The inline comment states a runtime credential resolver is needed, yet none is implemented. `uac_auth()` in `failure_route[TRUNK_FAILOVER]` will fail for trunks requiring digest auth. | Implement a credential resolver that decrypts `auth_password_encrypted` (BYTEA, pgcrypto) from `sip_trunk_providers` and sets `$avp(trunk_auth_pass)` before `t_relay()`. |
| F7 | Security Surface | **MEDIUM** | `opensips/opensips.cfg.tpl` | 445, 509, 529, 614, 647, 668, 790, 877, 893, 971 | Multiple `sql_query` / `sql_query_one` calls interpolate AVPs and variables directly into SQL strings without `{s.escape.common}` transformation. Canonical spec section 10 shows the escaping pattern. | Apply `{s.escape.common}` to all SQL-interpolated string variables, or use parameterized queries where supported. |
| F8 | Config Rot | **MEDIUM** | `docker-compose.yml` | — | `postgres_exporter`, `node_exporter`, and `tailscale_cert` services lack `healthcheck` stanzas. Feature 004 requires health probes for all production services. | Add `healthcheck` definitions to these three services. |
| F9 | Config Rot | **MEDIUM** | `docker-compose.prod.yml` | — | `tailscale_cert` service lacks `healthcheck` stanza. | Add a `healthcheck` to `tailscale_cert` in production compose. |
| F10 | Anti-Pattern | **MEDIUM** | `docker-compose.yml` | 107–134 | `node_exporter` is on `sip_internal` network. This is a host metrics exporter; placing it on the SIP signaling network violates network segregation principles. Canonical spec places metrics/observability on `db_internal` or `metrics_host`. | Move `node_exporter` to `db_internal` or `metrics_host`. |
| F11 | Security Surface | **MEDIUM** | `.env.example` | 28 | `TOPOLOGY_SECRET` default value is a weak placeholder string that may be copied without modification. | Replace with a non-functional placeholder like `TOPOLOGY_SECRET=SET_ME_32_CHAR_MIN` and add a startup validation. |
| F12 | Security Surface | **MEDIUM** | Runtime secrets dir | — | A secret template file contains a weak, predictable value. While the secrets directory is `.gitignore`-protected, the on-disk template is guessable and should not be used in any environment. | Rotate to a cryptographically random 32+ character string and document rotation procedure. |
| F13 | Technical Debt | **MEDIUM** | Multiple | — | 18 `sleep` statements across deploy, test, and container scripts without timeout loops or readiness probe justification. Files: `deploy/scripts/orchestrate-deploy.sh`, `deploy/scripts/safe-recovery.sh`, `deploy/scripts/test-vps-local.sh`, `deploy/scripts/vps-deploy.sh`, `tests/integration/test_trunk_*.sh`, `tests/vps-stabilization/rollback-runbook.sh`, `docker/backup/rpo-monitor.sh`, `docker/tailscale_cert/renew.sh`. | Replace fixed sleeps with polling loops (max retries + backoff) or add comments explaining why the delay is safe and bounded. |
| F14 | Spec Drift | **LOW** | `opensips/opensips.cfg.tpl` | 408–409, 413 | `remove_hf("X-TSiSIP-Internal")`, `remove_hf("X-Backend-IP")`, and `remove_hf("X-Backend-ID")` are not documented in canonical spec sections 8 or 17. | Document these headers in the canonical spec or remove if unused. |
| F15 | Spec Drift | **LOW** | `opensips/opensips.cfg.tpl` | 10–11 | WebSocket listeners `ws:${OPENSIPS_LISTEN_IP}:8080` and `wss:${OPENSIPS_LISTEN_IP}:4443` are configured. `docker-compose.yml` publishes `${OPENSIPS_WS_PORT:-8081}:8080/tcp` and `${OPENSIPS_WSS_PORT:-4443}:4443/tcp` on host. Canonical spec section 5 only lists `5060/udp`, `5060/tcp`, `5061/tcp` as published ports. | Document WebSocket as a feature extension in the canonical spec or constrain to internal networks. |
| F16 | Security Surface | **LOW** | Project root | — | No VEX (Vulnerability Exploitability eXchange), SBOM (CycloneDX/SPDX), or software-bill-of-materials artifacts are generated. Security/compliance workflows cannot assess supply-chain risk. | Add a CI step to generate CycloneDX SBOM and VEX from built images (e.g., `syft` + `grype`). |
| F17 | Config Rot | **LOW** | `web/rtpproxy.php` | 161 | Placeholder uses RFC 1918 address `udp:10.0.0.1:7722`. | Use a documentation-only placeholder (e.g., `udp:REPLACE_ME:7722`). |
| F18 | Config Rot | **LOW** | `web/keepalived.php` | 215 | Placeholder uses RFC 1918 address `10.0.0.100`. | Use a documentation-only placeholder. |
| F19 | Config Rot | **LOW** | `web/clusterer.php` | 265, 284 | Placeholders use RFC 1918 addresses `bin:10.0.0.1:5555` and `sip:10.0.0.1:5060`. | Use documentation-only placeholders. |
| F20 | Config Rot | **LOW** | `web/smpp-gateway.php` | 196 | Placeholder uses RFC 1918 address `10.0.0.1`. | Use a documentation-only placeholder. |
| F21 | Config Rot | **LOW** | `web/call-center.php` | 301 | Placeholder uses RFC 1918 address `sip:agent1@10.0.0.1:5060`. | Use a documentation-only placeholder. |

---

## Summary by Severity

- **Critical: 0**
- **High: 5**
- **Medium: 6**
- **Low: 10**

**Total: 21 findings**

---

## Top 3 Action Items

1. **Fix schema inconsistency in `sip_trunk_did_mappings` (F1)** — Change `tenant_id UUID` to `tenant_id VARCHAR(36)` to align with every other tenant-scoped table in the database. This is a straightforward DDL migration (`ALTER TABLE ... ALTER COLUMN ... TYPE VARCHAR(36)` with a cast) and prevents type-mismatch bugs in application queries.

2. **Implement trunk credential resolver (F6)** — `route[TRUNK_ROUTING]` currently leaves `$avp(trunk_auth_pass)` unset, which means `uac_auth()` in `failure_route[TRUNK_FAILOVER]` will always fail for trunk providers requiring digest authentication. Add a resolver that decrypts `auth_password_encrypted` (pgcrypto BYTEA) and sets the AVP before `t_relay()`.

3. **Remove Asterisk AMI port from `expose` lists (F2, F3)** — `5038/tcp` is the Asterisk Manager Interface, a powerful management port. Its presence in `expose` on `sip_internal` increases blast radius if any container on that network is compromised. Remove it from `docker-compose.vps.yml` and `docker-compose.prod.yml` unless an explicit ADR justifies controlled AMI access.

---

## Scan Methodology

1. **Spec Drift Scan**: Compared `Dockerfile`, `opensips/opensips.cfg.tpl`, and compose files against `docs/TSiSIP-CANONICAL-SPEC.md` sections 5–14.
2. **Technical Debt Scan**: Searched for `TODO`/`FIXME`/`HACK`/`XXX` markers, `sleep` statements, and commented dead code.
3. **Anti-Pattern Scan**: Checked for forbidden modules (`sanity`, `db_mysql`), plaintext columns, hard-coded dispatcher routing, missing `topology_hiding_match()`, credential stripping placement, backend port exposure, and network segregation violations.
4. **Configuration Rot Scan**: Verified image pinning, healthcheck coverage, restart policies, environment variable completeness, and stale volume mounts.
5. **Security Surface Scan**: Checked for exposed secrets, weak defaults, exposed management interfaces, RTPengine control socket binding, missing VEX/SBOM, and SQL injection patterns.

No `db_mysql`, `db_sqlite`, or `sanity` module references were found in TSiSIP application code. All forbidden module checks passed.

---

*Report generated by speckit-brownfield-scan skill.*
*Would you like me to generate a remediation plan for the top N findings?*
