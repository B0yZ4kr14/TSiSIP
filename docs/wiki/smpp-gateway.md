# SMPP Gateway

## Purpose

The SMPP Gateway module manages SMS gateway (SMSC) definitions for the OpenSIPS `smpp` module. It enables SIP-to-SMS and SMS-to-SIP messaging.

## Access

- **Roles**: devops, admin
- **Navigation**: System → SMPP Gateway

## Operations

| Operation | Permission | Description |
|---|---|---|
| List | devops, admin | View all SMSC definitions |
| Create | devops, admin | Add a new SMSC |
| Edit | devops, admin | Modify SMSC parameters |
| Delete | devops, admin | Remove an SMSC |
| Toggle | devops, admin | Enable/disable a gateway |

## Table Schema

- `smpp` (OpenSIPS smpp module)
  - `name` — gateway name
  - `ip`, `port` — SMSC address
  - `system_id`, `password` — SMPP credentials
  - `system_type`, `src_addr` — originator settings
  - `session_type` — TX (transmit), RX (receive), or TRX

## Related Modules

- Trunk Providers — SMS gateways can be modeled as trunk endpoints
