# UAC Registrant

## Purpose

The UAC Registrant module manages client-side SIP registrations. OpenSIPS acts as a UAC (User Agent Client) and registers with upstream SIP providers or PBXs on behalf of the system.

## Access

- **Roles**: devops, admin
- **Navigation**: System → UAC Registrant

## Operations

| Operation | Permission | Description |
|---|---|---|
| List | devops, admin | View all UAC registrations |
| Create | devops, admin | Add a new registration |
| Edit | devops, admin | Modify registration parameters |
| Delete | devops, admin | Remove a registration |

## Table Schema

- `uacreg` (OpenSIPS uac_registrant module)
  - `l_uuid` — local unique identifier
  - `l_username`, `l_domain` — local credentials
  - `r_username`, `r_domain` — remote credentials
  - `auth_proxy` — proxy/registrar to register with
  - `expires` — registration expiry (seconds)
  - `flags` — behavior flags

## Related Modules

- Trunk Providers — UAC registrations can represent trunk endpoints
