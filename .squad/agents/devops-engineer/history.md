# Project Context

- **Project:** TSiSIP
- **Created:** 2026-05-24

## Core Context

Agent DevOps Engineer initialized and ready for work.

## Recent Updates

📌 Team initialized on 2026-05-24
📌 HEALTHCHECK coverage completed — 15/15 Dockerfiles (2026-05-24)

## Learnings

- All Docker base images must be SHA-pinned for supply-chain determinism
- `userland-proxy: false` is required for RTPengine UDP port range
- `cap_drop: [ALL]` with minimal `cap_add` is the hardening baseline
