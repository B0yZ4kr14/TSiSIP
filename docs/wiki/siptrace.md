# SIPtrace

## Purpose

The SIPtrace module provides a searchable viewer for SIP packet captures stored by the OpenSIPS `siptrace` module. It is essential for debugging signaling issues and compliance auditing.

## Access

- **Roles**: Any authenticated (view), admin (purge)
- **Navigation**: System → SIPtrace

## Operations

| Operation | Permission | Description |
|---|---|---|
| Search | Any authenticated | Filter traces by Call-ID, method, IP |
| View | Any authenticated | Browse trace records with pagination |
| Purge | admin | Delete traces older than N days |

## Filters

- Call-ID (partial match)
- Source IP / From field
- Destination IP / To field
- SIP Method (INVITE, REGISTER, BYE, OPTIONS, ACK, CANCEL)

## Table Schema

- `sip_trace` (OpenSIPS siptrace module)
  - `time_stamp`, `callid`, `method`, `status`
  - `fromip`, `toip`, `direction`
  - `msg` — full SIP message text

## Retention

Traces can accumulate rapidly. Admins should configure periodic purge via the Purge button or an external cron job.
