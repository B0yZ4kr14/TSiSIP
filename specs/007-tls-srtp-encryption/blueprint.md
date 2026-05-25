# Blueprint — End-to-End Encryption for SIP Signaling (TLS) and Media (SRTP)

## Overview

Deliver mutual TLS authentication for SIP trunks, a self-signed CA infrastructure, zero-downtime certificate rotation, SRTP key exchange via SDP, and hardened cipher suites across the TSiSIP SIP edge and RTPengine stack.

## Requirements

- **FR-007-001**: Mutual TLS Authentication for SIP Trunks — `tls_mgm` with `verify_client = 1` on trunk listener (5061/tcp); client certs signed by project-owned CA; CRL support.
- **FR-007-002**: Self-Signed CA Infrastructure — `ca-tool` container generates root CA, intermediate CA, server cert, client certs; RSA 4096 or ECDSA P-256; SHA-256.
- **FR-007-003**: Certificate Rotation Without Downtime — stage new certs via Docker secrets; `tls_reload` via MI; existing connections continue with old cert.
- **FR-007-004**: SRTP Key Exchange via SDP — RTPengine `offer`/`answer` with `transport-protocol=RTP/SAVP` or `UDP/TLS/RTP/SAVP`; OpenSIPS relays SDP without inspecting keys.
- **FR-007-005**: Cipher Suite Hardening — TLS 1.3 preferred, TLS 1.2 minimum; ECDHE with P-256/RSA-4096, AES-256-GCM; SRTP: AES_256_CM_HMAC_SHA1_80 or AES_GCM_256.

## Architecture

- **Container Platform**: Docker Engine with Docker Compose V2; certificate management via Docker secrets.
- **TLS Stack**: `tls_mgm` (OpenSIPS TLS profiles); `verify_client = 1` (mutual auth); MI `tls_reload` (zero-downtime rotation); OpenSSL (CA infrastructure).
- **SRTP Stack**: RTPengine (key generation, encryption); OpenSIPS (relay without key inspection); DTLS-SRTP support.
- **Cipher Configuration**: TLS 1.3 preferred / TLS 1.2 minimum; ECDHE + AES-256-GCM; SRTP AES-256 preferred.

## Implementation Plan

### Phase 1 — CA Infrastructure
- CA tool container with OpenSSL.
- Root CA and intermediate CA generation.
- Server certificate for OpenSIPS; client certificates for trunks.
- CRL support.

### Phase 2 — TLS Configuration
- OpenSIPS `tls_mgm` module configuration.
- TLS listener on 5061/tcp.
- Mutual TLS for trunk interface.
- Certificate and key Docker secrets.

### Phase 3 — Certificate Rotation
- Staging new certificates via Docker secrets.
- MI `tls_reload` integration.
- Connection draining; rollback support.

### Phase 4 — SRTP Integration
- RTPengine SRTP configuration.
- SDP `a=crypto` line handling.
- DTLS-SRTP support.
- OpenSIPS SDP relay rules.

### Phase 5 — Cipher Hardening
- TLS cipher suite restriction.
- TLS version enforcement.
- SRTP cipher preference.
- Validation tests.

### Phase 6 — Integration & Testing
- End-to-end TLS registration test.
- Certificate rotation test.
- SRTP media encryption test.
- Cipher negotiation test.

## Tasks

**Phase 1 — CA Infrastructure**
- T1.1: Create CA tool container
- T1.2: Create certificate generation script
- T1.3: Create certificate rotation script
- T1.4: Add CA secrets to `docker-compose.yml`

**Phase 2 — TLS Configuration**
- T2.1: Configure OpenSIPS TLS module
- T2.2: Add TLS listener
- T2.3: Implement mutual TLS for trunks

**Phase 3 — Certificate Rotation**
- T3.1: Implement `tls_reload` via MI
- T3.2: Add rotation monitoring
- T3.3: Create rotation integration test

**Phase 4 — SRTP Integration**
- T4.1: Configure RTPengine for SRTP
- T4.2: Update OpenSIPS SDP routes for SRTP
- T4.3: Handle SDP re-INVITE for SRTP

**Phase 5 — Cipher Hardening**
- T5.1: Restrict TLS cipher suites
- T5.2: Restrict SRTP cipher suites
- T5.3: Create cipher negotiation test

**Phase 6 — Integration & Testing**
- T6.1: Create end-to-end TLS registration test
- T6.2: Add TLS/SRTP Grafana dashboard
- T6.3: Document TLS/SRTP runbook

## Validation

- `openssl verify -CAfile ca.crt server.crt` returns OK.
- `openssl s_client -connect` shows chain depth 2.
- Trunk registration with valid client cert succeeds; without cert fails at handshake.
- `tls_reload` completes in <1s; active calls remain stable.
- SDP contains `a=crypto` lines in both directions; RTPengine confirms activation.
- `openssl s_client -tls1_3` succeeds; `openssl s_client -tls1_1` fails.
- Re-INVITE shows continuous media; no key desynchronization.

## Risks & Dependencies

| Risk | Mitigation |
|---|---|
| Client certificate management overhead | Provide automated cert issuance script and clear documentation |
| `tls_reload` drops existing connections | Test thoroughly in staging; plan maintenance window as fallback |
| SRTP CPU overhead degrades call capacity | Benchmark with SRTP on; consider dedicated RTPengine hosts |
| Weak legacy UA cannot negotiate hardened cipher | Maintain separate legacy TLS profile on isolated listener if required |
| CA private key compromise | Store CA key offline or in HSM; maintain revocation list |

**Dependencies**: OpenSIPS 3.6 LTS (`tls_mgm`, `proto_tls`); RTPengine with SRTP/OpenSSL; OpenSSL ≥1.1.1; Docker Secrets.
