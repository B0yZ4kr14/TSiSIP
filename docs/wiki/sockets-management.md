# Sockets Management

## Purpose

The Sockets Management module configures dynamic socket definitions for OpenSIPS. This controls which network interfaces and protocols OpenSIPS listens on.

## Access

- **Roles**: devops, admin
- **Navigation**: System → Sockets Management

## Operations

| Operation | Permission | Description |
|---|---|---|
| List | devops, admin | View all configured sockets |
| Create | devops, admin | Add a new listening socket |
| Edit | devops, admin | Modify socket parameters |
| Delete | devops, admin | Remove a socket definition |

## Supported Protocols

- `udp`, `tcp`, `tls`, `ws`, `wss`, `sctp`, `hep`

## Table Schema

- `sockets` (OCP 9.3.6+ extension)
  - `proto` — transport protocol
  - `address` — bind address
  - `port` — listen port
  - `options` — additional socket options

## Related Modules

- TLS Management — TLS sockets require certificate configuration
