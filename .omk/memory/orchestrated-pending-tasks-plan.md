# Orchestrated Implementation Plan: All Pending Tasks
## Method: Socratic/Popperian

### Socratic Method Contract
For every architectural decision:
1. **Question (Elenchus)**: What is the claim? What are the assumptions?
2. **Hypothesis**: Formulate a falsifiable statement about the implementation
3. **Counter-argument**: What could disprove this? What are the edge cases?
4. **Refutation test**: Concrete test that would falsify the hypothesis
5. **Conclusion**: Decision with documented rationale and residual risk

### Popperian Falsification Contract
For every implementation:
1. **Hypothesis**: "This implementation satisfies requirement X"
2. **Falsification test**: Specific test that would prove the hypothesis false
3. **Experiment**: Run the test
4. **Survival**: If not falsified, the hypothesis is corroborated (not proven)
5. **Conjecture**: Document what future evidence could still refute it

---

## Task Inventory (31 pending tasks)

### Wave 1: Foundation Security (spec 001 — 3 tasks)
| Task | Description | Socratic Question | Popperian Test |
|------|-------------|-------------------|----------------|
| T5.1 | Implement trusted gateway bypass (permissions/address) | "Should trusted gateways bypass ALL auth or just digest auth?" | SIP probe from trusted IP must NOT receive 401/407 |
| T5.2 | Implement auth audit logging | "What constitutes an auth 'attempt' vs 'failure'?" | Query auth_audit_log after failed auth → row exists |
| T5.3 | Align auth contract (401 vs 407) | "Is 407 semantically correct for proxy auth, or is 401 sufficient?" | Non-REGISTER request without creds returns canonical code |

### Wave 2: Backup Resilience (spec 005 — 2 tasks)
| Task | Description | Socratic Question | Popperian Test |
|------|-------------|-------------------|----------------|
| T6.1 | Configure rclone for S3/MinIO | "Is S3 the right target, or should we support SFTP/RSYNC?" | `rclone ls` shows remote backup files |
| T6.2 | Implement bandwidth-throttled replication | "What is the minimum viable bandwidth limit?" | Replication completes without saturating VPS link |

### Wave 3: Edge Security (spec 006 — 9 tasks)
| Task | Description | Socratic Question | Popperian Test |
|------|-------------|-------------------|----------------|
| T1.2 | Handle NATed enterprise traffic | "How do we distinguish NATed legitimate traffic from spoofed?" | Enterprise NAT IP passes through without false pike block |
| T1.3 | Add TCP connection limits | "What is the per-source TCP connection ceiling?" | 1000 TCP conns from single IP → new conns rejected |
| T2.1 | Configure htable for auth failures | "Why htable instead of userblacklist?" | 5 failed auths → htable entry created |
| T2.2 | Implement subscriber auth throttling | "Should throttling be per-subscriber or per-IP?" | Same subscriber fails 3x → 429 Too Many Requests |
| T3.2 | Add dispatcher load monitoring | "Load monitoring: active probing or passive metrics?" | Failed dispatcher target marked inactive within 30s |
| T4.1 | Create ban list htable | "Ban list: in-memory htable or PostgreSQL?" | Banned IP receives 403 without DB query |
| T4.2 | Add ban management MI commands | "Should ban management be CLI or MI?" | `opensips-cli -x mi ban_list` shows banned IPs |
| T4.3 | Implement ban TTL accuracy | "What is the minimum viable ban TTL?" | Ban expires automatically after TTL |
| T5.3 | Add global throttle on anomaly | "Anomaly detection: statistical or rule-based?" | Traffic >3σ from baseline → global throttle activated |

### Wave 4: Encryption Hardening (spec 007 — 7 tasks)
| Task | Description | Socratic Question | Popperian Test |
|------|-------------|-------------------|----------------|
| T2.3 | Implement mutual TLS for trunks | "mTLS: client cert verification on every request or session?" | Client without valid cert → TLS handshake fails |
| T3.2 | Add rotation monitoring | "Rotation: proactive schedule or reactive alert?" | Cert expiry <30 days → alert generated |
| T4.1 | Configure RTPengine for SRTP | "SRTP: SDES, DTLS-SRTP, or both?" | RTP stream captured → unreadable (encrypted) |
| T4.2 | Update OpenSIPS SDP routes for SRTP | "SDP rewrite: replace crypto lines or append?" | INVITE SDP contains SRTP crypto offer |
| T4.3 | Handle SDP re-INVITE for SRTP | "Re-INVITE: maintain same SRTP key or renegotiate?" | Re-INVITE SDP contains valid SRTP crypto |
| T5.1 | Restrict TLS cipher suites | "Cipher restriction: allow only AES-256-GCM or also AES-128-GCM?" | SSL Labs scan shows only allowed ciphers |
| T5.2 | Restrict SRTP cipher suites | "SRTP cipher: AES-ICM or AES-GCM?" | SRTP packet analysis shows only allowed cipher |

### Wave 5: Deploy Automation (spec 009 — 11 tasks)
| Task | Description | Socratic Question | Popperian Test |
|------|-------------|-------------------|----------------|
| T1.1-T1.3 | Gated stages, impact analysis, rollback | "Is full automation safe without human gate?" | Deploy with HIGH impact → pipeline halts |
| T2.1-T2.5 | OMK agent roles and logic | "Agents: stateless scripts or stateful services?" | Agent failure → pipeline logs error, no corruption |
| T3.1-T3.3 | GitHub Actions, dry-run, live test | "CI/CD: GitHub Actions or local-only?" | `workflow_dispatch` triggers full pipeline |
| T4.1-T4.2 | Documentation updates | "Documentation: auto-generated or manual?" | Deploy README matches actual pipeline behavior |

---

## Dependency Graph

```
Wave 1 (spec 001 foundation)
    |
    +---> Wave 3 (spec 006 rate limiting) [depends on auth infra]
    +---> Wave 4 (spec 007 encryption) [depends on TLS listener]
    |
Wave 2 (spec 005 backup) [independent]
    |
Wave 5 (spec 009 deploy) [independent, tooling]
```

## Execution Strategy

1. **Wave 1** (spec 001): Single agent — core OpenSIPS config changes, high risk
2. **Wave 2** (spec 005): Parallel agent — backup scripts, medium risk
3. **Wave 3** (spec 006): Parallel agent — OpenSIPS modules, high risk
4. **Wave 4** (spec 007): Parallel agent — TLS/SRTP, high risk
5. **Wave 5** (spec 009): Parallel agent — shell scripts, low risk

## Quality Gates Per Wave

- Before wave: GitNexus impact analysis on target symbols
- During wave: Socratic decision log + Popperian test execution
- After wave: CI scan + `opensips -c` validation + SIP probe test
- Final: Cross-wave integration test
