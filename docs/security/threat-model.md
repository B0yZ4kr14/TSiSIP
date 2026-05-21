# Threat Model: TSiSIP SIP Edge Proxy

**Date**: 2026-05-21  
**Method**: STRIDE  
**Scope**: OpenSIPS SIP edge, RTPengine media relay, OCP admin panel, PostgreSQL backend  
**Assessor**: Security Governance + Architecture Guard

---

## Trust Boundaries

```
[Internet] --5060/tcp/udp--> [OpenSIPS] --sip_internal--> [Asterisk]
                                    |
                                    --db_internal--> [PostgreSQL]
                                    |
                                    --metrics--> [Prometheus/Grafana]
[Internet] --10000-20000/udp--> [RTPengine]
```

## STRIDE Analysis

### Spoofing
| Threat | Mitigation | Status |
|---|---|---|
| SIP INVITE spoofing | Digest auth (HA1-only, RFC 8760) | ✅ Implemented |
| RTP injection | SRTP + DTLS on RTPengine | ✅ Implemented |
| OCP session hijacking | bcrypt + secure cookie + CSRF | ✅ Implemented |
| DNS spoofing for tsiapp.io | Cloudflare DNSSEC + TLS pinning | ✅ Implemented |

### Tampering
| Threat | Mitigation | Status |
|---|---|---|
| SIP header injection | Header sanitization (remove_hf) | ✅ Implemented |
| Database tampering | Docker network isolation, no host ports | ✅ Implemented |
| Config tampering | SHA-pinned images, read-only secrets | ✅ Implemented |

### Repudiation
| Threat | Mitigation | Status |
|---|---|---|
| Deny SIP auth attempt | auth_audit_log table | ✅ Implemented |
| Deny OCP login | ocp_login_log table | ✅ Implemented |
| Deny password change | ocp_password_changes table | ✅ Implemented |
| Deny config change | CDR + dispatcher audit trail | ✅ Implemented |

### Information Disclosure
| Threat | Mitigation | Status |
|---|---|---|
| Backend PBX IP leak | topology_hiding("C") | ✅ Implemented |
| PostgreSQL exposure | Zero host-published ports | ✅ Implemented |
| Secret leakage in logs | Secrets via Docker secrets, envsubst | ✅ Implemented |
| TLS downgrade | TLS 1.2+ only, HSTS preload | ✅ Implemented |

### Denial of Service
| Threat | Mitigation | Status |
|---|---|---|
| SIP flood | pike module (50 req/2s per IP) | ✅ Implemented |
| RTP port exhaustion | 10000-20000 range, port limits | ✅ Implemented |
| DB connection exhaustion | max_connections=200, connection pooling | ✅ Implemented |
| Backup OOM | nice + ionice limits (ML-003) | ✅ Implemented |

### Elevation of Privilege
| Threat | Mitigation | Status |
|---|---|---|
| Container escape | cap_drop ALL, no-new-privileges | ✅ Implemented |
| Privilege escalation in OCP | Role hierarchy (readonly → admin) | ✅ Implemented |
| OpenSIPS config injection | -c syntax check, entrypoint validation | ✅ Implemented |

## Risk Register

| ID | Threat | Likelihood | Impact | Risk | Mitigation |
|---|---|---|---|---|---|
| TM-001 | Credential stuffing on OCP | Medium | High | Medium | Account lockout (5 fails), bcrypt |
| TM-002 | Zero-day in OpenSIPS C code | Low | Critical | Medium | Trivy CI scan, Debian security updates |
| TM-003 | Insider threat (admin abuse) | Low | High | Low | Audit logs, role separation |
| TM-004 | DDoS on SIP edge | Medium | Medium | Medium | pike, rate limiting, Cloudflare |
| TM-005 | Backup exfiltration | Low | Critical | Medium | AES-256-GCM encryption, S3 IAM |

## Accepted Risks

- **OpenSIPS C code**: TSiSIP does not modify OpenSIPS source; relies on upstream Debian security updates.
- **Cloudflare edge**: TLS termination at Cloudflare introduces a third-party trust boundary (acceptable for public SIP edge).

## References
- `security_constitution.md`
- `architecture_constitution.md`
- `reports/memorylint-audit-2026-05-21.md`
- `reports/critique-review-2026-05-21.md`
