# Requirements Checklist: End-to-End Encryption for SIP Signaling and Media

## Functional Requirements

- [x] FR-001: Mutual TLS Authentication for SIP Trunks — OpenSIPS tls_mgm with verify_client=1 on 5061/tcp and CRL checking.
- [x] FR-002: Self-Signed CA Infrastructure — RSA 4096/ECDSA P-256 CA, server, and client certificates with SHA-256.
- [x] FR-003: Certificate Rotation Without Downtime — Docker secrets staging and MI tls_reload with <1s execution.
- [x] FR-004: SRTP Key Exchange via SDP — RTPengine RTP/SAVP and DTLS-SRTP negotiation with a=crypto lines.
- [x] FR-005: Cipher Suite Hardening — TLS 1.2 minimum, TLS 1.3 preferred, AES-256-GCM cipher allowlists.

## Success Criteria

- [x] SC-001: TLS handshake success rate for valid trunks 100%.
- [x] SC-002: TLS handshake failure for missing/invalid client cert 100%.
- [x] SC-003: Certificate rotation downtime 0 seconds.
- [x] SC-004: SRTP media encryption coverage 100% of TLS-signaled calls.
- [x] SC-005: Weak cipher negotiation blocked 100%.
- [x] SC-006: Time to reload TLS profile ≤ 1 second.

## Risks

- [x] R-001: Client certificate management overhead mitigated by automated issuance scripts.
- [x] R-002: tls_reload connection drops mitigated by staging tests and maintenance window fallback.
- [x] R-003: SRTP CPU overhead mitigated by benchmarking and dedicated hosts if needed.
- [x] R-004: Legacy UA incompatibility mitigated by optional isolated legacy listener.
- [x] R-005: CA key compromise mitigated by offline/HSM storage and CRL maintenance.

**Status: PASS**
