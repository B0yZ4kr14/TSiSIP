# Dynamic Routing

## Purpose

The Dynamic Routing module manages Least Cost Routing (LCR) and dynamic gateway selection via the OpenSIPS `drouting` module. It supports gateway pools, rule-based prefix matching, and time-based routing.

## Access

- **Roles**: devops, admin
- **Navigation**: System → Dynamic Routing

## Operations

| Operation | Permission | Description |
|---|---|---|
| List Gateways | devops, admin | View all SIP gateways |
| Create Gateway | devops, admin | Add a new gateway |
| Edit Gateway | devops, admin | Modify gateway parameters |
| Delete Gateway | devops, admin | Remove a gateway |
| List Rules | devops, admin | View routing rules |
| Create Rule | devops, admin | Add a prefix-based routing rule |
| Edit Rule | devops, admin | Modify rule priority or gateway list |
| Delete Rule | devops, admin | Remove a routing rule |

## Table Schema

- `dr_gateways` — gateway definitions (`type`, `address`, `strip`, `pri_prefix`, `probe_mode`)
- `dr_rules` — routing rules (`groupid`, `prefix`, `timerec`, `priority`, `gwlist`)
- `dr_carriers` — carrier definitions (`carrierid`, `gwlist`)
- `dr_groups` — subscriber-to-group mappings

## Related Modules

- Dispatcher — alternative routing method using round-robin
- Load Balancer — resource-based routing
