# Feature 007 Memory: End-to-End Encryption for SIP Signaling and Media

## Current Scope
Mutual TLS authentication for SIP trunks, self-signed CA infrastructure, zero-downtime certificate rotation via MI tls_reload, SRTP/DTLS-SRTP media encryption, and hardened cipher suites. Status: In Progress (53%).

## Relevant Decisions
- **Self-signed CA over public CA**: Project-owned trust chain for trunk authentication; CA key kept offline or in HSM.
- **MI tls_reload for rotation**: Loads new certificates without process restart; existing connections use old cert until natural closure.
- **RTPengine handles SRTP keys**: OpenSIPS relays SDP without inspecting keys; rtpengine_offer()/answer() manage a=crypto lines.
- **TLS 1.2 minimum, TLS 1.3 preferred**: Weak ciphers and TLS 1.0/1.1 explicitly disabled.

## Active Architecture Constraints
- OpenSIPS is the only TLS termination point (no separate reverse proxy).
- Certificates and keys injected via Docker secrets or read-only volumes; never committed to Git.
- trunk_ips table remains authoritative for mTLS and IP-trust decisions.
- All service and network names use lowercase snake_case.

## Accepted Deviations
- 53% complete as of 2026-05-19: TLS listener done; mTLS trunk enforcement, full SRTP validation, cipher hardening pending.

## Relevant Security Constraints
- Client certificate required (verify_client = 1) on trunk listener (5061/tcp).
- CA private key compromise is critical risk; maintain revocation list and plan emergency re-issuance.
- Downgrade attacks from TLS to UDP must be rejected; trunk-facing interfaces require encryption.
- SRTP ensures media confidentiality even if backend RTP addresses are discovered.

## Related Historical Lessons
- tls_reload may drop existing connections on some OpenSIPS builds — test thoroughly in staging; plan maintenance window as fallback.
- SRTP CPU overhead may degrade call capacity; benchmark with SRTP enabled.
- Weak legacy UAs may need a separate legacy TLS profile on an isolated listener (only if absolutely required).
- RTPengine --dtls-cert-file and --dtls-key-file must be mounted as secrets.

## Conflict Warnings
- Feature 017 (SIP Trunk Provider Integration) depends on this feature for TLS transport and SRTP modes on trunk legs.
- Feature 003 exporter reads certificate expiry for alerting.

## Retrieval Notes
- Search terms: TLS, SRTP, mTLS, certificate rotation, tls_reload, ca-tool, cipher hardening, DTLS-SRTP.
- Related features: 001 (OpenSIPS foundation), 003 (cert expiry alerting), 017 (trunk integration).
