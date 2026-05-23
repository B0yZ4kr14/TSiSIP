# /speckit.team.setup

## Purpose

Guide the user to create or update their `team.yml` file with team members, roles, and capacity before running task assignment.

## Instructions

You are helping the user define their engineering team for task assignment within a spec-kit project.

### Step 1 — Check for existing team.yml

Look for a file at `.specify/team.yml` (or `team.yml` in the project root).

- If it exists, read it and show the current team to the user. Ask if they want to update it.
- If it does not exist, proceed to create one from scratch.

### Step 2 — Gather team information

Ask the user for the following information interactively, one question at a time:

1. **Team members**: names, GitHub handles (optional), and their primary role. Valid roles are: `backend`, `frontend`, `fullstack`, `devops`, `qa`. If the user provides a different role, accept it.
2. **Capacity**: how many tasks (or story points) each person can take in this sprint. If the user does not know, default to `5`.
3. **Sprint name** (optional): a label for this planning cycle, e.g. "Sprint 1" or "v2.0 release".
4. **Assignment strategy**: ask the user to choose one:
   - `role-match` — tasks are matched to engineers by role (recommended for most teams)
   - `round-robin` — tasks are spread evenly regardless of role
   - `manual` — the AI proposes assignments but leaves all slots empty for the user to fill manually

### Step 3 — Write team.yml

Write the completed configuration to `.specify/team.yml`. Use the following format exactly:

```yaml
team:
  members:
    - name: "<name>"
      github: "<handle>"   # omit line if not provided
      role: "<role>"
      capacity: <number>
  sprint: "<sprint name>"  # omit line if not provided
  strategy: "<strategy>"
```

### Step 4 — Confirm

Print a summary table of the team to the user:

```
Team configured ✓

| Name  | Role       | Capacity | GitHub  |
|-------|------------|----------|---------|
| Alice | backend    | 5        | @alice  |
| Bob   | frontend   | 5        | @bob    |
```

Tell the user they can now run `/speckit.team.assign` to assign tasks to the team.

## Notes

- Do not ask for sensitive information like salaries or personal details.
- If the user has only one person, still create the file — it will work with a team of one.
- Capacity is in whatever unit the team uses (tasks, points, hours). Do not enforce a unit.
