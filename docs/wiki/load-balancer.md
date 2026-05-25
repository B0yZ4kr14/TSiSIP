# Load Balancer

## Purpose

The Load Balancer module manages resource-based routing destinations using the OpenSIPS `load_balancer` module. Unlike dispatcher (round-robin), load balancer routes based on available resources per destination.

## Access

- **Roles**: devops, admin
- **Navigation**: System → Load Balancer

## Operations

| Operation | Permission | Description |
|---|---|---|
| List | devops, admin | View all load-balanced destinations |
| Create | devops, admin | Add a new destination |
| Edit | devops, admin | Modify resources or probe mode |
| Delete | devops, admin | Remove a destination |
| Toggle Probe | devops, admin | Enable/disable health probing |

## Table Schema

- `load_balancer` (OpenSIPS load_balancer module)
  - `group_id` — destination group
  - `dst_uri` — SIP URI of the destination
  - `resources` — resource allocation string (e.g., `pstn=32;audio=100`)
  - `probe_mode` — health check mode (0=off, 1=on)

## Related Modules

- Dispatcher — alternative round-robin routing
- Dynamic Routing — LCR-based gateway selection
