# Feature 007 Memory Synthesis: End-to-End Encryption for SIP Signaling and Media

## Current Scope
mTLS for SIP trunks, self-signed CA, zero-downtime cert rotation, SRTP/DTLS-SRTP, cipher hardening. Status: 53% complete.

## Relevant Decisions
- Self-signed CA; key offline/HSM.
- tls_reload for rotation (no restart).
- RTPengine manages SRTP keys; OpenSIPS relays SDP.
- TLS 1.2 min, TLS 1.3 preferred.

## Active Architecture Constraints
- OpenSIPS is sole TLS termination point.
- Certificates via Docker secrets only.
- trunk_ips table authoritative for mTLS.

## Accepted Deviations
- 53% complete; mTLS enforcement, full SRTP validation pending.

## Relevant Security Constraints
- verify_client = 1 on trunk listener.
- CRL support; emergency re-issuance plan.
- Downgrade attacks rejected.

## Related Historical Lessons
- tls_reload may drop connections on some builds — test in staging.
- SRTP CPU overhead requires benchmarking.
- Legacy UAs may need isolated listener.

## Conflict Warnings
- Feature 017 depends on this for trunk TLS/SRTP.
- Feature 003 reads cert expiry for alerting.

## Retrieval Notes
- Keywords: TLS, SRTP, mTLS, certificate rotation, tls_reload, ca-tool, cipher.
- Related: 001, 003, 017.
