# Feature 022 — Compliance Requirements Quality Checklist

**Purpose**: Validate the quality, clarity, and completeness of compliance requirements (LGPD, SOC 2, ANATEL) in spec.md and plan.md for Feature 022.

**Created**: 2026-05-23
**Feature**: 022 — VPS Go-Live Stabilization

---

## LGPD — Data Retention

- [ ] CHK001 - Are CDR retention requirements (7 years) explicitly verified in stabilization tests? [Measurability, security_constitution §9]
- [ ] CHK002 - Are audit log retention requirements (1 year minimum) verified? [Measurability, security_constitution §9]
- [ ] CHK003 - Are purge operation logging requirements defined? [Completeness, security_constitution §3]
- [ ] CHK004 - Is tenant deletion cascade behavior verified? [Coverage, security_constitution §3]

## LGPD — Encryption

- [ ] CHK005 - Is encryption at rest (backup AES-256-CBC + PBKDF2 + HMAC-SHA256) explicitly validated? [Completeness, security_constitution §9]
- [ ] CHK006 - Is TLS 1.2+ in transit requirement verified? [Measurability, security_constitution §9]
- [ ] CHK007 - Are backup encryption key rotation requirements defined? [Clarity, security_constitution §4]

## LGPD — Access Control

- [ ] CHK008 - Are role-based OCP requirements verified during stabilization? [Coverage, security_constitution §9]
- [ ] CHK009 - Is SIP digest auth requirement verified for all non-OPTIONS requests? [Completeness, security_constitution §9]

## LGPD — Audit Trail

- [ ] CHK010 - Are auth_audit_log and ocp_login_log table requirements verified? [Completeness, security_constitution §9]
- [ ] CHK011 - Is audit event completeness (all 5 event types) verified during smoke tests? [Coverage, security_constitution §7]

## LGPD — Right to Erasure

- [ ] CHK012 - Is tenant deletion cascade to subscriber and CDR defined with retention period? [Clarity, security_constitution §3]
- [ ] CHK013 - Are data erasure verification requirements defined? [Completeness, Gap]

## ANATEL — Telecom

- [ ] CHK014 - Are CDR integrity requirements (immutable with tenant attribution) verified? [Completeness, security_constitution §9]
- [ ] CHK015 - Are call detail record completeness requirements defined? [Coverage, Gap]

## SOC 2 — Change Management

- [ ] CHK016 - Is spec-driven development process verified during stabilization? [Measurability, security_constitution §9]
- [ ] CHK017 - Are gated deploy pipeline requirements (deploy.yml) verified? [Completeness, security_constitution §9]
- [ ] CHK018 - Is change documentation/evidence retention defined? [Clarity, Gap]

## SOC 2 — Vulnerability Management

- [ ] CHK019 - Are Trivy CI scan requirements verified? [Completeness, security_constitution §9]
- [ ] CHK020 - Is 90-day artifact retention requirement defined? [Clarity, security_constitution §9]
- [ ] CHK021 - Are CVE remediation timeframes defined (HIGH/CRITICAL)? [Coverage, Gap]

## Evidence Requirements

- [ ] CHK022 - Are compliance evidence artifacts explicitly listed in AC7? [Completeness, AC7]
- [ ] CHK023 - Is evidence naming convention compliant with audit standards? [Clarity, Gap]
- [ ] CHK024 - Are evidence retention requirements aligned with LGPD/SOC 2 retention periods? [Consistency, Gap]
