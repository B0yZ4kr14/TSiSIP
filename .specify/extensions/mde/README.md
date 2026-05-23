# MDE Spec Kit Extension

Spec Kit extension that does two things:

1. **Exposes the mde preset's three preset-only commands as Spec Kit slash commands:**

   ```text
   /speckit.mde.setup
   /speckit.mde.next
   /speckit.mde.status
   ```

2. **Syncs the mde preset into the project's AI auto-load files** (`CLAUDE.md`, `AGENTS.md`, `GEMINI.md`) and constitution via a self-healing `before_*` hook (`speckit.mde.sync`) so the preset gets loaded before every Spec Kit command, in any AI tool. The hook is marked `user-invocable: false` so it doesn't clutter the user's slash-command list — it only runs when fired by a hook.

## Why this extension exists

Upstream Spec Kit ships slash commands for `specify`, `clarify`, `plan`, `tasks`, `analyze`, `checklist`, `implement`, and `constitution`. The mde preset adds three more — `setup`, `next`, `status` — which have no upstream counterpart. Without this extension, those three commands are only available to AI agents that load the preset via Principle X (Preset Authority); they aren't reachable as `/speckit.*` slash commands.

This extension closes that gap by registering the three commands with Spec Kit. **All behavior lives in the preset.** Each command file in this extension is a thin delegator that:

1. Verifies the preset is installed at `.specify/presets/mde/`.
2. Reads the corresponding preset command file (`commands/setup.md`, `commands/next.md`, `commands/status.md`).
3. Executes it as the authoritative behavior.

There is no MDE-specific workflow, no `.mde/` state, no separate phase model. If you want to change command behavior, edit the preset, not this extension.

## How preset sync works

The `speckit.mde.sync` hook runs in two paths:

- **Before every upstream Spec Kit command** (`before_specify`, `before_clarify`, `before_plan`, `before_tasks`, `before_analyze`, `before_checklist`, `before_implement`, `before_constitution`) via Spec Kit's hook protocol.
- **At the start of every wrapper command** (`/speckit.mde.setup`, `/speckit.mde.next`, `/speckit.mde.status`) — each wrapper invokes sync as its first step before delegating to the preset.

On each fire, sync does three things:

1. **Heals durable wiring.** Checks each AI auto-load file (`CLAUDE.md`, `AGENTS.md`, `GEMINI.md`) and the constitution (`.specify/memory/constitution.md`) for a bounded marker block (`<!-- mde-preset:start -->` / `<!-- mde-preset:end -->`). For each file that exists: if the marker is missing or has drifted, re-injects it. Files that don't exist are skipped. Cost: four small file reads plus an occasional write.
2. **Primes the current context.** On the first sync fire of a session (and on any fire that just injected or refreshed a marker), reads a slim set of preset entry points into the active session's context: `RESOLUTION.md`, the constitution addendum (when the project constitution hasn't yet absorbed it), and `constitution.md`. Subsequent fires within the same session skip the load — the files are already in conversation history. `IMPACT_ANALYSIS.md` and `QUESTION_POLICY.md` are read by the specific commands that need them, not on every hook fire. The session-local rule keeps the cached prefix stable; see [presets/mde/docs/runtime-context-loading.md](https://github.com/AI-MDE/spec-kit-preset-mde/blob/main/docs/runtime-context-loading.md) for the full caching analysis.
3. **Bootstraps the session log.** Constitution Principle IX (Session Accountability) requires every material interaction to be logged at `logs/<YYYY-MM-DD>.md` and, when spec-bound, mirrored to `specs/NNN-*/session-log.md`. Sync ensures today's central log file exists with the canonical header and appends a one-line hook-fire entry. When the active spec is unambiguously resolvable, it also ensures the per-spec session log exists. Closes the coverage gap where logging was specified by the addendum but never actually started — even before the project's constitution has been ratified, since sync already loads the addendum directly.

This is **self-healing every fire**, not a one-time bootstrap. If the user switches AI tools (Claude → Codex → Gemini) and the new tool's auto-load file lacks the marker, the next sync fire installs it. If a file is edited and the marker drifts, the next fire restores it.

The hook never modifies content outside the marker block.

## Why a hook + wrapper invocation, and not install-time sync

Spec Kit extensions don't have install hooks — there's no point at which an extension can run code when it's added to a project. The only mechanisms an extension has are slash commands and `before_*`/`after_*` hooks. Sync runs at every Spec Kit command path:

- **Upstream commands** (`/speckit-specify`, etc.) trigger sync via Spec Kit's hook protocol.
- **Wrapper commands** (`/speckit.mde.*`) trigger sync inline as their first step.

Together these cover every slash-command entry point. Self-healing on every fire makes the timing irrelevant: the project's wiring is correct as soon as any Spec Kit command has run.

## What's not covered

If a user opens a chat and never invokes a slash command (just types "add a feature for X"), no sync fires. The auto-load files load at session start, but the marker may not be there yet (e.g., on a fresh install). For purely open-chat sessions, the user has to invoke at least one Spec Kit command (or manually add the marker) to get the preset wired in. After the first sync fire in any session, every subsequent open-chat exchange in *future* sessions has the preset loaded via the auto-load files.

## Requirements

The mde preset must be installed in the consuming project at:

```text
.specify/presets/mde/
```

The preset is not bundled with this extension. Install it separately before using these commands.

## Install locally

From a Spec Kit project that has the mde preset installed:

```bash
specify extension add --dev /path/to/mde_extension
```

Then invoke from your AI coding tool:

```text
/speckit.mde.setup
/speckit.mde.next
/speckit.mde.status
```

## Configuration

After `specify extension add --dev ./mde_extension`, the CLI installs the extension content to `.specify/extensions/mde/` and renders [config-template.yml](config-template.yml) to `.specify/extensions/mde/mde-config.yml`. That rendered file is user-editable and controls hook behavior:

```yaml
sync:
  enabled: true                  # disable the hook's marker reconciliation
  targets:                       # per-file toggles
    "CLAUDE.md": true
    "AGENTS.md": true
    "GEMINI.md": true
    ".specify/memory/constitution.md": true
  refresh_drifted: true          # restore drifted marker blocks vs leave them
  verbosity: "summary"           # silent | summary | verbose

logging:
  enabled: true                  # disable session-log bootstrap
  central_log_dir: "logs"        # where logs/<YYYY-MM-DD>.md lives
  log_hook_fires: true           # append a hook-fire row on each invocation
  mirror_to_active_spec: true    # mirror to specs/NNN-*/session-log.md when resolvable
```

If the config is missing, defaults take effect (everything enabled, summary verbosity). The two blocks are independent: `sync.enabled: false` opts out of marker reconciliation; `logging.enabled: false` opts out of session-log bootstrap. Either can be off without affecting the other.

The preset path is fixed at `.specify/presets/mde/` by Spec Kit convention. For development, use a symlink at that location pointing to your source.

## Files

```text
extension.yml
config-template.yml      - rendered to .specify/extensions/mde/mde-config.yml on install
commands/
  setup.md               - delegates to .specify/presets/mde/commands/setup.md
  next.md                - delegates to .specify/presets/mde/commands/next.md
  status.md              - delegates to .specify/presets/mde/commands/status.md
  sync.md          - self-healing hook that syncs the preset into auto-load files and constitution
```

No artifact templates, no schemas, no docs of its own — those all live in the preset.
