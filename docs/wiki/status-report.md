# Status Report

## Purpose

The Status Report module displays OpenSIPS status identifiers as reported by the `status_report` module. It provides a quick health overview of the SIP proxy without requiring MI command access.

## Access

- **Roles**: Any authenticated
- **Navigation**: System → Status Report

## Operations

| Operation | Permission | Description |
|---|---|---|
| View List | Any authenticated | Browse all status identifiers |
| Filter | Any authenticated | Filter by severity (error, warning, info) |

## Severity Levels

- `error` — requires immediate attention
- `warning` — should be investigated
- `info` — normal operational status

## Table Schema

- `status_report` (OpenSIPS status_report module)
  - `identifier` — status name
  - `severity` — error / warning / info
  - `timestamp` — last update time
  - `details` — human-readable description

## Related Modules

- MI Commands — fetch real-time status updates
- Statistics — view quantitative metrics
