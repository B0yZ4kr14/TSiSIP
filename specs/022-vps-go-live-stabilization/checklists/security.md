# Feature 022 — Security Requirements Quality Checklist

**Purpose**: Validate the quality, clarity, and completeness of security requirements in spec.md and plan.md for Feature 022.

**Created**: 2026-05-23
**Feature**: 022 — VPS Go-Live Stabilization

---

## Authentication & Authorization

- [ ] CHK001 - Are SIP Digest auth requirements specified for all non-OPTIONS request types (REGISTER, INVITE, BYE, etc.)? [Coverage, Gap]
- [ ] CHK002 - Is the trusted gateway whitelist explicitly defined with IP ranges and mTLS requirements? [Clarity, Spec §SIP Trunk Security]
- [ ] CHK003 - Are OCP web auth requirements (bcrypt, lockout, forced password change) validated during stabilization? [Coverage, Gap]
- [ ] CHK004 - Are role hierarchy requirements enforced in OCP during the go-live window? [Completeness, security_constitution §OCP Web Auth]
- [ ] CHK005 - Are SIP trunk credential encryption requirements (AES-256-CBC + PBKDF2) verified in the vps-lite stack? [Coverage, Gap]

## Trust Boundaries

- [ ] CHK006 - Is network segmentation explicitly verified for all 5 boundaries (Public SIP, Internal SIP, Database, Control Plane, Observability)? [Completeness, security_constitution §1]
- [ ] CHK007 - Are WebSocket/WSS transport requirements defined if WebRTC is enabled? [Coverage, Gap]
- [ ] CHK008 - Is the RTPengine control socket binding to sip_internal explicitly verified in T8? [Clarity, security_constitution §SIP-Specific Boundaries]
- [ ] CHK009 - Are metrics endpoints verified to not leak tenant-scoped data? [Coverage, Gap]

## Data Protection

- [ ] CHK010 - Are multi-tenancy isolation requirements (tenant_id filtering) verified in OCP queries? [Coverage, security_constitution §3]
- [ ] CHK011 - Is CDR retention policy (7 years) enforced and verified? [Measurability, security_constitution §3]
- [ ] CHK012 - Are audit log retention requirements (1 year minimum) verified? [Measurability, security_constitution §3]
- [ ] CHK013 - Is backup encryption (AES-256-CBC + PBKDF2 + HMAC-SHA256) verified for the backup service? [Completeness, security_constitution §3]

## Secrets Management

- [ ] CHK014 - Are Docker secrets injection paths (/run/secrets/) verified for all runtime secrets? [Completeness, R1]
- [ ] CHK015 - Is secret rotation policy (quarterly for runtime, 90 days for TLS) documented in rollback runbook? [Clarity, Gap]
- [ ] CHK016 - Are CI pre-flight scans (Gate 0) for committed secrets mentioned in stabilization scope? [Coverage, Gap]

## Header Sanitization

- [ ] CHK017 - Are all 6 header sanitization requirements (P-Asserted-Identity, P-Preferred-Identity, X-Tenant-ID, X-Backend-ID, X-Route-Override, X-Routing-Key) explicitly tested? [Completeness, security_constitution §5]
- [ ] CHK018 - Is Authorization/Proxy-Authorization stripping before backend forwarding verified? [Coverage, Gap]

## Container Hardening

- [ ] CHK019 - Are cap_drop: [ALL] and minimal cap_add requirements defined for each vps-lite service? [Completeness, SEC-022-01]
- [ ] CHK020 - Is security_opt: ["no-new-privileges:true"] verified on all services? [Measurability, security_constitution §5]
- [ ] CHK021 - Are base image SHA256 digests verified (no :latest tags)? [Completeness, security_constitution §5]

## TLS & Encryption

- [ ] CHK022 - Is TLS 1.2+ minimum version explicitly required in AC4? [Clarity, security_constitution §6]
- [ ] CHK023 - Are HSTS headers verified once HTTPS is active? [Coverage, Gap]
- [ ] CHK024 - Is certificate rotation automation (certbot deploy-hook) verified? [Completeness, security_constitution §6]
- [ ] CHK025 - Is SRTP/DTLS enabled verification included for WebRTC if applicable? [Coverage, Gap]

## Rate Limiting & DDoS

- [ ] CHK026 - Are pike module rate limiting thresholds quantified? [Clarity, security_constitution §5]
- [ ] CHK027 - Is per-tenant rate limiting enforcement verified during smoke tests? [Coverage, Gap]
- [ ] CHK028 - Is circuit breaker behavior on dispatcher failover defined? [Completeness, security_constitution §5]

## Audit & Monitoring

- [ ] CHK029 - Are all 5 mandatory audit events (SIP auth, OCP login, password change, dispatcher update, trunk config) verified to be logged? [Completeness, security_constitution §7]
- [ ] CHK030 - Is Alertmanager HIGH/CRITICAL alert routing verified? [Coverage, Gap]
- [ ] CHK031 - Is anomaly detector Z-score analysis baseline established? [Clarity, Gap]

## Incident Response

- [ ] CHK032 - Are P0 incident response triggers (5 categories) documented in operator runbook? [Completeness, security_constitution §8]
- [ ] CHK033 - Is evidence preservation procedure (logs, snapshots, freeze) defined for rollback scenarios? [Coverage, R3]
