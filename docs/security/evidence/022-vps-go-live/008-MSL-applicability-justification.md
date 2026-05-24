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
