# Brownfield Scan Report — 2026-05-21

> Non-destructive scan of TSiSIP codebase for technical debt, anti-patterns, spec drift, and brownfield degradation.
> Authority: `docs/TSiSIP-CANONICAL-SPEC.md` + `AGENTS.md`

## Scan Metadata

| Field | Value |
|---|---|
| **Scope** | all |
| **Branch** | master |
| **Commit** | `374956c` refactor(governance): eliminate constitution duplication, add cross-references |
| **Files scanned** | 3,990 (excluding `.git/`, `node_modules/`, `.venv/`, `.ansible-venv/`, `.opencode/`, `.omk/`) |
| **Canonical refs loaded** | `docs/TSiSIP-CANONICAL-SPEC.md`, `AGENTS.md`, `.github/copilot-instructions.md`, `Dockerfile`, `docker-compose.yml`, `docker-compose.prod.yml`, `docker-compose.vps.yml` |
| **Scan tool** | `speckit-brownfield-scan` (project-local skill) |

---

## Summary by Severity

| Severity | Count | Status |
|---|---|---|
| **CRITICAL** | 0 | No blocking violations |
| **HIGH** | 1 | 1 spec drift |
| **MEDIUM** | 2 | 1 config rot + 1 tech debt |
| **LOW** | 0 | Clean |

**Total findings**: 3

---

## Findings Detail

### B1 — HIGH — Spec Drift — RTPengine Control Socket Loopback Fallback

| Field | Value |
|---|---|
| **ID** | B1 |
| **Category** | Spec Drift / Anti-Pattern |
| **Severity** | HIGH |
| **File** | `docker-compose.vps.yml` |
| **Line** | 65 |
| **Finding** | `--listen-ng=${RTPENGINE_INTERNAL_IP:-127.0.0.1}:22222` uses loopback (`127.0.0.1`) as fallback when `RTPENGINE_INTERNAL_IP` is unset. |
| **Canonical Rule** | `AGENTS.md` Section 10 (Rejected Patterns): *"RTPengine `listen-ng=127.0.0.1` in multi-container runtime"* is explicitly rejected. The canonical replacement is: *"Bind to `${RTPENGINE_INTERNAL_IP}:22222` on `sip_internal`"*. |
| **Impact** | If `RTPENGINE_INTERNAL_IP` is not set in the VPS environment, OpenSIPS cannot reach RTPengine's control socket because `127.0.0.1` inside the RTPengine container is not accessible from the OpenSIPS container. Media relay (SDP rewriting, SRTP/DTLS) fails silently or produces 500 errors on INVITE. |
| **Recommendation** | Remove the `:-127.0.0.1` fallback. Use `:?must be set` syntax (same pattern as `TSISIP_IMAGE_TAG`) so Docker Compose fails fast if the variable is missing: `\n      - --listen-ng=${RTPENGINE_INTERNAL_IP:?must be set}:22222\n` |

---

### B2 — MEDIUM — Config Rot — Mutable Image Tag Fallback in VPS Compose

| Field | Value |
|---|---|
| **ID** | B2 |
| **Category** | Config Rot / Security Surface |
| **Severity** | MEDIUM |
| **File** | `docker-compose.vps.yml` |
| **Line** | certbot service |
| **Finding** | `image: tsisip/certbot:${TSISIP_IMAGE_TAG:-latest}` uses `:-latest` fallback instead of `:?must be set`. |
| **Canonical Rule** | `AGENTS.md` Section 9 (Docker Runtime Hardening): *"All base images are pinned to SHA256 digests"*. `docker-compose.prod.yml` was already fixed to use `:?must be set` (commit `21d982e`). The VPS compose was missed. |
| **Impact** | If `TSISIP_IMAGE_TAG` is unset, Docker pulls `tsisip/certbot:latest`, a mutable tag. Breaks deterministic deploys and breaks the Architecture Guard SHA-pinning contract. |
| **Recommendation** | Change to `tsisip/certbot:${TSISIP_IMAGE_TAG:?must be set}` to align with `docker-compose.prod.yml` and the image pinning policy. |

---

### B3 — MEDIUM — Technical Debt — Hard-Coded IP in VPS Deploy Script

| Field | Value |
|---|---|
| **ID** | B3 |
| **Category** | Technical Debt |
| **Severity** | MEDIUM |
| **File** | `deploy/scripts/vps-deploy.sh` |
| **Line** | 101 |
| **Finding** | `sed -i 's/RTPENGINE_INTERNAL_IP=10.0.0.2/RTPENGINE_INTERNAL_IP=172.21.0.1/'` hard-codes both the old IP (`10.0.0.2`) and the replacement IP (`172.21.0.1`). |
| **Impact** | The script assumes a specific Docker network CIDR (`172.21.0.0/24`). If the VPS uses a different Docker network range, this sed silently patches the wrong value or leaves the env file inconsistent. The hard-coded source IP (`10.0.0.2`) suggests a stale template value that should never have been committed. |
| **Recommendation** | Replace the sed hack with a deterministic network discovery step: `RTPENGINE_INTERNAL_IP=$(docker network inspect tsisip_sip_internal --format='{{range .IPAM.Config}}{{.Gateway}}{{end}}')` or use a `.env.vps` template that is validated before deploy. Remove the hard-coded `10.0.0.2` reference entirely. |

---

## Passes That Returned Clean

### A. Spec Drift Scan — PASS
- **OpenSIPS version**: `ARG OPENSIPS_VERSION=3.6` in `Dockerfile` — matches canonical spec.
- **Modules**: `include_modules` lists `db_postgres`, `auth`, `auth_db`, `dialog`, `dispatcher`, `rtpengine`, `topology_hiding`, `rr`, `maxfwd`, `pike`, `ratelimit`, `tls_mgm`, `proto_tls`, `acc`, `httpd`, `mi_http` — all canonical. No `db_mysql`, `db_sqlite`, or `sanity`.
- **Network names**: `sip_edge`, `sip_internal`, `db_internal`, `metrics_host` — all lowercase snake_case per spec.
- **Bare-metal/VM-first**: No bare-metal install instructions found outside Dockerfiles.

### C. Anti-Pattern Scan — PASS (except B1 above)
- **Plaintext passwords**: No `password_column = "password"` or plaintext subscriber fields found.
- **Private port exposure**: No host-published ports for Asterisk or PostgreSQL in any compose file.
- **RTPengine control in prod**: `docker-compose.prod.yml` correctly binds to `${RTPENGINE_INTERNAL_IP}:22222` (no loopback).
- **Missing maxfwd/rr**: `rr.so`, `maxfwd.so`, `loose_route()`, and `mf_process_maxfwd_header(70)` all present in `opensips.cfg.tpl`.
- **Auth scope**: `www_authorize()` / `proxy_authorize()` used for non-OPTIONS requests.

### D. Configuration Rot Scan — PASS (except B2 above)
- **Env vars in `.env.example`**: All compose variables (`ACME_EMAIL`, `HOST_PUBLIC_IP`, `OPENSIPS_LISTEN_IP`, `RTPENGINE_INTERNAL_IP`, `RTPENGINE_PRIVATE_IP`, `TLS_DOMAIN`) are documented in `.env.example`.
- **Image tags in prod**: Zero `:latest` or `:stable` references in `docker-compose.prod.yml` (verified by Architecture Guard fix `21d982e`).
- **Health checks**: All 13 services in `docker-compose.prod.yml` declare `healthcheck:` blocks. Dockerfiles also declare `HEALTHCHECK` instructions.
- **Restart policies**: All services declare `restart: on-failure` or `restart: unless-stopped`.
- **cap_drop / security_opt**: All services declare `cap_drop: [ALL]` and `security_opt: ["no-new-privileges:true"]`.

### E. Security Surface Scan — PASS
- **Secrets in tracked files**: No runtime secrets found in committed files. `.claude/agents/` files contain pseudocode and regex patterns only — no real credentials.
- **Weak defaults**: `ACME_EMAIL=admin@example.com` in `.env.example` is a documented placeholder, not a production secret.
- **Exposed management interfaces**: Prometheus (`9090`), Grafana (`3000`), OCP (`8080`) all bind to `127.0.0.1` only in production compose. No public exposure.

---

## Top 3 Action Items

1. **Fix B1 (HIGH)**: Change `docker-compose.vps.yml:65` from `${RTPENGINE_INTERNAL_IP:-127.0.0.1}` to `${RTPENGINE_INTERNAL_IP:?must be set}` to prevent silent loopback fallback in multi-container runtime.
2. **Fix B2 (MEDIUM)**: Change `docker-compose.vps.yml` certbot image from `:-latest` to `:?must be set` to align with the deterministic image pinning policy.
3. **Fix B3 (MEDIUM)**: Remove hard-coded IP sed hack in `deploy/scripts/vps-deploy.sh:101`. Replace with runtime network discovery or a validated `.env.vps` template.

---

## Comparison with Previous Scan

| Field | 2026-05-20 | 2026-05-21 (this scan) |
|---|---|---|
| **CRITICAL** | 1 (B1 plaintext comment) | 0 |
| **HIGH** | 4 | 1 |
| **MEDIUM** | 3 | 2 |
| **LOW** | 2 | 0 |
| **Total** | 10 | 3 |

The brownfield surface has shrunk significantly. All historical B1–B16 findings are resolved. The 3 new findings are all in the VPS-lite path (`docker-compose.vps.yml`, `vps-deploy.sh`), indicating that the production compose (`docker-compose.prod.yml`) and core runtime are clean.

---

**Scanner**: Kimi Code CLI (`speckit-brownfield-scan`)  
**Authority**: `docs/TSiSIP-CANONICAL-SPEC.md`, `AGENTS.md`  
**Scan date**: 2026-05-21  
**Next scan recommended**: Before next feature implementation or VPS deployment

---

## Remediation Status

| ID | Severity | File | Fix Applied | Commit |
|---|---|---|---|---|
| B1 | HIGH | `docker-compose.vps.yml:65` | `:-127.0.0.1` → `:?must be set` | `1aa2209` |
| B2 | MEDIUM | `docker-compose.vps.yml` certbot | `:-latest` → `:?must be set` | `1aa2209` |
| B3 | MEDIUM | `deploy/scripts/vps-deploy.sh:101` | Hard-coded sed → Docker network gateway discovery | `1aa2209` |

**Post-remediation scan**: 2026-05-21. All 3 findings resolved. Zero remaining brownfield items.
