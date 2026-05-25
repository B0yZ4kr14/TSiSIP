# Clusterer

## Purpose

The Clusterer module manages high-availability clustering for OpenSIPS. It defines cluster nodes, their URLs, and failure-detection parameters for automatic failover.

## Access

- **Roles**: devops, admin
- **Navigation**: System → Clusterer

## Operations

| Operation | Permission | Description |
|---|---|---|
| List | devops, admin | View all cluster nodes |
| Create | devops, admin | Add a new cluster node |
| Edit | devops, admin | Modify node parameters |
| Delete | devops, admin | Remove a node |

## Table Schema

- `clusterer` (OpenSIPS clusterer module)
  - `cluster_id`, `node_id` — cluster and node identifiers
  - `url` — node communication URL
  - `state` — node state (active/inactive)
  - `no_ping_retries` — failure detection threshold
  - `priority`, `sip_addr`, `flags`, `description`

## Related Modules

- Status Report — view cluster health identifiers
- MI Commands — trigger cluster checks manually
