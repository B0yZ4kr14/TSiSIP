# Repository Agent Governance — TSiSIP

This file is the source of truth for repository-level agent governance in the TSiSIP project.

## Sync Impact Report

- Active Integration: kimi
- Installed Integrations: kimi, codex, copilot, opencode
- Skills Scanned: 40+ (Project + User + Built-in scopes)
- MCP Config Files Scanned: `.kimi/mcp.json`, `.omk/mcp.json`
- Extension Config Status: 6 extensions active, 3 presets active, 1 workflow active
- Sections Changed: Authority Order, Write Boundaries, MCP Policy, Tool Integrations
- Follow-up TODOs: Validate constitution drift after spec updates

## Governance Domains

- **Agent Governance Domain**: this file is the SSOT for agent collaboration rules, tool and MCP permissions, write boundaries, and skill invocation contracts.
- **Project Governance Domain**: managed via `docs/TSiSIP-CANONICAL-SPEC.md`, `AGENTS.md`, and `.specify/memory/constitution.md`.
- **Orchestration Domain**: managed via `.omk/config.toml`, `.swarm/state.json`, and `.sisyphus/boulder.json`.

## Authority Order

1. Current user instruction (highest)
2. `docs/TSiSIP-CANONICAL-SPEC.md` — canonical architecture & security rules
3. `AGENTS.md` — agent onboarding and runtime constraints
4. This file (`.specify/memory/agent-governance.md`)
5. `.specify/memory/constitution.md` — project principles
6. Skill-local `SKILL.md`
7. Tool/MCP defaults (lowest)

## Write Boundaries

- Agent code writes are allowed only while executing `/speckit.implement`, `/speckit.architecture-guard.governed-implement`, or equivalent OMK-blessed implement flows.
- Before any agent writes source code, tests, build configuration, migrations, runtime assets, or other implementation files, the active change MUST have the required spec artifacts (`spec.md`, `plan.md`, `tasks.md`) and pass the architecture guard.
- Bug fixes, refactors, and small code changes are NOT exceptions. If required governance artifacts do not exist, first run the owning project-governance workflow, then stop before implementation.
- Do not edit governance, CI, MCP config, sensitive credential files, permissions, or tool settings unless explicitly requested.
- Do not modify files outside the active task scope.
- Do not overwrite user edits.
- Do not rewrite generated files unless the owning workflow requires it.
- `.specify/` directory files may be read by any agent; writes require explicit user confirmation.

## Tool & MCP Policy

### GitNexus (Code Intelligence)
- **Purpose**: Impact analysis, symbol navigation, refactoring safety
- **Trigger**: Before any edit that modifies a function, class, or method
- **Allowed Reads**: `gitnexus_impact`, `gitnexus_context`, `gitnexus_query`, `gitnexus_detect_changes`
- **Allowed Writes**: None (read-only toolset)
- **Forbidden**: `gitnexus_rename` without user confirmation
- **Validation**: Must report blast radius before proceeding with HIGH/CRITICAL risk

### OMK (Oh-My-Kimi Orchestration)
- **Purpose**: Multi-agent coordination, memory, goal tracking
- **Trigger**: Multi-step work, feature development, debugging loops
- **Allowed Reads**: `omk_goal_list`, `omk_goal_show`, `omk_memory_read`, `omk_memory_mindmap`
- **Allowed Writes**: `omk_goal_create`, `omk_evidence_add`, `omk_write_todos`
- **Forbidden**: `omk_goal_close` without verification; storing sensitive data in memory
- **Validation**: Quality gates (`omk_quality_gate`) must pass before goal completion

### Obsidian Vault (TSi-Vault)
- **Purpose**: Documentation persistence, session tracking, changelog
- **Trigger**: Documentation tasks, session start/end, release notes
- **Allowed Reads**: `read-note`, `search-vault`, `get_note_metadata`
- **Allowed Writes**: `create-note`, `edit-note`, `add_changelog_entry`, `add_todo`
- **Forbidden**: Deleting notes without confirmation; writing credentials
- **Validation**: Session changelog must be reviewed before `end_session`

### Speckit (Spec-Driven Development)
- **Purpose**: Spec generation, planning, task breakdown, implementation
- **Trigger**: New features, architecture changes, spec drift detection
- **Allowed Reads**: All `speckit.*` read commands, `speckit-utils.doctor`
- **Allowed Writes**: `speckit.specify`, `speckit.plan`, `speckit.tasks`, `speckit.implement` (gated)
- **Forbidden**: `speckit.implement` without preceding `spec.md` + `plan.md` + `tasks.md`
- **Validation**: `speckit.spec-validate.gate` must pass before implementation

### Firecrawl (Web Research)
- **Purpose**: External documentation, API references, research
- **Trigger**: Research tasks, API verification, pricing lookup
- **Allowed Reads**: `firecrawl_search`, `firecrawl_scrape`, `firecrawl_map`
- **Forbidden**: Crawling internal/private networks; scraping credentials
- **Validation**: Source URLs must be logged in research artifacts

## Skill Contract

Each skill invocation must declare:

| Skill | Purpose | Trigger | Allowed Writes | Forbidden | Validation |
|-------|---------|---------|----------------|-----------|------------|
| `agentmemory` | Persistent memory | Long sessions | Memory files | Sensitive data | Context audit |
| `andrej-karpathy-skills` | Surgical coding | Refactors, fixes | Source files | Broad edits | Diff review |
| `matt-pocock-skills` | TDD/Alignment | Test-first work | Test + Source | Untested code | Test pass |
| `multica` | Multi-agent teams | Complex features | Task assignments | Direct edits | Integration QA |
| `omk-*` | OMK workflows | Architecture, releases | Goals, memory | Credentials | Quality gates |
| `speckit-*` | Spec workflows | Features, docs | Specs, plans | Unplanned code | Spec validate |

## Validation

Before handoff, report:

- changed files
- commands run
- tests/validation result
- unresolved risks
- spec artifact alignment status
- architecture guard pass/fail
