---
description: "Self-healing sync that reconciles the project's auto-load files and constitution with the mde preset."
user-invocable: false
---

# MDE Sync

Hook command. Fires before Spec Kit work to keep the project's AI auto-load files and constitution in sync with the mde preset. Self-healing: runs every time, re-injects markers that are missing or out of date, no-ops when everything is already in place.

This command is registered as a `before_*` hook in [extension.yml](../extension.yml). It runs automatically before `/speckit-specify`, `/speckit-clarify`, `/speckit-plan`, `/speckit-tasks`, `/speckit-analyze`, `/speckit-checklist`, `/speckit-implement`, and `/speckit-constitution`.

## User Input

$ARGUMENTS

## Configuration

Read `.specify/extensions/mde/mde-config.yml` if it exists. The config controls hook behavior.

```yaml
sync:
  enabled: true                          # Master toggle for this hook
  targets:
    "CLAUDE.md": true                    # Per-file toggle (ignored if file is absent)
    "AGENTS.md": true
    "GEMINI.md": true
    ".specify/memory/constitution.md": true
  refresh_drifted: true                  # Whether to restore drifted marker blocks
  verbosity: "summary"                   # silent | summary | verbose

logging:
  enabled: true                          # Master toggle for session-log bootstrap
  central_log_dir: "logs"                # Where logs/<YYYY-MM-DD>.md lives
  log_hook_fires: true                   # Append a hook-fire row on each invocation
  mirror_to_active_spec: true            # Mirror to specs/NNN-*/session-log.md when resolvable
```

If the config file is missing, use these defaults. If present, parsed values override defaults.

If `sync.enabled` is `false`, exit immediately with `mde.sync: disabled by config; skipped` (or nothing if `verbosity: silent`). Do not block the original command. Logging is governed by `logging.enabled` independently — the marker sync can be disabled while session logging is left on, or vice versa.

## Pre-check

Confirm the mde preset is installed at the standard location by checking for its manifest:

```text
.specify/presets/mde/preset.yml
```

The preset path is fixed by Spec Kit convention. If you need the preset elsewhere for development, set up a symlink at `.specify/presets/mde/` pointing to your source.

If absent, the preset is not installed in this project. Exit silently with a single line: `mde.sync: preset not installed; skipped`. Do not block the original command.

If present, continue.

## Version compatibility check

The extension's `extension.yml` declares a required preset version under `requires.preset:`. Read both files and compare:

```text
.specify/presets/mde/preset.yml             - look for `version: "X.Y.Z"`
.specify/extensions/mde/extension.yml          - look for `requires.preset.version: ">=X.Y.Z"`
```

Apply semver-style comparison:

- If the installed preset version satisfies the requirement → continue normally.
- If the installed preset is **older** than required → emit `mde.sync: preset version <found> is older than required <requirement>; canonical markers may reference files that don't exist in this preset`. Continue with the rest of sync, but flag this in the response so the user knows the marker block may point at stale paths.
- If the installed preset is **newer** by a major version → emit `mde.sync: preset version <found> is newer than the extension's tested range <requirement>; behavior may differ`. Continue.
- If either file is unreadable or the version string is missing → emit `mde.sync: preset version unknown; skipping compatibility check` and continue.

Compatibility issues are warnings, not blockers — sync still tries to do its job.

## Targets

Check each of these files. The exact set is intentional — auto-load files vary by AI tool (Claude / Codex / Gemini), and the user may switch tools at any time, so all four are checked every fire:

```text
CLAUDE.md                                  - Claude Code auto-load
AGENTS.md                                  - Codex auto-load
GEMINI.md                                  - Gemini Code Assist auto-load
.specify/memory/constitution.md            - project constitution (preset reference block)
```

For each target file:

- If `sync.targets[<file>]` is `false` in config, skip; record `disabled`.
- If the file does not exist on disk, skip; record `absent`.
- Otherwise process per the algorithm below.

We never pre-emptively create auto-load files. A missing file means that AI tool isn't being used in this project.

## Marker block — auto-load files (`CLAUDE.md` / `AGENTS.md` / `GEMINI.md`)

Use this exact text. The `<!-- mde-preset:start -->` / `<!-- mde-preset:end -->` pair makes the block bounded so re-injection replaces, not appends.

```text
<!-- mde-preset:start -->
This project uses the mde preset.

Before any action that creates, modifies, or removes files, agents MUST consult:

- .specify/presets/mde/RESOLUTION.md
- .specify/presets/mde/QUESTION_POLICY.md
- .specify/presets/mde/IMPACT_ANALYSIS.md
- .specify/presets/mde/commands/
- .specify/presets/mde/templates/constitution-template-mde-addendum.md
- .specify/memory/constitution.md (Principle X — Preset Authority)

The constitution-template-addendum holds the mde principles (including Principle X). It is the source of truth even if the actual constitution has not yet been authored via /speckit-constitution.

Do not read from .specify/presets/mde/docs/ — that folder is human reference only.
The preset's commands/ folder governs slash-command behavior; the /speckit.mde.* wrappers delegate to it.
<!-- mde-preset:end -->
```

## Marker block — constitution (`.specify/memory/constitution.md`)

Same bounded markers, slightly different framing scoped to the constitution context. This block does not replace the constitution's own Principle X if the mde constitution-template-addendum has already been applied — it's a small reference block at the end of the file that names the preset entry points.

```text
<!-- mde-preset:start -->
## mde Preset Reference

This project incorporates the mde preset. Principle X (Preset Authority) is binding.

Authoritative preset sources:

- .specify/presets/mde/RESOLUTION.md
- .specify/presets/mde/QUESTION_POLICY.md
- .specify/presets/mde/IMPACT_ANALYSIS.md
- .specify/presets/mde/commands/
- .specify/presets/mde/templates/constitution-template-mde-addendum.md

If this constitution does not yet contain a Principle X (Preset Authority) section because /speckit-constitution has not been run under preset authority, the addendum file above is the binding source until it is.

The preset's commands/ folder governs slash-command behavior.
.specify/presets/mde/docs/ is human reference only — agents must not read from it.
<!-- mde-preset:end -->
```

## Algorithm

For each target file (in order: `CLAUDE.md`, `AGENTS.md`, `GEMINI.md`, `.specify/memory/constitution.md`):

1. Apply the target-toggle / file-existence checks above. If skipping, record `disabled` or `absent` and move on.
2. Read the file content.
3. Locate the marker pair `<!-- mde-preset:start -->` / `<!-- mde-preset:end -->`.
4. Decide the action:
   - **Both markers present, content between them matches the canonical block byte-for-byte** → no-op; record `ok`.
   - **Both markers present, content has drifted from the canonical block** →
     - if `sync.refresh_drifted: true`: replace the entire block (start marker through end marker, inclusive) with the canonical block; record `refreshed`.
     - if `sync.refresh_drifted: false`: leave the block alone; record `drifted` (no-op, but flagged).
   - **Markers absent** → append one blank line plus the canonical block to the end of the file; record `injected`.
   - **Only one marker present (corrupt state)** → strip the orphan marker, then inject the full block; record `refreshed`.
5. Write the file back only when content changed.

Do not modify any content outside the marker block. Do not reorder or rewrite the file's existing content.

## Session log initialization (Constitution Principle IX)

The mde constitution addendum's Principle IX (Session Accountability) requires every material interaction and AI action to be logged at `logs/<YYYY-MM-DD>.md` (chronological central log) and, when spec-bound, mirrored to `specs/NNN-change-name/session-log.md`. Sync bootstraps these files so the parent command never has to fail on "log file missing" — it just appends.

If `logging.enabled` is `false`, skip this entire section and record `disabled` in the final response. The marker reconciliation above is governed independently by `sync.enabled`; either toggle can be off without affecting the other.

Otherwise, perform the steps below. Sync only ensures infrastructure and records its own fire — the parent command (`setup`, `specify`, `clarify`, etc.) is responsible for appending its own user-prompt and AI-action entries during execution.

### Central log

1. Resolve the central log directory from `logging.central_log_dir` (default: `logs`). Path is relative to the project root.
2. If the directory does not exist, create it. Record `created` for the directory.
3. Compute today's filename as `<central_log_dir>/<YYYY-MM-DD>.md` using the local date.
4. If today's log file does not exist, create it with this canonical header (do not include explanatory prose):

   ```text
   # Session log — <YYYY-MM-DD>
   <!-- Maintained by the mde preset; see Constitution Principle IX (Session Accountability). -->

   | Time | Actor | Interaction | Outcome | Tokens | Size |
   |------|-------|-------------|---------|--------|------|
   ```

   Record `created`. If the file already exists, scan it for the canonical table header (`| Time | Actor | Interaction | Outcome | Tokens | Size |` followed by the separator row). If absent — for example, a previous command wrote bullet-style entries — append a horizontal rule and the canonical header to the end of the file before continuing, then record `header_appended` rather than `ok`. Do not rewrite or reformat the existing content above; leave any prior entries in their original layout. Subsequent rows append to the canonical table.

5. If `logging.log_hook_fires` is `true`, append one row to the table for this fire:

   ```text
   | <ISO 8601 timestamp or NA> | assistant | hook: speckit.mde.sync | reconciled markers, primed context | NA | NA |
   ```

   Use `NA` for any field that cannot be reliably populated. Keep the entry to a single line — no wrapping prose.

   If `logging.log_hook_fires` is `false`, skip the row append.

### File encoding

All log writes MUST be UTF-8 without a BOM. Mixed encodings inside a single file produce mojibake — the most common failure mode is a row that comes back with a literal space (or null byte) between every character.

Practical rules:

- Prefer the agent's own file-write tools (Edit / Write / append-equivalent) for log mutations. They use UTF-8 without BOM by default.
- Do **not** use raw `Out-File`, `Set-Content`, `Add-Content`, or `>>` redirection from Windows PowerShell 5.1 — the default encoding there is UTF-16 LE with BOM, which corrupts a UTF-8 log on append. If a shell write is unavoidable, pass `-Encoding utf8` explicitly (and on PS 5.1 prefer `[System.IO.File]::AppendAllText($path, $line + "`n", [System.Text.UTF8Encoding]::new($false))` for true UTF-8 without BOM).
- Before appending, sanity-check the existing file: if any byte in the last 64 bytes is `0x00`, the file has already been corrupted with UTF-16 content. Do not append on top of it. Record `error: encoding corruption detected — manual repair required` in the final response and stop. Subsequent fires will retry once the file is fixed.

### Cell content normalization

The `Interaction` and `Outcome` fields may contain user prompts or AI summaries that include newlines or pipe characters (`|`), both of which break a markdown table row. Whenever a cell value is written:

- Replace each newline with `<br>`.
- Replace each literal `|` with `\|`.
- Trim leading/trailing whitespace.
- If the resulting cell exceeds ~500 characters, truncate with a trailing `…` — the table is a chronological index, not the source of truth for full prompt content. The full prompt is recoverable from the conversation transcript.

Apply the same normalization when the parent command writes its own rows.

### Per-spec mirror

6. If `logging.mirror_to_active_spec` is `true`, attempt to resolve the active spec following the same rule as the preset's `.specify/presets/mde/RESOLUTION.md`: explicit branch match (`specs/NNN-*` folder named after the current git branch) > single in-flight spec folder under `specs/`.

   - If exactly one spec resolves, ensure `specs/NNN-*/session-log.md` exists with the same canonical header (substituting the spec name for the date in the title line, e.g. `# Session log — NNN-change-name`). Append the same hook-fire row that was written to the central log. Record `ok` or `created` for the per-spec target.
   - If zero or multiple specs resolve, do not write a per-spec mirror — record `absent` and let the parent command write its own per-spec entries when it knows its target.

7. Never create a `specs/NNN-*/session-log.md` for a spec the hook cannot identify with confidence. Spec resolution is the parent command's job; the hook only mirrors when the choice is unambiguous.

### Failure handling

If a write to the log files fails (permission denied, disk full, path not writable), do not block the parent command. Record the failure in the final response (`logs/<YYYY-MM-DD>.md: error: <reason>`) and continue. Logging is best-effort infrastructure, not a gate.

## Load into current context

The hook primes the current AI session's context with the preset's slim entry set on the **first sync fire of the session**. Subsequent fires within the same session skip this load — the files are already in conversation history and the prompt cache absorbs the cost of further references.

### When to load

Load on this fire if **either** condition holds:

1. **First sync fire of the session.** Inspect prior tool-call results in conversation history. If no earlier fire of `speckit.mde.sync` has read the entry set this session, this is the first fire — load.
2. **Marker reconciliation just ran.** If any target this fire was recorded as `injected` or `refreshed` (i.e. the marker block in an auto-load file was just installed or repaired), load — the prior session's auto-load couldn't have covered the agent.

If neither condition holds (steady-state mid-session fire), skip the load. The parent command will read what it needs via its own `Required Inputs`.

### What to load

```text
.specify/presets/mde/RESOLUTION.md
.specify/presets/mde/templates/constitution-template-mde-addendum.md
.specify/memory/constitution.md
```

The slim set covers active-spec resolution (used by every command) and the mde principles (binding governance). When `.specify/memory/constitution.md` already contains the addendum content (a section titled `## IX. Session Accountability` or `## X. Preset Authority`), the addendum file is redundant — skip it and load only `RESOLUTION.md` + `constitution.md`. When the constitution is still a placeholder or has not yet been ratified under preset authority, the addendum file is the binding source — load it.

Do not paraphrase or summarize. Load the raw content. Treat it as authoritative for the command that runs immediately after this hook.

### Files the hook does not load

- `IMPACT_ANALYSIS.md` — read per-command by `specify`, `clarify`, `plan`.
- `QUESTION_POLICY.md` — read per-command by `specify`, `clarify`, `next`.
- Anything under `.specify/presets/mde/docs/` — human reference only, never loaded into agent context.

### Caching note

The slim, stable load list keeps the conversation prefix cacheable. Anthropic's prompt cache has a 5-minute TTL; loads on subsequent fires within that window are largely cache hits, but the session-local rule above also skips them entirely so the conversation history doesn't accrete duplicate tool-call outputs. See [`presets/mde/docs/runtime-context-loading.md`](../../presets/mde/docs/runtime-context-loading.md) for the full caching analysis.

## Constitution caveat

If `.specify/memory/constitution.md` already contains a section titled `## Principle X` or `## X. Preset Authority` (from the mde constitution-template-addendum), the sync reference block at the end of the file is supplementary — it does not duplicate or override the addendum. The addendum is the binding rule; this block is a quick pointer for agents that read the constitution lazily.

## Final response

Verbosity is controlled by `sync.verbosity` in config (default: `summary`).

- **`silent`**: print nothing. Exit cleanly.
- **`summary`** (default): print one block with per-target status, including the log bootstrap lines:

  ```text
  mde.sync:
  - CLAUDE.md: ok | injected | refreshed | drifted | absent | disabled
  - AGENTS.md: ok | injected | refreshed | drifted | absent | disabled
  - GEMINI.md: ok | injected | refreshed | drifted | absent | disabled
  - .specify/memory/constitution.md: ok | injected | refreshed | drifted | absent | disabled
  - logs/<YYYY-MM-DD>.md: ok | created | disabled | error: <reason>
  - specs/<NNN-*>/session-log.md: ok | created | absent | disabled | error: <reason>
  ```

  Omit the per-spec line when `logging.mirror_to_active_spec` is `false`.

- **`verbose`**: print the summary plus, for each `injected` or `refreshed` target, the path of the canonical marker block source so the user can audit. For logs, also print the absolute path of the file written and the appended row when `log_hook_fires` is on. Use this only when troubleshooting.

Keep the user's attention on the original command's response, not the hook's bookkeeping.
