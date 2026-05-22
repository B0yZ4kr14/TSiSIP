# Implementation Plan: End-to-End Encryption for SIP Signaling (TLS) and Media (SRTP)

## Overview

This plan translates the feature specification into an executable implementation roadmap for mutual TLS authentication, certificate infrastructure, zero-downtime rotation, SRTP key exchange, and cipher hardening across the TSiSIP stack.

---

## Architecture & Stack Choices

### Container Platform
- **Docker Engine** with Docker Compose V2
- Certificate management via Docker secrets
- CA tool container for certificate generation

### TLS Stack
| Component | Module/Tool | Purpose |
|---|---|---|
| TLS Management | `tls_mgm` | OpenSIPS TLS profiles |
| Mutual Auth | `verify_client = 1` | Client certificate validation |
| Certificate Rotation | MI `tls_reload` | Zero-downtime cert update |
| CA Infrastructure | OpenSSL | Root CA, intermediate, certs |

### SRTP Stack
| Component | Tool | Purpose |
|---|---|---|
| SRTP Negotiation | RTPengine | Key generation, encryption |
| SDP Handling | OpenSIPS | Relay without key inspection |
| DTLS-SRTP | RTPengine | UDP/TLS/RTP/SAVP support |

### Cipher Configuration
- TLS 1.3 preferred, TLS 1.2 minimum
- ECDHE with P-256 or RSA-4096
- AES-256-GCM
- SRTP: AES_256_CM_HMAC_SHA1_80 or AES_GCM_256

---

## Implementation Phases

### Phase 1 — CA Infrastructure
- CA tool container with OpenSSL
- Root CA and intermediate CA generation
- Server certificate for OpenSIPS
- Client certificate generation for trunks
- CRL support

### Phase 2 — TLS Configuration
- OpenSIPS `tls_mgm` module configuration
- TLS listener on 5061/tcp
- Mutual TLS for trunk interface
- Certificate and key Docker secrets

### Phase 3 — Certificate Rotation
- Staging new certificates via Docker secrets
- MI `tls_reload` integration
- Connection draining
- Rollback support

### Phase 4 — SRTP Integration
- RTPengine SRTP configuration
- SDP `a=crypto` line handling
- DTLS-SRTP support
- OpenSIPS SDP relay rules

### Phase 5 — Cipher Hardening
- TLS cipher suite restriction
- TLS version enforcement
- SRTP cipher preference
- Validation tests

### Phase 6 — Integration & Testing
- End-to-end TLS registration test
- Certificate rotation test
- SRTP media encryption test
- Cipher negotiation test

---

## File Structure

```
docker/
  ca-tool/
    Dockerfile
    ca-init.sh               # Initialize CA infrastructure
    cert-gen.sh              # Generate server/client certs
    cert-rotate.sh           # Certificate rotation script
secrets/
  ca.crt                     # Root CA certificate
  ca.key                     # Root CA private key (protected)
  server.crt                 # OpenSIPS server certificate
  server.key                 # OpenSIPS server private key
  crl.pem                    # Certificate revocation list
opensips/
  opensips.cfg.tpl           # Main config with embedded TLS profile configuration
```

---

## Validation Gates

| Gate | Check | Command |
|---|---|---|
| CA | Certificate chain valid | `openssl verify -CAfile` |
| TLS | Handshake succeeds | `openssl s_client -connect` |
| mTLS | Client cert required | TLS handshake test without client cert (openssl s_client or container-based tool) |
| Rotation | Reload <1s | `opensips-cli -x mi tls_reload` |
| SRTP | Encryption active | RTPengine logs |
| Cipher | Weak ciphers rejected | `openssl s_client -tls1_1` |
