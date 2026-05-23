# spec-kit-team-assign

A [spec-kit](https://github.com/github/spec-kit) community extension that assigns tasks from `tasks.md` to **human engineers**, splits complex tasks into subtasks, and generates a per-person workboard — all inside your spec-kit workflow.

---

## Why this extension?

Spec-kit's built-in `/speckit.tasks` produces a great task list, but it doesn't know who on your team should do what. This extension fills that gap:

- **Define your team once** in a simple `team.yml` file
- **Assign tasks automatically** by role, round-robin, or get suggestions for manual assignment
- **Split complex tasks** into concrete subtasks interactively
- **Generate a workboard** grouped by engineer, ready to share in a standup or paste into Notion/Confluence

No Jira. No external tool. Just Markdown files inside your repo.

---

## Installation

```bash
specify extension add team-assign
```

Or install directly from GitHub:

```bash
specify extension add --from https://github.com/tarunkumarbhati/spec-kit-team-assign/archive/refs/tags/v1.0.0.zip
```

---

## Workflow

```
/speckit.tasks          ← (existing spec-kit command — generate tasks.md first)
        ↓
/speckit.team.setup     ← define your team members and roles
        ↓
/speckit.team.assign    ← assign tasks + split complex ones into subtasks
        ↓
/speckit.team.board     ← generate the per-engineer workboard
```

---

## Commands

### `/speckit.team.setup`

Guides you through creating `.specify/team.yml` with your team members, roles, and capacity.

Example `team.yml` output:

```yaml
team:
  members:
    - name: "Alice"
      github: "alice"
      role: "backend"
      capacity: 5
    - name: "Bob"
      github: "bob"
      role: "frontend"
      capacity: 5
  sprint: "Sprint 1"
  strategy: "role-match"
```

**Roles**: `backend`, `frontend`, `fullstack`, `devops`, `qa` (or any custom role).

**Strategies**:
| Strategy | Behaviour |
|----------|-----------|
| `role-match` | Matches task domain to engineer role. Recommended. |
| `round-robin` | Distributes tasks evenly regardless of role. |
| `manual` | Leaves assignments empty with suggestions in comments. |

---

### `/speckit.team.assign`

Reads `tasks.md` and assigns each task to an engineer. For complex tasks, proposes a subtask split and asks for confirmation before applying.

**Before:**
```markdown
- [ ] Build the authentication API
- [ ] Create the login page component
```

**After:**
```markdown
## Team summary — Sprint 1

| Engineer | Role     | Assigned | Capacity | Status |
|----------|----------|----------|----------|--------|
| Alice    | backend  | 4 pts    | 5 pts    | ✅ OK  |
| Bob      | frontend | 3 pts    | 5 pts    | ✅ OK  |

- [ ] Build the authentication API [@alice] [3pts]
  - [ ] Define JWT token schema
  - [ ] Implement /auth/login endpoint
  - [ ] Write unit tests for auth middleware
- [ ] Create the login page component [@bob] [2pts]
```

---

### `/speckit.team.board`

Generates `.specify/specs/<feature>/team-board.md` — a workboard view grouped by engineer.

```markdown
# Team workboard — my-feature
> Sprint 1

## 👤 Alice (backend) — 4 / 5 pts

| Status | Task | Points |
|--------|------|--------|
| ⬜ Todo | Build the authentication API | 3 pts |
| ⬜ Todo | Set up database migrations | 1 pt |

## 👤 Bob (frontend) — 3 / 5 pts

| Status | Task | Points |
|--------|------|--------|
| ⬜ Todo | Create the login page component | 2 pts |
| ⬜ Todo | Add loading state to form | 1 pt |
```

Re-run at any time to refresh the board as tasks are checked off.

---

## Files created by this extension

| File | Description |
|------|-------------|
| `.specify/team.yml` | Team configuration (created once per project) |
| `.specify/specs/<feature>/tasks.md` | Updated in-place with `[@assignee]` and `[Npts]` annotations |
| `.specify/specs/<feature>/team-board.md` | Generated workboard (regenerated on each run) |

---

## Tips

- **Re-run `/speckit.team.assign`** any time you add new tasks or change the team. It will ask whether to reassign from scratch or only fill unassigned tasks.
- **Re-run `/speckit.team.board`** at the start of each standup — it picks up any tasks checked off since last time.
- **Override estimates** by editing `[Npts]` in `tasks.md` directly after assignment.
- **Multiple features** — the extension always asks which feature's `tasks.md` to use if there are multiple.

---

## Contributing

Issues and pull requests are welcome. See [CONTRIBUTING.md](CONTRIBUTING.md) if you want to add new assignment strategies or output formats.

---

## License

MIT — see [LICENSE](LICENSE).
