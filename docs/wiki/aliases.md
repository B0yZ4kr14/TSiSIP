# Aliases

## Purpose

The Aliases module manages SIP alias mappings for subscribers. Aliases allow a single subscriber to receive calls at multiple SIP addresses (e.g., `sip:alias@domain` → `sip:user@domain`).

## Access

- **Roles**: admin, devops, dentist, assistant, user, readonly
- **Navigation**: SIP Users → Aliases

## Operations

| Operation | Permission | Description |
|---|---|---|
| List | Any authenticated | View all alias mappings |
| Create | devops, admin | Add a new alias for a subscriber |
| Edit | devops, admin | Modify an existing alias |
| Delete | devops, admin | Remove an alias mapping |

## Table Schema

- `aliases` (OpenSIPS alias_db module)
  - `alias_username`, `alias_domain` — the alias address
  - `username`, `domain` — the target subscriber

## Related Modules

- Subscribers — aliases link to existing subscriber records
- Groups — aliases can be combined with group-based routing
