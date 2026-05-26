# Security

<!-- docguard:version 0.2.0 -->
<!-- docguard:status active -->
<!-- docguard:last-reviewed 2026-05-26 -->
<!-- docguard:generated false -->

> **Canonical Security Document for TSiSIP.**

| Metadata | Value |
|----------|-------|
| **Status** | ![Status](https://img.shields.io/badge/status-active-brightgreen) |
| **Version** | `0.2.0` |
| **Last Updated** | 2026-05-26 |

---

## Authentication

TSiSIP uses two distinct authentication mechanisms:

| Method | Provider | Token Type | Expiry |
|--------|---------|-----------|--------|
| SIP Digest | OpenSIPS `auth_db` module | HA1 challenge-response | Per-request |
| PHP Session | OCP Admin Panel | `PHPSESSID` cookie | Session + force-password-change |

### SIP Digest Authentication
- OpenSIPS authenticates every non-OPTIONS request against PostgreSQL `subscriber` table.
- Credentials stored as **precomputed HA1 hashes only** (`ha1`, `ha1_sha256`, `ha1_sha512t256`); plaintext passwords are forbidden.
- `calculate_ha1 = 0` in OpenSIPS config; `password_column = "ha1"`.
- Supports RFC 3261 (MD5) and RFC 8760 (SHA-256, SHA-512/256).

### OCP Session Authentication
- PHP sessions with `requireAuth()` gate on every admin page.
- Brute-force protection: `failed_attempts` counter + `locked_until` timestamp.
- Mandatory password change on first login (`force_password_change` flag).
- Session role stored in `$_SESSION['ocp_user_role']`.

## Authorization

| Role | Level | Permissions | Notes |
|------|-------|-------------|-------|
| `readonly` | 1 | View dashboards, CDR, dialog viewer | Read-only access to monitoring data |
| `user` | 2 | View + limited config changes | Standard operator |
| `devops` | 3 | Audit log export, CDR viewer, clusterer, dispatcher, dialplan | Infrastructure management |
| `admin` | 4 | All permissions + user management | Full control; bypasses some checks |

Role hierarchy enforced by `requireRole()` in `web/common/config.php`:
\`\`\`php
$roleHierarchy = ['readonly' => 1, 'user' => 2, 'devops' => 3, 'admin' => 4];
\`\`\`

## Secrets Management

### Secrets Inventory

| Secret | Storage | Rotation | Access |
|--------|---------|----------|--------|
| `auth_secret` | `secrets/auth_secret` (Docker secret mount) | Manual | OpenSIPS `auth_db` |
| `db_password` | `secrets/db_password` (Docker secret mount) | Manual | OpenSIPS, OCP, Grafana |
| `topology_secret` | `secrets/topology_secret` (Docker secret mount) | Manual | OpenSIPS `topology_hiding` |
| `backup_encryption_key` | `secrets/backup_encryption_key` | Manual | Backup container |
| `grafana_admin_password` | `secrets/grafana_admin_password` | Manual | Grafana init |
| `proxy_api_secret` | `secrets/proxy_api_secret` | Manual | Internal API calls |
| `server.key` / `server.crt` | `secrets/` (TLS material) | Auto (Let's Encrypt) | OpenSIPS, nginx |
| `ca.key` | `ca-offline/ca.key` (offline, restricted) | Manual | Certificate authority |

### Environment Variables (non-secret config)

See `.env.example` for the full list. Key security-relevant variables:
- `TOPOLOGY_SECRET` — must be 32+ chars random string
- `SUBSCRIBER_CREATE_RATE_LIMIT=10`
- `SUBSCRIBER_UPDATE_RATE_LIMIT=30`
- `SUBSCRIBER_DELETE_RATE_LIMIT=10`
- `OCP_AUDIT_RETENTION_DAYS=90`

## Security Boundaries

### Network Boundaries

| Network | Members | External Access | Trust Level |
|---------|---------|----------------|-------------|
| `sip_edge` | OpenSIPS, RTPengine, nginx | Yes (5060, 10000-20000, 443) | Untrusted — Internet |
| `sip_internal` | OpenSIPS, RTPengine, Asterisk | No | Trusted — internal SIP + RTP control |
| `db_internal` | OpenSIPS, PostgreSQL, OCP (via proxy) | No | Trusted — database only |

### Input Boundaries
- All SIP messages pass through explicit header validation (the `sanity` module is not available in OpenSIPS 3.6).
- Untrusted inbound headers stripped before routing:
  - `P-Asserted-Identity`, `P-Preferred-Identity`
  - `X-Tenant-ID`, `X-Backend-ID`, `X-Route-Override`
  - `X-Routing-Key` (removed after lookup)
- Credentials stripped before forwarding to Asterisk:
  - `Authorization`, `Proxy-Authorization`

### Container Boundaries
- Asterisk and PostgreSQL have **zero host-published ports**.
- RTPengine control socket (`--listen-ng`) binds only to `sip_internal` address.
- Docker runtime hardened:
  - `security_opt: ["no-new-privileges:true"]`
  - Capabilities dropped except `NET_BIND_SERVICE`, `SETUID`, `SETGID`

## Security Rules

- [x] All secrets stored in `secrets/` directory (gitignored) or Docker secret mounts — never in code
- [x] `.env` and `.env.*` in `.gitignore` (except `.env.example`)
- [x] `deploy/nginx/ssl/*.key` in `.gitignore`
- [x] SIP Digest authentication required for all non-OPTIONS untrusted requests
- [x] OCP endpoints require `requireAuth()` + `requireRole()`
- [x] Input validation on all OCP forms (PDO prepared statements, CSRF tokens)
- [x] HTTPS enforced in production (Let's Encrypt TLS on `tsiapp.io`)
- [x] Rate limiting enforced at multiple layers: `pike`, `ratelimit`, per-trunk CPS (`rl_check`)
- [x] Topology hiding ensures backend Asterisk IPs are never exposed externally
- [x] Audit logging to `auth_audit_log` for all authentication events and MI command attempts

---

## Revision History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 0.1.0 | 2026-05-26 | DocGuard Generate | Auto-generated skeleton |
| 0.2.0 | 2026-05-26 | Kimi AI | Filled all placeholders; added SIP Digest auth, OCP RBAC, secrets inventory, network boundaries, container hardening, security rules checklist |

---

## Standards Reference

> **Aligned with**: OWASP ASVS v4.0 + CWE Top 25
>
> **Sections covered**: Authentication, Authorization, Secrets Management, Network Security, Input Validation, Container Security
>
> **Reference**: OWASP Foundation, "Application Security Verification Standard v4.0." https://owasp.org/asvs | MITRE, "CWE Top 25." https://cwe.mitre.org/top25
>
> *Standards alignment inspired by RAG-grounded generation (Lopez et al., AITPG, IEEE TSE 2026).*
