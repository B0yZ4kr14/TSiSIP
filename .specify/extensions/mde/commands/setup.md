---
description: "Run the mde preset's setup command (first-run project scaffolding)."
---

# MDE Setup

Thin Spec Kit slash-command wrapper around the mde preset's `setup`. The wrapper invokes the sync hook (which both reconciles auto-load files and primes the current context with preset rules) before delegating to the preset.

## User Input

$ARGUMENTS

## Step 1: Sync preset wiring and load into context

Read and execute `commands/sync.md` from this extension. Sync will:

1. Verify the preset is installed at `.specify/presets/mde/`. If not, sync exits with `mde.sync: preset not installed; skipped`. In that case, stop the wrapper too — tell the user to install the preset before using `/speckit.mde.setup`.
2. Reconcile `CLAUDE.md`, `AGENTS.md`, `GEMINI.md`, and `.specify/memory/constitution.md` against the preset markers (self-healing).
3. Read the preset entry points into the current context so step 2 below has them available.

Wait for sync's status block before continuing.

## Step 2: Delegate to the preset

Read `.specify/presets/mde/commands/setup.md` and execute it as the authoritative behavior. Treat that file as the source of truth — do not duplicate, paraphrase, or override its rules here.

The preset's `setup` is a thin alias for `specify` on first run. Follow whatever delegation chain the preset defines (currently: `setup -> specify` for first-run project scaffolding, with greenfield/brownfield branching and the Q1–Q6 question set).

## Why this command exists

See [README.md](../README.md#why-this-extension-exists) — the mde preset adds three commands (`setup`, `next`, `status`) that have no upstream Spec Kit counterpart; this extension exposes them as `/speckit.mde.*` slash commands.

## Final response

Whatever the preset's `setup` produces, surfaced to the user without modification.
