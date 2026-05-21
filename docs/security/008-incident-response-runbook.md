# Security Incident Response Runbook — Feature 008: DevSecOps Deployment Automation

**Document ID**: SEC-008-IR-001  
**Date**: 2026-05-19  
**Applies to**: TSiAPP VPS deployment  
**Review cycle**: 90 days  
**Next review**: 2026-08-17
**Review scheduled**: Yes (calendar reminder set)  

---

## 1. Incident Classification

| Severity | Criteria | Response Time |
|---|---|---|
| P0 — Critical | Active compromise, secret leakage, toll fraud in progress, DDoS causing outage | Immediate (< 15 min) |
| P1 — High | Suspected unauthorized access, CVE affecting exposed service, certificate expiry | < 1 hour |
| P2 — Medium | Policy violation, failed audit check, non-blocking vulnerability | < 24 hours |
| P3 — Low | Documentation gap, false positive alert | < 72 hours |

---

## 2. Scenario A: Suspected Secret Compromise

### Detection Signals

- Unauthorized access to `secrets/` directory on host.
- Unexpected git changes to secret files.
- Audit log shows admin actions from unknown IPs.
- Certificate transparency logs show unexpected certs for domain.

### Containment (First 15 Minutes)

1. **Rotate the compromised secret immediately** using `docs/security/008-secret-rotation-procedures.md`.
2. **Revoke related credentials**: If TLS key is compromised, revoke certificate via ACME.
3. **Block source IP**: Add IP to UFW deny list: `ufw deny from <IP>`.
4. **Enable enhanced logging**: Increase OpenSIPS log level to 4 (`opensipsctl fifo debug 4`).

### Investigation

1. Check audit log: `docker compose exec postgres psql -U opensips -c "SELECT * FROM auth_audit_log ORDER BY created_at DESC LIMIT 50;"`
2. Check nginx access logs for unusual patterns: `tail -n 1000 /var/log/nginx/access.log | grep <IP>`.
3. Check container logs: `docker compose logs --tail 500 <service>`.

### Evidence Preservation

1. Snapshot container state: `docker commit <container> evidence-$(date +%s)`.
2. Copy logs to evidence directory: `cp /var/log/nginx/access.log docs/security/evidence/incident-$(date +%Y%m%d)-nginx.log`.
3. Export audit log: `docker compose exec postgres pg_dump -U opensips -t auth_audit_log > evidence/audit-$(date +%Y%m%d).sql`.

### Recovery

1. Verify all secrets rotated.
2. Restart all services: `docker compose up -d --force-recreate`.
3. Run `scripts/verify-secrets-audit.sh`.
4. Run integration tests.

### Communication

| Audience | Message | Channel |
|---|---|---|
| Internal team | Incident summary + actions taken | Secure chat (Signal/Wire) |
| Provider (if trunk creds) | Compromise notification + revocation | Email + phone |
| Users (if PBX affected) | Service disruption notice | Status page |

---

## 3. Scenario B: Container Escape / Privilege Escalation

### Detection Signals

- Container process running as root unexpectedly.
- Host-level file modifications from container UID.
- `dmesg` shows seccomp/ AppArmor violations.
- Unexpected network connections from container namespace.

### Containment (First 15 Minutes)

1. **Stop the affected container**: `docker compose stop <service>`.
2. **Kill suspicious processes**: `docker kill <container>`.
3. **Isolate the host**: If escape is confirmed, take host offline from Tailscale: `tailscale down`.
4. **Preserve container filesystem**: `docker export <container> > evidence/container-$(date +%s).tar`.

### Investigation

1. Check container capabilities: `docker inspect <container> | grep -A20 CapAdd`.
2. Review Dockerfile for excessive privileges.
3. Check for kernel CVEs affecting the host: `uname -a` vs CVE database.
4. Scan container image for known exploits: `trivy image <image>`.

### Recovery

1. Patch host kernel if CVE confirmed.
2. Rebuild image from clean base.
3. Verify `security_opt: ["no-new-privileges:true"]` and minimal `cap_add`.
4. Redeploy with hardened configuration.

---

## 4. Scenario C: SIP Abuse / Toll Fraud

### Detection Signals

- Spike in REGISTER/INVITE rates from single IP.
- Multiple failed authentication attempts (401/407 responses).
- Calls to premium-rate destinations.
- RTPengine showing unexpected media streams.

### Containment (First 15 Minutes)

1. **Block abusive IP**: Add to OpenSIPS blocklist or UFW.
2. **Rate-limit the source**: Enable emergency rate limiting in OpenSIPS config.
3. **Disable affected trunk**: If toll fraud via trunk, disable provider in dispatcher.
4. **Capture SIP traffic**: `tcpdump -i any -w evidence/sip-abuse-$(date +%s).pcap port 5060`.

### Investigation

1. Query CDR for suspicious calls: `docker compose exec postgres psql -U opensips -c "SELECT * FROM cdr WHERE call_start > NOW() - INTERVAL '1 hour' ORDER BY call_start DESC;"`.
2. Check subscriber table for unauthorized accounts.
3. Review OpenSIPS logs for authentication bypass attempts.
4. Check trunk credential usage patterns.

### Recovery

1. Remove unauthorized subscribers.
2. Rotate trunk credentials.
3. Verify HA1-only auth is enforced (`calculate_ha1 = 0`).
4. Review and tighten rate limits.

---

## 5. Scenario D: DDoS Against Nginx or SIP Ports

### Detection Signals

- Bandwidth saturation alert from VPS provider.
- Nginx error log shows "upstream timed out".
- OpenSIPS CPU/memory spikes.
- `ss -s` shows massive connection table growth.

### Containment (First 15 Minutes)

1. **Enable UFW rate limiting**: `ufw limit proto tcp from any to any port 443`.
2. **Activate fail2ban**: `fail2ban-client set nginx-ddos banip <IP>` (custom jail).
3. **Enable CDN/cloud proxy**: If available, route through Cloudflare or similar.
4. **Scale up**: If VPS is resource-constrained, temporarily resize.

### Investigation

1. Identify attack vector: `nginx access log` vs `opensips syslog`.
2. Check if attack is volumetric (UDP flood) or application-layer (slowloris, SIP fuzzing).
3. GeoIP analysis of source addresses.

### Recovery

1. Maintain UFW rules for persistent attackers.
2. Consider geographic blocking if attack is region-specific.
3. Implement connection tracking limits in OpenSIPS.
4. Document attack pattern for future defense.

---

## 6. Communication Plan

### Internal Escalation

| Severity | Notify | Within |
|---|---|---|
| P0 | All team members + on-call | 5 minutes |
| P1 | Security owner + ops lead | 30 minutes |
| P2 | Security owner | 4 hours |
| P3 | Ticket only | 24 hours |

### External Communication

- **Provider (trunk)**: Direct phone + email for P0/P1 affecting service.
- **Users**: Status page update for service-impacting incidents.
- **Law enforcement**: Only for confirmed fraud with financial loss.

---

## 7. Post-Incident Review

Within 72 hours of resolution:

1. Timeline reconstruction.
2. Root cause analysis (5 Whys).
3. Corrective actions with owners and due dates.
4. Update this runbook if gaps found.

---

## 8. Contact List

| Role | Contact | Backup |
|---|---|---|
| Security Owner | @b0yz4kr14 | <OPS_BACKUP_EMAIL> |
| Ops Lead | @b0yz4kr14 | <OPS_BACKUP_EMAIL> |
| VPS Provider | Hetzner/Contabo support | provider-ticket-system |
| Trunk Provider | <TRUNK_SUPPORT_EMAIL> | provider-ticket-system |

---

## 9. Sign-off

| Role | Name | Date | Status |
|---|---|---|---|
| Author | Security Governance | 2026-05-19 | Approved |
| Reviewer | Architecture Review | 2026-05-19 | Approved |
