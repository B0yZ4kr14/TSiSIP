# TSiSIP — SIP Edge Proxy Platform

[![Docker](https://img.shields.io/badge/Docker-24.0+-blue.svg)](https://docker.com)
[![OpenSIPS](https://img.shields.io/badge/OpenSIPS-3.6%20LTS-green.svg)](https://opensips.org)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-15+-blue.svg)](https://postgresql.org)
[![License](https://img.shields.io/badge/License-Proprietary-red.svg)]()

## Overview

TSiSIP is a Docker-image-first SIP edge-proxy platform built on **OpenSIPS 3.6 LTS**. It acts as the public SIP signaling entry point and security boundary for a private, multi-tenant Asterisk PBX backend cluster.

## Architecture

```
Internet          Internet
   |                 |
5060/udp      10000-20000/udp
   |                 |
OpenSIPS ←→ RTPengine
   |                 |
   └──────┬──────────┘
          |
    PostgreSQL   Asterisk
```

## Features

- **SIP Proxy**: OpenSIPS 3.6 LTS with auth, routing, topology hiding
- **Media Relay**: RTPengine for SDP rewriting
- **Database**: PostgreSQL for subscribers, routing, audit
- **Control Panel**: Web UI with real-time monitoring
- **Dark Mode**: Toggle between light and dark themes
- **i18n**: English, Spanish, Portuguese
- **Mobile**: Responsive design for phones and tablets
- **API**: REST API v1 with Bearer auth, rate limiting, metrics/users/audit endpoints
- **User Management**: RBAC with 6 roles, password policy, session invalidation
- **MI Parity**: 100% OpenSIPS 3.6 MI command coverage via web UI
- **Security**: CSRF protection, role-based access, audit logging, hash chain integrity

## Quick Start

```bash
# Build
docker compose build

# Start
docker compose up -d

# Verify
curl http://localhost/health.php
```

## Documentation

- [User Guide](docs/wiki/OCP-USER-GUIDE.md)
- [Admin Guide](docs/wiki/OCP-ADMIN-GUIDE.md)
- [API Reference](docs/wiki/OCP-API-REFERENCE.md)
- [Deployment Guide](docs/wiki/DEPLOYMENT-GUIDE.md)
- [Troubleshooting](docs/wiki/TROUBLESHOOTING.md)
- [Changelog](docs/CHANGELOG-2026-05.md)

## Technology Stack

| Layer | Technology |
|-------|-----------|
| SIP Proxy | OpenSIPS 3.6 LTS |
| Database | PostgreSQL 15+ |
| Media Relay | RTPengine |
| PBX Backend | Asterisk |
| Web UI | PHP 8.2 + Apache |
| Container | Docker + Compose |

## Security

- SIP Digest authentication with HA1 hashes
- Topology hiding (backend IPs never exposed)
- Role-based access control (readonly → admin)
- CSRF tokens on all operations
- Audit logging for all actions
- Secure session cookies (HttpOnly, Secure, SameSite)

## Development

```bash
# Run tests
pytest tests/

# Integration tests
bash tests/integration/test-ocp-all.sh

# Build theme
./scripts/build-ocp-theme.sh
```

## License

Proprietary software. All rights reserved.

## Support

- Issues: GitHub Issues
- Email: devops@tsiapp.io
