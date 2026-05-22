# Research: End-to-End Encryption for SIP Signaling (TLS) and Media (SRTP)

## Decision: Self-Signed CA vs Public CA

**Decision**: Use project-owned self-signed CA infrastructure.

**Rationale**:
- Full control over certificate lifecycle
- No external dependency or cost
- Suitable for internal trunk authentication
- Industry standard for private SIP infrastructure

**Alternatives considered**:
- Let's Encrypt: requires public DNS, short validity (90 days)
- Public CA: expensive for many client certs
- No CA (self-signed certs): no revocation support

## Decision: RSA-4096 vs ECDSA P-256

**Decision**: Support both RSA-4096 and ECDSA P-256; default to ECDSA P-256 for performance.

**Rationale**:
- ECDSA P-256 offers equivalent security to RSA-3072 with better performance
- RSA-4096 provides maximum compatibility with legacy systems
- OpenSIPS 3.6 supports both
- TLS 1.3 prefers ECDSA

**Alternatives considered**:
- RSA-2048: considered weak for long-term use
- ECDSA P-384: overkill, 30% slower than P-256
- Ed25519: not widely supported in SIP stacks

## Decision: DTLS-SRTP vs SDES-SRTP

**Decision**: Support both SDES (RTP/SAVP) and DTLS-SRTP (UDP/TLS/RTP/SAVP); prefer DTLS-SRTP for WebRTC compatibility.

**Rationale**:
- SDES is simpler, widely supported in legacy SIP
- DTLS-SRTP provides perfect forward secrecy
- WebRTC requires DTLS-SRTP
- RTPengine supports both natively

**Alternatives considered**:
- SDES only: no forward secrecy
- DTLS only: may break legacy interop
- ZRTP: complex, limited support

## Decision: Certificate Rotation Strategy

**Decision**: Staged rotation with MI `tls_reload` and connection draining.

**Rationale**:
- Zero downtime for active calls
- New connections use new cert immediately
- Old cert remains valid for existing connections
- Rollback possible by reloading previous cert

**Alternatives considered**:
- Process restart: drops active calls
- Hot reload (zero-downtime): complex, requires proxy
- Dual-cert: not supported by OpenSIPS

## Decision: SRTP Cipher Preference

**Decision**: Prefer AES_GCM_256, fallback to AES_256_CM_HMAC_SHA1_80.

**Rationale**:
- AES-GCM provides authenticated encryption
- 256-bit key length for maximum security
- AES-CM is RFC 3711 compliant, widely supported
- RTPengine negotiates best common cipher

**Alternatives considered**:
- AES_128_CM_HMAC_SHA1_80: weaker, 128-bit
- NULL cipher: no encryption (debug only)
- F8 mode: complex, limited support

## Falsification Hypotheses

1. **Hypothesis**: Mutual TLS adds >50ms to call setup.
   **Test**: Measure INVITE-to-180 time with/without mTLS.
   **Mitigation**: If true, use session resumption or optimize cert chain.

2. **Hypothesis**: SRTP causes audio quality degradation.
   **Test**: MOS score measurement with/without SRTP.
   **Mitigation**: If MOS drops >0.1, optimize RTPengine CPU affinity.

3. **Hypothesis**: Certificate rotation causes client rejection.
   **Test**: Monitor trunk registration failures during rotation.
   **Mitigation**: If >1% failure, add overlap period with both certs valid.
