# Known Risks

- Do not store secrets, API keys, tokens, credentials, MCP env/header values, or private user data in memory.
- `--local-user` and all-scope MCP/skills are runtime-only; do not copy global resources unless the user explicitly opts into `--import-user-skills`.
- `chat-agent-harness.json` can contain private run inventory; summarize counts and gates, not full global inventories.
- Working trees can contain unrelated edits; inspect `git status --short` before changes and avoid reverting user work.
- Completion claims require evidence: tests, `omk verify --json`, replay/cockpit artifacts, or an explicit not-run reason.

## Project Risks (from 2026-05-26 Audit)

### CRITICAL — Pre-Production Blockers
- **M1 — OpenSIPS OOM Risk:** Memory config exceeds container limits in ALL profiles. VPS overage is 32.8%.
- **M2 — PostgreSQL Prod OOM:** Theoretical max ~12.1 GB on 8 GB container limit.
- **M3 — PostgreSQL shm_size:** 2gb shm_size with 2GB shared_buffers = zero headroom.

### HIGH — Merge/Deploy Blockers
- **B17 — Stale Copilot Instructions:** `.github/copilot-instructions.md` misleads AI agents by claiming greenfield state.
- **F1 — Floating Image Tag:** `.env.example` defaults to `latest`, violating SHA256 pinning policy.
- **D9/V7 — PHP Base Image Divergence:** Admin API and OCP use different digests for PHP 8.2.
- **A2/V19 — Unpinned RTPengine APT:** Critical media relay package floats with Debian updates.
- **PY8/V21 — Python requests Drift:** Version inconsistency between exporter containers.
- **M5-M6 — Unbounded PHP Queries:** LGPD export and audit integrity check load entire tables into memory.

### MEDIUM — Quality/Security
- **B18 — Plaintext Seed Credential:** Default admin credential visible in committed SQL.
- **B19 — Schema Drift:** tenant_id uses VARCHAR(36) instead of UUID per canonical spec.
- **B20 — OCP Parity Tables:** May store plaintext credentials instead of bcrypt hashes.
- **B21-B22 — Missing Shell Hardening:** Entrypoint scripts lack `set -euo pipefail`.
- **M7-M13 — Memory/Observability Gaps:** Missing children param, connection pooler, container alerts, cAdvisor.

### External Blockers
- Stage 6: SIP public exposure pending firewall/Tailscale ACL.
- Stage 8.1: S3 backup credentials pending operator insertion.
