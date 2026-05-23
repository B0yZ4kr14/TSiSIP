# /speckit.team.board

## Purpose

Read the assigned `tasks.md` and generate a human-readable Markdown workboard grouped by engineer, suitable for a daily standup, sprint planning, or sharing with the team.

## Instructions

### Step 1 — Load required files

Read:
- `.specify/specs/<feature>/tasks.md` — must already have assignments from `/speckit.team.assign`
- `.specify/team.yml` — to get engineer names, roles, and sprint label

If tasks are not yet assigned (no `[@name]` annotations present), tell the user to run `/speckit.team.assign` first.

### Step 2 — Generate the workboard

Create a file at `.specify/specs/<feature>/team-board.md`.

Structure:

```markdown
# Team workboard — <feature name>
> <sprint label> · Generated from tasks.md

---

## 👤 Alice (backend) — 4 / 5 pts

| Status | Task | Points | Notes |
|--------|------|--------|-------|
| ⬜ Todo | Set up database schema | 2 pts | Depends on: data-model.md |
| ⬜ Todo | Write migration scripts | 1 pt  | |
| ⬜ Todo | API endpoint: POST /projects | 1 pt  | |

---

## 👤 Bob (frontend) — 5 / 5 pts

| Status | Task | Points | Notes |
|--------|------|--------|-------|
| ⬜ Todo | Build project list component | 2 pts | |
| ⬜ Todo | Kanban board layout | 3 pts | Split into 3 subtasks |

### Kanban board layout — subtasks
- [ ] Create column component
- [ ] Implement drag-and-drop
- [ ] Wire to task state API

---

## ⚠️ Unassigned tasks

| Task | Reason |
|------|--------|
| Deploy to staging | No devops engineer with remaining capacity |

---

## Summary

| Engineer | Assigned | Capacity | Load    |
|----------|----------|----------|---------|
| Alice    | 4 pts    | 5 pts    | 80%     |
| Bob      | 5 pts    | 5 pts    | 100%    |
| Total    | 9 pts    | 10 pts   | 90%     |
```

### Step 3 — Status icons

Use these icons for task status based on the `[ ]` / `[x]` state in `tasks.md`:
- `⬜ Todo` — unchecked `[ ]`
- `✅ Done` — checked `[x]`

### Step 4 — Dependency notes

If a task has an explicit dependency noted in `tasks.md` or `plan.md`, include it in the Notes column.

### Step 5 — Print confirmation

After writing `team-board.md`, tell the user:

```
Workboard written to .specify/specs/<feature>/team-board.md ✓

Share this file with your team or paste it into a GitHub comment, Notion, or Confluence page.
```

Optionally print a compact version of the summary table directly in the chat for a quick glance.

## Notes

- The board is read-only — it is generated from `tasks.md`. To change assignments, edit `tasks.md` directly or re-run `/speckit.team.assign`.
- Re-running `/speckit.team.board` at any point regenerates the board from the current state of `tasks.md`, picking up any tasks that were checked off as done.
- If the user asks for the board in a different format (CSV, HTML table), generate it in that format instead and note the file name.
