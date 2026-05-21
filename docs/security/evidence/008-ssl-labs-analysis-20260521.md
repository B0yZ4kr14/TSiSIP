# SSL Labs Analysis — tsiapp.io

**Date**: 2026-05-21
**Scanner**: Qualys SSL Labs API v3
**Host**: tsiapp.io
**Status**: READY (all endpoints scanned)

---

## Executive Summary

| Metric | Value |
|--------|-------|
| Overall Grade | **B** |
| Target Grade | A+ (per SC-004b) |
| Grade Gap | 2 levels (B → A → A+) |
| Blocker | Cloudflare edge TLS 1.0/1.1 support |

---

## Endpoint Results

| IP Address | Grade | Status | Provider |
|------------|-------|--------|----------|
| 104.26.0.106 | B | Ready | Cloudflare |
| 104.26.1.106 | B | Ready | Cloudflare |
| 2606:4700:20::ac43:456e | B | Ready | Cloudflare |
| 2606:4700:20::681a:16a | B | Ready | Cloudflare |
| 2606:4700:20::681a:6a | B | Ready | Cloudflare |
| 172.67.69.110 | B | Ready | Cloudflare |

---

## Root Cause of Grade B

**TLS 1.0 and TLS 1.1 are enabled on the Cloudflare edge.**

SSL Labs penalizes grades when deprecated TLS versions are supported:

- **TLS 1.0**: Published 1999, vulnerable to POODLE, BEAST, and other attacks
- **TLS 1.1**: Published 2006, no modern security benefits over 1.2
- **TLS 1.2**: Minimum recommended version
- **TLS 1.3**: Optimal (fastest, most secure)

### SSL Labs Grade Rules (excerpt)

| Condition | Grade Impact |
|-----------|-------------|
| TLS 1.0 or 1.1 supported | Caps grade at **B** |
| TLS 1.2 + strong ciphers + HSTS + no weak protocols | **A** |
| TLS 1.3 + TLS 1.2 + perfect forward secrecy + HSTS preload | **A+** |

---

## Cloudflare Configuration Required

To achieve grade A+, update SSL/TLS settings in the Cloudflare dashboard:

1. Navigate to **SSL/TLS → Edge Certificates**
2. Set **Minimum TLS Version** to **TLS 1.2**
3. Enable **TLS 1.3** (full)
4. Enable **Automatic HTTPS Rewrites**
5. Enable **Always Use HTTPS**
6. Set **HSTS** (max-age ≥ 31536000, includeSubDomains, preload)

### Verification After Change

```bash
# Check supported protocols
curl -sI --tlsv1.0 https://tsiapp.io/   # Should fail after change
curl -sI --tlsv1.1 https://tsiapp.io/   # Should fail after change
curl -sI --tlsv1.2 https://tsiapp.io/   # Should succeed
curl -sI --tlsv1.3 https://tsiapp.io/   # Should succeed
```

Re-run SSL Labs scan 24 hours after change for grade confirmation.

---

## Positive Findings

| Control | Status | Evidence |
|---------|--------|----------|
| Certificate valid | ✅ Pass | Let's Encrypt E7, expires 2026-07-23 |
| Certificate chain complete | ✅ Pass | Cloudflare origin cert → Let's Encrypt |
| No RC4 support | ✅ Pass | No RC4 cipher suites |
| No BEAST/Heartbleed | ✅ Pass | Not vulnerable |
| OCSP stapling | ✅ Pass | Enabled |
| HSTS | ⚠️ Partial | Enabled but preload status unclear |
| Forward secrecy | ⚠️ Partial | Supported but not enforced for all handshakes |

---

## Risk Assessment

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| Downgrade attack to TLS 1.0/1.1 | Medium | Medium | Set Cloudflare minimum TLS to 1.2 |
| Compliance failure (PCI-DSS 4.0 requires TLS 1.2+) | Medium | High | Disable TLS 1.0/1.1 at edge |
| Grade B affects customer trust | Low | Low | Document remediation plan |

---

## Remediation Timeline

| Phase | Action | Owner | ETA |
|-------|--------|-------|-----|
| 1 | Update Cloudflare minimum TLS to 1.2 | @b0yz4kr14 | 2026-05-21 |
| 2 | Re-run SSL Labs scan | Security Governance | 2026-05-22 |
| 3 | Archive A+ evidence | Security Governance | 2026-05-22 |
| 4 | Update evidence index | Security Governance | 2026-05-22 |

---

## Evidence Artifacts

- `008-ssl-labs-grade-20260521.json` — Raw SSL Labs API response
- `008-ssl-labs-grade-20260521.html` — HTML report page (snapshot)
- `008-ssl-labs-analysis-20260521.md` — This analysis document

---

*Governance Statement: The grade B finding is documented, root-caused, and assigned a remediation plan. The blocker is external (Cloudflare edge configuration), not a TSiSIP code or configuration defect.*
