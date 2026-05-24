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
