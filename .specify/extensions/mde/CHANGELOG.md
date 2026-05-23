# Changelog

## 0.3.2

Hardening pass: pin preset version, include constitution addendum, add extension validator, drop uncertain frontmatter flag, rename stale tag, trim duplicated boilerplate.

Changed:

- `commands/sync.md` now includes `.specify/presets/mde/templates/constitution-template-mde-addendum.md` in both the auto-load file marker block and the "Load into current context" step. This closes a gap where Principle X (Preset Authority) only entered context if `/speckit-constitution` had been run under preset authority — now it is loaded directly from the addendum file regardless of constitution state. The addendum and the actual constitution are both loaded so any project-specific overrides remain visible.

- `extension.yml` declares `requires.preset: { id, version, path }` and pins `version: ">=0.4.0"` against the mde preset. `commands/sync.md` reads `.specify/presets/mde/preset.yml` and warns (does not block) when the installed preset is older or newer than the tested range. Catches the case where the preset moves files (e.g. our recent `spec-impact-analysis.md` → `IMPACT_ANALYSIS.md` migration) and the marker block points at stale paths.
- `commands/sync.md` frontmatter drops `disable-model-invocation: false`. Its semantics for extension commands are not documented; `user-invocable: false` is the conservative setting we trust. Hooks should still be able to fire sync regardless.
- `extension.yml` tag `preset-wiring` → `preset-sync` (matches the renamed command).
- `commands/setup.md`, `commands/next.md`, `commands/status.md` replace the duplicated "Why this command exists" prose with a one-line cross-reference to README.

Added:

- `tools/validate-extension.js` — checks `extension.yml` shape, that every registered command file exists on disk, that every hook target resolves to a registered command, that the config template exists, and that `requires.preset` is declared. Run with `node tools/validate-extension.js`. Currently runs 25 checks and exits non-zero on any failure.

## 0.3.1

Sync now also primes the current session's context, and the wrappers invoke sync explicitly so they're not bypassed by the upstream-only hook system.

Changed:

- `commands/sync.md` adds a "Load into current context" step after the marker reconciliation. The hook now reads `RULES.md`, `IMPACT_ANALYSIS.md`, `QUESTION_POLICY.md`, `RESOLUTION.md`, `PRESET_VALIDATION.md`, and the constitution into the active session before returning. Without this, the marker injection only takes effect at *next* session start, leaving the current command without preset coverage.
- `commands/setup.md`, `commands/next.md`, `commands/status.md` invoke sync as their first step before delegating to the preset. Spec Kit's hook protocol fires `before_*` hooks for upstream commands only — wrappers were uncovered. Now both paths run sync.
- Dropped `preset_path` from `config-template.yml` and `commands/sync.md`. The path is fixed at `.specify/presets/mde/` by Spec Kit convention; configurability was a fiction that the marker blocks couldn't honor anyway. Use a symlink for development setups.

## 0.3.0

Added self-healing preset wiring with a user-editable config.

Added:

- `commands/sync.md` — hook command that reconciles `CLAUDE.md`, `AGENTS.md`, `GEMINI.md`, and `.specify/memory/constitution.md` against the mde preset. Uses bounded markers (`<!-- mde-preset:start -->` / `<!-- mde-preset:end -->`) so re-injection replaces in place rather than appending duplicates. Skips files that don't exist (doesn't pre-emptively create auto-load files). Marked `user-invocable: false` so it doesn't appear in the user's slash-command list — it's a hook-only command.
- `extension.yml` registers `speckit.mde.sync` (matching the `speckit.<id>.<verb>` convention used by every other extension command) and hooks it as `before_*` for every upstream Spec Kit command (`specify`, `clarify`, `plan`, `tasks`, `analyze`, `checklist`, `implement`, `constitution`). All hooks are mandatory (`optional: false`) so the sync runs automatically.
- `config-template.yml` — rendered to `.specify/extensions/mde/mde-config.yml` on install. Closes the CLI's "Configuration may be required" warning. Knobs:
  - `sync.enabled` (master toggle)
  - `sync.targets[<file>]` (per-file toggles)
  - `sync.refresh_drifted` (restore vs preserve manually-edited markers)
  - `sync.verbosity` (silent / summary / verbose)
  - `preset_path` (override if the preset lives elsewhere)
- `extension.yml` declares the config under `provides.config:` so the install machinery renders the template into the project.

Why hooks instead of install-time wiring: Spec Kit extensions don't have install hooks. The `speckit.mde.sync` hook fires every time a Spec Kit command runs and self-heals if anything has drifted (engine switch, manual edit, file deletion). The cost is small — a marker check per file, occasional write.

## 0.2.0

Redesigned as a thin wrapper around the mde preset.

Changed:

- `commands/setup.md`, `commands/next.md`, `commands/status.md` now delegate to the mde preset's commands at `.specify/presets/mde/commands/<name>.md`. All behavior lives in the preset.
- `extension.yml` drops the `config:` block. The extension no longer authors workflow / validation / artifact-map config.

Removed:

- `templates/workflow.template.yml`, `templates/validation-rules.template.yml`, `templates/artifact-map.template.yml` — described a separate 4-phase MDE workflow that's superseded by the preset's scoped-change model.
- `docs/workflow-design.md` — described the now-removed 4-phase workflow.
- The `.mde/` state machine and per-phase artifact tree previously implied by the commands.

## 0.1.0

Initial minimal MDE Spec Kit extension.

Added:

- `/speckit.mde.setup`
- `/speckit.mde.next`
- `/speckit.mde.status`
- workflow template
- validation rules template
- artifact map template
