# Known Risks

- Do not store secrets, API keys, tokens, credentials, MCP env/header values, or private user data in memory.
- `--local-user` and all-scope MCP/skills are runtime-only; do not copy global resources unless the user explicitly opts into `--import-user-skills`.
- `chat-agent-harness.json` can contain private run inventory; summarize counts and gates, not full global inventories.
- Working trees can contain unrelated edits; inspect `git status --short` before changes and avoid reverting user work.
- Completion claims require evidence: tests, `omk verify --json`, replay/cockpit artifacts, or an explicit not-run reason.
