# Security Constitution — TSiSIP

> Project-wide security rules, standards, and requirements.
> Complements `.specify/memory/constitution.md` (governance) and
> `.specify/memory/architecture_constitution.md` (architecture enforcement).
> Do not duplicate P0 blocking rules or governance principles found in those files.

## 1. Trust Boundaries

### Network Segmentation
| Boundary | Members | Exposure | Rule |
|---|---|---|---|
| Public SIP Edge | OpenSIPS, RTPengine | Internet-facing | Authenticate all non-OPTIONS SIP requests |
| Internal SIP | OpenSIPS, RTPengine, Asterisk | Zero host ports | Topology hiding mandatory; no direct public access |
| Database | PostgreSQL, OCP (read-only) | Zero host ports | No container bypasses OpenSIPS to write subscriber data |
| Control Plane | OCP, Prometheus, Grafana | Loopback or internal only | OCP proxied via Nginx/Cloudflare; no direct container exposure |
| Observability | Prometheus, Grafana, Alertmanager, Exporter | Internal networks only | Metrics endpoints must not leak tenant-scoped data |

### SIP-Specific Boundaries
- OpenSIPS is the **sole** public SIP entry point. No Asterisk port may be published.
- RTPengine control socket (`--listen-ng`) binds only to `sip_internal` network.
- WebSocket/WSS transport is terminated at OpenSIPS; internal forwarding uses standard SIP.

## 2. Authentication & Authorization Standards

### SIP Digest Authentication
> Principle: See `constitution.md` Engineering Philosophy #4 (Precomputed HA1).
- **Hash algorithm**: HA1 precomputed (`calculate_ha1 = 0`).
- **Columns**: `ha1`, `ha1_sha256`, `ha1_sha512t256` only. Plaintext passwords are forbidden.
- **Scope**: All non-OPTIONS untrusted requests must pass `www_authorize()` or `proxy_authorize()`.
- **Trusted gateways**: Whitelist via `check_source_address()` only; exempt from digest but still subject to rate limiting.

### OCP Web Authentication
- **Hash algorithm**: bcrypt via PHP `password_hash()`.
- **Account lockout**: 5 failed attempts trigger temporary lockout.
- **Forced password change**: New accounts and password resets require change on first login.
- **Role hierarchy** (lowest to highest): `readonly` → `user` → `assistant` → `dentist` → `devops` → `admin`.
- **Session security**: `session.cookie_secure` enabled when HTTPS is detected via `X-Forwarded-Proto`.
- **CSRF**: All state-changing OCP forms require CSRF token validation.

### SIP Trunk Security
- Outbound trunk credentials encrypted at rest (AES-256-GCM via `TRUNK_CRED_KEY`).
- Inbound trunk whitelisting: IP-based `check_source_address()` + mTLS where provider supports it.
- Trunk health probes use unsigned OPTIONS; do not challenge probes with digest.

## 3. Data Isolation & Privacy Rules

### Multi-Tenancy Isolation
- All subscriber, routing, CDR, and audit tables include `tenant_id` foreign key.
- OCP queries must filter by `tenant_id` derived from authenticated session.
- Cross-tenant query leakage is a P0 security violation.

### CDR and Audit Retention
- CDR retention: 7 years (default), configurable per tenant.
- Audit logs (`auth_audit_log`, `ocp_login_log`): retained for 1 year minimum.
- Purge operations must be logged and require `admin` or `devops` role.

### LGPD / Privacy
- Personal data in CDR (caller/callee identifiers) is pseudonymized where possible.
- Backup encryption uses AES-256-CBC + PBKDF2 + HMAC-SHA256; decryption keys are runtime secrets, never committed.
- Right to erasure: tenant deletion cascades to subscriber and CDR after retention period.

## 4. Secrets Management Policy

### Classification
| Tier | Examples | Storage | Rotation |
|---|---|---|---|
| Runtime | DB password, auth_secret, topology_secret | Docker secrets (`/run/secrets/`) or env var | On compromise or quarterly |
| TLS | server.key, server.crt, ca.crt | Docker secrets; CA-tool generates/rotates | 90 days (Let's Encrypt) or on revocation |
| Trunk | `TRUNK_CRED_KEY`, provider credentials | Docker secrets; encrypted at rest | On provider change or annual |
| Backup | `backup_encryption_key` | Docker secrets | Annual or on key personnel change |

### Rules
- No secret may be committed to Git. `secrets/` and `.env*` are `.gitignore` protected.
- Secrets are injected at runtime via Docker secrets or env-templated config files.
- OCP copies secrets to `/tmp` with `www-data`-readable permissions at startup; original secret files remain read-only.
- CI pipeline (`deploy.yml`) scans for committed secrets in Gate 0 (pre-flight).

## 5. Secure-by-Design Patterns

### Header Sanitization
Remove or strip the following before forwarding or processing routing metadata:
- `P-Asserted-Identity`, `P-Preferred-Identity`
- `X-Tenant-ID`, `X-Backend-ID`, `X-Route-Override`
- `X-Routing-Key` (after lookup)
- `Authorization`, `Proxy-Authorization` (before forwarding to backend)

### Topology Hiding
> Principle: See `constitution.md` Engineering Philosophy #5 (Topology hiding).
- Canonical baseline: `topology_hiding("C")`.
- Backend PBX IP addresses must never appear in SIP headers exposed to the public internet.

### Container Hardening
- All services declare `cap_drop: [ALL]`.
- Minimal `cap_add`:
  - OpenSIPS: `NET_BIND_SERVICE`, `SETUID`, `SETGID`
  - RTPengine: `NET_BIND_SERVICE`, `NET_ADMIN`
- `security_opt: ["no-new-privileges:true"]` on all services.
- All base images pinned to SHA256 digests (verified by Architecture Guard).

### Rate Limiting & DDoS
- `pike` module tracks per-source request rate; exceed → temporary block.
- Per-tenant rate limits enforced in OpenSIPS routing logic.
- Circuit breaker on dispatcher failover to prevent cascade overload.

## 6. API & Integration Security

### TLS Requirements
- **Minimum version**: TLS 1.2 (origin); TLS 1.0/1.1 explicitly disabled at Cloudflare edge.
- **HSTS**: Enabled with preload; documented in SSL Labs evidence.
- **Certificate source**: Let's Encrypt (origin); Cloudflare (edge).
- **Rotation**: Automated via certbot container with deploy-hook reload.

### SIP Trunk API
- mTLS required for trusted provider integrations where supported.
- Provider health probes use unsigned OPTIONS; no digest challenge.
- Failover: automatic dispatcher fallback on 5xx or timeout.

### WebSocket / WebRTC
- WSS mandatory; no unencrypted WS in production.
- SRTP/DTLS enabled on RTPengine for all WebRTC calls.
- STUN/TURN credentials are short-lived and rotated via OCP.

## 7. Audit, Logging & Monitoring Requirements

### Audit Events (Mandatory)
| Event | Table | Fields |
|---|---|---|
| SIP auth success/failure | `auth_audit_log` | timestamp, method, src_ip, username, tenant_id, result |
| OCP login | `ocp_login_log` | timestamp, username, ip, role, result |
| Password change | `ocp_password_changes` | timestamp, username, changed_by |
| Dispatcher update | `dispatcher` + log | timestamp, setid, destination, state, changed_by |
| Trunk config change | `sip_trunk_config` + log | timestamp, trunk_id, field, old_val, new_val, changed_by |

### Log Retention
- Container stdout/stderr: retained by Docker logging driver (json-file or journald).
- PostgreSQL audit tables: 1 year minimum.
- CDR: 7 years default.
- Security evidence scans: retained in `docs/security/evidence/` with dated filenames.

### Monitoring
- Prometheus scrapes OpenSIPS MI, exporter metrics, and container health.
- Alertmanager routes HIGH/CRITICAL alerts to configured channels.
- Anomaly detector runs Z-score analysis on SIP traffic patterns.

## 8. Security Incident Response Triggers

### P0 Incidents (Immediate Response)
- Unauthorized access to PostgreSQL or Asterisk containers.
- Architecture P0 violations detected (e.g., `sanity` module, `db_mysql`, exposed private ports) — see `architecture_constitution.md` Blocking Architecture Violations.
- Plaintext password found in committed files or runtime logs.
- TLS certificate expiry without valid replacement.
- SIP trunk credential decryption failure or unauthorized trunk registration.

### P1 Incidents (24h Response)
- SSL Labs grade below B.
- Trivy scan detects new HIGH/CRITICAL CVE in deployed image.
- Rate limit bypass or DDoS anomaly detected.
- Backup encryption key compromise suspicion.

### Response Procedures
- Primary: `docs/security/008-incident-response-runbook.md`
- Evidence preservation: capture logs, snapshot containers, freeze audit tables.
- Escalation: documented contacts in runbook (no `[TBD]` placeholders).

## 9. Compliance & Regulatory Mapping

| Requirement | TSiSIP Control | Evidence |
|---|---|---|
| LGPD — data retention | CDR 7yr, audit 1yr, purge logging | `docs/security/008-MSL-applicability-justification.md` |
| LGPD — encryption at rest | Backup AES-256-GCM; TLS 1.2+ in transit | SSL Labs report, backup scripts |
| LGPD — access control | Role-based OCP; SIP digest auth | `web/common/role-nav.php`, `opensips.cfg.tpl` |
| LGPD — audit trail | `auth_audit_log`, `ocp_login_log` | `db/init/02-tsisip-extensions.sql` |
| LGPD — right to erasure | Tenant deletion cascade | `subscribers.php` (admin flow) |
| ANATEL / telecom — CDR integrity | Immutable CDR with tenant attribution | `db/init/02-tsisip-extensions.sql` |
| SOC 2 — change management | Spec-driven development, gated deploy pipeline | `deploy/scripts/orchestrate-deploy.sh` |
| SOC 2 — vulnerability management | Trivy CI scan, 90-day artifact retention | `.github/workflows/deploy.yml` |

## References

- **Governance**: `.specify/memory/constitution.md` (v1.1.0)
- **Architecture enforcement**: `.specify/memory/architecture_constitution.md`
- **Evidence index**: `docs/security/008-security-evidence-index.md`
- **Incident response**: `docs/security/008-incident-response-runbook.md`
- **Operator runbook**: `docs/TSiSIP-OPERATOR-RUNBOOK.md`

---
**Version**: 1.0.0 | **Ratified**: 2026-05-21 | **Last Review**: 2026-05-21
