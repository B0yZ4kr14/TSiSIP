---
description: "Create or refresh repository agent governance instructions"
---

# Agent Governance Refresh

Create or refresh the repository-local agent governance projection.

## User Input

$ARGUMENTS

## Goal

Keep `.specify/memory/agent-governance.md` as the source of truth for repository-level agent behavior, and project a concise managed section into the active agent context file.

## Procedure

1. Verify that the current directory is a Spec Kit project by checking for `.specify/`.
2. If `.specify/memory/agent-governance.md` does not exist, create it from `.specify/extensions/agent-governance/templates/agent-governance-template.md`.
3. Resolve the target context file in this order:
   - `.specify/init-options.json` field `context_file`
   - known context file for `.specify/integration.json` field `default_integration` or `integration`
   - `AGENTS.md`
4. Run the helper script when Python is available:

   ```bash
   python3 .specify/extensions/agent-governance/scripts/refresh_agent_governance.py
   ```

5. If Python is not available, manually update the resolved context file by replacing the managed section between:

   ```text
   <!-- SPECKIT GOVERNANCE START -->
   <!-- SPECKIT GOVERNANCE END -->
   ```

6. Preserve all user-authored content outside the managed markers.
7. Report:
   - source memory file path
   - target context file path
   - whether the governance memory was created or already existed
   - whether the projection was inserted or replaced

## Manual Projection Requirements

When manual editing is required, the managed section must include:

- `## Repository Agent Governance`
- source of truth: `.specify/memory/agent-governance.md`
- active integration
- resolved constraints file
- installed integrations
- authority order
- write boundaries
- MCP and external tool policy
- skill usage policy
- required handoff report

Do not edit implementation files while running this command.
