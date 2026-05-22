# Spec Kit Agent Governance

Spec Kit Agent Governance adds a project-local governance memory file and a refresh command that projects concise agent instructions into the active Spec Kit integration context file.

The source of truth is:

```text
.specify/memory/agent-governance.md
```

The generated projection is written between managed markers in the active agent context file, such as `AGENTS.md`, `CLAUDE.md`, `GEMINI.md`, `.cursor/rules/specify-rules.mdc`, or `.github/copilot-instructions.md`.

## Features

- Creates `.specify/memory/agent-governance.md` from a bundled template when missing.
- Detects the active integration from `.specify/init-options.json` and `.specify/integration.json`.
- Updates only the managed `SPECKIT GOVERNANCE` section.
- Preserves user-authored content outside the managed markers.
- Removes stale managed sections from known non-active context files.
- Registers optional hooks after constitution, plan, and tasks workflows.

## Install

From a Spec Kit project:

```bash
specify extension add agent-governance --from https://github.com/bigsmartben/spec-kit-agent-governance/archive/refs/tags/v1.1.0.zip
```

For local development:

```bash
specify extension add --dev /path/to/spec-kit-agent-governance
```

## Usage

Run the generated command from your agent:

```text
/speckit.agent-governance.refresh
```

Codex skills mode may expose it as:

```text
$speckit-agent-governance-refresh
```

The command runs:

```bash
python3 .specify/extensions/agent-governance/scripts/refresh_agent_governance.py
```

After it completes, review and edit `.specify/memory/agent-governance.md` as the durable source of truth, then run the refresh command again.

## Files

- `extension.yml`: Spec Kit extension manifest.
- `commands/speckit.agent-governance.refresh.md`: Agent command.
- `scripts/refresh_agent_governance.py`: Projection helper.
- `templates/agent-governance-template.md`: Initial governance memory template.

## Safety Model

The command only writes:

- `.specify/memory/agent-governance.md`
- the managed governance section in known agent context files

It does not edit implementation files.

## Community Catalog Submission

After creating the GitHub release, submit this extension through the Spec Kit Extension Submission issue template. Do not open a pull request directly against `extensions/catalog.community.json`.
