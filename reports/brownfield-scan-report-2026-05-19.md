# Brownfield Scan Report

**Scan Metadata:**
- Scope: all
- Branch: main
- Commit: `8364b0e` — chore(env): replace example.com placeholders with tsiapp.io
- Files scanned: 34,611
- Scan date: 2026-05-24
- Authority: `docs/TSiSIP-CANONICAL-SPEC.md` (v1.1), `AGENTS.md`

---

## Findings

| ID | Category | Severity | File | Line | Finding | Recommendation |
|----|----------|----------|------|------|---------|----------------|
| B1 | Security Surface | CRITICAL | `docker-compose.yml` | 153 | RTPengine `--listen-ng=0.0.0.0:22222` binds control socket on all interfaces, exposing it on `sip_edge`. Canonical spec section 5 and AC-06 require binding to `${RTPENGINE_INTERNAL_IP}:22222` only. | Change to `--listen-ng=${RTPENGINE_INTERNAL_IP}:22222` to match prod/vps compose profiles. |
| B2 | Spec Drift | CRITICAL | `opensips/opensips.cfg.tpl` | 245–261 | In-dialog route handling uses `loose_route()` only; missing `topology_hiding_match()` required by canonical skeleton section 8 for topology-hidden dialogs. | Add `topology_hiding_match() || loose_route()` as the canonical in-dialog gate. |
| B3 | Anti-Pattern | HIGH | `opensips/opensips.cfg.tpl` | 402–403 | Auth credential headers are removed in `route[SANITIZE]`, which runs before `route[AUTH]`. Canonical skeleton section 8 places credential stripping in `route[RELAY]` after successful auth and `consume_credentials()`. | Move credential header removal from `SANITIZE` to `RELAY`. Retain `consume_credentials()` in `AUTH`. |
| B4 | Spec Drift | HIGH | `opensips/opensips.cfg.tpl` | 359–363 | `rtpengine_answer()` in `onreply_route` is called without explicit flags and only for 2xx replies. Canonical spec section 11 requires flags `replace-origin replace-session-connection ICE=remove` and activation on `183–299`. | Add canonical flags and handle 183 provisional responses. |
| B5 | Spec Drift | HIGH | `docker-compose.yml` | 150 | RTPengine published port range is `10000-10050:10000-10050/udp` (51 ports). Canonical spec sections 5 and 11 specify `10000-20000/udp`. | Align port range with canonical spec or document an architecture decision for the reduced range. |
| B6 | Spec Drift | HIGH | `opensips/opensips.cfg.tpl` | 113 | `modparam("dispatcher", "ds_probing_threshold", 3)` — canonical spec section 7 requires `2`. | Change to `ds_probing_threshold=2`. |
| B7 | Config Rot | HIGH | `docker-compose.yml` | 435 | `alertmanager` image `prom/alertmanager:v0.27.0` lacks SHA256 digest pinning. Other images (postgres-exporter, node-exporter) are pinned. AGENTS.md section 9 requires pinned base images. | Add `@sha256:<digest>` to the alertmanager image tag. |
| B8 | Security Surface | MEDIUM | `opensips/opensips.cfg.tpl` | 496, 516 | SQL queries in `AUTH_AUDIT` and `HEADER_ROUTING` interpolate variables without `s.escape.common` escaping. Canonical spec section 10 shows escaping pattern. | Apply `{s.escape.common}` transformation to all SQL-interpolated variables, or validate/escape via AVP transformations. |
| B9 | Security Surface | MEDIUM | `docker-compose.yml` | 490–491 | `anomaly-detector` attaches to `sip_internal` but publishes `127.0.0.1:8082:8080` on host. Canonical spec section 5 states management interfaces should use `metrics_host` for loopback exposure. | Attach `anomaly-detector` to `metrics_host` instead of `sip_internal`, or remove host port binding. |
| B10 | Config Rot | MEDIUM | `docker-compose.prod.yml` | — | `admin-api`, `certbot`, and `tailscale-cert` services lack `healthcheck` stanzas. Feature 004 requires health probes for all production services. | Add `healthcheck` definitions to these services. |
| B11 | Config Rot | MEDIUM | `docker-compose.yml` | — | `postgres-exporter`, `node-exporter`, and `tailscale-cert` services lack `healthcheck` stanzas. | Add `healthcheck` definitions for observability consistency. |
| B12 | Config Rot | MEDIUM | `.env.example` | 8 | `TSISIP_IMAGE_TAG=latest` is the default value. While comments advise pinning for production, the default encourages mutable tags. AGENTS.md section 9 requires pinned images. | Default to a placeholder like `TSISIP_IMAGE_TAG=set-me` or a stable tag, and add a pre-flight validation script. |
| B13 | Technical Debt | MEDIUM | `opensips/opensips.cfg.tpl` | 728 | `route[TRUNK_ROUTING]` sets the realm AVP but never populates the pass AVP. The inline comment admits the pass AVP must be populated by a runtime credential resolver, yet no resolver is implemented. Outbound trunk digest auth via `uac_auth()` will fail. | Implement a runtime credential resolver that decrypts `auth_password_encrypted` from `sip_trunk_providers` and sets the pass AVP before `t_relay()`. |
| B14 | Technical Debt | LOW | `docker/backup/rpo-monitor.sh` | 55 | `sleep 2` without a timeout loop or readiness probe justification. | Replace with a polling loop (max retries + backoff) or document why a fixed sleep is safe. |
| B15 | Technical Debt | LOW | `docker/tailscale-cert/renew.sh` | 18, 23 | `sleep 2` and `sleep 1` without timeout loops or readiness probe justification. | Replace with polling loops or add explanatory comments. |
| B16 | Spec Drift | LOW | `opensips/opensips.cfg.tpl` | 394–395 | `remove_hf("X-TSiSIP-Internal")` and `remove_hf("X-Backend-IP")` are not documented in canonical spec sections 8 or 17. | Document these headers in the canonical spec or remove if unused. |
| B17 | Spec Drift | LOW | `docker-compose.yml` | 199–200 | OpenSIPS publishes WebSocket ports `8080/tcp` and `4443/tcp` on host. Canonical spec section 5 only lists `5060/udp`, `5060/tcp`, `5061/tcp` as published ports. | Document WebSocket as a feature extension in the canonical spec or constrain to internal networks. |

---

## Summary by Severity

- **Critical: 2**
- **High: 5**
- **Medium: 6**
- **Low: 4**

**Total: 17 findings**

---

## Top 3 Action Items

1. **Fix RTPengine control socket exposure (B1)** — Change `--listen-ng=0.0.0.0:22222` to `--listen-ng=${RTPENGINE_INTERNAL_IP}:22222` in `docker-compose.yml`. This is a direct security boundary violation; the control socket must never be reachable from `sip_edge`.

2. **Restore canonical in-dialog topology hiding (B2)** — Add `topology_hiding_match()` to the in-dialog gate in `opensips.cfg.tpl`. Without it, topology-hidden dialogs may fail to match and route correctly, leaking backend topology or dropping in-dialog requests.

3. **Align credential stripping with canonical skeleton (B3)** — Move auth credential header removal from `route[SANITIZE]` to `route[RELAY]`. Stripping credentials before authentication is a non-canonical anti-pattern that can break retransmissions and confuse the auth flow.

---

## Scan Methodology

1. **Spec Drift Scan**: Compared `Dockerfile`, `opensips.cfg.tpl`, and compose files against `docs/TSiSIP-CANONICAL-SPEC.md` sections 5–14.
2. **Technical Debt Scan**: Searched for TODO/FIXME/HACK/XXX markers, `sleep` statements, and commented dead code.
3. **Anti-Pattern Scan**: Checked for forbidden modules (`sanity`, `db_mysql`), plaintext columns, hard-coded dispatcher routing, missing `topology_hiding_match()`, and credential stripping placement.
4. **Configuration Rot Scan**: Verified image pinning, healthcheck coverage, restart policies, environment variable completeness, and stale volume mounts.
5. **Security Surface Scan**: Checked for exposed secrets, weak defaults, exposed management interfaces, and RTPengine control socket binding.

No `db_mysql`, `db_sqlite`, or `sanity` module references were found in TSiSIP application code. All forbidden module checks passed.

---

*Report generated by speckit-brownfield-scan skill.*
*Would you like me to generate a remediation plan for the top N findings?*
