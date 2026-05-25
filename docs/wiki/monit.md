# Monit

## Purpose

The Monit module configures process and system monitoring checks. It defines what services to monitor and where to send alerts when checks fail.

## Access

- **Roles**: devops, admin
- **Navigation**: System → Monit

## Operations

| Operation | Permission | Description |
|---|---|---|
| List | devops, admin | View all monitoring checks |
| Create | devops, admin | Add a new check |
| Edit | devops, admin | Modify check parameters |
| Delete | devops, admin | Remove a check |
| Toggle | devops, admin | Enable/disable a check |

## Check Types

- `process` — ensure a process is running
- `file` — monitor file existence/size
- `filesystem` — monitor disk usage
- `host` — network reachability
- `network` — interface status
- `program` — custom script execution
- `system` — overall system health

## Table Schema

- `monit` (OCP extension table)
  - `name`, `process_name`, `check_type`, `alert_email`, `enabled`

## Related Modules

- Status Report — view aggregated system status
