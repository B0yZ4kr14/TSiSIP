# SIP Port Exposure Decision — Feature 008: DevSecOps Deployment Automation

**Document ID**: SEC-008-SIP-001  
**Date**: 2026-05-19  
**Review date**: 2026-06-19  
**Decision owner**: @b0yz4kr14  

---

## 1. Decision

**DEFERRED** — Public SIP signaling port exposure (UDP 5060, TCP 5060) on TSiAPP's public interface is deferred pending completion of prerequisites.

---

## 2. Rationale

### Prerequisites Not Yet Met

| Prerequisite | Status | Target Date |
|---|---|---|
| TLS certificate automation (Feature 015) | Complete | 2026-05-19 |
| Trunk provider IP whitelist configuration | Pending | 2026-05-26 |
| Rate limiting and DDoS protection validation (Feature 006) | Pending | 2026-05-26 |
| SIP authentication hardening review | Pending | 2026-05-26 |
| fail2ban SIP jail configuration | Pending | 2026-05-26 |

### Risk of Premature Exposure

- SIP is a high-abuse protocol (toll fraud, scanning, DDoS).
- Without provider IP whitelisting, any source can attempt REGISTER/INVITE.
- Rate limiting must be validated under realistic load before public exposure.

---

## 3. Current State

- OpenSIPS listens on `0.0.0.0:5060/udp` and `0.0.0.0:5060/tcp` inside the container.
- The host publishes these ports: `5060:5060/udp` and `5060:5060/tcp`.
- **UFW is configured to allow 5060/udp and 5060/tcp**, but the VPS provider's cloud firewall (if any) may block them.
- Tailscale interface (`100.111.74.69`) is reachable for management and testing.

---

## 4. Activation Conditions

Public SIP exposure will be activated when ALL of the following are true:

1. [x] TLS certificate automation is operational (Feature 015)
2. [ ] At least one trunk provider IP range is whitelisted in OpenSIPS config
3. [ ] Feature 006 (rate limiting / DDoS protection) is validated under load
4. [ ] fail2ban SIP jail is configured and tested
5. [ ] SIP authentication review confirms HA1-only, no plaintext, strong nonces
6. [ ] Incident response runbook is reviewed and approved (SG4.4)

---

## 5. Mitigation During Deferral

- UFW default-deny remains active.
- fail2ban monitors SSH and nginx; SIP jail to be added.
- OpenSIPS config requires authentication for all non-OPTIONS requests.
- Topology hiding prevents backend IP leakage.
- RTPengine binds control socket to internal address only.

---

## 6. Sign-off

| Role | Name | Date | Status |
|---|---|---|---|
| Decision Owner | @b0yz4kr14 | 2026-05-19 | Deferred |
| Security Review | Security Governance | 2026-05-19 | Acknowledged |
