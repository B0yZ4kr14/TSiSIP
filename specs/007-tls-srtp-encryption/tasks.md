# Tasks: End-to-End Encryption for SIP Signaling (TLS) and Media (SRTP)
**Last Updated**: 2026-05-19


## Phase 1 — CA Infrastructure

### [completed] T1.1: Create CA tool container
**Description**: Create `docker/ca-tool/Dockerfile` from `alpine:3.19` with `openssl`. Add `ca-init.sh` that generates root CA (RSA 4096, SHA-256), intermediate CA, and creates directory structure (`certs/`, `private/`, `crl/`).
**Phase**: 1
**Depends on**: —
**Parallel**: No
**Acceptance**: `docker build -t tsisip/ca-tool:test docker/ca-tool/` succeeds; `ca-init.sh` generates valid root CA.

### [completed] T1.2: Create certificate generation script
**Description**: Create `docker/ca-tool/cert-gen.sh` that: generates server cert for OpenSIPS (CN=tsiapp.io, SAN=DNS:tsiapp.io,IP:OPENSIPS_IP), generates client certs for trunks (CN=trunk_id), signs with intermediate CA, outputs PEM files.
**Phase**: 1
**Depends on**: T1.1
**Parallel**: No
**Acceptance**: `openssl verify -CAfile ca.crt server.crt` returns OK; client cert chain validates.

### [completed] T1.3: Create certificate rotation script
**Description**: Create `docker/ca-tool/cert-rotate.sh` that: generates new server cert with incremented serial, updates CRL, stages files to `secrets/` directory, provides rollback capability.
**Phase**: 1
**Depends on**: T1.2
**Parallel**: No
**Acceptance**: Rotation generates new cert with different serial; rollback restores previous.

### [completed] T1.4: Add CA secrets to docker-compose.yml
**Description**: Add Docker secrets for `ca.crt`, `server.crt`, `server.key`, `crl.pem`. Mount to OpenSIPS container. Ensure `ca.key` is NOT mounted (kept offline).
**Phase**: 1
**Depends on**: T1.3
**Parallel**: No
**Acceptance**: `docker compose config` validates; secrets are mounted read-only.

## Phase 2 — TLS Configuration

### [completed] T2.1: Configure OpenSIPS TLS module
**Description**: Update `opensips/opensips.cfg.tpl` with `loadmodule "tls_mgm.so"`, `modparam("tls_mgm", "server_domain", "default")`, `modparam("tls_mgm", "tls_method", "[default]TLSv1.3")`, `modparam("tls_mgm", "verify_cert", "[default]1")`, `modparam("tls_mgm", "require_cert", "[default]1")`, `modparam("tls_mgm", "certificate", "[default]/run/secrets/server.crt")`, `modparam("tls_mgm", "private_key", "[default]/run/secrets/server.key")`, `modparam("tls_mgm", "ca_list", "[default]/run/secrets/ca.crt")`, `modparam("tls_mgm", "crl", "[default]/run/secrets/crl.pem")`.
**Phase**: 2
**Depends on**: T1.4
**Parallel**: No
**Acceptance**: `opensips -c` passes; TLS profile loaded.

### [completed] T2.2: Add TLS listener
**Description**: Add `socket=tls:OPENSIPS_LISTEN_IP:5061` to `opensips/opensips.cfg.tpl`. Update advertised address. Add TLS-specific route handling.
**Phase**: 2
**Depends on**: T2.1
**Parallel**: No
**Acceptance**: `netstat -tlnp` shows 5061/tcp listening inside container.

### [completed] T2.3: Implement mutual TLS for trunks
**Description**: Add route logic: if source is in `trunk_ips` table, require client certificate (`$tls_peer_subject_cn`). Reject if no cert or untrusted CA. Allow non-trunk traffic without client cert.
**Phase**: 2
**Depends on**: T2.2
**Parallel**: No
**Acceptance**: Trunk registration with valid client cert succeeds; without cert fails at handshake.
**Implementation**: Added `trunk_ips` table to PostgreSQL schema. Updated `tls_mgm` to `require_cert=0` (allow non-trunk without cert). Added `TRUNK_VERIFY` route in `opensips.cfg.tpl` that checks source IP against `trunk_ips` and enforces `$tls_peer_subject_cn` for trunk connections.

## Phase 3 — Certificate Rotation

### [completed] T3.1: Implement tls_reload via MI
**Description**: Add `tls_reload` MI command support. Create script `scripts/tls-reload.sh` that calls `opensips-cli -x mi tls_reload` and verifies new cert is active.
**Phase**: 3
**Depends on**: T2.3
**Parallel**: No
**Acceptance**: `tls_reload` completes in <1s; active calls remain stable.

### [completed] T3.2: Add rotation monitoring
**Description**: Add Prometheus metric: `opensips_tls_certificate_expiry_timestamp`. Add alert rule: critical if expiry < 30 days.
**Phase**: 3
**Depends on**: T3.1
**Parallel**: No
**Acceptance**: Metric shows correct expiry; alert fires at threshold.
**Implementation**: Updated `opensips-exporter/exporter.py` to read cert expiry via OpenSSL subprocess and expose `opensips_tls_certificate_expiry_timestamp`. Added `TLS_CERT_PATH` env var and mounted `server.crt` secret into exporter container. Added `OpenSIPSTLSCertificateExpiringSoon` alert rule to Prometheus. Created `scripts/cert-expiry-monitor.sh` for standalone checks.

### [completed] T3.3: Create rotation integration test
**Description**: Create `tests/integration/test_cert_rotation.py` that: starts TLS call, rotates cert, reloads, verifies new handshake uses new cert, verifies active call stable.
**Phase**: 3
**Depends on**: T3.2
**Parallel**: No
**Acceptance**: Test passes; zero dropped calls during rotation.

## Phase 4 — SRTP Integration

### [completed] T4.1: Configure RTPengine for SRTP
**Description**: Update RTPengine command to include `—dtls-passive`, `—dtls-cert-file`, `—dtls-key-file`. Enable SRTP cipher `AES_256_CM_HMAC_SHA1_80` and `AES_GCM_256`.
**Phase**: 4
**Depends on**: T3.3
**Parallel**: No
**Acceptance**: RTPengine logs show SRTP support; DTLS cert loads.
**Implementation**: Updated `docker-compose.yml` rtpengine service command with `--dtls-cert-file`, `--dtls-key-file`, `--dtls-passive`, and `--srtp-ciphers=AES_GCM_256:AES_256_CM_HMAC_SHA1_80`. Mounted `server.crt` and `server.key` secrets into rtpengine container.

### [completed] T4.2: Update OpenSIPS SDP routes for SRTP
**Description**: Modify INVITE route to use `rtpengine_offer("transport-protocol=RTP/SAVP")` for TLS-signaled calls. Use `rtpengine_answer()` for 200 OK. Preserve `a=crypto` lines.
**Phase**: 4
**Depends on**: T4.1
**Parallel**: No
**Acceptance**: SDP contains `a=crypto` lines in both directions; RTPengine confirms activation.
**Implementation**: Updated `HANDLE_INVITE` route in `opensips.cfg.tpl` to use `rtpengine_offer("RTP/SAVP replace-origin replace-session-connection")` for TLS connections and plain RTP for non-TLS. Added `rtpengine_answer()` in `onreply_route` for 2xx SDP responses.

### [completed] T4.3: Handle SDP re-INVITE for SRTP
**Description**: Add logic for hold/resume/re-INVITE: call `rtpengine_offer()`/`answer()` with updated SDP. Ensure SRTP context updates without media dropout.
**Phase**: 4
**Depends on**: T4.2
**Parallel**: No
**Acceptance**: Re-INVITE test shows continuous media; no key desynchronization.
**Implementation**: Added `SRTP_REOFFER` route called for in-dialog INVITEs with SDP. Added `rtpengine_delete()` on BYE for session cleanup. `onreply_route` handles `rtpengine_answer()` for re-INVITE 2xx responses, preserving existing SRTP context.

## Phase 5 — Cipher Hardening

### [completed] T5.1: Restrict TLS cipher suites
**Description**: Update `tls_mgm` config: `cipher_list="ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:TLS_AES_256_GCM_SHA384"`. Disable TLS 1.0/1.1.
**Phase**: 5
**Depends on**: T4.3
**Parallel**: No
**Acceptance**: `openssl s_client -tls1_3` succeeds; `openssl s_client -tls1_1` fails.
**Implementation**: Updated `tls_mgm` `ciphers_list` to include both AES-128-GCM and AES-256-GCM with CBC and weak cipher exclusion. Changed `tls_method` from `TLSv1_2` to `TLSv1_2:TLSv1_3` to disable TLS 1.0/1.1 while retaining TLS 1.2 and enabling TLS 1.3.

### [completed] T5.2: Restrict SRTP cipher suites
**Description**: Update RTPengine config: `—srtp-ciphers=AES_256_CM_HMAC_SHA1_80:AES_GCM_256`. Reject weak ciphers.
**Phase**: 5
**Depends on**: T5.1
**Parallel**: No
**Acceptance**: RTPengine logs show only allowed SRTP ciphers negotiated.
**Implementation**: Added `--srtp-ciphers=AES_GCM_256:AES_256_CM_HMAC_SHA1_80` to rtpengine command in `docker-compose.yml`. AES-GCM is listed first (preferred) with AES-ICM as fallback.

### [completed] T5.3: Create cipher negotiation test
**Description**: Create `tests/integration/test_cipher_hardening.py` that: tests TLS 1.3 negotiation, tests TLS 1.1 rejection, tests weak cipher rejection, tests SRTP cipher preference.
**Phase**: 5
**Depends on**: T5.2
**Parallel**: No
**Acceptance**: All tests pass; only strong ciphers accepted.

## Phase 6 — Integration & Testing

### [completed] T6.1: Create end-to-end TLS registration test
**Description**: Create `tests/integration/test_tls_srtp.py` that: registers trunk via mutual TLS, places call with SRTP, verifies encryption in packet capture, verifies no plaintext SIP on 5061.
**Phase**: 6
**Depends on**: T5.3
**Parallel**: No
**Acceptance**: Test passes; packet capture confirms encrypted signaling and media.

### [completed] T6.2: Add TLS/SRTP Grafana dashboard
**Description**: Create `docker/grafana/provisioning/dashboards/tsisip/tls-srtp.json` with panels: TLS handshake rate (graph), SRTP sessions active (stat), certificate expiry (stat), cipher distribution (pie chart), client cert validation failures (table).
**Phase**: 6
**Depends on**: T6.1
**Parallel**: No
**Acceptance**: Dashboard shows real-time TLS/SRTP metrics.

### [completed] T6.3: Document TLS/SRTP runbook
**Description**: Update `docs/TSiSIP-OPERATOR-RUNBOOK.md` with: certificate generation, rotation procedure, troubleshooting TLS handshake failures, SRTP key issues, cipher compatibility, CRL updates.
**Phase**: 6
**Depends on**: T6.2
**Parallel**: No
**Acceptance**: Runbook contains actionable procedures for all TLS/SRTP scenarios.
