# Repository Agent Governance

This file is the source of truth for repository-level agent governance.

The agent governance domain covers:

- agent collaboration rules
- tool and MCP permissions
- write boundaries
- skill invocation contracts

The project governance domain is independent and has its own source of truth. Keep these
domains decoupled: this file does not name, rank, or depend on project-governance files.

<!--
Sync Impact Report
- Active Integration: TODO(ACTIVE_INTEGRATION)
- Installed Integrations: TODO(INSTALLED_INTEGRATIONS)
- Skills Scanned: TODO(SKILL_COUNT)
- MCP Config Files Scanned: TODO(MCP_CONFIG_FILES)
- Extension Config Status: TODO(EXTENSION_CONFIG_STATUS)
- Sections Changed: TODO(SECTIONS_CHANGED)
- Follow-up TODOs: TODO(FOLLOW_UP_TODOS)
-->

## Governance Domains

- Agent Governance Domain: this file is the SSOT for agent collaboration rules,
  tool and MCP permissions, write boundaries, and skill invocation contracts.
- Project Governance Domain: independent SSOT, managed outside this file.
- Do not encode upstream/downstream dependencies between governance domains.

## Authority Order

1. Current user instruction
2. Agent governance domain rules in this file
3. User-authored repository instructions for agent behavior
4. Skill-local `SKILL.md`
5. Tool/MCP defaults

## Write Boundaries

- Agent code writes are allowed only while executing the generated Spec Kit implement
  command or integration-equivalent implement skill/alias, such as `/speckit.implement`
  or `/speckit-implement`.
- Before any agent writes source code, tests, build configuration, migrations, runtime
  assets, or other implementation files, the active change MUST have the required
  project-governance artifacts for implementation.
- Bug fixes, refactors, and small code changes are not exceptions. If the required
  project-governance artifacts do not exist, first run the owning project-governance
  workflow, then stop before implementation.
- Direct user requests to "just edit code" or similar are treated as requests to run the
  owning project-governance workflow; they are not permission to bypass the
  implementation gate.
- Do not edit governance, CI, MCP config, secrets, permissions, or tool settings unless
  explicitly requested.
- Do not modify files outside the active task scope.
- Do not overwrite user edits.
- Do not rewrite generated files unless the owning workflow requires it.

## Skill Contract

Each skill must declare:

- purpose
- trigger
- allowed read paths
- allowed write paths
- forbidden paths
- outputs
- validation command

## MCP Policy

- MCP tools are read-only by default.
- Mutating MCP calls require explicit user intent.
- External writes must report target, action, and result.
- Secrets and tokens must never be logged or written to repo files.

## Validation

Before handoff, report:

- changed files
- commands run
- tests/validation result
- unresolved risks
