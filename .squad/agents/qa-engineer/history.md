# Project Context

- **Project:** TSiSIP
- **Created:** 2026-05-24

## Core Context

Agent QA Engineer initialized and ready for work.

## Recent Updates

📌 Team initialized on 2026-05-24
📌 Hard-coded IP remediation completed — all test files parameterized via TEST_IP (2026-05-24)

## Learnings

- SIP tests must never embed hard-coded Docker network IPs
- Parameterize test IPs via `TEST_IP` env var with `127.0.0.1` default
- Brownfield findings must be tracked by severity with cycle-based remediation
