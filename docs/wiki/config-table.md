# Config Table

## Purpose

The Config Table module provides runtime database-driven configuration for OpenSIPS. Key-value pairs stored here can be queried by the `cfgutils` module during request processing.

## Access

- **Roles**: devops, admin
- **Navigation**: System → Config Table

## Operations

| Operation | Permission | Description |
|---|---|---|
| List | devops, admin | View all config entries |
| Create | devops, admin | Add a new config entry |
| Edit | devops, admin | Modify a config value |
| Delete | devops, admin | Remove a config entry |

## Table Schema

- `config` (OpenSIPS cfgutils module)
  - `name` — unique config key
  - `value` — config value
  - `category` — grouping category (default: `general`)
  - `description` — human-readable description

## Related Modules

- MI Commands — config changes may require module reload
