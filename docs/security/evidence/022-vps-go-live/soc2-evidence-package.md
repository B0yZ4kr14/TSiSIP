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
