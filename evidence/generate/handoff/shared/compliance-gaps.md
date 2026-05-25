# Shared Compliance Gaps

## OWASP-ASVS

| Requirement | Gap | Affected Teams |
|---|---|---|
| V2.10 Service Authentication | MI HTTP lacks auth | Platform / SRE (BC-001) |
| V6.2 Algorithms | HA1 without PBKDF2 | Operations / DevOps (BC-004) |
| V8.3 Sensitive Private Data | RTP media unencrypted | Platform / SRE (BC-002) |
| V11.1.1 HTTP Request Rate Limiting | No global per-IP limit | Operations / DevOps (BC-005) |
