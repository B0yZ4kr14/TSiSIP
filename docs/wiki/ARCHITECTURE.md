# TSiSIP Architecture

## Overview

TSiSIP uses a microservices architecture with Docker containers.

## Components

### OpenSIPS
- **Role**: SIP edge proxy
- **Ports**: 5060/udp, 5060/tcp
- **Functions**: Auth, routing, topology hiding

### PostgreSQL
- **Role**: Database
- **Functions**: Auth, routing metadata, audit

### RTPengine
- **Role**: Media relay
- **Ports**: 10000-20000/udp
- **Functions**: RTP relay, SDP rewriting

### Asterisk
- **Role**: PBX backend
- **Network**: Internal only

### OCP
- **Role**: Web control panel
- **Port**: 80/tcp
- **Functions**: Management, monitoring

## Networks

| Network | Members | External |
|---------|---------|----------|
| sip_edge | OpenSIPS, RTPengine | Yes |
| sip_internal | OpenSIPS, RTPengine, Asterisk | No |
| db_internal | OpenSIPS, PostgreSQL | No |

## Data Flow

```
SIP Client → OpenSIPS → Asterisk
                ↓
            PostgreSQL
                ↓
                OCP
```

## Security

- No exposed database ports
- No exposed Asterisk ports
- Topology hiding enabled
- HA1 hashes only
- CSRF protection
- Audit logging

## Scalability

- Stateless OpenSIPS
- Shared database
- Dispatcher for load balancing
- Cache layer

## Monitoring

- Health endpoint
- Prometheus metrics
- Grafana dashboards
- Audit logs
