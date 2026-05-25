# RTPProxy

## Purpose

The RTPProxy module manages legacy RTP proxy socket definitions for the OpenSIPS `rtpproxy` module. RTPProxy is an alternative to RTPEngine for media relay.

## Access

- **Roles**: devops, admin
- **Navigation**: System → RTPProxy

## Operations

| Operation | Permission | Description |
|---|---|---|
| List | devops, admin | View all RTPProxy instances |
| Create | devops, admin | Add a new RTPProxy socket |
| Delete | devops, admin | Remove an instance |

## Table Schema

- `rtpproxy_sockets` (OpenSIPS rtpproxy module)
  - `rtpproxy_sock` — socket address (e.g., `udp:10.0.0.1:7722`)
  - `set_id` — socket set identifier

## Related Modules

- RTPEngine — modern replacement for RTPProxy (preferred for TSiSIP)
