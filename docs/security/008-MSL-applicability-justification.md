# MSL Applicability Justification — Feature 008: DevSecOps Deployment Automation

**Document ID**: SEC-008-MSL-001  
**Date**: 2026-05-19  
**Applies to**: TSiAPP VPS deployment (Tailscale 100.111.74.69, Public 179.190.15.116)  
**Review cycle**: 90 days  
**Next review**: 2026-08-17  

---

## 1. Purpose

This document maps Minimum Security Level (MSL) control areas to the TSiSIP architecture and declares applicability with justification. It answers: *Which MSL controls apply to TSiAPP, why, and what is our compliance posture?*

---

## 2. MSL Control Area Matrix

| Control Area | Applicability | Justification | Compliance Posture |
|---|---|---|---|
| Identity & Access Management (IAM) | Applicable | SSH access to TSiAPP, GitHub Actions identity, Docker registry auth, OCP admin accounts | Partial — deploy user exists, SSH key restricted; OCP RBAC not yet formalized |
| Network Security | Applicable | Public SIP ports, Nginx reverse proxy, Docker network isolation, UFW firewall | Strong — UFW default-deny, fail2ban, internal Docker networks, port restrictions |
| Data Protection | Applicable | PostgreSQL subscriber data, backup encryption, TLS termination | Strong — HA1-only auth, backup encryption key, TLS 1.2/1.3, HSTS |
| Logging & Monitoring | Applicable | Nginx access logs, OCP audit log (Feature 014-B), Prometheus/Grafana (full profiles) | Partial — audit log is immutable; Prometheus excluded from VPS-lite by design |
| Vulnerability Management | Applicable | Container image CVE scans, unattended-upgrades, base image updates | Strong — Trivy HIGH/CRITICAL blocking, unattended-upgrades active |
| Incident Response | Applicable | Security incidents on public-facing SIP edge and web portal | Partial — runbook pending (SG4.4) |
| Business Continuity | Applicable | Backup/restore, stack rollback, database PITR | Strong — automated backups, validated restore tested, image rollback documented |

---

## 3. Residual Risk Register

| ID | Risk Description | Likelihood | Impact | Mitigating Controls | Risk Owner | Review Date |
|---|---|---|---|---|---|---|
| R-001 | Backup metrics exporter main process runs as root due to Debian cron daemon requirement | Low | Medium | Container is single-purpose, no shell access, no-new-privileges, minimal filesystem exposure | @b0yz4kr14 | 2026-08-17 |
| R-002 | Prometheus/Grafana full observability stack excluded from VPS-lite profile | Medium | Low | VPS-lite targets resource-constrained hosts; monitoring available via backup metrics exporter and host-level tools; full profile available for production hosts with >4GB RAM | @b0yz4kr14 | 2026-08-17 |
| R-003 | Dummy TLS certificates used in CI and initial deployment until real certs are provisioned | Medium | High | Real certs are provisioned by Feature 014-A (certbot/tailscale-cert automation); dummy certs are never used in production after initial bootstrap | @b0yz4kr14 | 2026-06-19 |
| R-004 | Ansible syntax-check not runnable in all environments without ansible binary installed | Low | Low | validate.sh gracefully skips; CI path to be added (SG2.3) | @b0yz4kr14 | 2026-06-19 |
| R-005 | nginx -t not runnable in all environments without nginx binary installed | Low | Low | validate.sh gracefully skips; CI path to be added (SG2.2) | @b0yz4kr14 | 2026-06-19 |

---

## 4. Out-of-Scope Controls

| Control Area | Reason for Exclusion |
|---|---|
| Physical Security | TSiAPP is a rented VPS; physical controls are provider responsibility |
| Endpoint Detection & Response (EDR) | TSiAPP is a single-purpose server; container runtime is the endpoint boundary |
| Data Loss Prevention (DLP) | No user-generated content leaves the platform; SIP signaling only |

---

## 5. Sign-off

| Role | Name | Date | Status |
|---|---|---|---|
| Author | Security Governance (speckit-tasks) | 2026-05-19 | Draft |
| Reviewer | [Pending — SG1.1 acceptance gate] | — | Pending |
