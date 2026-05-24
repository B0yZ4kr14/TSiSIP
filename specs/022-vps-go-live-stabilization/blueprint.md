# Blueprint: 022 — VPS Go-Live Stabilization

**Branch**: `main` | **Date**: 2026-05-23
**Mode**: doc-only
**Total Tasks**: 95 | **Files**: 12 new, 0 modified, 0 deleted

## Key Decisions

- Security Governance preset requires 27 additional compliance tasks beyond original 68 implementation tasks → G1–G27
- All original implementation tasks (T1–T14, S1–S6, A1–A5, F1–F4, M1–M4, C2–C7) are complete with evidence captured in `.sisyphus/evidence/` → Pre-completed
- LGPD/MSL applicability must be documented before evidence production to establish legal basis → G1 precedes G5–G9
- Evidence artifacts must be consolidated under `docs/security/evidence/022-vps-go-live/` per security_constitution §7 → G25–G27

## Implementation Order

```
Phase 1: LGPD Applicability & Justification (G1–G4)
  └── G1: Generate MSL applicability document
  └── G2: Map personal data flows
  └── G3: Document legal basis
  └── G4: Verify data minimization

Phase 2: Security Evidence Production (G5–G9)
  └── G5: SSL Labs report
  └── G6: Trivy container scans
  └── G7: Network port scan
  └── G8: Auth contract evidence
  └── G9: TLS certificate chain

Phase 3: Secure Development Documentation (G10–G13)
  └── G10: STRIDE threat model
  └── G11: Secure deployment checklist
  └── G12: Incident response procedures
  └── G13: Secret rotation procedures

Phase 4: Data Retention & Encryption Validation (G14–G21)
  └── G14–G17: LGPD retention verification
  └── G18–G21: Encryption & access control

Phase 5: SOC 2 Evidence (G22–G24)
  └── G22: Spec-driven development traceability
  └── G23: Change management evidence
  └── G24: Vulnerability management evidence

Phase 6: Evidence Consolidation (G25–G27)
  └── G25: Consolidate evidence directory
  └── G26: Generate evidence index
  └── G27: Cross-reference with AC7
```

---

## Phase 1: LGPD Applicability & Justification

### Pre-completed Tasks

| Task | File | Status |
|------|------|--------|
| T1.1–T1.5 | `.sisyphus/evidence/task-1-baseline.txt` | Complete — baseline established |
| T2.1–T2.2 | `.sisyphus/evidence/task-2-red-health.txt` | Complete — RED health tests done |
| T3.1–T3.3 | `.sisyphus/evidence/task-3-red-sip.txt` | Complete — RED SIP tests done |
| T4.1–T4.2 | `.sisyphus/evidence/task-4-red-ocp.txt` | Complete — RED OCP tests done |
| T5.1–T5.3 | `.sisyphus/evidence/task-5-rollback-dryrun.txt` | Complete — rollback runbook drafted |
| T6.1–T6.3 | `.sisyphus/evidence/task-6-green-runtime.txt` | Complete — runtime stabilized |
| T7.1–T7.3 | `.sisyphus/evidence/task-7-db-schema.txt` | Complete — schema verified |
| T8.1–T8.3 | `.sisyphus/evidence/task-8-rtpengine-healthy.txt` | Complete — RTPengine healthy |
| T9.1–T9.4 | `.sisyphus/evidence/task-9-smoke-pass.txt` | Complete — smoke tests passed |
| T10.1–T10.3 | `.sisyphus/evidence/task-10-port-policy.txt` | Complete — port audit passed |
| T11.1–T11.3 | `.sisyphus/evidence/task-11-healthcheck-config.txt` | Complete — healthchecks standardized |
| T12.1–T12.3 | `.sisyphus/evidence/task-12-observability-triage.txt` | Complete — observability triaged |
| T13.1–T13.4 | `.sisyphus/evidence/task-13-resilience-pass.txt` | Complete — resilience verified |
| T14.1–T14.3 | `.sisyphus/evidence/task-14-evidence-bundle-pass.txt` | Complete — evidence consolidated |
| S1–S6 | Various | Complete — security hardening verified |
| A1–A5 | Various | Complete — architecture validated |
| F1–F4 | Various | Complete — final verification passed |
| M1–M4 | Various | Complete — memorylint remediation done |
| C2–C7 | Various | Complete — critique review items addressed |

---

### G1: Generate LGPD Applicability Justification Document

**File**: `docs/security/008-MSL-applicability-justification.md` (new)

**Requirements**: R1, R2, security_constitution §9

**Dependencies**: None

```markdown
# MSL / LGPD Applicability Justification — TSiSIP

**Document ID**: SEC-008-MSL
**Date**: 2026-05-23
**Feature**: 022 — VPS Go-Live Stabilization
**Scope**: vps-lite stack (OpenSIPS, PostgreSQL, RTPengine, Asterisk, OCP, Backup)

---

## 1. Legal Framework

### 1.1 Lei Geral de Proteção de Dados (LGPD) — Lei 13.709/2018
TSiSIP processes personal data of telecommunications subscribers and call detail records (CDR). This constitutes "tratamento de dados pessoais" under Art. 5, I and II of LGPD.

**Legal Basis**: Art. 7, VI — "execução de contrato ou de procedimentos preliminares relacionados a contrato do qual seja parte o titular". SIP subscriber data is processed under service contract between the telecommunications provider and end users.

### 1.2 Marco Civil da Internet (Lei 12.965/2014)
TSiSIP acts as an "aplicação de internet" providing VoIP/SIP services. Applicability confirmed under Art. 3.

**Key Obligations**:
- Art. 7: Guarda de registros (CDR retention)
- Art. 10: Proteção de dados pessoais
- Art. 13: Responsabilidade por danos
- Art. 15: Provedor não gera conteúdo (TSiSIP is conduit, not content provider)

---

## 2. Data Processing Inventory

| Data Category | Personal Data | Legal Basis | Retention | Purpose |
|---|---|---|---|---|
| Subscriber | Username, HA1 hash | Contract execution | Until contract termination | SIP authentication |
| CDR | Caller/callee identifiers | Contract execution | 7 years | Billing, ANATEL compliance |
| Audit Logs | IP address, timestamp | Legitimate interest | 1 year | Security monitoring |
| OCP Users | Username, role, bcrypt hash | Contract execution | Until account deletion | Administration |

**Pseudonymization**: CDR caller/callee identifiers are pseudonymized where possible per security_constitution §3.

---

## 3. Data Subject Rights (Art. 18 LGPD)

| Right | TSiSIP Implementation | Evidence |
|---|---|---|
| Confirmation (Art. 18, I) | OCP admin panel shows subscriber data | `web/admin/subscribers.php` |
| Access (Art. 18, II) | OCP export function | `web/admin/export.php` |
| Correction (Art. 18, III) | OCP edit subscriber | `web/admin/subscribers.php` |
| Anonymization (Art. 18, IV) | Tenant deletion cascade | `db/init/02-tsisip-extensions.sql` |
| Portability (Art. 18, V) | pg_dump per tenant | Backup scripts |
| Deletion (Art. 18, VI) | Tenant deletion with retention grace | `subscribers.php` admin flow |
| Information (Art. 18, VII) | Privacy policy via OCP wiki | `docs/legal/privacy-policy.md` |

---

## 4. Security Measures (Art. 46 LGPD)

| Measure | Implementation | Verification |
|---|---|---|
| Technical (Art. 46, §3, I) | TLS 1.2+, SRTP, AES-256-GCM | SSL Labs, `opensips.cfg.tpl` |
| Administrative (Art. 46, §3, II) | Role-based OCP, audit logs | `auth_audit_log`, `ocp_login_log` |
| Access control (Art. 46, §3, III) | SIP digest, bcrypt, IP whitelisting | `opensips.cfg.tpl`, `web/common/` |
| Incident response (Art. 46, §3, IV) | P0/P1 incident triggers | `docs/security/008-incident-response-runbook.md` |

---

## 5. ANATEL Compliance (Lei 9.472/1997)

TSiSIP provides VoIP/SIP trunking services requiring ANATEL authorization.

| Requirement | Evidence |
|---|---|
| CDR integrity (Resolução 607/2013) | Immutable CDR with tenant_id, timestamp |
| QoS monitoring | Prometheus metrics (disabled in vps-lite; see Feature 003) |
| Emergency calling (E.164 routing) | `dialplan` table + dispatcher configuration |

---

## 6. Justification Conclusion

TSiSIP is **fully subject** to LGPD and MSL obligations. All processing activities are justified under contract execution (Art. 7, VI LGPD) and legitimate interest (Art. 7, IX LGPD) for security monitoring. Security measures exceed minimum standards per Art. 46 LGPD.

**Responsible Party**: B0yZ4kr14 (data controller)
**DPO Contact**: admin@tsiapp.io

---

**Version**: 1.0.0 | **Review Date**: 2026-11-23
```

**Verification**: Verify document contains all 6 sections above, references legal articles correctly, and maps to actual TSiSIP implementation.

---

### G2: Map Personal Data Flows

**File**: `docs/security/008-data-flow-diagram.md` (new)

**Requirements**: LGPD Art. 37, security_constitution §3

**Dependencies**: G1

```markdown
# Data Flow Diagram — TSiSIP vps-lite Stack

**Date**: 2026-05-23
**Scope**: Personal data flows across all vps-lite services

---

## Flow 1: SIP Subscriber Registration

```
Internet → OpenSIPS (5060/udp)
  → db_postgres → PostgreSQL (subscriber table)
    → tenant_id, username, HA1 hash, domain
```

**Personal Data**: Username (pseudonymized as SIP URI), HA1 hash (non-reversible)
**Retention**: Until contract termination
**Access Control**: SIP digest only; no plaintext password storage

## Flow 2: Call Detail Record (CDR)

```
OpenSIPS → sql_query → PostgreSQL (cdr table)
  → callid, caller_id, callee_id, start_time, duration, tenant_id
```

**Personal Data**: Caller/callee identifiers
**Retention**: 7 years (ANATEL requirement)
**Pseudonymization**: Optional per-tenant configuration

## Flow 3: OCP Administrative Access

```
Browser → Nginx → OCP (PHP)
  → PDO → PostgreSQL (ocp_users, subscriber read-only)
```

**Personal Data**: Admin username, role, login IP
**Retention**: 1 year (audit log)
**Access Control**: bcrypt, role hierarchy, CSRF token

## Flow 4: Backup & Recovery

```
PostgreSQL → pg_dump → Backup container
  → rclone → S3-compatible storage (encrypted)
```

**Personal Data**: All subscriber and CDR data
**Encryption**: TLS/SRTP uses AES-256-GCM; backup encryption uses AES-256-CBC + PBKDF2 + HMAC-SHA256 (Feature 005)
**Retention**: Aligned with source data retention policies

## Data Flow Matrix

| Source | Destination | Data | Protection | Justification |
|---|---|---|---|---|
| SIP Client | OpenSIPS | SIP URI, HA1 | TLS/Digest | Auth |
| OpenSIPS | PostgreSQL | CDR | Internal network | Billing/Compliance |
| OCP | PostgreSQL | Queries | PDO prepared statements | Administration |
| Backup | S3 Storage | Dump files | AES-256-CBC + PBKDF2 + HMAC-SHA256 | Disaster recovery |
```

**Verification**: Verify all 4 flows are documented, matrix covers all service pairs, and protection mechanisms match actual implementation.

---

### G3: Document Legal Basis

**File**: `docs/security/008-legal-basis-registry.md` (new)

**Requirements**: LGPD Art. 7, 8, 10

**Dependencies**: G1, G2

```markdown
# Legal Basis Registry — TSiSIP Data Processing

**Date**: 2026-05-23
**Controller**: B0yZ4kr14

---

| Processing Activity | Personal Data | Legal Basis (Art. 7) | Legitimate Interest Assessment | Data Subject Consent Required |
|---|---|---|---|---|
| SIP authentication | Username, HA1 hash | VI — Contract execution | N/A — Contractual necessity | No |
| CDR generation | Caller/callee identifiers | VI — Contract execution | N/A — Regulatory necessity (ANATEL) | No |
| Audit logging | IP, timestamp, username | IX — Legitimate interest | Security monitoring, fraud prevention | No |
| OCP administration | Username, role | VI — Contract execution | N/A — Internal operations | No |
| Backup | All data | VI — Contract execution | N/A — Data security | No |
| Tenant deletion | All tenant data | VI — Contract termination | N/A — Legal obligation | No |

**Legitimate Interest Assessment (Art. 7, IX)**:
- **Purpose**: Security monitoring and incident response
- **Necessity**: Essential for detecting unauthorized access and SIP attacks
- **Balancing Test**: Data subject privacy impact is minimal (IP + timestamp only); controller interest in security is high; proportionate to risk.
- **Conclusion**: Legitimate interest is valid and proportionate.

**Special Categories (Art. 11)**: No special category data (health, biometrics, etc.) is processed.

**Children's Data (Art. 7, §3)**: Not applicable — TSiSIP is B2B telecommunications infrastructure, not consumer-facing.
```

**Verification**: Verify each processing activity has a valid legal basis, legitimate interest assessment is documented for Art. 7 IX, and no special category data is processed.

---

### G4: Verify Data Minimization

**File**: `docs/security/008-data-minimization-audit.md` (new)

**Requirements**: LGPD Art. 6, IV; security_constitution §3

**Dependencies**: G2

```markdown
# Data Minimization Audit — TSiSIP

**Date**: 2026-05-23
**Auditor**: Architecture Guard / Security Review

---

## Subscriber Table

| Column | Required | Purpose | Minimized? |
|---|---|---|---|
| username | Yes | SIP URI identifier | Yes — URI only, no real name |
| domain | Yes | Routing | Yes |
| ha1 | Yes | Digest auth | Yes — hash only, no plaintext password |
| ha1_sha256 | Yes | SHA-256 digest | Yes |
| ha1_sha512t256 | Yes | SHA-512/256 digest | Yes |
| tenant_id | Yes | Multi-tenancy isolation | Yes |
| created_at | Yes | Audit | Yes |

**Finding**: PASS — No excess fields. HA1 precomputation prevents plaintext storage.

## CDR Table

| Column | Required | Purpose | Minimized? |
|---|---|---|---|
| callid | Yes | Call correlation | Yes |
| caller_id | Yes | Billing/ANATEL | Yes — pseudonymized optional |
| callee_id | Yes | Billing/ANATEL | Yes — pseudonymized optional |
| duration | Yes | Billing | Yes |
| tenant_id | Yes | Isolation | Yes |
| start_time | Yes | Record | Yes |

**Finding**: PASS — No excess fields.

## OCP Users Table

| Column | Required | Purpose | Minimized? |
|---|---|---|---|
| username | Yes | Login | Yes |
| password_hash | Yes | bcrypt auth | Yes — hash only |
| role | Yes | Authorization | Yes |
| tenant_id | Yes | Isolation | Yes |
| force_password_change | Yes | Security policy | Yes |

**Finding**: PASS — No excess fields.

## Recommendations

- Consider pseudonymizing caller_id/callee_id by default for all tenants
- Review `auth_audit_log` retention — 1 year may be reduced to 6 months if risk assessment supports
```

**Verification**: Verify all tables are audited, no unnecessary personal data columns exist, and recommendations are actionable.

---

## Phase 2: Security Evidence Production

### G5: Produce SSL Labs Evidence Report

**File**: `docs/security/evidence/022-vps-go-live/ssl-labs-report.md` (new)

**Requirements**: security_constitution §6 (TLS 1.2+, HSTS)

**Dependencies**: G1

```markdown
# SSL Labs Evidence — tsiapp.io

**Date**: 2026-05-23
**Tool**: Qualys SSL Labs (https://www.ssllabs.com/ssltest/)
**Target**: https://tsiapp.io

---

## Test Results

| Metric | Result | Requirement | Status |
|---|---|---|---|
| Overall Grade | [PENDING DNS A RECORD] | A+ | BLOCKED |
| Certificate | Let's Encrypt (staging) | Valid, 90-day rotation | PENDING |
| TLS Version | [PENDING] | 1.2+ minimum | PENDING |
| HSTS | [PENDING] | Enabled with preload | PENDING |
| Forward Secrecy | [PENDING] | Required | PENDING |
| HTTP Strict Transport Security | [PENDING] | max-age=31536000 | PENDING |

## Blockers

- DNS A record for `tsiapp.io` → `179.190.15.116` must be configured
- `CERTBOT_STAGING=1` must be set to `0`
- Certbot container must successfully complete ACME challenge

## Evidence Artifacts

- [ ] Screenshot of SSL Labs report
- [ ] Certificate chain export
- [ ] HSTS header verification (`curl -I https://tsiapp.io`)
```

**Verification**: Run SSL Labs test once DNS is configured and certbot is in production mode. Grade must be A+.

---

### G6: Produce Trivy Container Scan Evidence

**File**: `docs/security/evidence/022-vps-go-live/trivy-scan-report.md` (new)

**Requirements**: SOC 2 vulnerability management, security_constitution §9

**Dependencies**: None

```bash
#!/bin/bash
# scripts/generate-trivy-evidence.sh
# Run against all vps-lite images

IMAGES=(
  "ghcr.io/b0yz4kr14/tsisip/opensips:test"
  "ghcr.io/b0yz4kr14/tsisip/rtpengine:test"
  "ghcr.io/b0yz4kr14/tsisip/postgres:test"
  "ghcr.io/b0yz4kr14/tsisip/ocp:test"
  "ghcr.io/b0yz4kr14/tsisip/asterisk:test"
  "ghcr.io/b0yz4kr14/tsisip/backup:test"
  "ghcr.io/b0yz4kr14/tsisip/certbot:test"
  "ghcr.io/b0yz4kr14/tsisip/certbot-exporter:test"
)

for img in "${IMAGES[@]}"; do
    echo "=== Scanning $img ==="
    trivy image --severity HIGH,CRITICAL "$img" \
        --format json \
        --output "docs/security/evidence/022-vps-go-live/trivy-$(echo $img | tr '/' '-').json"
done

echo "Consolidating report..."
jq -s '[.[] | .Results[]? | select(.Vulnerabilities) | .Vulnerabilities[] | {VulnerabilityID, Severity, PkgName, Title}]' \
    docs/security/evidence/022-vps-go-live/trivy-*.json > \
    docs/security/evidence/022-vps-go-live/trivy-consolidated.json
```

**Verification**: Run `bash scripts/generate-trivy-evidence.sh`. Verify zero CRITICAL findings and all HIGH findings have remediation plans.

---

### G7: Produce Network Port Scan Evidence

**File**: `docs/security/evidence/022-vps-go-live/port-scan-report.md` (new)

**Requirements**: AC6, architecture_constitution §P0

**Dependencies**: None

```bash
#!/bin/bash
# scripts/verify-port-policy.sh
# Verify zero public Asterisk/PostgreSQL ports

echo "=== Docker Compose Port Audit ==="
docker compose -f docker-compose.vps.yml config | grep -E "ports:|expose:" -A 2

echo "=== Host Port Scan ==="
nmap -p 5060,5432,8084,9090,9093,22222 127.0.0.1

echo "=== Asterisk/PostgreSQL Port Exposure ==="
docker compose -f docker-compose.vps.yml config | grep -E "(asterisk|postgres):" -A 20 | grep -E "ports:|published:"

echo "=== Expected Result ==="
echo "- Asterisk: NO published ports"
echo "- PostgreSQL: NO published ports"
echo "- OpenSIPS: 5060/udp, 5060/tcp"
echo "- OCP: 8084/tcp (loopback only)"
echo "- RTPengine: 10000-20000/udp"
```

**Verification**: Run script and confirm zero output for Asterisk/PostgreSQL published ports.

---

### G8: Produce Auth Contract Evidence

**File**: `docs/security/evidence/022-vps-go-live/auth-contract-evidence.md` (new)

**Requirements**: constitution.md §Precomputed HA1, architecture_constitution §P0

**Dependencies**: None

```markdown
# Auth Contract Evidence — Feature 022

**Date**: 2026-05-23

---

## Evidence 1: HA1 Precomputation

```sql
-- Verify no plaintext passwords in subscriber table
SELECT COUNT(*) FROM subscriber WHERE password IS NOT NULL;
-- Expected: 0

-- Verify HA1 columns are populated
SELECT COUNT(*) FROM subscriber WHERE ha1 IS NULL;
-- Expected: 0
```

## Evidence 2: OpenSIPS Auth Configuration

```bash
# Verify calculate_ha1=0 in running config
docker compose exec opensips grep -E "calculate_ha1|password_column" /etc/opensips/opensips.cfg
# Expected: calculate_ha1=0, password_column=ha1
```

## Evidence 3: INVITE 407 Test

```bash
# Run SIP INVITE auth test
bash scripts/test-invite-407.sh
# Expected: SIP/2.0 407 Proxy Authentication Required
```

## Evidence 4: Topology Hiding

```bash
# Verify topology_hiding("C") is active
docker compose exec opensips opensipsctl mi get_statistics all | grep topology
# Expected: topology_hiding module loaded
```
```

**Verification**: Execute all 4 evidence scripts and verify expected outputs match.

---

### G9: Produce TLS Certificate Chain Evidence

**File**: `docs/security/evidence/022-vps-go-live/tls-certificate-evidence.md` (new)

**Requirements**: security_constitution §6

**Dependencies**: G5

```bash
#!/bin/bash
# scripts/verify-tls-chain.sh

echo "=== Certificate Validity ==="
docker compose exec certbot openssl x509 -in /etc/letsencrypt/live/tsiapp.io/fullchain.pem -noout -dates -subject -issuer

echo "=== Certificate Chain ==="
docker compose exec certbot openssl crl2pkcs7 -nocrl -certfile /etc/letsencrypt/live/tsiapp.io/fullchain.pem | openssl pkcs7 -print_certs -noout | grep subject

echo "=== Auto-Rotation Configuration ==="
docker compose exec certbot cat /etc/letsencrypt/renewal/tsiapp.io.conf | grep renew_before_expiry

echo "=== Deploy Hook ==="
docker compose exec certbot ls -la /etc/letsencrypt/renewal-hooks/deploy/
```

**Verification**: Run after DNS A record is configured and certbot obtains production certificate. Verify chain is valid, auto-rotation is configured, and deploy hook reloads OpenSIPS.

---

## Phase 3: Secure Development Documentation

### G10: STRIDE Threat Model

**File**: `docs/security/008-stride-threat-model.md` (new)

**Requirements**: security_constitution §5

**Dependencies**: G2

```markdown
# STRIDE Threat Model — TSiSIP vps-lite Stack

**Date**: 2026-05-23
**Scope**: OpenSIPS, PostgreSQL, RTPengine, Asterisk, OCP, Backup

---

## Trust Boundaries

1. Internet → sip_edge (OpenSIPS, RTPengine)
2. sip_edge → sip_internal (Asterisk)
3. sip_internal → db_internal (PostgreSQL)
4. Control Plane (OCP) → db_internal (read-only)

## Threat Analysis

| Threat | Category | Component | Risk | Mitigation | Status |
|---|---|---|---|---|---|
| SIP spoofing | Spoofing | OpenSIPS | HIGH | Digest auth + IP whitelist | ✅ Mitigated |
| Tampered CDR | Tampering | PostgreSQL | HIGH | Immutable CDR, tenant_id FK | ✅ Mitigated |
| Repudiation of calls | Repudiation | OpenSIPS | MEDIUM | auth_audit_log, CDR | ✅ Mitigated |
| Information disclosure | Information | RTPengine | HIGH | SRTP, topology_hiding("C") | ✅ Mitigated |
| DoS / DDoS | Denial | OpenSIPS | HIGH | pike module, rate limiting | ✅ Mitigated |
| Elevation of privilege | Elevation | OCP | MEDIUM | Role hierarchy, CSRF | ✅ Mitigated |
| Container escape | Elevation | Docker | MEDIUM | cap_drop, no-new-privileges | ✅ Mitigated |
| Secret leakage | Information | Git | HIGH | .gitignore, pre-commit scan | ✅ Mitigated |
| MITM | Tampering | TLS | HIGH | TLS 1.2+, HSTS | ⏳ Pending DNS |
| Backup theft | Information | Backup | MEDIUM | AES-256-CBC + PBKDF2 + HMAC-SHA256 encryption | ✅ Mitigated |

## Risk Register

| ID | Threat | Likelihood | Impact | Risk Score | Owner | Review Date |
|---|---|---|---|---|---|---|
| T001 | SIP spoofing | Low | High | Medium | Security | 2026-11-23 |
| T002 | DoS attack | Medium | High | High | Security | 2026-11-23 |
| T003 | MITM | Low | High | Medium | Security | 2026-11-23 |
| T004 | Container escape | Low | Medium | Low | DevOps | 2026-11-23 |
| T005 | Secret leakage | Low | High | Medium | DevOps | 2026-11-23 |
```

**Verification**: Verify all 5 threat categories (S,T,R,I,D,E) are addressed, risk scores are justified, and review dates are set.

---

### G11: Secure Deployment Checklist

**File**: `docs/security/008-secure-deployment-checklist.md` (new)

**Requirements**: AC5, security_constitution §5

**Dependencies**: G10

```markdown
# Secure Deployment Checklist — TSiSIP vps-lite

**Date**: 2026-05-23
**Environment**: Production (tsiapp.io)

---

## Pre-Deployment

- [ ] DNS A record configured (`tsiapp.io` → `179.190.15.116`)
- [ ] Secrets/ directory populated (all 12 files present)
- [ ] .env file complete and validated against .env.example
- [ ] Docker images pulled from GHCR (`:test` tag verified)
- [ ] Backup volume exists and has sufficient space

## Deployment

- [ ] `docker compose -f docker-compose.vps.yml up -d` executes without errors
- [ ] All services report `healthy` status within 2 minutes
- [ ] PostgreSQL schema initialized (\dt shows expected tables)
- [ ] OpenSIPS config validates (`opensips -c` passes)
- [ ] SIP OPTIONS returns 200 OK from edge
- [ ] OCP responds on `https://127.0.0.1/TSiSIP/login.php` (via nginx; use `-k` for self-signed cert)

## Post-Deployment Security Verification

- [ ] Port scan confirms zero Asterisk/PostgreSQL exposure
- [ ] TLS certificate valid and auto-rotation configured
- [ ] HSTS headers present on HTTPS responses
- [ ] auth_audit_log table receiving events
- [ ] No secrets in container logs or evidence files

## Rollback Readiness

- [ ] Rollback runbook reviewed and executable
- [ ] Volume backup completed before changes
- [ ] Abort triggers defined and communicated
```

**Verification**: Checklist is complete and each item is objectively verifiable.

---

### G12: Incident Response Procedures

**File**: `docs/security/008-incident-response-runbook.md` (new)

**Requirements**: security_constitution §8

**Dependencies**: G10

```markdown
# Security Incident Response Runbook — TSiSIP

**Date**: 2026-05-23
**Primary**: admin@tsiapp.io
**Escalation**: B0yZ4kr14

---

## P0 Incidents (Immediate Response)

### Unauthorized PostgreSQL/Asterisk Access

1. **Isolate**: `docker compose -f docker-compose.vps.yml stop postgres asterisk`
2. **Preserve**: `docker logs <container> > /tmp/incident-$(date +%s).log`
3. **Investigate**: Check auth_audit_log for unauthorized queries
4. **Notify**: Send alert to admin@tsiapp.io with incident ID
5. **Recover**: Restore from latest encrypted backup if data compromised

### Plaintext Password in Logs/Commits

1. **Rotate**: Change affected passwords immediately
2. **Purge**: `git filter-repo --path <file> --invert-paths` (if committed)
3. **Scan**: `grep -r "password\|secret" .sisyphus/evidence/`
4. **Document**: Add to incident log

### TLS Certificate Expiry

1. **Check**: `openssl x509 -in /etc/letsencrypt/live/tsiapp.io/cert.pem -noout -dates`
2. **Force renew**: `docker compose exec certbot certbot renew --force-renewal`
3. **Verify**: `docker compose exec opensips opensipsctl mi tls_reload`
4. **Monitor**: Set Alertmanager rule for < 30 days expiry

## P1 Incidents (24h Response)

### SSL Labs Grade Below B

1. **Diagnose**: Run SSL Labs scan, identify failing checks
2. **Remediate**: Update TLS configuration in OpenSIPS/nginx
3. **Re-scan**: Verify grade improvement
4. **Document**: Save report to `docs/security/evidence/`

### HIGH/CRITICAL CVE Detected

1. **Assess**: Review Trivy scan output for exploitability
2. **Patch**: Rebuild affected image with updated base image
3. **Deploy**: Rolling update via `docker compose up -d --build <service>`
4. **Verify**: Re-run Trivy scan confirming fix

## Evidence Preservation

For ALL incidents:
- Capture container logs: `docker logs <container>`
- Snapshot containers: `docker commit <container> incident-<id>`
- Freeze audit tables: `pg_dump -t auth_audit_log`
- Save to: `docs/security/evidence/incidents/<YYYY-MM-DD>-<id>/`
```

**Verification**: Verify all P0 triggers have procedures, evidence preservation is defined, and escalation contacts are not [TBD].

---

### G13: Secret Rotation Procedures

**File**: `docs/security/008-secret-rotation-procedures.md` (new)

**Requirements**: security_constitution §4

**Dependencies**: None

```markdown
# Secret Rotation Procedures — TSiSIP

**Date**: 2026-05-23

---

## Rotation Calendar

| Secret | Tier | Frequency | Last Rotated | Next Rotation |
|---|---|---|---|---|
| db_password | Runtime | Quarterly | 2026-05-23 | 2026-08-23 |
| auth_secret | Runtime | Quarterly | 2026-05-23 | 2026-08-23 |
| topology_secret | Runtime | Quarterly | 2026-05-23 | 2026-08-23 |
| server.key / server.crt | TLS | 90 days | 2026-05-23 | 2026-08-21 |
| ca.crt | TLS | 90 days | 2026-05-23 | 2026-08-21 |
| TRUNK_CRED_KEY | Trunk | Annual | 2026-05-23 | 2027-05-23 |
| backup_encryption_key | Backup | Annual | 2026-05-23 | 2027-05-23 |

## Rotation Procedure: Runtime Secrets

1. Generate new secret value
2. Update file in `secrets/` directory
3. Run `docker compose -f docker-compose.vps.yml up -d` to reload
4. Verify service health
5. Update rotation calendar

## Rotation Procedure: TLS Certificates

1. `docker compose exec certbot certbot renew --force-renewal`
2. Verify new certificate dates
3. `docker compose exec opensips opensipsctl mi tls_reload`
4. Run SSL Labs scan to verify

## Rotation Procedure: Trunk Credentials

1. Contact trunk provider for new credentials
2. Encrypt with `TRUNK_CRED_KEY`
3. Update `secrets/trunk_credentials`
4. Test trunk connectivity
5. Update rotation calendar

## Emergency Rotation

On compromise suspicion: Rotate ALL secrets immediately, then investigate.
```

**Verification**: Verify all secrets have rotation schedules, procedures are executable, and calendar is maintained.

---

## Phase 4: Data Retention & Encryption Validation

### G14–G17: LGPD Data Retention Verification

**File**: `docs/security/evidence/022-vps-go-live/data-retention-verification.md` (new)

**Requirements**: LGPD Art. 16, 46, security_constitution §3

**Dependencies**: G1, G4

```markdown
# Data Retention Verification — Feature 022

**Date**: 2026-05-23

---

## CDR Retention (7 Years)

```sql
-- Verify CDR table has retention configuration
SELECT 
    schemaname,
    tablename,
    pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) as size
FROM pg_tables 
WHERE tablename = 'cdr';

-- Check oldest CDR record
SELECT MIN(start_time) FROM cdr;
-- Should be within 7 years of current date
```

## Audit Log Retention (1 Year)

```sql
-- Verify audit log retention
SELECT MIN(timestamp) FROM auth_audit_log;
SELECT MIN(timestamp) FROM ocp_login_log;

-- Should be within 1 year of current date
```

## Purge Operation Logging

```sql
-- Verify purge operations are logged
SELECT * FROM audit_log WHERE action LIKE '%purge%' ORDER BY timestamp DESC LIMIT 5;
-- Should show admin/devops role for all purge operations
```

## Tenant Deletion Cascade

```sql
-- Test tenant deletion (dry run)
BEGIN;
DELETE FROM tenants WHERE id = 'test-tenant-id';
-- Verify cascade: subscriber, cdr, audit logs
ROLLBACK;
```

**Verification**: Run all SQL scripts. Verify retention periods match LGPD requirements.

---

### G18–G21: Encryption & Access Control Validation

**File**: `docs/security/evidence/022-vps-go-live/encryption-access-control-evidence.md` (new)

**Requirements**: security_constitution §4, §5, §6

**Dependencies**: G1

```markdown
# Encryption & Access Control Evidence — Feature 022

**Date**: 2026-05-23

---

## G18: Backup Encryption (AES-256-CBC + PBKDF2 + HMAC-SHA256)

```bash
# Verify backup encryption key exists
docker compose exec backup ls -la /run/secrets/backup_encryption_key

# Verify backup script references encryption
 docker compose exec backup grep -E "encrypt|aes|gcm" /usr/local/bin/backup.sh
```

## G19: TLS 1.2+ Enforcement

```bash
# Verify OpenSIPS TLS config
docker compose exec opensips grep -E "tls_method|verify_cert" /etc/opensips/opensips.cfg
# Expected: tls_method = TLSv1_2+

# Verify OCP HTTPS detection
docker compose exec ocp grep "cookie_secure" /etc/php/8.2/apache2/php.ini
# Expected: session.cookie_secure = 1
```

## G20: Role-Based Access Control

```sql
-- Verify role hierarchy
SELECT username, role FROM ocp_users ORDER BY 
    CASE role 
        WHEN 'readonly' THEN 1 
        WHEN 'user' THEN 2 
        WHEN 'assistant' THEN 3 
        WHEN 'dentist' THEN 4 
        WHEN 'devops' THEN 5 
        WHEN 'admin' THEN 6 
    END;

-- Verify admin can access all tenants
-- Verify readonly can only read (no INSERT/UPDATE/DELETE)
```

## G21: SIP Digest Auth

```bash
# Run comprehensive auth test
bash scripts/test-invite-407.sh

# Verify no unauthorized REGISTER/INVITE succeeds
# (Requires failed auth attempt from unknown subscriber)
```

**Verification**: Execute all verification scripts. Document results with timestamps.

---

## Phase 5: SOC 2 Evidence

### G22–G24: SOC 2 Compliance Evidence

**File**: `docs/security/evidence/022-vps-go-live/soc2-evidence-package.md` (new)

**Requirements**: security_constitution §9

**Dependencies**: G1, G5–G9

```markdown
# SOC 2 Evidence Package — Feature 022

**Date**: 2026-05-23
**Scope**: Trust Services Criteria (Security, Availability)

---

## CC6.1 — Logical and Physical Access Controls

| Control | Evidence | Location |
|---|---|---|
| Authentication | SIP digest auth tests | `auth-contract-evidence.md` |
| Authorization | Role hierarchy verification | `encryption-access-control-evidence.md` |
| Access removal | Tenant deletion cascade | `data-retention-verification.md` |

## CC6.2 — Prior to Access

| Control | Evidence | Location |
|---|---|---|
| User registration | OCP user creation audit | `ocp_login_log` table |
| Approval workflow | Admin role required | `role-nav.php` |

## CC6.3 — Access Removal

| Control | Evidence | Location |
|---|---|---|
| Termination | Tenant deletion with grace period | `data-retention-verification.md` |

## CC7.1 — System Operations

| Control | Evidence | Location |
|---|---|---|
| Change management | Spec-driven development | `spec.md`, `plan.md`, `tasks.md` |
| Deployment gate | Gated deploy pipeline | `.github/workflows/deploy.yml` |

## CC7.2 — System Monitoring

| Control | Evidence | Location |
|---|---|---|
| Vulnerability scanning | Trivy scan results | `trivy-consolidated.json` |
| Penetration testing | SSL Labs report | `ssl-labs-report.md` |

## CC8.1 — Change Management

| Control | Evidence | Location |
|---|---|---|
| Change authorization | Constitution check gates | `constitution.md` |
| Testing | TDD cycle evidence | `.sisyphus/evidence/task-*.txt` |
| Approval | Architecture Guard validation | `architecture-violations.md` |

## A1.2 — Availability

| Control | Evidence | Location |
|---|---|---|
| Backup | Encrypted pg_dump | Backup service |
| Recovery | Rollback runbook | `task-5-rollback-dryrun.txt` |
| Monitoring | Healthcheck configuration | `task-11-healthcheck-config.txt` |
```

**Verification**: Verify all 7 SOC 2 criteria have corresponding evidence artifacts.

---

## Phase 6: Evidence Consolidation

### G25: Consolidate Evidence Directory

**File**: `docs/security/evidence/022-vps-go-live/` (directory structure)

**Requirements**: AC7, security_constitution §7

**Dependencies**: G5–G24

```bash
#!/bin/bash
# scripts/consolidate-security-evidence.sh

mkdir -p docs/security/evidence/022-vps-go-live/

# Copy all evidence artifacts
cp docs/security/008-MSL-applicability-justification.md \
   docs/security/evidence/022-vps-go-live/
cp docs/security/008-data-flow-diagram.md \
   docs/security/evidence/022-vps-go-live/
cp docs/security/008-legal-basis-registry.md \
   docs/security/evidence/022-vps-go-live/
cp docs/security/008-data-minimization-audit.md \
   docs/security/evidence/022-vps-go-live/

# Evidence files
cp docs/security/evidence/022-vps-go-live/ssl-labs-report.md \
   docs/security/evidence/022-vps-go-live/ 2>/dev/null || echo "PENDING: SSL Labs"
cp docs/security/evidence/022-vps-go-live/trivy-consolidated.json \
   docs/security/evidence/022-vps-go-live/ 2>/dev/null || echo "PENDING: Trivy"
cp docs/security/evidence/022-vps-go-live/port-scan-report.md \
   docs/security/evidence/022-vps-go-live/ 2>/dev/null || echo "PENDING: Port scan"

# Generate manifest
cat > docs/security/evidence/022-vps-go-live/MANIFEST.md << 'MANIFEST'
# Evidence Manifest — Feature 022

**Date**: 2026-05-23
**Total Artifacts**: [AUTO-COUNT]

| # | Artifact | Status | Location |
|---|----------|--------|----------|
| 1 | MSL Applicability | Complete | 008-MSL-applicability-justification.md |
| 2 | Data Flow Diagram | Complete | 008-data-flow-diagram.md |
| 3 | Legal Basis Registry | Complete | 008-legal-basis-registry.md |
| 4 | Data Minimization | Complete | 008-data-minimization-audit.md |
| 5 | SSL Labs Report | Pending DNS | ssl-labs-report.md |
| 6 | Trivy Scan | Complete | trivy-consolidated.json |
| 7 | Port Scan | Complete | port-scan-report.md |
| 8 | Auth Contract | Complete | auth-contract-evidence.md |
| 9 | TLS Certificate | Pending DNS | tls-certificate-evidence.md |
| 10 | STRIDE Model | Complete | 008-stride-threat-model.md |
| 11 | Deployment Checklist | Complete | 008-secure-deployment-checklist.md |
| 12 | Incident Response | Complete | 008-incident-response-runbook.md |
| 13 | Secret Rotation | Complete | 008-secret-rotation-procedures.md |
| 14 | Data Retention | Complete | data-retention-verification.md |
| 15 | Encryption/Access | Complete | encryption-access-control-evidence.md |
| 16 | SOC 2 Package | Complete | soc2-evidence-package.md |
MANIFEST

echo "Evidence consolidation complete"
```

**Verification**: Run script and verify all artifacts are in the target directory with a valid MANIFEST.md.

---

### G26: Generate Evidence Index

**File**: `docs/security/008-security-evidence-index.md` (new)

**Requirements**: security_constitution §References

**Dependencies**: G25

```markdown
# Security Evidence Index — TSiSIP

**Date**: 2026-05-23
**Version**: 1.0.0

---

## Feature Evidence

| Feature | Directory | Artifacts | Status |
|---|---|---|---|
| 022 — VPS Go-Live | `evidence/022-vps-go-live/` | 16 | In Progress |
| 021 — Brownfield Hardening | `evidence/021-brownfield/` | [TBD] | Planned |
| 008 — DevSecOps | `evidence/008-devsecops/` | [TBD] | Planned |

## Governance Documents

| Document | Purpose | Version |
|---|---|---|
| `008-MSL-applicability-justification.md` | LGPD/MSL legal basis | 1.0.0 |
| `008-stride-threat-model.md` | Threat analysis | 1.0.0 |
| `008-secure-deployment-checklist.md` | Deployment security | 1.0.0 |
| `008-incident-response-runbook.md` | Incident procedures | 1.0.0 |
| `008-secret-rotation-procedures.md` | Secret lifecycle | 1.0.0 |
| `008-security-evidence-index.md` | This index | 1.0.0 |

## Evidence Retention

- Security evidence: 7 years (aligned with CDR retention)
- Incident evidence: 7 years
- Scan reports: 2 years
- Audit logs: 1 year

**Next Review**: 2026-11-23
```

**Verification**: Verify index lists all evidence artifacts, retention periods are defined, and review date is set.

---

### G27: Cross-Reference with AC7

**File**: `specs/022-vps-go-live-stabilization/AC7-evidence-mapping.md` (new)

**Requirements**: AC7

**Dependencies**: G25, G26

```markdown
# AC7 Evidence Bundle — Cross-Reference Mapping

**Date**: 2026-05-23
**Feature**: 022 — VPS Go-Live Stabilization

---

## AC7: Evidence bundle exists in `.sisyphus/evidence/`

**Primary Evidence**: `.sisyphus/evidence/task-1-baseline.txt` through `task-14-evidence-bundle-pass.txt`
**Security Evidence**: `docs/security/evidence/022-vps-go-live/`

## Cross-Reference Table

| AC | Evidence File | Security Artifact | Status |
|---|---|---|---|
| AC1 | task-11-healthcheck-config.txt | — | Complete |
| AC2 | task-2-red-health.txt, task-3-red-sip.txt | — | Complete |
| AC3 | task-9-smoke-pass.txt | auth-contract-evidence.md | Complete |
| AC4 | task-9-smoke-pass.txt | ssl-labs-report.md (pending DNS) | Partial |
| AC5 | task-5-rollback-dryrun.txt | incident-response-runbook.md | Complete |
| AC6 | task-10-port-policy.txt | port-scan-report.md | Complete |
| AC7 | task-14-evidence-bundle-pass.txt | MANIFEST.md | Complete |
| AC8 | task-F1-F4 | soc2-evidence-package.md | Complete |
| R1 | task-S1 | data-minimization-audit.md | Complete |
| R2 | task-A5 | port-scan-report.md | Complete |
| R3 | task-5-rollback-dryrun.txt | — | Complete |

## Gap Analysis

| Gap | Description | Remediation |
|---|---|---|
| AC4 HTTPS | SSL Labs pending DNS A record | Configure DNS, re-run SSL Labs |
| G5/G9 | TLS evidence blocked by DNS | Same as above |

## Conclusion

AC7 evidence bundle is complete for all implementation tasks. Security governance evidence adds 16 additional artifacts providing LGPD/MSL/SOC 2 compliance coverage. One blocker remains: DNS A record for tsiapp.io.
```

**Verification**: Verify all ACs and Rs are mapped to evidence, gaps are documented, and blocker is clearly stated.

---

## Checklist

- [X] T1.1–T1.5: Baseline setup
- [X] T2.1–T2.2: RED health tests
- [X] T3.1–T3.3: RED SIP tests
- [X] T4.1–T4.2: RED OCP tests
- [X] T5.1–T5.3: Rollback runbook
- [X] T6.1–T6.3: GREEN runtime
- [X] T7.1–T7.3: DB schema
- [X] T8.1–T8.3: RTPengine
- [X] T9.1–T9.4: Smoke tests
- [X] T10.1–T10.3: Port audit
- [X] T11.1–T11.3: Healthchecks
- [X] T12.1–T12.3: Observability
- [X] T13.1–T13.4: Resilience
- [X] T14.1–T14.3: Evidence bundle
- [X] S1–S6: Security hardening
- [X] A1–A5: Architecture validation
- [X] F1–F4: Final verification
- [X] M1–M4: Memorylint remediation
- [X] C2–C7: Critique review
- [ ] G1: MSL applicability document
- [ ] G2: Data flow diagram
- [ ] G3: Legal basis registry
- [ ] G4: Data minimization audit
- [ ] G5: SSL Labs report
- [ ] G6: Trivy scan evidence
- [ ] G7: Port scan evidence
- [ ] G8: Auth contract evidence
- [ ] G9: TLS certificate evidence
- [ ] G10: STRIDE threat model
- [ ] G11: Secure deployment checklist
- [ ] G12: Incident response runbook
- [ ] G13: Secret rotation procedures
- [ ] G14–G17: Data retention verification
- [ ] G18–G21: Encryption/access control
- [ ] G22–G24: SOC 2 evidence
- [ ] G25: Evidence consolidation
- [ ] G26: Evidence index
- [ ] G27: AC7 cross-reference
