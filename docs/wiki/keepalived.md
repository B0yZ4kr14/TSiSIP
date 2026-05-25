# Keepalived

## Purpose

The Keepalived module manages VRRP (Virtual Router Redundancy Protocol) instances for high-availability network failover. It ensures that a virtual IP is always available on an active node.

## Access

- **Roles**: devops, admin
- **Navigation**: System → Keepalived

## Operations

| Operation | Permission | Description |
|---|---|---|
| List | devops, admin | View all VRRP instances |
| Create | devops, admin | Add a new VRRP instance |
| Edit | devops, admin | Modify priority or state |
| Delete | devops, admin | Remove an instance |
| Toggle | devops, admin | Enable/disable an instance |

## Parameters

- `vrrp_id` — VRRP instance identifier
- `state` — `master` or `backup`
- `priority` — election priority (1-255)
- `interface` — network interface (e.g., `eth0`)
- `virtual_ip` — floating IP address

## Table Schema

- `keepalived` (OCP extension table)
  - `vrrp_id`, `state`, `priority`, `interface`, `virtual_ip`, `enabled`

## Related Modules

- Clusterer — node-level clustering (complements Keepalived at network layer)
