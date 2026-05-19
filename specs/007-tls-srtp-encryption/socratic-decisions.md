# Socratic Decision Log — Feature 007: TLS/SRTP Encryption (Wave 4)

## T2.3: Mutual TLS for Trunks
**Q**: Client cert verification on every request or session?  
**A**: Per-session (TLS handshake) for efficiency; cert validation at connection time.  
**Rationale**: Re-verifying on every SIP request adds unnecessary overhead. The TLS handshake already validates the certificate chain against the CA and CRL. Once the TLS session is established, the peer identity is trusted for the lifetime of that connection. Application-layer enforcement via `$tls_peer_subject_cn` in the OpenSIPS script provides defense-in-depth without redundant re-verification.

## T3.2: Rotation Monitoring
**Q**: Proactive schedule or reactive alert?  
**A**: Proactive monitoring with 30-day expiry alert.  
**Rationale**: Waiting for certificates to expire before acting guarantees service disruption. A 30-day proactive alert gives operators time to stage new certificates, run integration tests, and execute `tls_reload` during a maintenance window if needed.

## T4.1: Configure RTPengine for SRTP
**Q**: SDES, DTLS-SRTP, or both?  
**A**: SDES first (simpler, broader client support); DTLS-SRTP as Phase 2.  
**Rationale**: SDES key exchange via `a=crypto` lines in SDP is universally supported by legacy SIP trunks and most PBX backends. DTLS-SRTP requires WebRTC-style `fingerprint` attributes and is primarily needed for WebRTC clients. RTPengine is configured with `--dtls-cert-file` and `--dtls-key-file` so DTLS-SRTP can be enabled later without restarting the media relay.

## T4.2: Update OpenSIPS SDP Routes for SRTP
**Q**: Replace crypto lines or append?  
**A**: Replace to ensure canonical crypto offer.  
**Rationale**: Appending crypto lines can lead to ambiguous SDP where the UAs negotiate different ciphers. By replacing the SDP transport to `RTP/SAVP` and letting RTPengine generate the canonical `a=crypto` lines, we guarantee a single, consistent SRTP offer that matches our cipher hardening policy.

## T4.3: Handle SDP re-INVITE for SRTP
**Q**: Maintain same key or renegotiate?  
**A**: Maintain same key for re-INVITEs within same dialog; renegotiate only on explicit session refresh.  
**Rationale**: Re-INVITEs for hold/resume should not break existing media streams. RTPengine manages SRTP contexts by Call-ID and tag; calling `rtpengine_offer()`/`answer()` on a re-INVITE preserves the existing crypto context unless the SDP explicitly signals a new key. This prevents media dropout during hold/resume transitions.

## T5.1: Restrict TLS Cipher Suites
**Q**: AES-256-GCM only or also AES-128-GCM?  
**A**: Allow both (AES-128-GCM is secure and faster); exclude CBC and weak ciphers.  
**Rationale**: AES-128-GCM provides ~128 bits of security strength, which is considered secure until post-quantum cryptanalysis becomes practical. It is measurably faster on older hardware. AES-256-GCM is retained for high-assurance deployments. CBC mode and weak ciphers (aNULL, MD5, DSS) are explicitly excluded to prevent padding oracle and downgrade attacks.

## T5.2: Restrict SRTP Cipher Suites
**Q**: AES-ICM or AES-GCM?  
**A**: AES-GCM for SRTP (authenticated encryption); AES-ICM as fallback.  
**Rationale**: AES-GCM provides built-in authentication, eliminating the separate HMAC-SHA1 step required by AES-ICM. This reduces CPU overhead and packet expansion. AES-ICM with HMAC-SHA1-80 is retained as a fallback for legacy endpoints that do not yet support AES-GCM.
