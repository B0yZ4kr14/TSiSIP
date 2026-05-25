# Groups

## Purpose

The Groups module manages group-based ACL for SIP subscribers. Groups enable policy-based routing and access control (e.g., `local`, `longdistance`, `international`).

## Access

- **Roles**: admin, devops, dentist, assistant, user, readonly
- **Navigation**: SIP Users → Groups

## Operations

| Operation | Permission | Description |
|---|---|---|
| List | Any authenticated | View all group memberships |
| Create | devops, admin | Add a subscriber to a group |
| Edit | devops, admin | Change group assignment |
| Delete | devops, admin | Remove a subscriber from a group |

## Table Schema

- `grp` (OpenSIPS group module)
  - `username`, `domain` — the subscriber
  - `grp` — the group name

## Related Modules

- Subscribers — group memberships reference subscribers
- Aliases — can be used together for fine-grained routing
