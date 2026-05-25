# Contributing to TSiSIP

## Commit Conventions

We use [Conventional Commits](https://www.conventionalcommits.org/):

- `feat:` — new feature or module
- `fix:` — bug fix
- `docs:` — documentation changes
- `refactor:` — code restructuring without behavior change
- `test:` — test additions or fixes
- `chore:` — build, tooling, dependency updates
- `security:` — security fixes or hardening

Example:
```
feat(ocp): add SIPtrace module with search and purge

Implements siptrace viewer for OpenSIPS 3.6 LTS.
Includes Call-ID filter, method filter, and admin-only purge.
```

## Spec Workflow

1. Every feature starts in `specs/NNN-feature-name/`
2. Required files: `spec.md`, `plan.md`, `tasks.md`
3. Optional: `blueprint.md` for architectural decisions
4. Update `tasks.md` as work progresses

## Agent Orchestration Rules

- Read `AGENTS.md` before making changes
- Follow the documentation workflow in `docs/TSiSIP-AGENT-ORCHESTRATION-PLAYBOOK.md`
- Use `requireRole('devops')` or `requireRole('admin')` for mutating OCP operations
- Never commit secrets; use Docker secrets or environment templates

## Security

- Run `scripts/ci-scan.sh` before significant commits
- Validate OpenSIPS config with `opensips -c`
- Do not introduce `db_mysql`, `sanity`, or Kamailio-only modules
- Keep PostgreSQL as the only database

## Docker-First Rule

All runtime components must be deliverable as Docker images.
Do not add bare-metal or VM-first installation instructions.
