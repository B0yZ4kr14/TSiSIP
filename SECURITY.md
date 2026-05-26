# Security Policy

## Reporting Vulnerabilities

Please report security issues via GitHub Issues with the `security` label.

## Built-in Protections

oh-my-kimi provides default hooks that protect against destructive commands and secret leakage.

## MCP and Harness Secret Handling

- Fresh init writes project-local `omk-project` MCP only; user/global MCP and skills are runtime-only unless explicitly imported by a trusted local user.
- Always protect MCP `env`, headers, tokens, and provider keys from printing, committing, or summarizing.
- Handle `chat-agent-harness.json` as private run metadata: use it for inventory/gates, and sanitize any values before including them in prompts, memory, or reports.
- Prefer sanitized `omk mcp doctor --json`, `omk verify --json`, test summaries, and secret scans as shareable evidence.

## Best Practices

- Review hooks before running in production repositories.
- Use `--print` mode only in disposable worktrees.
- Keep secrets out of agent memory files and all committed artifacts.
