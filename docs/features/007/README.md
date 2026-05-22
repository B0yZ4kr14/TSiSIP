# Feature 007: TLS/SRTP Encryption

## Overview

End-to-end encryption for SIP signaling (TLS) and media (SRTP) across the TSiSIP stack.

## Components

| Component | Technology | Purpose |
|-----------|-----------|---------|
| TLS Management | OpenSIPS tls_mgm | TLS profiles and certificate handling |
| TLS Transport | OpenSIPS proto_tls | TLS listener on 5061/tcp |
| CA Infrastructure | OpenSSL | Root CA, intermediate CA, certificate generation |
| Certificate Rotation | MI tls_reload | Zero-downtime certificate update |
| SRTP Negotiation | RTPengine | Media encryption via SDP |

## Architecture

```
SIP Trunk (mTLS)
  |-- TLS 1.3 + Client Certificate
  +-- OpenSIPS:5061/tcp
        |-- tls_mgm verifies client cert against CA
        |-- Auth via auth_db
        +-- RTPengine (SRTP)
              |-- a=crypto lines in SDP
              +-- Encrypted RTP stream
```

## CA Infrastructure

### Certificate Generation

```bash
# Initialize CA
docker run --rm -v /tmp/ca-data:/ca tsisip/ca-tool:test ca-init.sh

# Generate server certificate
docker run --rm -v /tmp/ca-data:/ca tsisip/ca-tool:test \
  cert-gen.sh server --cn tsiapp.io --san "DNS:tsiapp.io"

# Generate client certificate for trunk
docker run --rm -v /tmp/ca-data:/ca tsisip/ca-tool:test \
  cert-gen.sh client --cn trunk-carrier-01
```

### Certificate Rotation

```bash
# Stage new certificates
docker run --rm -v /tmp/ca-data:/ca -v ./secrets:/ca/secrets tsisip/ca-tool:test \
  cert-rotate.sh stage

# Reload OpenSIPS TLS profile (zero downtime)
./scripts/tls-reload.sh

# Verify new certificate
openssl x509 -in secrets/server.crt -noout -dates -serial
```

## OpenSIPS TLS Configuration

### Listener

```
socket=tls:OPENSIPS_LISTEN_IP:5061 as HOST_PUBLIC_IP:5061
```

### TLS Profile

```
modparam("tls_mgm", "server_domain",
  "dom=default;cert=/run/secrets/server.crt;pkey=/run/secrets/server.key;
   ca=/run/secrets/ca.crt;verify_cert=1;require_cert=0;crl=/run/secrets/crl.pem")
```

### Security Settings

- TLS 1.3 preferred, TLS 1.2 minimum
- Cipher suites: ECDHE + AES-256-GCM
- Client certificate verification for trunks
- CRL checking enabled

## Docker Secrets

| Secret | File | Mounted To |
|--------|------|-----------|
| ca.crt | ./secrets/ca.crt | /run/secrets/ca.crt |
| server.crt | ./secrets/server.crt | /run/secrets/server.crt |
| server.key | ./secrets/server.key | /run/secrets/server.key |
| crl.pem | ./secrets/crl.pem | /run/secrets/crl.pem |

**Important**: ca.key (root CA private key) is NEVER mounted. It remains offline.

## SRTP Configuration

RTPengine handles SRTP negotiation via SDP:

- **SDES-SRTP**: `RTP/SAVP` transport protocol
- **DTLS-SRTP**: `UDP/TLS/RTP/SAVP` transport protocol
- RTPengine inserts `a=crypto` lines into SDP
- OpenSIPS relays SDP without inspecting keys

## Testing

```bash
# Run integration tests
pytest tests/integration/test_tls_srtp.py -v

# Verify TLS listener
docker compose exec opensips ss -tlnp | grep 5061

# Test certificate chain
docker compose exec opensips \
  openssl verify -CAfile /run/secrets/ca.crt /run/secrets/server.crt
```

## Files

- `docker/ca-tool/Dockerfile` - CA tool container
- `docker/ca-tool/ca-init.sh` - CA initialization
- `docker/ca-tool/cert-gen.sh` - Certificate generation
- `docker/ca-tool/cert-rotate.sh` - Certificate rotation
- `scripts/tls-reload.sh` - TLS profile reload
- `opensips/opensips.cfg.tpl` - TLS configuration
- `tests/integration/test_tls_srtp.py` - Integration tests
