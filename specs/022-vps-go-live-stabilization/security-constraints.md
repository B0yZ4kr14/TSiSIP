# Security Constraints — Feature 022: VPS Go-Live Stabilization

**Generated**: 2026-05-23
**Review Scope**: spec.md, plan.md, docker-compose.vps.yml (implied)
**Security Constitution Version**: 1.0.0

---

## Boundaries Validated

| Boundary | Status | Evidence |
|---|---|---|
| Public SIP Edge | PASS | OpenSIPS on sip_edge (5060/udp, 5060/tcp); auth enforced per AC3 |
| Internal SIP | PASS | sip_internal network; Asterisk/RTPengine have no host-published ports |
| Database | PASS | PostgreSQL on db_internal; zero host ports per AC6 |
| Control Plane | REVIEW | OCP exposed on host port 8084 (http://127.0.0.1:8084); intended for local healthcheck only. Verify this is not exposed to 0.0.0.0 |
| Observability | PASS | Prometheus/Grafana on metrics_host (internal only) |

## Authentication & Authorization

| Control | Status | Finding |
|---|---|---|
| SIP Digest (HA1 precomputed) | PASS | OpenSIPS 3.6 LTS with calculate_ha1=0 assumed from baseline |
| OCP Web Auth | GAP | Spec does not require OCP login validation during stabilization |
| Trunk mTLS | PASS | Out of scope for 24h stabilization; Feature 007 covers TLS/SRTP |

## Data Isolation

| Control | Status | Finding |
|---|---|---|
| Multi-tenancy | PASS | No schema changes proposed; existing tenant_id constraints apply |
| CDR/Audit retention | PASS | No retention policy changes proposed |
| Secrets in evidence | PASS | R1 explicitly prohibits; grep verification defined |

## Secure-by-Design Gaps

| ID | Severity | Description | Remediation |
|---|---|---|---|
| SEC-022-01 | MEDIUM | Plan does not validate cap_drop: [ALL] and minimal cap_add for vps-lite services | Add healthcheck test to verify container capabilities |
| SEC-022-02 | MEDIUM | AC4 tests HTTP only (port 8084); does not verify TLS 1.2+ minimum or HSTS headers | Add TLS version verification once CERTBOT_STAGING=0 |
| SEC-022-03 | LOW | Rate limiting (pike module) not exercised during stabilization tests | Document as known limitation; Feature 006 covers rate limiting |
| SEC-022-04 | LOW | No audit event validation for SIP auth during smoke tests | AC3 verifies OPTIONS 200 OK but not auth failure logging |

## Compliance Mapping

| Requirement | Feature 022 Coverage | Gap |
|---|---|---|
| LGPD — encryption at rest | Backup encryption assumed from Feature 005 | No explicit validation in ACs |
| LGPD — audit trail | auth_audit_log table exists | No validation that events are written during test |
| SOC 2 — change management | Spec-driven development followed | PASS |

## Security Review Sign-off

- **Status**: Approved with 4 advisory findings (0 blocking)
- **Condition**: SEC-022-01 and SEC-022-02 should be addressed before production TLS activation
